<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'Timekeeper']);
$userId = (int) ($_SESSION['user_id'] ?? 0);

function payroll_summary_json_error(string $message, int $statusCode = 400): void
{
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function payroll_summary_resolve_range(string $range, ?string $customStart = null, ?string $customEnd = null): array
{
    $today = new DateTime('today');

    switch ($range) {
        case 'last-month':
            $start = new DateTime('first day of last month');
            $end = new DateTime('last day of last month');
            $label = 'Last Month (' . $start->format('F Y') . ')';
            break;
        case 'last-3-months':
            $end = clone $today;
            $start = (clone $today)->modify('-2 months')->modify('first day of this month');
            $label = 'Last 3 Months (' . $start->format('M Y') . ' - ' . $end->format('M Y') . ')';
            break;
        case 'last-6-months':
            $end = clone $today;
            $start = (clone $today)->modify('-5 months')->modify('first day of this month');
            $label = 'Last 6 Months (' . $start->format('M Y') . ' - ' . $end->format('M Y') . ')';
            break;
        case 'this-year':
            $start = new DateTime($today->format('Y-01-01'));
            $end = clone $today;
            $label = 'This Year (' . $today->format('Y') . ')';
            break;
        case 'custom':
            if (!$customStart || !$customEnd) {
                payroll_summary_json_error('Custom range requires start and end dates.');
            }
            $start = DateTime::createFromFormat('Y-m-d', $customStart);
            $end = DateTime::createFromFormat('Y-m-d', $customEnd);
            if (!$start || !$end || $start > $end) {
                payroll_summary_json_error('Invalid custom date range.');
            }
            $label = 'Custom Range (' . $start->format('M j, Y') . ' - ' . $end->format('M j, Y') . ')';
            break;
        case 'current-month':
        default:
            $start = new DateTime('first day of this month');
            $end = new DateTime('last day of this month');
            $label = 'Current Month (' . $start->format('F Y') . ')';
            break;
    }

    return [
        'start' => $start->format('Y-m-d'),
        'end' => $end->format('Y-m-d'),
        'label' => $label,
    ];
}

function payroll_summary_get_scoped_site_ids(mysqli $conn, string $role, int $userId): ?array
{
    if (in_array($role, ['Admin', 'Assistant Admin'], true)) {
        return null;
    }

    return null;
}

$range = trim((string) ($_GET['range'] ?? 'current-month'));
$singleDate = trim((string) ($_GET['date'] ?? ''));
$siteFilter = trim((string) ($_GET['site'] ?? ''));
$searchFilter = trim((string) ($_GET['search'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? ''));
$customStart = trim((string) ($_GET['start'] ?? ''));
$customEnd = trim((string) ($_GET['end'] ?? ''));
if ($singleDate !== '') {
    $dateObj = DateTime::createFromFormat('Y-m-d', $singleDate);
    if (!$dateObj) {
        payroll_summary_json_error('Invalid payroll date.');
    }
    $period = [
        'start' => $dateObj->format('Y-m-d'),
        'end' => $dateObj->format('Y-m-d'),
        'label' => 'Payroll Date (' . $dateObj->format('M j, Y') . ')',
    ];
} else {
    $period = payroll_summary_resolve_range($range, $customStart ?: null, $customEnd ?: null);
}

$siteIds = payroll_summary_get_scoped_site_ids($conn, $currentRole, $userId);
$scopeSql = '';
$scopeTypes = '';
$scopeParams = [];
$filterSql = '';
$filterTypes = '';
$filterParams = [];

if ($siteIds !== null) {
    if (empty($siteIds)) {
        echo json_encode([
            'success' => true,
            'range' => $range,
            'period' => $period,
            'summary' => [
                'total_gross_pay' => 0,
                'total_deductions' => 0,
                'total_net_pay' => 0,
                'employee_count' => 0,
                'site_count' => 0,
                'batch_count' => 0,
            ],
            'sites' => [],
        ]);
        $conn->close();
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($siteIds), '?'));
    $scopeSql = " AND pr.SiteID IN ({$placeholders})";
    $scopeTypes = str_repeat('i', count($siteIds));
    $scopeParams = $siteIds;
}

if ($siteFilter !== '') {
    $filterSql .= ' AND ps.Site_Name = ?';
    $filterTypes .= 's';
    $filterParams[] = $siteFilter;
}

if ($searchFilter !== '') {
    $filterSql .= ' AND (ps.Site_Name LIKE ? OR pr.Status LIKE ?)';
    $filterTypes .= 'ss';
    $searchLike = '%' . $searchFilter . '%';
    $filterParams[] = $searchLike;
    $filterParams[] = $searchLike;
}

if ($statusFilter !== '' && in_array($statusFilter, ['Pending', 'Approved', 'Rejected'], true)) {
    $filterSql .= ' AND pr.Status = ?';
    $filterTypes .= 's';
    $filterParams[] = $statusFilter;
}

$sql = "
    SELECT
        ps.SiteID,
        ps.Site_Name,
        pr.Status,
        COALESCE(SUM(pr.worker_count), 0) AS employees,
        COALESCE(SUM(pr.Total_gross_pay), 0) AS gross_pay,
        COALESCE(SUM(pr.Total_deductions), 0) AS deductions,
        COALESCE(SUM(pr.Total_net_pay), 0) AS net_pay,
        COUNT(pr.Payroll_RecordsID) AS batch_count
    FROM payroll_records pr
    INNER JOIN projectsite ps ON ps.SiteID = pr.SiteID
    WHERE pr.Period_end >= ?
      AND pr.Period_start <= ?
      {$scopeSql}
      {$filterSql}
    GROUP BY ps.SiteID, ps.Site_Name, pr.Status
    ORDER BY ps.Site_Name ASC, pr.Status ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    payroll_summary_json_error('Failed to prepare payroll summary query.', 500);
}

