<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/timekeeper_report_helpers.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR']);
$userId = (int) ($_SESSION['user_id'] ?? 0);
$reportId = (int) ($_GET['report_id'] ?? $_GET['id'] ?? 0);

tk_report_ensure_schema($conn);

if ($reportId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Report ID is required']);
    exit;
}

if (!tk_report_table_exists($conn)) {
    echo json_encode(['success' => false, 'message' => 'Reports table is not installed']);
    exit;
}

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
    WHERE tr.TK_ReportsID = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to load report details']);
    exit;
}

$stmt->bind_param('i', $reportId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Report not found']);
    exit;
}

$report = tk_report_format_row($row);
$report['can_update'] = in_array($currentRole, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR'], true);

echo json_encode([
    'success' => true,
    'report' => $report,
    'delay_categories' => tk_report_delay_categories(),
]);

$conn->close();
