<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once '../includes/position_catalog.php';

require_auth($conn, ['Admin', 'Assistant Admin']);
if (!position_catalog_ensure_table($conn)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to prepare position storage.']);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['success' => false, 'message' => 'Use POST to save positions.']);
    exit;
}
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request data.']);
    exit;
}
$id = filter_var($data['id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
if ($id === false) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid position ID.']);
    exit;
}
require_once __DIR__ . '/../includes/position_validation.php';
$validation = position_validate($data);
if ($validation['errors']) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please correct the highlighted fields.', 'errors' => $validation['errors']]);
    exit;
}
$name = $validation['values']['position_name'];
$hourly = $validation['values']['hourly_rate'];
$weekly = $validation['values']['weekly_rate'];
$salary = $validation['values']['salary_rate'];
$stmt = $id > 0
    ? $conn->prepare('UPDATE employee_position_catalog SET position_name = ?, hourly_rate = ?, salary_rate = ?, weekly_rate = ? WHERE id = ?')
    : $conn->prepare('INSERT INTO employee_position_catalog (position_name, hourly_rate, salary_rate, weekly_rate) VALUES (?, ?, ?, ?)');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to save position.']);
    exit;
}
if ($id > 0) $stmt->bind_param('sdddi', $name, $hourly, $salary, $weekly, $id);
else $stmt->bind_param('sddd', $name, $hourly, $salary, $weekly);
$ok = $stmt->execute();
if (!$ok && $stmt->errno === 1062) {
    echo json_encode(['success' => false, 'message' => 'A position with that name already exists.', 'errors' => ['position_name' => 'A position with that name already exists.']]);
    exit;
}
echo json_encode(['success' => $ok, 'message' => $ok ? ($id > 0 ? 'Position updated successfully.' : 'Position added successfully.') : 'Unable to save position.']);
