<?php
$embeddedReport = $embeddedReport ?? false;

if (!$embeddedReport) {
    include '../api/connection/db_config.php';
    include '../includes/auth.php';

    require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff']);
}

require_once __DIR__ . '/../includes/report_scope_helpers.php';

function deductions_report_h($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function deductions_report_money($value): string {
    return 'PHP ' . number_format((float) ($value ?? 0), 2);
}

$deductionRows = [];
$deductionSiteOptions = report_site_options($conn);
$deductionSummary = [
    'records' => 0,
    'gross' => 0.0,
    'deductions' => 0.0,
    'net' => 0.0,
];

$deductionTypes = '';
$deductionParams = [];
$deductionScope = report_scope_condition($conn, 'pr.SiteID', $deductionTypes, $deductionParams);
$deductionWhere = $deductionScope !== '' ? "WHERE {$deductionScope}" : '';

$deductionsSql = "
    SELECT
        pr.Payroll_RecordsID,
        pr.SiteID,
        ps.Site_Name,
        pr.Period_start,
        pr.Period_end,
        pr.Total_gross_pay,
        pr.Total_deductions,
        pr.Total_net_pay,
        pr.Status
    FROM payroll_records pr
    INNER JOIN projectsite ps ON ps.SiteID = pr.SiteID
    {$deductionWhere}
    ORDER BY pr.Period_end DESC, pr.Payroll_RecordsID DESC
    LIMIT 100
";

$deductionsResult = report_query($conn, $deductionsSql, $deductionTypes, $deductionParams);
if ($deductionsResult) {
    while ($row = $deductionsResult->fetch_assoc()) {
        $row['Total_gross_pay'] = (float) ($row['Total_gross_pay'] ?? 0);
        $row['Total_deductions'] = (float) ($row['Total_deductions'] ?? 0);
        $row['Total_net_pay'] = (float) ($row['Total_net_pay'] ?? 0);
        $row['deduction_rate'] = $row['Total_gross_pay'] > 0
            ? round(($row['Total_deductions'] / $row['Total_gross_pay']) * 100, 1)
            : 0;

        $deductionSummary['records']++;
        $deductionSummary['gross'] += $row['Total_gross_pay'];
        $deductionSummary['deductions'] += $row['Total_deductions'];
        $deductionSummary['net'] += $row['Total_net_pay'];
        $deductionRows[] = $row;
    }
}

$overallDeductionRate = $deductionSummary['gross'] > 0
    ? round(($deductionSummary['deductions'] / $deductionSummary['gross']) * 100, 1)
    : 0;

$deductionWorkerRows = [];
$deductionWorkerSql = "
    SELECT DISTINCT pr.SiteID, p.PayrollID, p.WorkerID,
        TRIM(CONCAT(w.First_Name, ' ', w.Last_Name)) AS worker_name,
        p.Pay_Period_Start, p.Pay_Period_End, p.Gross_Pay, p.Total_Deductions, p.Net_Pay
    FROM payroll_records pr
    INNER JOIN payroll p ON p.Pay_Period_Start = pr.Period_start AND p.Pay_Period_End = pr.Period_end
    INNER JOIN worker w ON w.WorkerID = p.WorkerID
    WHERE (
        EXISTS (SELECT 1 FROM attendance a WHERE a.WorkerID = p.WorkerID AND a.SiteID = pr.SiteID AND a.Date BETWEEN pr.Period_start AND pr.Period_end)
        OR EXISTS (SELECT 1 FROM workerassignment wa WHERE wa.WorkerID = p.WorkerID AND wa.SiteID = pr.SiteID)
        OR EXISTS (SELECT 1 FROM siteassignmenthistory sah WHERE sah.WorkerID = p.WorkerID AND sah.SiteID = pr.SiteID AND sah.StartDate <= pr.Period_end AND (sah.EndDate IS NULL OR sah.EndDate >= pr.Period_start))
    )
    " . ($deductionScope !== '' ? "AND {$deductionScope}" : '') . "
    ORDER BY p.Pay_Period_End DESC, worker_name
    LIMIT 500
";
$deductionWorkerResult = report_query($conn, $deductionWorkerSql, $deductionTypes, $deductionParams);
while ($deductionWorkerResult && $workerRow = $deductionWorkerResult->fetch_assoc()) {
    $deductionWorkerRows[] = $workerRow;
}
?>
<?php if (!$embeddedReport): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deductions Report - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/reports.css?v=20260905-1">
    <script src="../js/action_result_modal.js?v=20260920-access-1" defer></script>
</head>
<body>
<div class="main-content">
    <div class="content-area">
<?php endif; ?>

<div class="report-panel">
    <h2 class="summary-header">Deductions Report</h2>
    <div class="report-table-filters report-filter-bar report-inline-filter-card" aria-label="Deduction report filters">
        <label class="report-filter-field" for="deductionDateFilter">
            <span>Date</span>
            <input type="date" id="deductionDateFilter" aria-label="Deduction date">
        </label>
        <label class="report-filter-field" for="deductionSiteSelect">
            <span>Site</span>
            <select id="deductionSiteSelect" aria-label="Deduction site">
                <option value="">All Sites</option>
                <?php foreach ($deductionSiteOptions as $siteName): ?>
                <option value="<?php echo deductions_report_h($siteName); ?>"><?php echo deductions_report_h($siteName); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="report-filter-field report-search-field" for="deductionSearchFilter">
            <span>Search</span>
            <span class="report-search-control">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="deductionSearchFilter" placeholder="Search records">
            </span>
        </label>
        <label class="report-filter-field" for="deductionTypeFilter">
            <span>Type</span>
            <select id="deductionTypeFilter">
                <option value="">Deduction Type</option>
                <option value="with-deductions">With deductions</option>
                <option value="no-deductions">No deductions</option>
            </select>
        </label>
        <label class="report-filter-field" for="deductionStatusFilter">
            <span>Status</span>
            <select id="deductionStatusFilter">
                <option value="">All statuses</option>
                <option value="Approved">Approved</option>
                <option value="Pending">Pending</option>
                <option value="Rejected">Rejected</option>
            </select>
        </label>
    </div>
    <div class="summary-cards">
        <div class="summary-card yellow">
            <div class="summary-card-label">Gross Pay</div>
            <div class="summary-card-value"><?php echo deductions_report_h(deductions_report_money($deductionSummary['gross'])); ?></div>
        </div>
        <div class="summary-card pink">
            <div class="summary-card-label">Total Deductions</div>
            <div class="summary-card-value"><?php echo deductions_report_h(deductions_report_money($deductionSummary['deductions'])); ?></div>
        </div>
        <div class="summary-card green">
            <div class="summary-card-label">Net Pay</div>
            <div class="summary-card-value"><?php echo deductions_report_h(deductions_report_money($deductionSummary['net'])); ?></div>
        </div>
        <div class="summary-card purple">
            <div class="summary-card-label">Deduction Rate</div>
            <div class="summary-card-value"><?php echo deductions_report_h($overallDeductionRate); ?>%</div>
        </div>
    </div>

    <div class="table-section">
        <h3 class="table-title">Payroll Deductions by Site</h3>
        <div class="table-container">
            <table class="payroll-table">
                <thead>
                    <tr>
                        <th>Site</th>
                        <th>Period Start</th>
                        <th>Period End</th>
                        <th>Gross Pay</th>
                        <th>Deductions</th>
                        <th>Net Pay</th>
                        <th>Rate</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="deductionsReportBody">
                    <?php if (!$deductionRows): ?>
                    <tr><td colspan="8" style="text-align:center;">No deduction records found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($deductionRows as $row): ?>
                    <tr data-site-id="<?php echo (int) $row['SiteID']; ?>" data-deduction-amount="<?php echo deductions_report_h($row['Total_deductions']); ?>">
                        <td class="department-name"><?php echo deductions_report_h($row['Site_Name']); ?></td>
                        <td><?php echo deductions_report_h($row['Period_start']); ?></td>
                        <td><?php echo deductions_report_h($row['Period_end']); ?></td>
                        <td class="amount"><?php echo deductions_report_h(deductions_report_money($row['Total_gross_pay'])); ?></td>
                        <td class="amount"><?php echo deductions_report_h(deductions_report_money($row['Total_deductions'])); ?></td>
                        <td class="amount"><?php echo deductions_report_h(deductions_report_money($row['Total_net_pay'])); ?></td>
                        <td><?php echo deductions_report_h($row['deduction_rate']); ?>%</td>
                        <td><?php echo deductions_report_h($row['Status']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="report-pagination" id="deductionsReportPagination" aria-label="Deductions table pagination"></div>
    </div>

    <div class="reports-modal-overlay" id="deductionWorkersModal" aria-hidden="true">
        <div class="reports-modal" role="dialog" aria-modal="true" aria-labelledby="deductionWorkersModalTitle">
            <div class="reports-modal-header">
                <div>
                    <h3 id="deductionWorkersModalTitle">Worker Deduction Details</h3>
                    <p id="deductionWorkersModalSubtitle">Deduction records for the selected site</p>
                </div>
                <button type="button" class="reports-modal-close" data-close-report-modal aria-label="Close">&times;</button>
            </div>
            <div class="reports-modal-body">
                <div class="table-container">
                    <table class="payroll-table">
                        <thead><tr><th>Worker</th><th>Payroll Period</th><th>Gross Pay</th><th>Deductions</th><th>Net Pay</th></tr></thead>
                        <tbody id="deductionWorkerReportBody">
                    <?php foreach ($deductionWorkerRows as $workerRow): ?>
                    <tr data-site-id="<?php echo (int) $workerRow['SiteID']; ?>" hidden>
                        <td><?php echo deductions_report_h($workerRow['worker_name']); ?></td>
                        <td><?php echo deductions_report_h($workerRow['Pay_Period_Start'] . ' - ' . $workerRow['Pay_Period_End']); ?></td>
                        <td><?php echo deductions_report_h(deductions_report_money($workerRow['Gross_Pay'])); ?></td>
                        <td><?php echo deductions_report_h(deductions_report_money($workerRow['Total_Deductions'])); ?></td>
                        <td><?php echo deductions_report_h(deductions_report_money($workerRow['Net_Pay'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr data-selection-empty="true"><td colspan="5" class="reports-empty-row">Select a site to view worker deduction records.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="report-pagination" id="deductionWorkerReportPagination"></div>
            </div>
        </div>
    </div>
</div>

<?php if (!$embeddedReport): ?>
    </div>
</div>
</body>
</html>
<?php endif; ?>
