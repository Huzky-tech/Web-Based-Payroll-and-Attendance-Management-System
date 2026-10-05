<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/timekeeper_report_helpers.php';

require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR']);
$reportId = (int) ($_GET['report_id'] ?? $_GET['id'] ?? 0);
tk_report_ensure_schema($conn);
if ($reportId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Report ID is required']);
    exit;
}
$map = timekeeper_report_schema_map($conn);
if ($map === [] || empty($map['id']) || empty($map['site_id']) || empty($map['timekeeper_id'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => timekeeper_report_last_error() ?: 'Timekeeper reports are not configured correctly.']);
    exit;
}
$q = static fn(string $name): string => '`' . str_replace('`', '', $name) . '`';
$col = static function (string $logical) use ($map, $q): string { return !empty($map[$logical]) ? 'tr.' . $q($map[$logical]) : 'NULL'; };
$id = $col('id'); $site = $col('site_id'); $timekeeper = $col('timekeeper_id'); $user = $col('user_id');
$status = $col('status'); $type = $col('report_type'); $subject = $col('subject'); $description = $col('description');
$reportDate = $col('report_date'); $created = $col('created_at'); $photo = $col('photo_path'); $siteName = $col('site_name');
$userJoin = $user !== 'NULL' ? $user : $timekeeper;
$typeSql = "COALESCE(NULLIF({$type}, ''), 'Site Report')";
$descriptionSql = "COALESCE(NULLIF({$description}, ''), '')";
$createdSql = "COALESCE({$created}, CONCAT({$reportDate}, ' 00:00:00'))";
$statusSql = "COALESCE(NULLIF({$status}, ''), 'Pending')";
$sql = "SELECT {$id} AS id, {$userJoin} AS user_id, {$timekeeper} AS timekeeper_id, {$site} AS site_id,
    COALESCE(ps.Site_Name, NULLIF({$siteName}, ''), 'Archived Site') AS site_name, COALESCE(ps.Location, '') AS Location,
    COALESCE(u.full_name, u.email, 'Unknown Timekeeper') AS timekeeper_name, u.email AS timekeeper_email,
    {$typeSql} AS report_type_raw, {$subject} AS subject, {$descriptionSql} AS description_raw,
    {$statusSql} AS Status, {$reportDate} AS ReportDate, {$createdSql} AS created_at_raw,
    {$photo} AS AttachmentPath, tr.`DelayCategory`, tr.`HoursLost`, tr.`WorkersAffected`, tr.`CauseOfDelay`,
    tr.`RecommendedAction`, tr.`AdminRemarks`
    FROM timekeeper_reports tr LEFT JOIN projectsite ps ON ps.SiteID = {$site}
    LEFT JOIN users u ON u.id = {$userJoin} WHERE {$id} = ? LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load report details.']);
    exit;
}
$stmt->bind_param('i', $reportId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Report not found']);
    exit;
}
$report = tk_report_format_row($row);
$report['can_update'] = true;
echo json_encode(['success' => true, 'report' => $report, 'delay_categories' => tk_report_delay_categories()]);
$conn->close();
