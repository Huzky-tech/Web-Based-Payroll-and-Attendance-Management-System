<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/record_audit_log.php';
require_once __DIR__ . '/payroll_approval_helpers.php';

// HR may prepare and submit payroll, but must not approve or reject payroll
// batches (including their own). Approval remains an independent
// Admin/Assistant Admin responsibility.
$currentRole = require_auth($conn, ['Admin', 'Assistant Admin']);
$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$recordId = (int) ($data['record_id'] ?? 0);
$action = strtolower(trim((string) ($data['action'] ?? '')));
$rejectionReason = trim((string) ($data['rejection_reason'] ?? ''));

if ($recordId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    echo json_encode(['success' => false, 'message' => 'Record ID and a valid action are required']);
    exit;
}

if ($action === 'reject' && $rejectionReason === '') {
    echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
    exit;
}

$stmt = $conn->prepare("
    SELECT Payroll_RecordsID, SiteID, Status
    FROM payroll_records
    WHERE Payroll_RecordsID = ?
    LIMIT 1
");
$stmt->bind_param('i', $recordId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Payroll record not found']);
    exit;
}

$currentStatus = payroll_approval_normalize_status($row['Status'] ?? 'Pending');
if ($currentStatus !== 'Pending') {
    echo json_encode(['success' => false, 'message' => 'Only pending payroll submissions can be updated']);
    exit;
}

$hasWorkflowColumns = payroll_approval_columns_ready($conn);

if ($hasWorkflowColumns) {
    if ($action === 'approve') {
        $updateStmt = $conn->prepare("
            UPDATE payroll_records
            SET Status = 'Approved',
                approved_by = ?,
                approved_at = NOW(),
                rejected_by = NULL,
                rejected_at = NULL,
                rejection_reason = NULL
            WHERE Payroll_RecordsID = ?
        ");
        $updateStmt->bind_param('ii', $userId, $recordId);
    } else {
        $updateStmt = $conn->prepare("
            UPDATE payroll_records
            SET Status = 'Rejected',
                rejected_by = ?,
                rejected_at = NOW(),
                rejection_reason = ?,
                approved_by = NULL,
                approved_at = NULL
            WHERE Payroll_RecordsID = ?
        ");
        $updateStmt->bind_param('isi', $userId, $rejectionReason, $recordId);
    }
} else {
    $newStatus = $action === 'approve' ? 'Approved' : 'Rejected';
    $updateStmt = $conn->prepare("
        UPDATE payroll_records
        SET Status = ?
        WHERE Payroll_RecordsID = ?
    ");
    $updateStmt->bind_param('si', $newStatus, $recordId);
}

$success = $updateStmt->execute();
$updateStmt->close();

if (!$success) {
    echo json_encode(['success' => false, 'message' => 'Failed to update payroll record']);
    exit;
}

$reviewerLabel = trim((string) ($_SESSION['full_name'] ?? $currentRole));
$siteId = (int) ($row['SiteID'] ?? 0);
$auditAction = $action === 'approve' ? 'Payroll Approved' : 'Payroll Rejected';
$auditDetails = $action === 'approve'
    ? "{$currentRole} {$reviewerLabel} approved payroll record #{$recordId} for site #{$siteId}"
    : "{$currentRole} {$reviewerLabel} rejected payroll record #{$recordId} for site #{$siteId}. Reason: {$rejectionReason}";
record_audit_log($userId, $auditAction, $auditDetails);

$updatedRecord = payroll_approval_fetch_record_by_id($conn, $recordId);
$submittedByFilter = null;
$summary = payroll_approval_get_summary_counts($conn, $submittedByFilter);

echo json_encode([
    'success' => true,
    'message' => $action === 'approve'
        ? 'Payroll approved for release successfully'
        : 'Payroll rejected successfully',
    'record' => $updatedRecord,
    'summary' => $summary,
]);

$conn->close();
