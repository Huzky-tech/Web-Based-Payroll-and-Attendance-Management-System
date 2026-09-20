<?php
if (!isset($conn) || !($conn instanceof mysqli)) {
    include __DIR__ . '/../api/connection/db_config.php';
}

include __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../api/payroll_deduction_helpers.php';
require_once __DIR__ . '/../api/payroll_approval_helpers.php';

$currentRole = require_auth($conn, ['Assistant Admin', 'Payroll Staff']);
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);

function build_pay_period(DateTime $start, DateTime $end): array {
    return [
        'start' => $start->format('Y-m-d'),
        'end' => $end->format('Y-m-d'),
        'label' => $start->format('M d') . ' - ' . $end->format('M d, Y')
    ];
}

function derive_pay_period(string $payPeriods): array {
    $today = new DateTime('today');
    $normalized = strtolower($payPeriods);

    if (strpos($normalized, 'weekly') !== false || trim($normalized) === 'week') {
        $year = (int) $today->format('o');
        $week = (int) $today->format('W');
        $start = new DateTime();
        $start->setISODate($year, $week, 1);
        $end = new DateTime();
        $end->setISODate($year, $week, 6);
    } elseif (strpos($normalized, 'semi') !== false) {
        if ((int) $today->format('d') <= 15) {
            $start = new DateTime($today->format('Y-m-01'));
            $end = new DateTime($today->format('Y-m-15'));
        } else {
            $start = new DateTime($today->format('Y-m-16'));
            $end = new DateTime($today->format('Y-m-t'));
        }
    } else {
        $start = new DateTime($today->format('Y-m-01'));
        $end = new DateTime($today->format('Y-m-t'));
    }

    return build_pay_period($start, $end);
}

function payroll_effective_start(string $periodStart, ?string $siteStart): string {
    $siteStart = trim((string) $siteStart);
    return ($siteStart !== '' && $siteStart > $periodStart) ? $siteStart : $periodStart;
}

function payroll_period_label(string $periodStart, string $periodEnd): string {
    return date('M d', strtotime($periodStart)) . ' - ' . date('M d, Y', strtotime($periodEnd));
}

function get_current_payroll_staff_id(mysqli $conn, int $userId): int {
    $stmt = $conn->prepare("SELECT PayrollStaff_ID FROM payrollstaff WHERE UserID = ? LIMIT 1");
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return (int) ($row['PayrollStaff_ID'] ?? 0);
}

$settingsResult = $conn->query("SELECT * FROM payroll_settings WHERE id = 1 LIMIT 1");
$payrollSettings = $settingsResult ? $settingsResult->fetch_assoc() : null;
$payrollSettings = $payrollSettings ?: [
    'pay_periods' => 'Semi-monthly (1-15, 16-end)',
    'sss_rate' => 0,
    'philhealth_rate' => 3,
    'pagibig_rate' => 2,
    'overtime_rate' => 1.25
];

$payPeriod = derive_pay_period(
    (string) $payrollSettings['pay_periods']
);
$overtimeRate = (float) ($payrollSettings['overtime_rate'] ?? 1.25);
$hasGovernmentDeductionColumn = worker_government_deduction_column_exists($conn);
$governmentDeductionSelect = $hasGovernmentDeductionColumn
    ? "COALESCE(w.GovernmentDeductionStatus, 'With Deductions') AS GovernmentDeductionStatus"
    : "'With Deductions' AS GovernmentDeductionStatus";
$governmentDeductionGroupBy = $hasGovernmentDeductionColumn ? ', w.GovernmentDeductionStatus' : '';
$validPayrollRecordClause = payroll_approval_columns_ready($conn)
    ? " AND COALESCE(pr.worker_count, 0) > 0"
    : "";

