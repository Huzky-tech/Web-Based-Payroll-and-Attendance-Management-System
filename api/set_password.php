<?php
require_once __DIR__ . '/../includes/security.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !security_verify_csrf()) {
    security_reject_invalid_csrf();
}

$isFirstLoginChange = isset($_SESSION['must_change_password_user_id']);
if ($isFirstLoginChange && (int) ($_SESSION['first_login_verified_until'] ?? 0) < time()) {
    echo json_encode(['success' => false, 'message' => 'Verify your identity with the one-time code before setting a password.']);
    exit;
}
if (!$isFirstLoginChange && (!isset($_SESSION['verified']) || !$_SESSION['verified'])) {
    echo json_encode(['success' => false, 'message' => 'Not verified']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$password = $data['password'] ?? '';

$email = $isFirstLoginChange
    ? (string) ($_SESSION['must_change_password_email'] ?? '')
    : (string) ($_SESSION['pending_email'] ?? '');
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Database connection
$servername = "localhost";
$username = "root";
$password_db = "";
$dbname = "payroll_db";

$conn = new mysqli($servername, $username, $password_db, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

require_once __DIR__ . '/../includes/password_policy.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/record_audit_log.php';
if ($passwordError = password_policy_validate($conn, $password)) {
    echo json_encode(['success' => false, 'message' => $passwordError]);
    exit;
}
login_security_ensure_columns($conn);

if ($isFirstLoginChange) {
    $userId = (int) $_SESSION['must_change_password_user_id'];
    $stmt = $conn->prepare('UPDATE users SET password = ?, password_last_set_at = NOW(), must_change_password = 0, last_login = NOW(), failed_login_attempts = 0, account_locked_at = NULL, admin_login_cooldown_until = NULL WHERE id = ? AND must_change_password = 1');
    $stmt->bind_param('si', $hashed_password, $userId);

    if (!$stmt->execute() || $stmt->affected_rows !== 1) {
        echo json_encode(['success' => false, 'message' => 'Unable to complete the first-time password change.']);
        exit;
    }

    $userStmt = $conn->prepare('SELECT full_name, email FROM users WHERE id = ? LIMIT 1');
    $userStmt->bind_param('i', $userId);
    $userStmt->execute();
    $user = $userStmt->get_result()->fetch_assoc() ?: [];
    $role = auth_get_user_role($conn, $userId);
    $_SESSION['user_id'] = $userId;
    $_SESSION['full_name'] = $user['full_name'] ?? '';
    $_SESSION['email'] = $user['email'] ?? $email;
    $_SESSION['role'] = $role;
    $_SESSION['last_activity'] = time();
    record_audit_log($userId, 'User Login', "Successful login as {$role} after first-time password setup");
    unset($_SESSION['must_change_password_user_id'], $_SESSION['must_change_password_email'], $_SESSION['first_login_verified_until'], $_SESSION['first_login_otp_hash'], $_SESSION['first_login_otp_expires'], $_SESSION['first_login_otp_attempts'], $_SESSION['first_login_otp_last_sent'], $_SESSION['first_login_otp_window_started'], $_SESSION['first_login_otp_send_count']);

    echo json_encode([
        'success' => true,
        'message' => 'Password created successfully.',
        'redirect' => preg_replace('#^\.\./#', '', auth_get_redirect_path($role)),
    ]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO users (email, password, password_last_set_at) VALUES (?, ?, NOW())");
$stmt->bind_param("ss", $email, $hashed_password);

if ($stmt->execute()) {
    unset($_SESSION['pending_email'], $_SESSION['verification_code'], $_SESSION['verified']);
    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully. Please log in and wait for admin approval.',
        'redirect' => 'index.php'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to create account']);
}

$stmt->close();
$conn->close();
?>
