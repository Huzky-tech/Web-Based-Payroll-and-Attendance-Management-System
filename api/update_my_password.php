<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
include '../includes/password_policy.php';

require_auth($conn, ['Admin', 'Payroll Staff', 'HR']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized user']);
    exit;
}

if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
    echo json_encode(['success' => false, 'message' => 'All password fields are required']);
    exit;
}

if ($passwordError = password_policy_validate($conn, $newPassword)) {
    echo json_encode(['success' => false, 'message' => $passwordError]);
    exit;
}

if ($newPassword !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
    exit;
}

$stmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$user || empty($user['password']) || !password_verify($currentPassword, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
    exit;
}

$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
login_security_ensure_columns($conn);
$updateStmt = $conn->prepare("UPDATE users SET password = ?, password_last_set_at = NOW() WHERE id = ?");
$updateStmt->bind_param('si', $hashedPassword, $userId);

if ($updateStmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update password']);
}

$updateStmt->close();
$conn->close();
?>
