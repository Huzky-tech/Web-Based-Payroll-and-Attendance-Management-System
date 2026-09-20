<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/connection/db_config.php';
header('Content-Type: application/json');

if (empty($_SESSION['password_reset_verified']) || empty($_SESSION['password_reset_email'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Please verify your email first.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$newPassword = (string) ($data['password'] ?? '');
require_once __DIR__ . '/../includes/password_policy.php';
login_security_ensure_columns($conn);
if ($error = password_policy_validate($conn, $newPassword)) {
    echo json_encode(['success' => false, 'message' => $error]);
    exit;
}

$email = (string) $_SESSION['password_reset_email'];
$hash = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $conn->prepare('UPDATE users SET password = ?, password_last_set_at = NOW(), must_change_password = 0, failed_login_attempts = 0, account_locked_at = NULL, admin_login_cooldown_until = NULL WHERE email = ?');
$stmt->bind_param('ss', $hash, $email);
$ok = $stmt->execute() && $stmt->affected_rows === 1;

if ($ok) {
    unset($_SESSION['password_reset_email'], $_SESSION['password_reset_expires'], $_SESSION['password_reset_verified'], $_SESSION['password_reset_attempts'], $_SESSION['password_reset_last_sent_at'], $_SESSION['password_reset_send_count'], $_SESSION['password_reset_window_started_at']);
    echo json_encode(['success' => true, 'message' => 'Your password has been reset. You can now log in.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to reset the password right now.']);
}
