<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/timekeeper_report_helpers.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR']);
tk_report_ensure_schema($conn);

if (!tk_report_table_exists($conn)) {
    echo json_encode(['success' => true, 'items' => [], 'stats' => ['total' => 0, 'pending' => 0, 'reviewed' => 0, 'resolved' => 0, 'submitted_today' => 0], 'report_types' => tk_report_valid_types()]);
    exit;
}

// Older deployments use ReportID/TimekeeperID; newer ones use TK_ReportsID/UserID.
$map = timekeeper_report_schema_map($conn);
if ($map === [] || empty($map['id']) || empty($map['site_id']) || empty($map['timekeeper_id'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => timekeeper_report_last_error() ?: 'Timekeeper reports are not configured correctly.']);
    exit;
}

$q = static fn(string $name): string => '`' . str_replace('`', '', $name) . '`';
$col = static function (string $logical) use ($map, $q): string {
    return !empty($map[$logical]) ? 'tr.' . $q($map[$logical]) : 'NULL';
};
$idCol = $col('id');
$siteCol = $col('site_id');
$timekeeperCol = $col('timekeeper_id');
$userCol = $col('user_id');
$statusCol = $col('status');
$typeCol = $col('report_type');
$subjectCol = $col('subject');
$descriptionCol = $col('description');
$reportDateCol = $col('report_date');
$createdCol = $col('created_at');
$photoCol = $col('photo_path');
$siteNameCol = $col('site_name');
$reportTypeSql = "COALESCE(NULLIF({$typeCol}, ''), 'Site Report')";
$descriptionSql = "COALESCE(NULLIF({$descriptionCol}, ''), '')";
$createdSql = "COALESCE({$createdCol}, CONCAT({$reportDateCol}, ' 00:00:00'))";
$statusSql = "COALESCE(NULLIF({$statusCol}, ''), 'Pending')";
$userJoinCol = $userCol !== 'NULL' ? $userCol : $timekeeperCol;

$where = ['1=1'];
$types = '';
$params = [];
$statusFilter = trim((string) ($_GET['status'] ?? ''));
$typeFilter = trim((string) ($_GET['report_type'] ?? ''));
$search = trim((string) ($_GET['search'] ?? ''));
if ($statusFilter !== '' && in_array($statusFilter, tk_report_valid_statuses(), true)) {
    $where[] = "{$statusSql} = ?";
    $types .= 's';
    $params[] = $statusFilter;
}
if ($typeFilter !== '') {
    $where[] = "{$reportTypeSql} = ?";
    $types .= 's';
    $params[] = $typeFilter;
}
if ($search !== '') {
    $where[] = "({$subjectCol} LIKE ? OR {$reportTypeSql} LIKE ? OR ps.Site_Name LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $types .= 'sssss';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like, $like);
}
$whereSql = implode(' AND ', $where);

$sql = "SELECT
    {$idCol} AS id, {$userJoinCol} AS user_id, {$timekeeperCol} AS timekeeper_id, {$siteCol} AS site_id,
    COALESCE(ps.Site_Name, NULLIF({$siteNameCol}, ''), 'Archived Site') AS site_name,
    COALESCE(ps.Location, '') AS Location,
    COALESCE(u.full_name, u.email, 'Unknown Timekeeper') AS timekeeper_name, u.email AS timekeeper_email,
    {$reportTypeSql} AS report_type_raw, {$subjectCol} AS subject, {$descriptionSql} AS description_raw,
    {$statusSql} AS Status, {$reportDateCol} AS ReportDate, {$createdSql} AS created_at_raw,
    {$photoCol} AS AttachmentPath, tr.`DelayCategory`, tr.`HoursLost`, tr.`WorkersAffected`,
    tr.`CauseOfDelay`, tr.`RecommendedAction`, tr.`AdminRemarks`
    FROM timekeeper_reports tr
    LEFT JOIN projectsite ps ON ps.SiteID = {$siteCol}
    LEFT JOIN users u ON u.id = {$userJoinCol}
    WHERE {$whereSql}
    ORDER BY {$createdSql} DESC, {$idCol} DESC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load timekeeper reports.']);
    exit;
}
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) {
    $formatted = tk_report_format_row($row);
    $formatted['can_update'] = true;
    $items[] = $formatted;
}
$stmt->close();

$statsSql = "SELECT COUNT(*) AS total,
    SUM(CASE WHEN {$statusSql} = 'Pending' THEN 1 ELSE 0 END) AS pending,
    SUM(CASE WHEN {$statusSql} = 'Reviewed' THEN 1 ELSE 0 END) AS reviewed,
    SUM(CASE WHEN {$statusSql} = 'Resolved' THEN 1 ELSE 0 END) AS resolved,
    SUM(CASE WHEN DATE({$createdSql}) = CURDATE() THEN 1 ELSE 0 END) AS submitted_today
    FROM timekeeper_reports tr LEFT JOIN projectsite ps ON ps.SiteID = {$siteCol}
    LEFT JOIN users u ON u.id = {$userJoinCol} WHERE {$whereSql}";
$stats = ['total' => 0, 'pending' => 0, 'reviewed' => 0, 'resolved' => 0, 'submitted_today' => 0];
$statsStmt = $conn->prepare($statsSql);
if ($statsStmt) {
    if ($types !== '') $statsStmt->bind_param($types, ...$params);
    $statsStmt->execute();
    $stats = array_merge($stats, $statsStmt->get_result()->fetch_assoc() ?: []);
    $statsStmt->close();
}
echo json_encode(['success' => true, 'items' => $items, 'stats' => $stats, 'role' => $currentRole, 'report_types' => tk_report_valid_types(), 'statuses' => tk_report_valid_statuses()]);
$conn->close();
