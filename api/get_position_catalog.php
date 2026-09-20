<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once '../includes/position_catalog.php';

require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR', 'Timekeeper']);
if (!position_catalog_ensure_table($conn)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load positions.']);
    exit;
}

$result = $conn->query('SELECT id, position_name, hourly_rate, salary_rate FROM employee_position_catalog ORDER BY position_name');
$positions = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
echo json_encode(['success' => true, 'positions' => $positions]);
