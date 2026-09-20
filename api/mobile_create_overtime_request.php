<?php
require_once 'connection/db_config.php';
require_once __DIR__ . '/mobile_auth_helpers.php';
require_once __DIR__ . '/overtime_helpers.php';
require_once __DIR__ . '/record_audit_log.php';

mobile_json_headers();
mobile_handle_options();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mobile_json_error('Invalid request method.', 405);
}

if (!overtime_table_exists($conn)) {
    mobile_json_error('Overtime requests table is not installed');
}

$payload = mobile_get_request_payload();
$timekeeperId = mobile_resolve_timekeeper_id($payload);
$assignment = mobile_require_timekeeper_assignment($conn, $timekeeperId);
$assignedSiteId = (int) ($assignment['SiteID'] ?? 0);

$workerId = (int) ($payload['worker_id'] ?? 0);
$siteId = (int) ($payload['site_id'] ?? $assignedSiteId);
$requestDate = trim((string) ($payload['request_date'] ?? ''));
$overtimeType = trim((string) ($payload['overtime_type'] ?? ''));
$overtimeStart = normalize_site_time_input($payload['overtime_start'] ?? null);
$overtimeEnd = normalize_site_time_input($payload['overtime_end'] ?? null);
$reason = trim((string) ($payload['reason'] ?? ''));

if ($workerId <= 0 || $siteId <= 0 || $requestDate === '' || $overtimeType === '' || $reason === '') {
    mobile_json_error('Worker, site, date, overtime type, and reason are required');
}

if (!in_array($overtimeType, overtime_valid_types(), true)) {
    mobile_json_error('Invalid overtime type');
}

$accessError = validate_timekeeper_worker_access($conn, $timekeeperId, $workerId, $siteId);
if ($accessError !== null) {
    mobile_json_error($accessError);
}

$totalHours = calculate_overtime_total_hours($overtimeStart, $overtimeEnd);
if ($totalHours <= 0) {
    mobile_json_error('End time must be greater than start time and total hours must be greater than zero');
}

$stmt = $conn->prepare("
    INSERT INTO overtime_requests (
        WorkerID, SiteID, RequestDate, OvertimeType, OvertimeStart, OvertimeEnd,
        TotalHours, Reason, SubmittedBy, Status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
");

if (!$stmt) {
    mobile_json_error('Unable to save overtime request');
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
    $timekeeperId
);

if (!$stmt->execute()) {
    $stmt->close();
    mobile_json_error('Failed to submit overtime request');
}

$overtimeId = (int) $stmt->insert_id;
$stmt->close();

record_audit_log(
    $timekeeperId,
    'Mobile Overtime Request Submitted',
    "Timekeeper #{$timekeeperId} submitted Overtime Request for Worker #{$workerId}"
);

echo json_encode([
    'status' => 'success',
    'success' => true,
    'message' => 'Overtime request submitted successfully',
    'overtime_id' => $overtimeId,
    'total_hours' => $totalHours,
    'request_status' => 'Pending',
]);

$conn->close();
