<?php
/**
 * Mobile timekeeper report submission endpoint.
 */

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ob_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/connection/db_config.php';
require_once __DIR__ . '/timekeeper_report_helpers.php';

// Compatibility wrappers for the updated Hostinger report helper.
if (!function_exists('timekeeper_report_find_by_request_id')) {
    function timekeeper_report_find_by_request_id(mysqli $conn, string $requestId, int $timekeeperId): ?array
    {
        if ($requestId === '') return null;
        $map = timekeeper_report_schema_map($conn);
        if (empty($map['request_id']) || empty($map['timekeeper_id']) || empty($map['id'])) return null;
        $stmt = $conn->prepare("SELECT * FROM timekeeper_reports WHERE `{$map['request_id']}` = ? AND `{$map['timekeeper_id']}` = ? ORDER BY `{$map['id']}` DESC LIMIT 1");
        if (!$stmt) return null;
        $stmt->bind_param('si', $requestId, $timekeeperId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return is_array($row) ? $row : null;
    }
}

if (!function_exists('timekeeper_report_build_success_payload')) {
    function timekeeper_report_build_success_payload(array $row, bool $duplicate = false): array
    {
        $formatted = function_exists('tk_report_format_row')
            ? tk_report_format_row($row)
            : timekeeper_report_format_row($row);
        return [
            'report_id' => (int) ($formatted['report_id'] ?? $formatted['id'] ?? 0),
            'request_id' => (string) ($formatted['request_id'] ?? ''),
            'site_id' => (int) ($formatted['site_id'] ?? 0),
            'site_name' => (string) ($formatted['site_name'] ?? ''),
            'report_type' => (string) ($formatted['report_type'] ?? ''),
            'subject' => (string) ($formatted['subject'] ?? ''),
            'description' => (string) ($formatted['description'] ?? ''),
            'report_date' => (string) ($formatted['report_date'] ?? ''),
            'report_status' => (string) ($formatted['status'] ?? 'Pending'),
            'duplicate' => $duplicate,
            'message' => $duplicate ? 'Report already submitted.' : 'Timekeeper report submitted successfully',
        ];
    }
}

function mobile_report_error(string $message, int $code = 200): void
{
    if (ob_get_level() > 0) ob_clean();
    if ($code !== 200) http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

function mobile_report_success(array $payload): void
{
    if (ob_get_level() > 0) ob_clean();
    echo json_encode(array_merge(['status' => 'success'], $payload));
    exit;
}

function mobile_report_payload(): array
{
    $decoded = json_decode((string) file_get_contents('php://input'), true);
    return is_array($decoded) ? $decoded : $_POST;
}

function mobile_report_site(mysqli $conn, int $timekeeperId): ?array
{
    foreach ([
        "SELECT SiteID FROM timekeeper_assignment WHERE UserID = ? AND Status = 'Active' ORDER BY AssignedDate DESC LIMIT 2",
        'SELECT SiteID FROM timekeeperassignment WHERE TimekeeperID = ? ORDER BY SiteID LIMIT 2',
    ] as $sql) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) continue;
        $stmt->bind_param('i', $timekeeperId);
        $stmt->execute();
        $result = $stmt->get_result();
        $ids = [];
        while ($row = $result->fetch_assoc()) $ids[] = (int) ($row['SiteID'] ?? 0);
        $stmt->close();
        $ids = array_values(array_filter(array_unique($ids)));
        if (count($ids) > 1) mobile_report_error('Multiple sites assigned. Contact your administrator.', 403);
        if ($ids) {
            $stmt = $conn->prepare('SELECT SiteID, Site_Name, Location, ShiftStart, LunchStart, LunchEnd, ShiftEnd FROM projectsite WHERE SiteID = ? LIMIT 1');
            if (!$stmt) return null;
            $stmt->bind_param('i', $ids[0]);
            $stmt->execute();
            $site = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $site ?: null;
        }
    }
    return null;
}

function mobile_report_date(string $raw): string
{
    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $raw, $m)) return $m[1];
    $timestamp = strtotime($raw);
    return $timestamp === false ? '' : date('Y-m-d', $timestamp);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') mobile_report_error('Invalid request method');

