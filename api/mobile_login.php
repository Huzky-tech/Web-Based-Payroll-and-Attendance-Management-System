<?php
/**
 * Mobile login for Flutter Attendance Application.
 * Stateless JSON API — authenticates active Timekeeper accounts only.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

include 'connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/login_security.php';
require_once __DIR__ . '/timekeeper_assignment_helpers.php';

if (!login_security_ensure_columns($conn)) {
    mobile_login_error('Login security is unavailable. Please try again later.');
}

function mobile_login_error(string $message = 'Invalid credentials'): void
{
    echo json_encode([
        'status' => 'error',
        'message' => $message,
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mobile_login_error('Invalid credentials');
}

$mobileLoginRateLimit = security_rate_limit('mobile-login', 10, 300);
if (!$mobileLoginRateLimit['allowed']) {
    header('Retry-After: ' . $mobileLoginRateLimit['retry_after']);
    http_response_code(429);
    mobile_login_error('Too many login attempts. Please wait a few minutes and try again.');
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$email = trim((string) ($payload['email'] ?? ''));
$password = (string) ($payload['password'] ?? '');

if ($email === '' || $password === '') {
    mobile_login_error();
}

$stmt = $conn->prepare(
    'SELECT id, full_name, email, password, status, failed_login_attempts, account_locked_at, password_last_set_at FROM users WHERE email = ? LIMIT 1'
);
if (!$stmt) {
    mobile_login_error();
}

$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$user) {
    mobile_login_error('User not found');
}

$userId = (int)$user['id'];
$status = (string)($user['status'] ?? '');

if (!empty($user['account_locked_at'])) {
    mobile_login_error('Account is locked. Please contact an administrator.');
}

if (!password_verify($password, (string)$user['password'])) {
    $maxAttempts = login_security_max_attempts($conn);
    $attempt = login_security_record_failed_attempt($conn, $userId, $maxAttempts);
    if ($attempt['locked']) {
        mobile_login_error("Account locked after {$maxAttempts} incorrect password attempts.");
    }
    mobile_login_error('Password incorrect');
}

if (login_security_password_is_expired($conn, $user['password_last_set_at'] ?? null)) {
    mobile_login_error('Password has expired. Please ask an administrator to reset it.');
}

if (strcasecmp($status, 'Active') !== 0) {
    mobile_login_error('Account not active');
}

$role = auth_get_user_role($conn, $userId);

if ($role !== 'Timekeeper') {
    mobile_login_error('Role is not Timekeeper');
}

// CHECK SITE ASSIGNMENT
$assignment = get_active_timekeeper_assignment($conn, $userId);

if (!$assignment) {
    mobile_login_error('No site assigned to this Timekeeper.');
}

// update login time
login_security_reset_attempts($conn, $userId, true);

// SUCCESS RESPONSE
echo json_encode([
    'status' => 'success',
    'user' => [
        'id' => $userId,
        'name' => $user['full_name'],
        'email' => $user['email'],
        'role' => 'Timekeeper',
        'site_id' => (int) $assignment['SiteID'],
        'site_name' => $assignment['Site_Name'],
    ],
]);

$conn->close();
exit;
