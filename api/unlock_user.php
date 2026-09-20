<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/login_security.php';

require_auth($conn, ['Admin', 'Assistant Admin']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$userId = (int) ($_POST['user_id'] ?? 0);
if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user selected']);
    exit;
}
if (!login_security_ensure_columns($conn)) {
    echo json_encode(['success' => false, 'message' => 'Login security is unavailable.']);
    exit;
}

$check = $conn->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
$check->bind_param('i', $userId);
$check->execute();
$user = $check->get_result()->fetch_assoc();
$check->close();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

login_security_reset_attempts($conn, $userId);
echo json_encode(['success' => true, 'message' => 'User account unlocked successfully.']);
