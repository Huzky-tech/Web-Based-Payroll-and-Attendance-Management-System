<?php
require_once __DIR__ . '/../includes/security.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$code = trim((string) ($data['code'] ?? ''));
$hash = (string) ($_SESSION['password_reset_code_hash'] ?? '');
$expires = (int) ($_SESSION['password_reset_expires'] ?? 0);
$attempts = (int) ($_SESSION['password_reset_attempts'] ?? 0);
if ($attempts >= 5) {
    unset($_SESSION['password_reset_code_hash'], $_SESSION['password_reset_expires']);
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many incorrect attempts. Request a new verification code.']);
    exit;
}
if (!preg_match('/^\d{6}$/', $code) || !$hash || time() > $expires || !password_verify($code, $hash)) {
    $_SESSION['password_reset_attempts'] = $attempts + 1;
    echo json_encode(['success' => false, 'message' => 'The verification code is invalid or has expired.']);
    exit;
}

$_SESSION['password_reset_verified'] = true;
unset($_SESSION['password_reset_code_hash'], $_SESSION['password_reset_attempts']);
echo json_encode(['success' => true, 'message' => 'Email verified.']);
