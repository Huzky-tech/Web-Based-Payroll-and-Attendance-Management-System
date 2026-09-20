<?php
header('Content-Type: application/json');
require_once __DIR__ . '/connection/db_config.php';
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['Admin', 'Assistant Admin'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}
$email = trim($_GET['email'] ?? '');
$excludeId = max(0, (int) ($_GET['exclude_id'] ?? 0));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Enter a valid email address.']);
    exit;
}
// Include inactive accounts: their email remains registered when archived.
$stmt = $conn->prepare('SELECT id FROM users WHERE LOWER(TRIM(email)) = LOWER(?) AND id <> ? LIMIT 1');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to check email availability.']);
    exit;
}
$stmt->bind_param('si', $email, $excludeId);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to check email availability.']);
    exit;
}
echo json_encode(['success' => true, 'available' => $stmt->get_result()->num_rows === 0]);
$stmt->close();
