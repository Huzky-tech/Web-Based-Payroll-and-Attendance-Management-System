<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/overtime_helpers.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Timekeeper', 'Payroll Staff', 'HR']);
$userId = (int) ($_SESSION['user_id'] ?? 0);

if (!overtime_table_exists($conn)) {
    echo json_encode(['success' => true, 'items' => [], 'counts' => ['all' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0]]);
    exit;
}

$statusFilter = trim((string) ($_GET['status'] ?? ''));
$typeFilter = trim((string) ($_GET['type'] ?? ''));
$siteFilter = (int) ($_GET['site_id'] ?? 0);
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$search = trim((string) ($_GET['search'] ?? ''));

$where = ['1=1'];
$types = '';
$params = [];

if ($currentRole === 'Timekeeper') {
    $siteIds = auth_get_timekeeper_site_ids($conn, $userId);
    if (count($siteIds) === 0) {
        echo json_encode(['success' => true, 'items' => [], 'counts' => ['all' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0]]);
        exit;
    }
    $placeholders = implode(',', array_fill(0, count($siteIds), '?'));
    $where[] = "(ot.SiteID IN ({$placeholders}) AND ot.SubmittedBy = ?)";
    $types .= str_repeat('i', count($siteIds)) . 'i';
    foreach ($siteIds as $siteId) {
        $params[] = $siteId;
    }
    $params[] = $userId;
}

if ($statusFilter !== '' && in_array($statusFilter, overtime_valid_statuses(), true)) {
    $where[] = 'ot.Status = ?';
    $types .= 's';
    $params[] = $statusFilter;
}

if ($typeFilter !== '' && in_array($typeFilter, overtime_valid_types(), true)) {
    $where[] = 'ot.OvertimeType = ?';
    $types .= 's';
    $params[] = $typeFilter;
}

if ($siteFilter > 0) {
    $where[] = 'ot.SiteID = ?';
    $types .= 'i';
    $params[] = $siteFilter;
}

if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
    $where[] = 'ot.RequestDate >= ?';
    $types .= 's';
    $params[] = $dateFrom;
}

if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
    $where[] = 'ot.RequestDate <= ?';
    $types .= 's';
    $params[] = $dateTo;
}

if ($search !== '') {
    $where[] = "(w.First_Name LIKE ? OR w.Last_Name LIKE ? OR ps.Site_Name LIKE ? OR ot.OvertimeType LIKE ?)";
    $types .= 'ssss';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
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
    ORDER BY ot.OvertimeID DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to load overtime requests']);
    exit;
}

if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $row['lunch_schedule_label'] = get_overtime_lunch_schedule_label($conn, (int) ($row['SiteID'] ?? 0));
    $formatted = format_overtime_request_row($row);
    $canReview = in_array($currentRole, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR'], true);
    $formatted['can_approve'] = $canReview && $formatted['status'] === 'Pending';
    $formatted['can_reject'] = $canReview && $formatted['status'] === 'Pending';
    $items[] = $formatted;
}
$stmt->close();

$counts = ['all' => count($items), 'pending' => 0, 'approved' => 0, 'rejected' => 0];
foreach ($items as $item) {
    $key = strtolower((string) ($item['status'] ?? ''));
    if (isset($counts[$key])) {
        $counts[$key]++;
    }
}

echo json_encode([
    'success' => true,
    'items' => $items,
    'counts' => $counts,
    'role' => $currentRole,
]);

$conn->close();