$payload = mobile_report_payload();
$timekeeperId = (int) ($payload['TimekeeperID'] ?? $payload['timekeeper_id'] ?? 0);
if ($timekeeperId <= 0) mobile_report_error('TimekeeperID is required.');
$assignedSite = mobile_report_site($conn, $timekeeperId);
if ($assignedSite === null) mobile_report_error('No site assigned to this Timekeeper.', 403);
$assignedSiteId = (int) $assignedSite['site_id'];

if ($assignedSiteId <= 0) $assignedSiteId = (int) ($assignedSite['SiteID'] ?? 0);
$assignedSite['site_id'] = $assignedSiteId;
$assignedSite['site_name'] = (string) ($assignedSite['site_name'] ?? $assignedSite['Site_Name'] ?? '');

tk_report_ensure_schema($conn);

$requestId = trim((string) ($payload['request_id'] ?? ''));
if ($requestId !== '') {
    $existing = timekeeper_report_find_by_request_id($conn, $requestId, $timekeeperId);
    if ($existing !== null) {
        mobile_report_success(timekeeper_report_build_success_payload($existing, true));
    }
}

$siteId = (int) ($payload['site_id'] ?? $assignedSiteId);
$reportTypeRaw = trim((string) ($payload['report_type'] ?? ''));
$subject = trim((string) ($payload['subject'] ?? ''));
$description = trim((string) ($payload['description'] ?? ''));
$reportDateRaw = trim((string) ($payload['report_date'] ?? $payload['date'] ?? ''));
$siteName = trim((string) ($payload['site_name'] ?? $assignedSite['site_name'] ?? ''));
$photoBase64 = $payload['photo_base64'] ?? null;

if ($siteId !== $assignedSiteId) {
    mobile_report_error('Access denied for this site.', 403);
}

// The direct assignment lookup above is the mobile authorization check. Do
// not call the optional web-only validator here; older Hostinger API folders
// may not include its assignment helper dependency.

$reportType = timekeeper_report_normalize_type($reportTypeRaw);
if ($reportType === '') {
    mobile_report_error('Invalid report type.');
}

if ($description === '') {
    mobile_report_error('Description is required.');
}

$reportDate = mobile_report_date($reportDateRaw);
if ($reportDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $reportDate)) {
    mobile_report_error('Invalid report date.');
}

$serverToday = date('Y-m-d');
if ($reportDate > $serverToday) {
    mobile_report_error('Future dates are not allowed. Please select today\'s date.');
}

if ($siteName === '') {
    $siteName = (string) ($assignedSite['site_name'] ?? '');
}

if ($subject === '') {
    $subject = $reportType . ' - ' . date('m/d/Y', strtotime($reportDate));
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
    'request_id' => $requestId,
]);

if ($reportId <= 0) {
    $detail = timekeeper_report_last_error();
    mobile_report_error(
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

if (function_exists('tk_report_create_admin_notification')) {
    $timekeeperName = '';
    $nameStmt = $conn->prepare('SELECT full_name FROM users WHERE id = ? LIMIT 1');
    if ($nameStmt) {
        $nameStmt->bind_param('i', $timekeeperId);
        $nameStmt->execute();
        $nameRow = $nameStmt->get_result()->fetch_assoc();
        $nameStmt->close();
        $timekeeperName = (string) ($nameRow['full_name'] ?? '');
    }
    tk_report_create_admin_notification($conn, $reportId, $reportType, $subject, $siteName, $timekeeperName);
}

$inserted = timekeeper_report_find_by_request_id($conn, $requestId, $timekeeperId);
if ($inserted === null) {
    $map = timekeeper_report_schema_map($conn);
    $idColumn = $map['id'] ?? 'ReportID';
    $stmt = $conn->prepare("SELECT * FROM timekeeper_reports WHERE {$idColumn} = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $reportId);
        $stmt->execute();
        $result = $stmt->get_result();
        $inserted = $result !== false ? $result->fetch_assoc() : null;
        $stmt->close();
    }
}

if (!is_array($inserted)) {
    mobile_report_success([
        'report_id' => $reportId,
        'request_id' => $requestId,
        'site_id' => $assignedSiteId,
        'site_name' => $siteName,
        'report_type' => $reportType,
        'subject' => $subject,
        'description' => $description,
        'report_date' => date('m/d/Y', strtotime($reportDate)),
        'photo_path' => $photoPath,
        'report_status' => 'Pending',
        'duplicate' => false,
        'message' => 'Timekeeper report submitted successfully',
    ]);
}

mobile_report_success(timekeeper_report_build_success_payload($inserted, false));

$conn->close();