$types = 'ss' . $scopeTypes . $filterTypes;
$params = array_merge([$period['start'], $period['end']], $scopeParams, $filterParams);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$sites = [];
$summary = [
    'total_gross_pay' => 0.0,
    'total_deductions' => 0.0,
    'total_net_pay' => 0.0,
    'employee_count' => 0,
    'site_count' => 0,
    'batch_count' => 0,
];

while ($row = $result->fetch_assoc()) {
    $siteRow = [
        'site_id' => (int) ($row['SiteID'] ?? 0),
        'name' => (string) ($row['Site_Name'] ?? ''),
        'status' => (string) ($row['Status'] ?? ''),
        'employees' => (int) ($row['employees'] ?? 0),
        'gross_pay' => round((float) ($row['gross_pay'] ?? 0), 2),
        'deductions' => round((float) ($row['deductions'] ?? 0), 2),
        'net_pay' => round((float) ($row['net_pay'] ?? 0), 2),
        'batch_count' => (int) ($row['batch_count'] ?? 0),
    ];

    $sites[] = $siteRow;
    $summary['total_gross_pay'] += $siteRow['gross_pay'];
    $summary['total_deductions'] += $siteRow['deductions'];
    $summary['total_net_pay'] += $siteRow['net_pay'];
    $summary['employee_count'] += $siteRow['employees'];
    $summary['batch_count'] += $siteRow['batch_count'];
}

$stmt->close();
$summary['site_count'] = count($sites);
$summary['total_gross_pay'] = round($summary['total_gross_pay'], 2);
$summary['total_deductions'] = round($summary['total_deductions'], 2);
$summary['total_net_pay'] = round($summary['total_net_pay'], 2);

