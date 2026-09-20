<?php
$embeddedReport = $embeddedReport ?? false;

if (!$embeddedReport) {
    include '../api/connection/db_config.php';
    include '../includes/auth.php';

    require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR']);
}

require_once __DIR__ . '/../includes/report_scope_helpers.php';

function leave_report_h($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$leaveRows = [];
$leaveSummary = [
    'on_leave' => 0,
    'sites' => 0,
    'active_workers' => 0,
    'inactive_workers' => 0,
];

$summaryTypes = '';
$summaryParams = [];
$summaryScope = report_scope_condition($conn, 'wa.SiteID', $summaryTypes, $summaryParams);
$summaryWhere = $summaryScope !== '' ? "WHERE {$summaryScope}" : '';

$summaryResult = report_query($conn, "
    SELECT ws.Status, COUNT(DISTINCT w.WorkerID) AS workers_count
    FROM worker w
    INNER JOIN workerstatus ws ON ws.WorkerStatusID = w.WorkerStatusID
    LEFT JOIN workerassignment wa ON wa.WorkerID = w.WorkerID
    {$summaryWhere}
    GROUP BY ws.Status
", $summaryTypes, $summaryParams);
if ($summaryResult) {
    while ($row = $summaryResult->fetch_assoc()) {
        $status = strtolower((string) ($row['Status'] ?? ''));
        if ($status === 'onleave' || $status === 'on leave') {
            $leaveSummary['on_leave'] = (int) ($row['workers_count'] ?? 0);
        } elseif ($status === 'active') {
            $leaveSummary['active_workers'] = (int) ($row['workers_count'] ?? 0);
        } elseif ($status === 'inactive') {
            $leaveSummary['inactive_workers'] = (int) ($row['workers_count'] ?? 0);
        }
    }
}

$leaveTypes = '';
$leaveParams = [];
$leaveScope = report_scope_condition($conn, 'wa.SiteID', $leaveTypes, $leaveParams);
$leaveWhereParts = ["ws.Status = 'OnLeave'"];
if ($leaveScope !== '') {
    $leaveWhereParts[] = $leaveScope;
}
$leaveWhere = implode(' AND ', $leaveWhereParts);

$leaveSql = "
    SELECT
        w.WorkerID,
        CONCAT(w.First_Name, ' ', w.Last_Name) AS worker_name,
        COALESCE(ps.Site_Name, 'Unassigned') AS site_name,
        COALESCE(wa.Role_On_Site, '') AS role_on_site,
        w.DateHired,
        ws.Status
    FROM worker w
    INNER JOIN workerstatus ws ON ws.WorkerStatusID = w.WorkerStatusID
    LEFT JOIN workerassignment wa ON wa.WorkerID = w.WorkerID
    LEFT JOIN projectsite ps ON ps.SiteID = wa.SiteID
    WHERE {$leaveWhere}
    ORDER BY ps.Site_Name ASC, w.Last_Name ASC, w.First_Name ASC
";

$leaveResult = report_query($conn, $leaveSql, $leaveTypes, $leaveParams);
$siteNames = [];
if ($leaveResult) {
    while ($row = $leaveResult->fetch_assoc()) {
        $leaveRows[] = $row;
        $siteNames[(string) ($row['site_name'] ?? 'Unassigned')] = true;
    }
}
$leaveSummary['sites'] = count($siteNames);
?>
<?php if (!$embeddedReport): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Report - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/reports.css">
    <script src="../js/action_result_modal.js?v=20260912-1" defer></script>
</head>
<body>
<div class="main-content">
    <div class="content-area">
<?php endif; ?>

<div class="report-panel">
    <h2 class="summary-header">Leave Report</h2>
    <div class="summary-cards">
        <div class="summary-card yellow">
            <div class="summary-card-label">Workers on Leave</div>
            <div class="summary-card-value"><?php echo leave_report_h($leaveSummary['on_leave']); ?></div>
        </div>
        <div class="summary-card green">
            <div class="summary-card-label">Active Workers</div>
            <div class="summary-card-value"><?php echo leave_report_h($leaveSummary['active_workers']); ?></div>
        </div>
        <div class="summary-card pink">
            <div class="summary-card-label">Affected Sites</div>
            <div class="summary-card-value"><?php echo leave_report_h($leaveSummary['sites']); ?></div>
        </div>
        <div class="summary-card purple">
            <div class="summary-card-label">Inactive Workers</div>
            <div class="summary-card-value"><?php echo leave_report_h($leaveSummary['inactive_workers']); ?></div>
        </div>
    </div>

    <div class="table-section">
        <h3 class="table-title">Workers Currently on Leave</h3>
        <div class="table-container">
            <table class="payroll-table">
                <thead>
                    <tr>
                        <th>Worker</th>
                        <th>Site</th>
                        <th>Role</th>
                        <th>Date Hired</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$leaveRows): ?>
                    <tr><td colspan="5" style="text-align:center;">No workers are currently marked as on leave.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($leaveRows as $row): ?>
                    <tr>
                        <td class="department-name"><?php echo leave_report_h($row['worker_name']); ?></td>
                        <td><?php echo leave_report_h($row['site_name']); ?></td>
                        <td><?php echo leave_report_h($row['role_on_site'] ?: 'Not set'); ?></td>
                        <td><?php echo leave_report_h($row['DateHired']); ?></td>
                        <td><?php echo leave_report_h($row['Status']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!$embeddedReport): ?>
    </div>
</div>
</body>
</html>
<?php endif; ?>
