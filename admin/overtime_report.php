<?php
$embeddedDashboard = $embeddedDashboard ?? false;
$overtimeReportInnerOnly = $overtimeReportInnerOnly ?? false;

if (!$overtimeReportInnerOnly) {
    include '../api/connection/db_config.php';
    include '../includes/auth.php';

    require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff']);
}
?>
<?php if (!$embeddedDashboard && !$overtimeReportInnerOnly): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overtime Report - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/overtime_requests.css?v=20260905-1">
</head>
<body>
<?php endif; ?>

<?php if (!$overtimeReportInnerOnly): ?>
<div class="content-area overtime-page">
<?php else: ?>
<div class="overtime-page">
<?php endif; ?>
    <div class="overtime-header">
        <div>
            <h1>Overtime Report</h1>
            <p>Review overtime hours by worker, site, type, date, and status.</p>
        </div>
    </div>

    <div class="overtime-filters report-filter-bar">
        <label class="report-filter-field" for="overtimeReportDate">
            <span>Date</span>
            <input type="date" id="overtimeReportDate" aria-label="Overtime date">
        </label>
        <label class="report-filter-field" for="overtimeReportSite">
            <span>Site</span>
            <select id="overtimeReportSite" aria-label="Overtime site">
                <option value="">All Sites</option>
            </select>
        </label>
        <label class="report-filter-field report-search-field" for="overtimeReportSearch">
            <span>Search</span>
            <span class="report-search-control">
                <i class="fa-solid fa-search" aria-hidden="true"></i>
                <input type="search" id="overtimeReportSearch" placeholder="Search records" aria-label="Search overtime report">
            </span>
        </label>
        <label class="report-filter-field" for="overtimeReportStatus">
            <span>Status</span>
            <select id="overtimeReportStatus" aria-label="Overtime status">
                <option value="">All Statuses</option>
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
                <option value="Rejected">Rejected</option>
            </select>
        </label>
    </div>

    <div class="overtime-table-wrap">
        <table class="overtime-table">
            <thead>
                <tr>
                    <th>Worker</th>
                    <th>Site</th>
                    <th>Overtime Type</th>
                    <th>Hours</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="overtimeReportBody">
                <tr><td colspan="6" class="overtime-empty">Loading overtime records...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="report-pagination" id="overtimeReportPagination" aria-label="Overtime table pagination"></div>
</div>

    <script src="../js/action_result_modal.js?v=20260920-access-1" defer></script>
<?php if (!$embeddedDashboard && !$overtimeReportInnerOnly): ?>
<script src="../js/overtime_report.js?v=20260905-1" defer></script>
</body>
</html>
<?php endif; ?>
