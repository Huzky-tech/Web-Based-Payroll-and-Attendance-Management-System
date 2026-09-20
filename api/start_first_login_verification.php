<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/email_delivery.php';
require_once __DIR__ . '/connection/db_config.php';
require_once __DIR__ . '/../includes/login_security.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !security_verify_csrf()) {
    security_reject_invalid_csrf();
}

$userId = (int) ($_SESSION['must_change_password_user_id'] ?? 0);
$email = (string) ($_SESSION['must_change_password_email'] ?? '');
if ($userId <= 0 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Your first-time login session has expired. Please sign in again.']);
    exit;
}

$now = time();
if (($now - (int) ($_SESSION['first_login_otp_last_sent'] ?? 0)) < 60) {
    echo json_encode(['success' => false, 'message' => 'Please wait one minute before requesting another code.']);
    exit;
}
if (($now - (int) ($_SESSION['first_login_otp_window_started'] ?? 0)) >= 3600) {
    $_SESSION['first_login_otp_window_started'] = $now;
    $_SESSION['first_login_otp_send_count'] = 0;
}
if ((int) ($_SESSION['first_login_otp_send_count'] ?? 0) >= 5) {
    echo json_encode(['success' => false, 'message' => 'Too many codes requested. Please try again later.']);
    exit;
}

login_security_ensure_columns($conn);
$accountStmt = $conn->prepare("SELECT email FROM users WHERE id = ? AND email = ? AND status = 'Active' AND must_change_password = 1 LIMIT 1");
$accountStmt->bind_param('is', $userId, $email);
$accountStmt->execute();
$account = $accountStmt->get_result()->fetch_assoc();
$accountStmt->close();
if (!$account) {
    echo json_encode(['success' => false, 'message' => 'This account is not available for first-time verification.']);
    exit;
}

$code = (string) random_int(100000, 999999);
$message = "Your first-time account verification code is: {$code}\n\nThis code expires in 10 minutes. Do not share it with anyone.\n\nPhilippians CDO Construction Company";
if (!app_send_email($email, 'First-time account verification code', $message)) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Unable to send the verification code. Please contact an administrator.']);
    exit;
}

$_SESSION['first_login_otp_hash'] = password_hash($code, PASSWORD_DEFAULT);
$_SESSION['first_login_otp_expires'] = $now + 600;
$_SESSION['first_login_otp_attempts'] = 0;
$_SESSION['first_login_otp_last_sent'] = $now;
$_SESSION['first_login_otp_window_started'] = $_SESSION['first_login_otp_window_started'] ?? $now;
$_SESSION['first_login_otp_send_count'] = (int) ($_SESSION['first_login_otp_send_count'] ?? 0) + 1;
unset($_SESSION['first_login_verified_until']);
echo json_encode(['success' => true, 'message' => 'A 6-digit verification code was sent to your registered email address.', 'expires_in' => 600]);
