<?php
/**
 * Mobile overtime submission endpoint.
 * Inserts into overtime_requests so requests appear on the web admin dashboard.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

include 'connection/db_config.php';
require_once __DIR__ . '/mobile_auth_helpers.php';
require_once __DIR__ . '/overtime_helpers.php';

// #region agent log
if (!function_exists('_agent_debug_log_efaf62')) {
    function _agent_debug_log_efaf62(string $hypothesisId, string $location, string $message, array $data = []): void
    {
        $entry = json_encode([
            'sessionId' => 'efaf62',
            'hypothesisId' => $hypothesisId,
            'location' => $location,
            'message' => $message,
            'data' => $data,
            'timestamp' => (int) round(microtime(true) * 1000),
            'runId' => 'pre-fix',
        ]);
        @file_put_contents(__DIR__ . '/../../debug-efaf62.log', $entry . "\n", FILE_APPEND);
    }
}
// #endregion

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mobile_json_error('Invalid request method');
}

$payload = mobile_get_request_payload();
$timekeeperId = mobile_parse_timekeeper_id([$payload]);

// #region agent log
$authFn = function_exists('auth_get_timekeeper_site_ids') ? 'yes' : 'no';
$authSiteIds = function_exists('auth_get_timekeeper_site_ids')
    ? auth_get_timekeeper_site_ids($conn, $timekeeperId)
    : [];
$tkTable = function_exists('timekeeper_assignment_table_exists')
    ? (timekeeper_assignment_table_exists($conn) ? 'yes' : 'no')
    : 'unknown';
_agent_debug_log_efaf62('A', 'mobile_submit_overtime.php:pre-assignment', 'overtime submit payload', [
    'timekeeperId' => $timekeeperId,
    'payloadSiteId' => (int) ($payload['site_id'] ?? 0),
    'authFnExists' => $authFn,
    'authSiteIds' => $authSiteIds,
    'authSiteIdCount' => count($authSiteIds),
    'timekeeperAssignmentTable' => $tkTable,
    'authFnDefinedIn' => function_exists('auth_get_timekeeper_site_ids')
        ? (new ReflectionFunction('auth_get_timekeeper_site_ids'))->getFileName()
        : null,
]);
// #endregion

mobile_validate_timekeeper($conn, $timekeeperId);
$assignedSite = mobile_require_assigned_site($conn, $timekeeperId);

// #region agent log
_agent_debug_log_efaf62('A', 'mobile_submit_overtime.php:post-assignment', 'assigned site resolved', [
    'siteId' => (int) ($assignedSite['site_id'] ?? 0),
    'siteName' => (string) ($assignedSite['site_name'] ?? ''),
]);
// #endregion
$assignedSiteId = (int) $assignedSite['site_id'];

if (!overtime_table_exists($conn)) {
    mobile_json_error('Overtime requests table is not installed');
}

$workerId = (int) ($payload['worker_id'] ?? 0);
$workerName = trim((string) ($payload['worker_name'] ?? ''));
$siteId = (int) ($payload['site_id'] ?? $assignedSiteId);
$requestDateRaw = trim((string) ($payload['request_date'] ?? $payload['date'] ?? ''));
$overtimeTypeRaw = trim((string) ($payload['overtime_type'] ?? $payload['type'] ?? ''));
$overtimeStart = normalize_site_time_input($payload['overtime_start'] ?? null);
$overtimeEnd = normalize_site_time_input($payload['overtime_end'] ?? null);
$totalHoursRaw = $payload['total_hours'] ?? $payload['hours'] ?? null;
$reason = trim((string) ($payload['reason'] ?? 'Submitted via mobile app'));

if ($siteId !== $assignedSiteId) {
    mobile_json_error('Access denied for this site.', 403);
}

$workerId = overtime_resolve_worker_id($conn, $workerId, $workerName, $assignedSiteId);
if ($workerId <= 0) {
    mobile_json_error('Worker is required.');
}

if (!worker_assigned_to_site($conn, $workerId, $assignedSiteId)) {
    mobile_json_error('Worker is not assigned to your site.', 403);
}

$requestDate = mobile_parse_attendance_date($requestDateRaw);
if ($requestDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestDate)) {
    mobile_json_error('Invalid request date.');
}

$overtimeType = mobile_normalize_overtime_type($overtimeTypeRaw);
if ($overtimeType === '') {
    mobile_json_error('Invalid overtime type.');
}

if ($reason === '') {
    mobile_json_error('Reason is required.');
}

if ($overtimeStart === null || $overtimeEnd === null) {
    $totalHours = is_numeric($totalHoursRaw) ? (float) $totalHoursRaw : 0.0;
    if ($totalHours <= 0) {
        mobile_json_error('Total hours must be greater than zero.');
    }

    $overtimeStart = overtime_site_shift_end($conn, $assignedSiteId);
    $startMin = site_schedule_time_to_minutes($overtimeStart);
    if ($startMin === null) {
        mobile_json_error('Unable to determine overtime start time.');
    }

    $overtimeEnd = minutes_to_site_time($startMin + (int) round($totalHours * 60));
    $totalHours = calculate_overtime_total_hours($overtimeStart, $overtimeEnd);
} else {
    $totalHours = calculate_overtime_total_hours($overtimeStart, $overtimeEnd);
}

if ($totalHours <= 0) {
    mobile_json_error('End time must be greater than start time.');
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
    $duplicateStmt->bind_param('iissss', $workerId, $assignedSiteId, $requestDate, $overtimeType, $overtimeStart, $overtimeEnd);
    $duplicateStmt->execute();
    $duplicateRequest = $duplicateStmt->get_result()->fetch_assoc();
    $duplicateStmt->close();

    if ($duplicateRequest) {
        mobile_json_error('This overtime request already exists.');
    }
}

$stmt = $conn->prepare("
    INSERT INTO overtime_requests (
        WorkerID, SiteID, RequestDate, OvertimeType, OvertimeStart, OvertimeEnd,
        TotalHours, Reason, SubmittedBy, Status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
");
if (!$stmt) {
    mobile_json_error('Database error', 500);
}

$stmt->bind_param(
    'iissssdsi',
    $workerId,
    $assignedSiteId,
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

$nameStmt = $conn->prepare('SELECT First_Name, Last_Name FROM worker WHERE WorkerID = ? LIMIT 1');
$workerDisplayName = 'Worker #' . $workerId;
if ($nameStmt) {
    $nameStmt->bind_param('i', $workerId);
    $nameStmt->execute();
    $nameRow = $nameStmt->get_result()->fetch_assoc();
    $nameStmt->close();
    $resolvedName = trim((string) (($nameRow['First_Name'] ?? '') . ' ' . ($nameRow['Last_Name'] ?? '')));
    if ($resolvedName !== '') {
        $workerDisplayName = $resolvedName;
    }
}

mobile_json_success([
    'overtime_id' => $overtimeId,
    'worker_id' => $workerId,
    'worker_name' => $workerDisplayName,
    'site_id' => $assignedSiteId,
    'date' => date('m/d/Y', strtotime($requestDate)),
    'type' => $overtimeType,
    'hours' => $totalHours,
    'request_status' => 'Pending',
    'message' => 'Overtime request submitted successfully',
]);

$conn->close();
