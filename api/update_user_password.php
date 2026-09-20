<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
include '../includes/login_security.php';
include '../includes/password_policy.php';
include 'record_audit_log.php';

$currentRole = require_auth($conn, ['Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$userId = (int) ($_POST['user_id'] ?? 0);
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($currentRole !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied. Only Admin can change user passwords.']);
    exit;
}

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user selected']);
    exit;
}

if ($newPassword === '' || $confirmPassword === '') {
    echo json_encode(['success' => false, 'message' => 'All password fields are required']);
    exit;
}

if ($passwordError = password_policy_validate($conn, $newPassword)) {
    echo json_encode(['success' => false, 'message' => $passwordError]);
    exit;
}

if ($newPassword !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit;
}

$userStmt = $conn->prepare('SELECT full_name, email FROM users WHERE id = ? LIMIT 1');
if (!$userStmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare user lookup']);
    exit;
}

$userStmt->bind_param('i', $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
login_security_ensure_columns($conn);
$updateStmt = $conn->prepare('UPDATE users SET password = ?, password_last_set_at = NOW(), failed_login_attempts = 0, account_locked_at = NULL, admin_login_cooldown_until = NULL WHERE id = ?');
if (!$updateStmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare password update']);
    exit;
}

$updateStmt->bind_param('si', $hashedPassword, $userId);
if (!$updateStmt->execute()) {
    $updateStmt->close();
    echo json_encode(['success' => false, 'message' => 'Failed to update password']);
    exit;
}
$updateStmt->close();

$label = trim((string) ($user['full_name'] ?? ''));
if ($label === '') {
    $label = (string) ($user['email'] ?? ('User #' . $userId));
}

record_audit_log($currentUserId, 'User Password Changed', "Changed password for {$label}");

echo json_encode(['success' => true, 'message' => 'User password updated successfully']);
?>
