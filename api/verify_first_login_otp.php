<?php
require_once __DIR__ . '/../includes/security.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !security_verify_csrf()) {
    security_reject_invalid_csrf();
}
$data = json_decode(file_get_contents('php://input'), true) ?: [];
$code = trim((string) ($data['code'] ?? ''));
$hash = (string) ($_SESSION['first_login_otp_hash'] ?? '');
$expires = (int) ($_SESSION['first_login_otp_expires'] ?? 0);
$attempts = (int) ($_SESSION['first_login_otp_attempts'] ?? 0);
if (!isset($_SESSION['must_change_password_user_id']) || $attempts >= 5) {
    unset($_SESSION['first_login_otp_hash'], $_SESSION['first_login_otp_expires']);
    echo json_encode(['success' => false, 'message' => 'Too many attempts. Request a new verification code.']);
    exit;
}
if (!preg_match('/^\d{6}$/', $code) || !$hash || time() > $expires || !password_verify($code, $hash)) {
    $_SESSION['first_login_otp_attempts'] = $attempts + 1;
    echo json_encode(['success' => false, 'message' => 'The verification code is invalid or has expired.']);
    exit;
}
$_SESSION['first_login_verified_until'] = time() + 600;
unset($_SESSION['first_login_otp_hash'], $_SESSION['first_login_otp_expires'], $_SESSION['first_login_otp_attempts']);
echo json_encode(['success' => true, 'message' => 'Identity verified. You may now set your password.']);
