<?php
/**
 * Returns timekeeper reports for the assigned site.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

include 'connection/db_config.php';
require_once __DIR__ . '/mobile_auth_helpers.php';
require_once __DIR__ . '/timekeeper_report_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    mobile_json_error('Invalid request method');
}

$timekeeperId = mobile_parse_timekeeper_id([$_GET]);
mobile_validate_timekeeper($conn, $timekeeperId);
$assignedSite = mobile_require_assigned_site($conn, $timekeeperId);
$siteId = (int) $assignedSite['site_id'];

tk_report_ensure_schema($conn);

$rows = timekeeper_report_fetch_for_timekeeper($conn, $siteId, $timekeeperId);
if ($rows === [] && timekeeper_report_schema_map($conn) === []) {
    mobile_json_error('Database error', 500);
}

$items = [];
foreach ($rows as $row) {
    $items[] = timekeeper_report_format_row($row);
}

mobile_json_success([
    'site_id' => $siteId,
    'site_name' => $assignedSite['site_name'],
    'items' => $items,
    'reports' => $items,
]);

$conn->close();