$recordsMap = [];
$recordSql = $conn->prepare("
    SELECT pr.SiteID, pr.Period_start, pr.Period_end, pr.Payroll_RecordsID, pr.Status
    FROM payroll_records pr
    INNER JOIN payroll p
        ON p.PayrollID = pr.PayrollID
        AND p.Pay_Period_Start = pr.Period_start
        AND p.Pay_Period_End = pr.Period_end
    WHERE pr.Period_end = ?{$validPayrollRecordClause}
");
$recordSql->bind_param("s", $payPeriod['end']);
$recordSql->execute();
$recordResult = $recordSql->get_result();
while ($row = $recordResult->fetch_assoc()) {
    $recordKey = (int) $row['SiteID'] . '|' . $row['Period_start'] . '|' . $row['Period_end'];
    $currentRecord = $recordsMap[$recordKey] ?? null;
    if (!$currentRecord
        || (strcasecmp((string) ($currentRecord['Status'] ?? ''), 'Rejected') === 0
            && strcasecmp((string) ($row['Status'] ?? ''), 'Rejected') !== 0)) {
        $recordsMap[$recordKey] = $row;
    }
}
$recordSql->close();

$siteQuerySql = "
    SELECT 
        ps.SiteID,
        ps.Site_Name,
        ps.Location,
        ps.Start_Date,
        ps.Status,
        COUNT(DISTINCT wa.WorkerID) AS assigned_workers
    FROM projectsite ps
    LEFT JOIN workerassignment wa ON wa.SiteID = ps.SiteID
";
$siteQueryWhere = "";
$siteQueryTypes = "";
$siteQueryParams = [];

if ($currentRole === 'Payroll Staff') {
    $currentPayrollStaffId = get_current_payroll_staff_id($conn, $currentUserId);
    $siteQuerySql .= " INNER JOIN payrollstaffassignment psa ON psa.SiteID = ps.SiteID ";
    $siteQueryWhere = " WHERE psa.PayrollStaff_ID = ? ";
    $siteQueryTypes = "i";
    $siteQueryParams[] = $currentPayrollStaffId;
}

$siteQuerySql .= $siteQueryWhere . "
    GROUP BY ps.SiteID, ps.Site_Name, ps.Location, ps.Start_Date, ps.Status
    ORDER BY ps.Site_Name
";

$sitesQuery = $conn->prepare($siteQuerySql);
if ($siteQueryTypes !== '' && $sitesQuery) {
    $sitesQuery->bind_param($siteQueryTypes, ...$siteQueryParams);
}
if ($sitesQuery) {
    $sitesQuery->execute();
    $sitesQuery = $sitesQuery->get_result();
}

$sitePayrollCards = [];
$summaryTotals = [
    'sites' => 0,
    'workers' => 0,
    'gross' => 0,
    'net' => 0,
    'processed' => 0
];

$workerStmt = $conn->prepare("
    SELECT 
        w.WorkerID,
        w.First_Name,
        w.Last_Name,
        w.RateType,
        w.RateAmount,
        {$governmentDeductionSelect},
        COALESCE(SUM(CASE
            WHEN a.Time_In IS NOT NULL AND a.Time_In <> '' AND a.Time_In <> '00:00:00'
             AND a.Time_Out IS NOT NULL AND a.Time_Out <> '' AND a.Time_Out <> '00:00:00'
            THEN a.Hours_Worked ELSE 0
        END), 0) AS total_hours,
        COALESCE(SUM(CASE
            WHEN a.Time_In IS NOT NULL AND a.Time_In <> '' AND a.Time_In <> '00:00:00'
             AND a.Time_Out IS NOT NULL AND a.Time_Out <> '' AND a.Time_Out <> '00:00:00'
            THEN a.Overtime_Hours ELSE 0
        END), 0) AS overtime_hours,
        SUM(CASE
            WHEN a.AttendanceStatus = 'Present'
             AND a.Time_In IS NOT NULL AND a.Time_In <> '' AND a.Time_In <> '00:00:00'
             AND a.Time_Out IS NOT NULL AND a.Time_Out <> '' AND a.Time_Out <> '00:00:00'
            THEN 1 ELSE 0
        END) AS present_days,
        SUM(CASE
            WHEN a.AttendanceStatus = 'Late'
             AND a.Time_In IS NOT NULL AND a.Time_In <> '' AND a.Time_In <> '00:00:00'
             AND a.Time_Out IS NOT NULL AND a.Time_Out <> '' AND a.Time_Out <> '00:00:00'
            THEN 1 ELSE 0
        END) AS late_days
    FROM workerassignment wa
    INNER JOIN worker w ON wa.WorkerID = w.WorkerID
    LEFT JOIN attendance a
        ON a.WorkerID = w.WorkerID
        AND a.SiteID = wa.SiteID
        AND a.Date BETWEEN ? AND ?
    WHERE wa.SiteID = ?
    GROUP BY w.WorkerID, w.First_Name, w.Last_Name, w.RateType, w.RateAmount{$governmentDeductionGroupBy}
    ORDER BY w.Last_Name, w.First_Name
");

$attendanceStmt = $conn->prepare("
    SELECT Date, AttendanceStatus
    FROM attendance
    WHERE WorkerID = ? AND SiteID = ? AND Date BETWEEN ? AND ?
    ORDER BY Date ASC
");

while ($site = $sitesQuery->fetch_assoc()) {
    $siteId = (int) $site['SiteID'];
    $siteWorkers = [];
    $siteGross = 0;
    $siteNet = 0;
    $siteDeductions = 0;

    $sitePeriodStart = payroll_effective_start($payPeriod['start'], $site['Start_Date'] ?? null);
    $sitePeriodEnd = $payPeriod['end'];
    $sitePeriodLabel = payroll_period_label($sitePeriodStart, $sitePeriodEnd);

    $workerStmt->bind_param("ssi", $sitePeriodStart, $sitePeriodEnd, $siteId);
    $workerStmt->execute();
    $workerResult = $workerStmt->get_result();

    while ($worker = $workerResult->fetch_assoc()) {
        $rateType = strtolower((string) ($worker['RateType'] ?? 'hourly'));
        $rateAmount = (float) ($worker['RateAmount'] ?? 0);
        $totalHours = (float) ($worker['total_hours'] ?? 0);
        $overtimeHours = (float) ($worker['overtime_hours'] ?? 0);
        $regularHours = max(0, $totalHours - $overtimeHours);

        if ($rateType === 'salary') {
            $grossPay = $rateAmount;
        } else {
            $grossPay = ($regularHours * $rateAmount) + ($overtimeHours * $rateAmount * $overtimeRate);
        }

        $deductionBreakdown = compute_worker_payroll_deductions(
            round($grossPay, 2),
            (string) ($worker['GovernmentDeductionStatus'] ?? GOVERNMENT_DEDUCTION_WITH),
            $payrollSettings
        );
        $deductions = $deductionBreakdown['total'];
        $netPay = round(round($grossPay, 2) - $deductions, 2);
        $attendanceTrail = [];

        $attendanceStmt->bind_param("iiss", $worker['WorkerID'], $siteId, $sitePeriodStart, $sitePeriodEnd);
        $attendanceStmt->execute();
        $attendanceRows = $attendanceStmt->get_result();
        while ($attendance = $attendanceRows->fetch_assoc()) {
            $attendanceTrail[] = [
                'date' => $attendance['Date'],
                'status' => $attendance['AttendanceStatus']
            ];
        }

        $siteWorkers[] = [
            'WorkerID' => (int) $worker['WorkerID'],
            'full_name' => trim(($worker['First_Name'] ?? '') . ' ' . ($worker['Last_Name'] ?? '')),
            'rate_type' => $worker['RateType'] ?: 'Hourly',
            'rate_amount' => round($rateAmount, 2),
            'present_days' => (int) ($worker['present_days'] ?? 0),
            'late_days' => (int) ($worker['late_days'] ?? 0),
            'attendance_days' => (int) (($worker['present_days'] ?? 0) + ($worker['late_days'] ?? 0)),
            'total_hours' => round($totalHours, 2),
            'overtime_hours' => round($overtimeHours, 2),
            'gross_pay' => round($grossPay, 2),
            'deductions' => $deductions,
            'net_pay' => $netPay,
            'government_deduction_status' => $deductionBreakdown['government_deduction_status'],
            'government_deduction_label' => $deductionBreakdown['government_deduction_label'],
            'sss_deduction' => $deductionBreakdown['sss'],
            'philhealth_deduction' => $deductionBreakdown['philhealth'],
            'pagibig_deduction' => $deductionBreakdown['pagibig'],
            'tax_deduction' => $deductionBreakdown['tax'],
            'attendance_trail' => $attendanceTrail,
        ];

        $siteGross += $grossPay;
        $siteNet += $netPay;
        $siteDeductions += $deductions;
    }

    $periodRecord = $recordsMap[$siteId . '|' . $sitePeriodStart . '|' . $sitePeriodEnd] ?? null;
    $processed = $periodRecord && strcasecmp((string) ($periodRecord['Status'] ?? ''), 'Rejected') !== 0;

    $sitePayrollCards[] = [
        'SiteID' => $siteId,
        'Site_Name' => $site['Site_Name'],
        'Location' => $site['Location'],
        'Status' => $site['Status'],
        'assigned_workers' => (int) ($site['assigned_workers'] ?? 0),
        'period_start' => $sitePeriodStart,
        'period_end' => $sitePeriodEnd,
        'period_label' => $sitePeriodLabel,
        'gross_pay' => round($siteGross, 2),
        'deductions' => round($siteDeductions, 2),
        'net_pay' => round($siteNet, 2),
        'processed' => $processed,
        'workers' => $siteWorkers
    ];

    $summaryTotals['sites']++;
    $summaryTotals['workers'] += count($siteWorkers);
    $summaryTotals['gross'] += $siteGross;
    $summaryTotals['net'] += $siteNet;
    $summaryTotals['processed'] += $processed ? 1 : 0;
}

$workerStmt->close();
$attendanceStmt->close();
?>
<div class="content-area">
    <script>
        window.payrollPageData = <?php echo json_encode([
            'period' => $payPeriod,
            'settings' => [
                'pay_periods' => $payrollSettings['pay_periods'],
                'sss_rate' => (float) ($payrollSettings['sss_rate'] ?? 0),
                'philhealth_rate' => (float) ($payrollSettings['philhealth_rate'] ?? 0),
                'pagibig_rate' => (float) ($payrollSettings['pagibig_rate'] ?? 0),
                'overtime_rate' => (float) ($payrollSettings['overtime_rate'] ?? 1.25)
            ],
            'summary' => [
                'sites' => (int) $summaryTotals['sites'],
                'workers' => (int) $summaryTotals['workers'],
                'gross' => round($summaryTotals['gross'], 2),
                'net' => round($summaryTotals['net'], 2),
                'processed' => (int) $summaryTotals['processed']
            ],
            'sites' => $sitePayrollCards,
            'scope' => [
                'role' => $currentRole,
                'restricted_to_assigned_sites' => $currentRole === 'Payroll Staff'
            ]
        ], ( JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>

    <div class="page-view active" id="sitesPage">
        <div class="page-header">
            <h1>Payroll Processing</h1>
            <p>Review site-based payroll for the current pay period and process it into payroll records.</p>
        </div>

        <div class="summary-cards">
            <div class="summary-card">
                <div class="summary-card-icon blue"><i class="fas fa-building"></i></div>
                <div class="summary-card-content">
                    <div class="summary-card-label">Sites This Period</div>
                    <div class="summary-card-value" id="payrollSitesCount"><?php echo (int) $summaryTotals['sites']; ?></div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-card-icon green"><i class="fas fa-users"></i></div>
                <div class="summary-card-content">
                    <div class="summary-card-label">Workers Included</div>
                    <div class="summary-card-value" id="payrollWorkersCount"><?php echo (int) $summaryTotals['workers']; ?></div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-card-icon red"><i class="fas fa-check-circle"></i></div>
                <div class="summary-card-content">
                    <div class="summary-card-label">Processed Sites</div>
                    <div class="summary-card-value" id="processedSitesCount"><?php echo (int) $summaryTotals['processed']; ?></div>
                </div>
            </div>
        </div>

        <div class="sites-container" id="payrollSitesContainer">
            <?php if (count($sitePayrollCards) === 0): ?>
            <div class="site-card">
                <div class="site-card-title">No sites available</div>
                <div class="site-info-item">Create site assignments to begin payroll processing.</div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="page-view" id="payrollDetailsPage">
        <div class="payroll-details-header">
            <button class="btn-back" id="btnBackToSites" type="button">
                <i class="fas fa-arrow-left"></i>
            </button>
            <div class="payroll-header-info">
                <h1 id="siteNameHeader">Site Payroll</h1>
                <p id="sitePeriodHeader"><?php echo htmlspecialchars($payPeriod['label']); ?></p>
            </div>
        </div>

        <div class="summary-cards">
            <div class="summary-card">
                <div class="summary-card-icon blue"><i class="fas fa-wallet"></i></div>
                <div class="summary-card-content">
                    <div class="summary-card-label">Gross Pay</div>
                    <div class="summary-card-value" id="grossPayValue">PHP 0.00</div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-card-icon red"><i class="fas fa-minus-circle"></i></div>
                <div class="summary-card-content">
                    <div class="summary-card-label">Deductions</div>
                    <div class="summary-card-value" id="deductionsValue">PHP 0.00</div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-card-icon green"><i class="fas fa-money-bill-wave"></i></div>
                <div class="summary-card-content">
                    <div class="summary-card-label">Net Pay</div>
                    <div class="summary-card-value" id="netPayValue">PHP 0.00</div>
                </div>
            </div>
        </div>

        <div class="payroll-table-container">
            <div class="section-title" id="workersSectionTitle">Workers Assigned</div>
            <table class="payroll-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Base Rate</th>
                        <th>Rate %</th>
                        <th>Attendance</th>
                        <th>OT Hrs</th>
                        <th>Gross Pay</th>
                        <th>Deduction Status</th>
                        <th>Deductions</th>
                        <th>Net Pay</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="payrollTableBody"></tbody>
            </table>
        </div>

        <div class="process-payroll-section">
            <div class="process-note" id="processPayrollText">Process payroll for selected site.</div>
            <button class="btn-process-payroll" id="btnProcessPayroll" type="button">
                <i class="fas fa-money-check-dollar"></i>
                <span>Process Payroll</span>
            </button>
        </div>

    </div>

    <div class="payroll-modal-overlay" id="payrollWorkerBreakdownModal" aria-hidden="true">
        <div class="payroll-modal" role="dialog" aria-modal="true" aria-labelledby="payrollWorkerBreakdownModalTitle">
            <div class="payroll-modal-header">
                <h2 class="payroll-modal-title" id="payrollWorkerBreakdownModalTitle">Worker Payroll Breakdown</h2>
                <button type="button" class="payroll-modal-close" id="payrollWorkerBreakdownCloseBtn" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="payroll-modal-body" id="payrollWorkerBreakdownBody"></div>
            <div class="payroll-modal-footer">
                <button type="button" class="payroll-modal-btn" id="payrollWorkerBreakdownCloseBtn2">Close</button>
            </div>
        </div>
    </div>
</div>
