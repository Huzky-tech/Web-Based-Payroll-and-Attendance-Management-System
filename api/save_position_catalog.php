<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once '../includes/position_catalog.php';

require_auth($conn, ['Admin', 'Assistant Admin']);
position_catalog_ensure_table($conn);
$data = json_decode(file_get_contents('php://input'), true) ?: [];
$id = (int) ($data['id'] ?? 0);
$name = preg_replace('/\s+/u', ' ', trim((string) ($data['position_name'] ?? '')));
$hourly = (float) ($data['hourly_rate'] ?? 0);
$weekly = (float) ($data['weekly_rate'] ?? 0);
if ($name === '' || $hourly <= 0 || $weekly <= 0) {
    echo json_encode(['success' => false, 'message' => 'Position name, hourly rate, and daily pay are required.']);
    exit;
}
if (!preg_match('/^[\p{L} ]+$/u', $name)) {
    echo json_encode(['success' => false, 'message' => 'Position name can contain letters and spaces only.']);
    exit;
}

$stmt = $id > 0
    ? $conn->prepare('UPDATE employee_position_catalog SET position_name = ?, hourly_rate = ?, weekly_rate = ? WHERE id = ?')
    : $conn->prepare('INSERT INTO employee_position_catalog (position_name, hourly_rate, weekly_rate) VALUES (?, ?, ?)');
if ($id > 0) $stmt->bind_param('sddi', $name, $hourly, $weekly, $id);
else $stmt->bind_param('sdd', $name, $hourly, $weekly);
$ok = $stmt->execute();
if (!$ok && $stmt->errno === 1062) {
    echo json_encode(['success' => false, 'message' => 'A position with that name already exists.']);
    exit;
}
echo json_encode(['success' => $ok, 'message' => $ok ? ($id > 0 ? 'Position updated successfully.' : 'Position added successfully.') : 'Unable to save position.']);