$workers = [];
$workerSql = "
    SELECT DISTINCT
        pr.SiteID,
        p.PayrollID,
        p.WorkerID,
        TRIM(CONCAT(w.First_Name, ' ', w.Last_Name)) AS worker_name,
        p.Pay_Period_Start AS period_start,
        p.Pay_Period_End AS period_end,
        p.Gross_Pay AS gross_pay,
        p.Total_Deductions AS deductions,
        p.Net_Pay AS net_pay,
        pr.Status
    FROM payroll_records pr
    INNER JOIN projectsite ps ON ps.SiteID = pr.SiteID
    INNER JOIN payroll p
        ON p.Pay_Period_Start = pr.Period_start
        AND p.Pay_Period_End = pr.Period_end
    INNER JOIN worker w ON w.WorkerID = p.WorkerID
    WHERE pr.Period_end >= ? AND pr.Period_start <= ?
      AND (
          EXISTS (
              SELECT 1 FROM attendance a
              WHERE a.WorkerID = p.WorkerID
                AND a.SiteID = pr.SiteID
                AND a.Date BETWEEN pr.Period_start AND pr.Period_end
          )
          OR EXISTS (
              SELECT 1 FROM workerassignment wa
              WHERE wa.WorkerID = p.WorkerID AND wa.SiteID = pr.SiteID
          )
          OR EXISTS (
              SELECT 1 FROM siteassignmenthistory sah
              WHERE sah.WorkerID = p.WorkerID
                AND sah.SiteID = pr.SiteID
                AND sah.StartDate <= pr.Period_end
                AND (sah.EndDate IS NULL OR sah.EndDate >= pr.Period_start)
          )
      )
      {$scopeSql} {$filterSql}
    ORDER BY worker_name, p.PayrollID DESC
";
$workerStmt = $conn->prepare($workerSql);
if ($workerStmt) {
    $workerStmt->bind_param($types, ...$params);
    $workerStmt->execute();
    $workerResult = $workerStmt->get_result();
    while ($workerRow = $workerResult->fetch_assoc()) {
        $workers[] = [
            'site_id' => (int) $workerRow['SiteID'],
            'payroll_id' => (int) $workerRow['PayrollID'],
            'worker_id' => (int) $workerRow['WorkerID'],
            'worker_name' => (string) $workerRow['worker_name'],
            'period_start' => (string) $workerRow['period_start'],
            'period_end' => (string) $workerRow['period_end'],
            'gross_pay' => (float) $workerRow['gross_pay'],
            'deductions' => (float) $workerRow['deductions'],
            'net_pay' => (float) $workerRow['net_pay'],
            'status' => (string) $workerRow['Status'],
        ];
    }
    $workerStmt->close();
}

$employeeStmt = $conn->prepare("
    SELECT COUNT(DISTINCT p.WorkerID) AS employee_count
    FROM payroll p
    INNER JOIN payroll_records pr
        ON pr.Period_start = p.Pay_Period_Start
        AND pr.Period_end = p.Pay_Period_End
    INNER JOIN projectsite ps ON ps.SiteID = pr.SiteID
    WHERE pr.Period_end >= ?
      AND pr.Period_start <= ?
      AND (
          EXISTS (SELECT 1 FROM attendance a WHERE a.WorkerID = p.WorkerID AND a.SiteID = pr.SiteID AND a.Date BETWEEN pr.Period_start AND pr.Period_end)
          OR EXISTS (SELECT 1 FROM workerassignment wa WHERE wa.WorkerID = p.WorkerID AND wa.SiteID = pr.SiteID)
          OR EXISTS (SELECT 1 FROM siteassignmenthistory sah WHERE sah.WorkerID = p.WorkerID AND sah.SiteID = pr.SiteID AND sah.StartDate <= pr.Period_end AND (sah.EndDate IS NULL OR sah.EndDate >= pr.Period_start))
      )
      {$scopeSql}
      {$filterSql}
");

if ($employeeStmt) {
    $employeeStmt->bind_param($types, ...$params);
    $employeeStmt->execute();
    $employeeResult = $employeeStmt->get_result();
    $employeeRow = $employeeResult ? $employeeResult->fetch_assoc() : null;
    $employeeStmt->close();

    if ($employeeRow) {
        $summary['employee_count'] = (int) ($employeeRow['employee_count'] ?? $summary['employee_count']);
    }
}

echo json_encode([
    'success' => true,
    'range' => $range,
    'period' => $period,
    'summary' => $summary,
    'sites' => $sites,
    'workers' => $workers,
]);

$conn->close();
