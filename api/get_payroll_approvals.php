<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/payroll_approval_helpers.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR']);
$userId = (int) ($_SESSION['user_id'] ?? 0);

$statusFilter = trim((string) ($_GET['status'] ?? ''));
$search = trim((string) ($_GET['search'] ?? ''));

$submittedByFilter = null;
if (in_array($currentRole, ['Payroll Staff', 'HR'], true) && payroll_approval_columns_ready($conn)) {
    $submittedByFilter = $userId;
}

$items = payroll_approval_fetch_records($conn, $currentRole, $userId, $statusFilter, $search);
$summary = payroll_approval_get_summary_counts($conn, $submittedByFilter);

echo json_encode([
    'success' => true,
    'role' => $currentRole,
    'summary' => $summary,
    'items' => $items,
]);

$conn->close();
