<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/record_audit_log.php';
require_once __DIR__ . '/overtime_helpers.php';

$currentRole = require_auth($conn, ['Timekeeper']);
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
$workerId = (int) ($data['worker_id'] ?? 0);
$siteId = (int) ($data['site_id'] ?? 0);
$requestDate = trim((string) ($data['request_date'] ?? ''));
$overtimeType = trim((string) ($data['overtime_type'] ?? ''));
$overtimeStart = normalize_site_time_input($data['overtime_start'] ?? null);
$overtimeEnd = normalize_site_time_input($data['overtime_end'] ?? null);
$reason = trim((string) ($data['reason'] ?? ''));

if ($workerId <= 0 || $siteId <= 0 || $requestDate === '' || $overtimeType === '' || $reason === '') {
    echo json_encode(['success' => false, 'message' => 'Worker, site, date, overtime type, and reason are required']);
    exit;
}

if (!in_array($overtimeType, overtime_valid_types(), true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid overtime type']);
    exit;
}

if (!auth_timekeeper_has_site_access($conn, $userId, $siteId)) {
    echo json_encode(['success' => false, 'message' => 'You are not assigned to this site']);
    exit;
}

if (!worker_assigned_to_site($conn, $workerId, $siteId)) {
    echo json_encode(['success' => false, 'message' => 'Worker is not assigned to the selected site']);
    exit;
}

$totalHours = calculate_overtime_total_hours($overtimeStart, $overtimeEnd);
if ($totalHours <= 0) {
    echo json_encode(['success' => false, 'message' => 'End time must be greater than start time and total hours must be greater than zero']);
    exit;
}

$duplicateStmt = $conn->prepare("
    SELECT OvertimeID
    FROM overtime_requests
    WHERE WorkerID = ?
      AND SiteID = ?
      AND RequestDate = ?
      AND OvertimeType = ?
      AND OvertimeStart = ?
      AND OvertimeEnd = ?
      AND Status <> 'Rejected'
    LIMIT 1
");
if ($duplicateStmt) {
    $duplicateStmt->bind_param('iissss', $workerId, $siteId, $requestDate, $overtimeType, $overtimeStart, $overtimeEnd);
    $duplicateStmt->execute();
    $duplicateRequest = $duplicateStmt->get_result()->fetch_assoc();
    $duplicateStmt->close();

    if ($duplicateRequest) {
        echo json_encode(['success' => false, 'message' => 'This overtime request already exists.']);
        exit;
    }
}

$stmt = $conn->prepare("
    INSERT INTO overtime_requests (
        WorkerID, SiteID, RequestDate, OvertimeType, OvertimeStart, OvertimeEnd,
        TotalHours, Reason, SubmittedBy, Status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Unable to save overtime request']);
    exit;
}

$stmt->bind_param(
    'iissssdsi',
    $workerId,
    $siteId,
    $requestDate,
    $overtimeType,
    $overtimeStart,
    $overtimeEnd,
    $totalHours,
    $reason,
    $userId
);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to submit overtime request']);
    $stmt->close();
    exit;
}

$overtimeId = (int) $stmt->insert_id;
$stmt->close();

$submitterName = trim((string) ($_SESSION['full_name'] ?? ''));
if ($submitterName === '') {
    $nameStmt = $conn->prepare('SELECT full_name, email FROM users WHERE id = ? LIMIT 1');
    if ($nameStmt) {
        $nameStmt->bind_param('i', $userId);
        $nameStmt->execute();
        $nameResult = $nameStmt->get_result();
        $nameRow = $nameResult ? $nameResult->fetch_assoc() : null;
        $nameStmt->close();
        $submitterName = trim((string) ($nameRow['full_name'] ?? $nameRow['email'] ?? 'Timekeeper'));
    }
}

record_audit_log(
    $userId,
    'Overtime Request Submitted',
    "Timekeeper {$submitterName} submitted Overtime Request for Worker #{$workerId}"
);

echo json_encode([
    'success' => true,
    'message' => 'Overtime request submitted successfully',
    'overtime_id' => $overtimeId,
    'total_hours' => $totalHours,
    'status' => 'Pending',
]);

$conn->close();
