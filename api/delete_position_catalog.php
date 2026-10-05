<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once '../includes/position_catalog.php';

require_auth($conn, ['Admin', 'Assistant Admin']);
position_catalog_ensure_table($conn);
$data = json_decode(file_get_contents('php://input'), true) ?: [];
$id = (int) ($data['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid position.']);
    exit;
}
$stmt = $conn->prepare('DELETE FROM employee_position_catalog WHERE id = ?');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to prepare position removal.']);
    exit;
}
$stmt->bind_param('i', $id);
$ok = $stmt->execute();
$removed = $ok && $stmt->affected_rows > 0;
$stmt->close();
if (!$removed) {
    http_response_code($ok ? 404 : 500);
    echo json_encode(['success' => false, 'message' => $ok ? 'Position was not found or was already removed.' : 'Unable to remove position.']);
    exit;
}
echo json_encode(['success' => true, 'message' => 'Position removed successfully.']);
