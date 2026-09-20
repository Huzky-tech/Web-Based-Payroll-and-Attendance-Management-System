<?php
/**
 * Mobile timekeeper report submission endpoint.
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
require_once __DIR__ . '/timekeeper_report_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mobile_json_error('Invalid request method');
}

$payload = mobile_get_request_payload();
$timekeeperId = mobile_parse_timekeeper_id([$payload]);
mobile_validate_timekeeper($conn, $timekeeperId);
$assignedSite = mobile_require_assigned_site($conn, $timekeeperId);
$assignedSiteId = (int) $assignedSite['site_id'];

tk_report_ensure_schema($conn);

$siteId = (int) ($payload['site_id'] ?? $assignedSiteId);
$reportTypeRaw = trim((string) ($payload['report_type'] ?? ''));
$subject = trim((string) ($payload['subject'] ?? ''));
$description = trim((string) ($payload['description'] ?? ''));
$reportDateRaw = trim((string) ($payload['report_date'] ?? $payload['date'] ?? ''));
$siteName = trim((string) ($payload['site_name'] ?? $assignedSite['site_name'] ?? ''));
$photoBase64 = $payload['photo_base64'] ?? null;

if ($siteId !== $assignedSiteId) {
    mobile_json_error('Access denied for this site.', 403);
}

$reportType = timekeeper_report_normalize_type($reportTypeRaw);
if ($reportType === '') {
    mobile_json_error('Invalid report type.');
}

if ($description === '') {
    mobile_json_error('Description is required.');
}

$reportDate = mobile_parse_attendance_date($reportDateRaw);
if ($reportDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $reportDate)) {
    mobile_json_error('Invalid report date.');
}

if ($siteName === '') {
    $siteName = (string) ($assignedSite['site_name'] ?? '');
}

if ($subject === '') {
    $subject = $reportType;
}

$reportId = timekeeper_report_insert_row($conn, [
    'timekeeper_id' => $timekeeperId,
    'site_id' => $assignedSiteId,
    'site_name' => $siteName,
    'report_type' => $reportType,
    'subject' => $subject,
    'description' => $description,
    'report_date' => $reportDate,
    'status' => 'Pending',
]);

if ($reportId <= 0) {
    $detail = timekeeper_report_last_error();
    mobile_json_error(
        $detail !== '' ? $detail : 'Failed to submit report. Check timekeeper_reports table schema.',
        500
    );
}

$photoPath = timekeeper_report_save_photo(
    is_string($photoBase64) ? $photoBase64 : null,
    $reportId
);

if ($photoPath !== null) {
    timekeeper_report_update_photo_path($conn, $reportId, $photoPath);
}

mobile_json_success([
    'report_id' => $reportId,
    'site_id' => $assignedSiteId,
    'site_name' => $siteName,
    'report_type' => $reportType,
    'subject' => $subject,
    'description' => $description,
    'report_date' => date('m/d/Y', strtotime($reportDate)),
    'photo_path' => $photoPath,
    'status' => 'Submitted',
    'message' => 'Timekeeper report submitted successfully',
]);

$conn->close();
