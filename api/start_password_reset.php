<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/email_delivery.php';
require_once __DIR__ . '/connection/db_config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$email = trim((string) ($data['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

$stmt = $conn->prepare("SELECT id, email FROM users WHERE email = ? AND status = 'Active' LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$account) {
    echo json_encode(['success' => false, 'message' => 'No active account was found with that email address.']);
    exit;
}

$now = time();
$lastSentAt = (int) ($_SESSION['password_reset_last_sent_at'] ?? 0);
if ($lastSentAt > 0 && ($now - $lastSentAt) < 60) {
    $retryAfter = 60 - ($now - $lastSentAt);
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => "Please wait {$retryAfter} seconds before requesting another code."]);
    exit;
}

$windowStartedAt = (int) ($_SESSION['password_reset_window_started_at'] ?? 0);
if ($windowStartedAt === 0 || ($now - $windowStartedAt) >= 3600) {
    $_SESSION['password_reset_window_started_at'] = $now;
    $_SESSION['password_reset_send_count'] = 0;
}
if ((int) ($_SESSION['password_reset_send_count'] ?? 0) >= 5) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many verification codes requested. Please try again later.']);
    exit;
}

$expiresIn = 600;
$code = (string) random_int(100000, 999999);
$storedEmail = (string) $account['email'];
$message = "Your password reset verification code is: {$code}\n\n"
    . "This code expires in 10 minutes. If you did not request a password reset, ignore this email.\n\n"
    . "Philippians CDO Construction Company";

if (!app_send_email($storedEmail, 'Password reset verification code', $message)) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'The verification email could not be sent. Please contact the system administrator to check the mail configuration.']);
    exit;
}

$_SESSION['password_reset_email'] = $storedEmail;
$_SESSION['password_reset_code_hash'] = password_hash($code, PASSWORD_DEFAULT);
$_SESSION['password_reset_expires'] = $now + $expiresIn;
$_SESSION['password_reset_last_sent_at'] = $now;
$_SESSION['password_reset_send_count'] = (int) ($_SESSION['password_reset_send_count'] ?? 0) + 1;
$_SESSION['password_reset_attempts'] = 0;
unset($_SESSION['password_reset_verified']);

echo json_encode([
    'success' => true,
    'message' => 'A verification code was sent to the email address registered to this account.',
    'expires_in' => $expiresIn,
]);
