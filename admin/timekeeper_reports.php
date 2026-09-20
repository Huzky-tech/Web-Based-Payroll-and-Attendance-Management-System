<?php
include '../api/connection/db_config.php';
include '../includes/auth.php';

require_auth($conn, ['Admin']);
$embeddedDashboard = $embeddedDashboard ?? false;
?>
<?php if (!$embeddedDashboard): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timekeeper Reports - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/timekeeper_reports.css?v=20260904-2">
</head>
<body data-tk-reports-role="admin">
<?php endif; ?>

<div class="content-area tk-reports-page" data-tk-reports-role="admin">
    <div class="tk-reports-header">
        <div>
            <h1>Timekeeper Reports</h1>
            <p>Review operational reports submitted from project sites by timekeepers.</p>
        </div>
    </div>

    <div class="tk-reports-stats" id="tkReportsStats">
        <button type="button" class="tk-stat-card" data-stat-filter="all" aria-pressed="true">
            <span class="tk-stat-label">Total Reports</span>
            <strong class="tk-stat-value" data-stat="total">—</strong>
        </button>
        <button type="button" class="tk-stat-card pending" data-stat-filter="Pending" aria-pressed="false" hidden>
            <span class="tk-stat-label">Pending Reports</span>
            <strong class="tk-stat-value" data-stat="pending">—</strong>
        </button>
        <button type="button" class="tk-stat-card reviewed" data-stat-filter="Reviewed" aria-pressed="false">
            <span class="tk-stat-label">Reviewed Reports</span>
            <strong class="tk-stat-value" data-stat="reviewed">—</strong>
        </button>
        <button type="button" class="tk-stat-card resolved" data-stat-filter="Resolved" aria-pressed="false" hidden>
            <span class="tk-stat-label">Resolved Reports</span>
            <strong class="tk-stat-value" data-stat="resolved">—</strong>
        </button>
        <button type="button" class="tk-stat-card today" data-stat-filter="today" aria-pressed="false">
            <span class="tk-stat-label">Submitted Today</span>
            <strong class="tk-stat-value" data-stat="submitted_today">—</strong>
        </button>
    </div>

    <div class="tk-reports-filters">
        <input type="text" id="tkReportsSearchInput" placeholder="Search site, timekeeper, subject...">
        <select id="tkReportsSiteFilter" aria-label="Filter by site">
            <option value="">All Sites</option>
        </select>
        <select id="tkReportsTypeFilter">
            <option value="">All Report Types</option>
        </select>
    </div>

    <div class="tk-reports-table-wrap">
        <table class="tk-reports-table">
            <thead>
                <tr>
                    <th>Site Name</th>
                    <th>Timekeeper Name</th>
                    <th>Report Type</th>
                    <th>Subject</th>
                    <th>Date Submitted</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="tkReportsBody">
                <tr><td colspan="7" class="tk-reports-empty">Loading reports...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="tk-reports-modal-overlay" id="tkReportDetailsModal" aria-hidden="true">
    <div class="tk-reports-modal" role="dialog" aria-modal="true">
        <div class="tk-reports-modal-header">
            <h2 id="tkReportDetailsTitle">Report Details</h2>
            <button type="button" class="tk-reports-close-btn" id="closeTkReportDetailsBtn" aria-label="Close">&times;</button>
        </div>
        <div class="tk-reports-modal-body" id="tkReportDetailsBody"></div>
        <div class="tk-reports-modal-footer" id="tkReportDetailsFooter">
            <button type="button" class="tk-reports-btn secondary" id="cancelTkReportDetailsBtn">Close</button>
            <button type="button" class="tk-reports-btn primary" id="reviewTkReportBtn">Review</button>
        </div>
    </div>
</div>

    <script src="../js/action_result_modal.js?v=20260920-access-1" defer></script>
<?php if (!$embeddedDashboard): ?>
<script src="../js/timekeeper_reports.js?v=20260913-security-1" defer></script>
</body>
</html>
<?php endif; ?>
