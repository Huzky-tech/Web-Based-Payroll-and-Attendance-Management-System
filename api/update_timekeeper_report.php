<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/record_audit_log.php';
require_once __DIR__ . '/timekeeper_report_helpers.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR']);
$userId = (int) ($_SESSION['user_id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}
tk_report_ensure_schema($conn);
$data = json_decode(file_get_contents('php://input'), true) ?: [];
$reportId = (int) ($data['report_id'] ?? $data['id'] ?? 0);
$status = trim((string) ($data['status'] ?? ''));
$adminRemarks = trim((string) ($data['admin_remarks'] ?? $data['remarks'] ?? ''));
if (!empty($data['mark_resolved'])) $status = 'Resolved';
if ($reportId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Report ID is required']);
    exit;
}
if ($status !== '' && !in_array($status, tk_report_valid_statuses(), true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid report status']);
    exit;
}
$map = timekeeper_report_schema_map($conn);
if ($map === [] || empty($map['id']) || empty($map['status'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => timekeeper_report_last_error() ?: 'Timekeeper reports are not configured correctly.']);
    exit;
}
$q = static fn(string $name): string => '`' . str_replace('`', '', $name) . '`';
$idCol = $q($map['id']);
$statusCol = $q($map['status']);
$remarksColumn = timekeeper_report_resolve_column(timekeeper_report_table_columns($conn), ['AdminRemarks', 'admin_remarks']);
$updatedColumn = timekeeper_report_resolve_column(timekeeper_report_table_columns($conn), ['UpdatedAt', 'updated_at']);
$select = $conn->prepare("SELECT {$statusCol} AS status FROM timekeeper_reports WHERE {$idCol} = ? LIMIT 1");
if (!$select) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load the report.']);
    exit;
}
$select->bind_param('i', $reportId);
$select->execute();
$existing = $select->get_result()->fetch_assoc();
$select->close();
if (!$existing) {
    echo json_encode(['success' => false, 'message' => 'Report not found']);
    exit;
}
$newStatus = $status !== '' ? $status : (trim((string) $existing['status']) ?: 'Pending');
$sets = ["{$statusCol} = ?"];
$types = 's';
$values = [$newStatus];
if ($remarksColumn !== null) { $sets[] = $q($remarksColumn) . ' = ?'; $types .= 's'; $values[] = $adminRemarks; }
if ($updatedColumn !== null) $sets[] = $q($updatedColumn) . ' = NOW()';
$types .= 'i'; $values[] = $reportId;
$update = $conn->prepare('UPDATE timekeeper_reports SET ' . implode(', ', $sets) . " WHERE {$idCol} = ?");
if (!$update) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update the report.']);
    exit;
}
$update->bind_param($types, ...$values);
$success = $update->execute();
$update->close();
if (!$success) {
    echo json_encode(['success' => false, 'message' => 'Failed to update report']);
    exit;
}
$adminLabel = trim((string) ($_SESSION['full_name'] ?? $currentRole));
record_audit_log($userId, 'Timekeeper Report Updated', "{$currentRole} {$adminLabel} updated Timekeeper Report #{$reportId} to {$newStatus}");
echo json_encode(['success' => true, 'message' => $newStatus === 'Resolved' ? 'Report marked as resolved' : 'Report updated successfully', 'report_id' => $reportId, 'status' => $newStatus, 'admin_remarks' => $adminRemarks]);
$conn->close();
