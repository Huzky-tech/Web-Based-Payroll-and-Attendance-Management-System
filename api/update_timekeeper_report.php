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

if (!tk_report_table_exists($conn)) {
    echo json_encode(['success' => false, 'message' => 'Reports table is not installed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$reportId = (int) ($data['report_id'] ?? $data['id'] ?? 0);
$status = trim((string) ($data['status'] ?? ''));
$adminRemarks = trim((string) ($data['admin_remarks'] ?? $data['remarks'] ?? ''));
$markResolved = !empty($data['mark_resolved']);

if ($reportId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Report ID is required']);
    exit;
}

if ($markResolved) {
    $status = 'Resolved';
}

if ($status !== '' && !in_array($status, tk_report_valid_statuses(), true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid report status']);
    exit;
}

$stmt = $conn->prepare("
    SELECT TK_ReportsID, Status, Subject
    FROM timekeeper_reports
    WHERE TK_ReportsID = ?
    LIMIT 1
");
$stmt->bind_param('i', $reportId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Report not found']);
    exit;
}

$currentStatus = trim((string) ($row['Status'] ?? 'Pending'));
if ($currentStatus === '') {
    $currentStatus = 'Pending';
}

$newStatus = $status !== '' ? $status : $currentStatus;

$updateStmt = $conn->prepare("
    UPDATE timekeeper_reports
    SET Status = ?, AdminRemarks = ?, UpdatedAt = NOW()
    WHERE TK_ReportsID = ?
");
$updateStmt->bind_param('ssi', $newStatus, $adminRemarks, $reportId);
$success = $updateStmt->execute();
$updateStmt->close();

if (!$success) {
    echo json_encode(['success' => false, 'message' => 'Failed to update report']);
    exit;
}

$adminLabel = trim((string) ($_SESSION['full_name'] ?? $currentRole));
record_audit_log(
    $userId,
    'Timekeeper Report Updated',
    "{$currentRole} {$adminLabel} updated Timekeeper Report #{$reportId} to {$newStatus}"
);

echo json_encode([
    'success' => true,
    'message' => $newStatus === 'Resolved'
        ? 'Report marked as resolved'
        : 'Report updated successfully',
    'report_id' => $reportId,
    'status' => $newStatus,
    'admin_remarks' => $adminRemarks,
]);

$conn->close();
