<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/record_audit_log.php';
require_once __DIR__ . '/overtime_helpers.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR']);
$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!overtime_table_exists($conn)) {
    echo json_encode(['success' => false, 'message' => 'Overtime requests table is not installed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$overtimeId = (int) ($data['overtime_id'] ?? 0);
$action = strtolower(trim((string) ($data['action'] ?? '')));

if ($overtimeId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    echo json_encode(['success' => false, 'message' => 'Overtime ID and a valid action are required']);
    exit;
}

$stmt = $conn->prepare("
    SELECT OvertimeID, Status
    FROM overtime_requests
    WHERE OvertimeID = ?
    LIMIT 1
");
$stmt->bind_param('i', $overtimeId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Overtime request not found']);
    exit;
}

$newStatus = $action === 'approve' ? 'Approved' : 'Rejected';
$updateStmt = $conn->prepare("
    UPDATE overtime_requests
    SET Status = ?, ApprovedBy = ?, ApprovedDate = NOW()
    WHERE OvertimeID = ?
");
$updateStmt->bind_param('sii', $newStatus, $userId, $overtimeId);
$success = $updateStmt->execute();
$updateStmt->close();

if (!$success) {
    echo json_encode(['success' => false, 'message' => 'Failed to update overtime request']);
    exit;
}

$auditAction = $action === 'approve' ? 'Overtime Request Approved' : 'Overtime Request Rejected';
$reviewerLabel = trim((string) ($_SESSION['full_name'] ?? $currentRole));
record_audit_log($userId, $auditAction, "{$currentRole} {$reviewerLabel} {$action}d Overtime Request #{$overtimeId}");

echo json_encode([
    'success' => true,
    'message' => $action === 'approve'
        ? 'Overtime request approved successfully'
        : 'Overtime request rejected successfully',
    'overtime_id' => $overtimeId,
    'status' => $newStatus,
]);

$conn->close();
