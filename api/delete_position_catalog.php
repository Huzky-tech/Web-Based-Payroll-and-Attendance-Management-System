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
$stmt->bind_param('i', $id);
$ok = $stmt->execute();
echo json_encode(['success' => $ok, 'message' => $ok ? 'Position removed.' : 'Unable to remove position.']);
