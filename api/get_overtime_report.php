<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/overtime_helpers.php';
require_once __DIR__ . '/../includes/report_scope_helpers.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR', 'Timekeeper']);
$userId = (int) ($_SESSION['user_id'] ?? 0);

if (!overtime_table_exists($conn)) {
    echo json_encode(['success' => true, 'items' => []]);
    exit;
}

$date = trim((string) ($_GET['date'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$siteFilter = trim((string) ($_GET['site'] ?? ''));
$searchFilter = trim((string) ($_GET['search'] ?? ''));
$typeFilter = trim((string) ($_GET['type'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? ''));

$where = ['1=1'];
$types = '';
$params = [];

if ($date !== '') {
    $where[] = 'ot.RequestDate = ?';
    $types .= 's';
    $params[] = $date;
} elseif ($dateFrom !== '' || $dateTo !== '') {
    $dateFrom = $dateFrom !== '' ? $dateFrom : $dateTo;
    $dateTo = $dateTo !== '' ? $dateTo : $dateFrom;
    $where[] = 'ot.RequestDate BETWEEN ? AND ?';
    $types .= 'ss';
    array_push($params, $dateFrom, $dateTo);
}

if ($currentRole === 'Timekeeper') {
    $siteIds = auth_get_timekeeper_site_ids($conn, $userId);
    if (count($siteIds) === 0) {
        echo json_encode(['success' => true, 'items' => [], 'date_from' => $dateFrom, 'date_to' => $dateTo]);
        exit;
    }
    $placeholders = implode(',', array_fill(0, count($siteIds), '?'));
    $where[] = "ot.SiteID IN ({$placeholders})";
    $types .= str_repeat('i', count($siteIds));
    foreach ($siteIds as $siteId) {
        $params[] = $siteId;
    }
} elseif ($currentRole === 'Payroll Staff') {
    $payrollStaffId = auth_get_payroll_staff_id($conn, $userId);
    if ($payrollStaffId <= 0) {
        echo json_encode(['success' => true, 'items' => [], 'date_from' => $dateFrom, 'date_to' => $dateTo]);
        exit;
    }
    $where[] = 'ot.SiteID IN (SELECT SiteID FROM payrollstaffassignment WHERE PayrollStaff_ID = ?)';
    $types .= 'i';
    $params[] = $payrollStaffId;
}

if ($statusFilter !== '' && in_array($statusFilter, overtime_valid_statuses(), true)) {
    $where[] = 'ot.Status = ?';
    $types .= 's';
    $params[] = $statusFilter;
}

if ($siteFilter !== '') {
    $where[] = 'ps.Site_Name LIKE ?';
    $types .= 's';
    $params[] = '%' . $siteFilter . '%';
}

if ($typeFilter !== '') {
    $where[] = 'ot.OvertimeType = ?';
    $types .= 's';
    $params[] = $typeFilter;
}

if ($searchFilter !== '') {
    $where[] = "(ps.Site_Name LIKE ? OR CONCAT(COALESCE(w.First_Name, ''), ' ', COALESCE(w.Last_Name, '')) LIKE ? OR ot.OvertimeType LIKE ? OR ot.Status LIKE ?)";
    $types .= 'ssss';
    $searchLike = '%' . $searchFilter . '%';
    array_push($params, $searchLike, $searchLike, $searchLike, $searchLike);
}

$whereSql = implode(' AND ', $where);

$sql = "
    SELECT
        ot.OvertimeID,
        ot.WorkerID,
        ot.SiteID,
        ot.RequestDate,
        ot.OvertimeType,
        ot.OvertimeStart,
        ot.OvertimeEnd,
        ot.TotalHours,
        ot.Reason,
        ot.SubmittedBy,
        ot.Status,
        ot.ApprovedBy,
        ot.ApprovedDate,
        ot.CreatedAt,
        w.First_Name,
        w.Last_Name,
        ps.Site_Name,
        su.full_name AS submitted_by_name,
        su.email AS submitted_by_email,
        au.full_name AS approved_by_name,
        au.email AS approved_by_email
    FROM overtime_requests ot
    INNER JOIN worker w ON w.WorkerID = ot.WorkerID
    INNER JOIN projectsite ps ON ps.SiteID = ot.SiteID
    INNER JOIN users su ON su.id = ot.SubmittedBy
    LEFT JOIN users au ON au.id = ot.ApprovedBy
    WHERE {$whereSql}
    ORDER BY ot.RequestDate DESC, ot.OvertimeID DESC
";

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = format_overtime_request_row($row);
}
$stmt->close();

$sites = report_site_options($conn);

echo json_encode([
    'success' => true,
    'items' => $items,
    'sites' => $sites,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
]);

$conn->close();
