<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/password_change_notification.php';
require_once __DIR__ . '/record_audit_log.php';
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
    $userLookup = $conn->prepare('SELECT id, full_name FROM users WHERE email = ? LIMIT 1');
    if ($userLookup) {
        $userLookup->bind_param('s', $email);
        $userLookup->execute();
        $changedUser = $userLookup->get_result()->fetch_assoc() ?: [];
        $userLookup->close();
        $changedUserId = (int) ($changedUser['id'] ?? 0);
        if ($changedUserId > 0 && auth_get_user_role($conn, $changedUserId) !== 'Admin') {
            notify_admins_of_password_change($conn, $changedUserId, (string) ($changedUser['full_name'] ?? ''), 'password reset');
        }
        if ($changedUserId > 0) {
            record_audit_log($changedUserId, 'Password Changed', 'Reset password using verified email recovery.');
        }
    }
    unset($_SESSION['password_reset_email'], $_SESSION['password_reset_expires'], $_SESSION['password_reset_verified'], $_SESSION['password_reset_attempts'], $_SESSION['password_reset_last_sent_at'], $_SESSION['password_reset_send_count'], $_SESSION['password_reset_window_started_at']);
    echo json_encode(['success' => true, 'message' => 'Your password has been reset. You can now log in.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to reset the password right now.']);
}
