<?php
require_once __DIR__ . '/../includes/security.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !security_verify_csrf()) {
    security_reject_invalid_csrf();
}
foreach (['must_change_password_user_id', 'must_change_password_email', 'first_login_otp_hash', 'first_login_otp_expires', 'first_login_otp_attempts', 'first_login_otp_last_sent', 'first_login_otp_window_started', 'first_login_otp_send_count', 'first_login_verified_until'] as $key) {
    unset($_SESSION[$key]);
}
echo json_encode(['success' => true]);
