<?php
require_once 'connection/db_config.php';
require_once __DIR__ . '/mobile_auth_helpers.php';
require_once __DIR__ . '/record_audit_log.php';
require_once __DIR__ . '/timekeeper_report_helpers.php';

mobile_json_headers();
mobile_handle_options();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mobile_json_error('Invalid request method.', 405);
}

tk_report_ensure_schema($conn);

if (!tk_report_table_exists($conn)) {
    mobile_json_error('Timekeeper reports table is not installed');
}

$payload = mobile_get_request_payload();
$timekeeperId = mobile_resolve_timekeeper_id($payload);
$assignment = mobile_require_timekeeper_assignment($conn, $timekeeperId);
$assignedSiteId = (int) ($assignment['SiteID'] ?? 0);

$siteId = (int) ($payload['site_id'] ?? $assignedSiteId);
$reportType = trim((string) ($payload['report_type'] ?? ''));
$subject = trim((string) ($payload['subject'] ?? ''));
$description = trim((string) ($payload['description'] ?? ''));
$delayCategory = trim((string) ($payload['delay_category'] ?? ''));
$hoursLost = isset($payload['hours_lost']) ? (float) $payload['hours_lost'] : null;
$workersAffected = isset($payload['workers_affected']) ? (int) $payload['workers_affected'] : null;
$causeOfDelay = trim((string) ($payload['cause_of_delay'] ?? ''));
$recommendedAction = trim((string) ($payload['recommended_action'] ?? ''));
$attachmentBase64 = $payload['attachment_base64'] ?? $payload['photo_base64'] ?? null;
$attachmentFilename = $payload['attachment_filename'] ?? $payload['photo_filename'] ?? null;

if ($siteId <= 0 || $reportType === '' || $subject === '' || $description === '') {
    mobile_json_error('Site, report type, subject, and description are required');
}

if (!in_array($reportType, tk_report_valid_types(), true)) {
    mobile_json_error('Invalid report type');
}

$accessError = tk_report_validate_timekeeper_site($conn, $timekeeperId, $siteId);
if ($accessError !== null) {
    mobile_json_error($accessError, 403);
}

if (strcasecmp($reportType, 'Site Delay') === 0) {
    if ($delayCategory === '' || !in_array($delayCategory, tk_report_delay_categories(), true)) {
        mobile_json_error('A valid delay category is required for Site Delay reports');
    }
    if ($hoursLost === null || $hoursLost <= 0) {
        mobile_json_error('Estimated hours lost is required for Site Delay reports');
    }
} else {
    $delayCategory = $delayCategory !== '' ? $delayCategory : null;
    $hoursLost = $hoursLost !== null && $hoursLost > 0 ? $hoursLost : null;
}

$attachmentPath = tk_report_save_attachment(
    is_string($attachmentBase64) ? $attachmentBase64 : null,
    is_string($attachmentFilename) ? $attachmentFilename : null
);

$reportDate = date('Y-m-d');
$status = 'Pending';
$hoursLostValue = $hoursLost !== null ? $hoursLost : null;
$workersAffectedValue = $workersAffected !== null && $workersAffected > 0 ? $workersAffected : null;
$delayCategoryValue = $delayCategory !== '' ? $delayCategory : null;

$stmt = $conn->prepare("
    INSERT INTO timekeeper_reports (
        UserID, SiteID, ReportDate, Subject, ReportType, Description,
        DelayCategory, HoursLost, WorkersAffected, CauseOfDelay, RecommendedAction,
        AttachmentPath, Status, DelayType, AdditionalNotes, CreatedAt, UpdatedAt
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
");

if (!$stmt) {
    mobile_json_error('Unable to save report');
}

$stmt->bind_param(
    'iisssssdissssss',
    $timekeeperId,
    $siteId,
    $reportDate,
    $subject,
    $reportType,
    $description,
    $delayCategoryValue,
    $hoursLostValue,
    $workersAffectedValue,
    $causeOfDelay,
    $recommendedAction,
    $attachmentPath,
    $status,
    $reportType,
    $description
);

if (!$stmt->execute()) {
    $stmt->close();
    mobile_json_error('Failed to submit report');
}

$reportId = (int) $stmt->insert_id;
$stmt->close();

$timekeeperName = (string) ($assignment['Timekeeper_Name'] ?? 'Timekeeper');
$siteName = (string) ($assignment['Site_Name'] ?? 'Site');

$nameStmt = $conn->prepare('SELECT COALESCE(full_name, email, ?) AS display_name FROM users WHERE id = ? LIMIT 1');
if ($nameStmt) {
    $nameStmt->bind_param('si', $timekeeperName, $timekeeperId);
    $nameStmt->execute();
    $nameRow = $nameStmt->get_result()->fetch_assoc();
    $nameStmt->close();
    if (!empty($nameRow['display_name'])) {
        $timekeeperName = (string) $nameRow['display_name'];
    }
}

tk_report_create_admin_notification(
    $conn,
    $reportId,
    $reportType,
    $subject,
    $siteName,
    $timekeeperName
);

record_audit_log(
    $timekeeperId,
    'Mobile Timekeeper Report Submitted',
    "Timekeeper #{$timekeeperId} submitted {$reportType} report #{$reportId} for Site #{$siteId}"
);

echo json_encode([
    'status' => 'success',
    'success' => true,
    'message' => 'Report submitted successfully',
    'report_id' => $reportId,
    'report_status' => 'Pending',
]);

$conn->close();
