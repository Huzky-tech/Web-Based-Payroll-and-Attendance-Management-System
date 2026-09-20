<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/timekeeper_report_helpers.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR']);
$userId = (int) ($_SESSION['user_id'] ?? 0);

tk_report_ensure_schema($conn);

if (!tk_report_table_exists($conn)) {
    echo json_encode([
        'success' => true,
        'items' => [],
        'stats' => ['total' => 0, 'pending' => 0, 'reviewed' => 0, 'resolved' => 0, 'submitted_today' => 0],
    ]);
    exit;
}

$statusFilter = trim((string) ($_GET['status'] ?? ''));
$typeFilter = trim((string) ($_GET['report_type'] ?? ''));
$search = trim((string) ($_GET['search'] ?? ''));

$where = ['1=1'];
$types = '';
$params = [];

if ($statusFilter !== '' && in_array($statusFilter, tk_report_valid_statuses(), true)) {
    $where[] = "COALESCE(NULLIF(tr.Status, ''), 'Pending') = ?";
    $types .= 's';
    $params[] = $statusFilter;
}

if ($typeFilter !== '') {
    $where[] = tk_report_type_sql() . ' = ?';
    $types .= 's';
    $params[] = $typeFilter;
}

if ($search !== '') {
    $where[] = "(tr.Subject LIKE ? OR " . tk_report_type_sql() . " LIKE ? OR ps.Site_Name LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $types .= 'sssss';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = implode(' AND ', $where);
$reportTypeSql = tk_report_type_sql();
$descriptionSql = tk_report_description_sql();
$createdSql = tk_report_created_at_sql();

$sql = "
    SELECT
        tr.TK_ReportsID,
        tr.UserID,
        tr.SiteID,
        tr.Subject,
        tr.ReportType,
        tr.DelayType,
        tr.Description,
        tr.AdditionalNotes,
        tr.DelayCategory,
        tr.HoursLost,
        tr.WorkersAffected,
        tr.CauseOfDelay,
        tr.RecommendedAction,
        tr.AttachmentPath,
        tr.Status,
        tr.AdminRemarks,
        tr.ReportDate,
        tr.CreatedAt,
        tr.UpdatedAt,
        {$reportTypeSql} AS report_type_raw,
        {$descriptionSql} AS description_raw,
        {$createdSql} AS created_at_raw,
        ps.Site_Name,
        ps.Location,
        COALESCE(u.full_name, u.email, 'Unknown Timekeeper') AS timekeeper_name,
        u.email AS timekeeper_email
    FROM timekeeper_reports tr
    INNER JOIN projectsite ps ON ps.SiteID = tr.SiteID
    INNER JOIN users u ON u.id = tr.UserID
    WHERE {$whereSql}
    ORDER BY {$createdSql} DESC, tr.TK_ReportsID DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to load timekeeper reports']);
    exit;
}

if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $formatted = tk_report_format_row($row);
    $formatted['can_update'] = in_array($currentRole, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR'], true);
    $items[] = $formatted;
}
$stmt->close();

$stats = tk_report_get_stats($conn, $whereSql, $types, $params);

echo json_encode([
    'success' => true,
    'items' => $items,
    'stats' => $stats,
    'role' => $currentRole,
    'report_types' => tk_report_valid_types(),
    'statuses' => tk_report_valid_statuses(),
]);

$conn->close();
