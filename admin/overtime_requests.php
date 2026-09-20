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
    <title>Overtime Requests - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/overtime_requests.css?v=20260905-1">
</head>
<body data-overtime-role="admin">
<?php endif; ?>

<div class="content-area overtime-page" data-overtime-role="admin">
    <div class="overtime-header">
        <div>
            <h1>Overtime Requests</h1>
            <p>Review, approve, or reject timekeeper overtime submissions.</p>
        </div>
    </div>

    <div class="overtime-filters">
        <input type="text" id="overtimeSearchInput" placeholder="Search worker, site, or type...">
        <select id="overtimeTypeFilter">
            <option value="">All Types</option>
            <option value="Regular Overtime">Regular Overtime</option>
            <option value="Lunch Overtime">Lunch Overtime</option>
            <option value="Emergency Overtime">Emergency Overtime</option>
            <option value="Weekend Overtime">Weekend Overtime</option>
        </select>
        <select id="overtimeStatusFilter">
            <option value="">All Statuses</option>
            <option value="Pending">Pending</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
        </select>
        <select id="overtimeSiteFilter">
            <option value="">All Sites</option>
        </select>
        <input type="date" id="overtimeDateFilter" aria-label="Filter by request date" title="Filter by exact request date">
    </div>

    <div class="overtime-table-wrap">
        <table class="overtime-table">
            <thead>
                <tr>
                    <th>Worker</th>
                    <th>Site</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Hours</th>
                    <th>Submitted By</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="overtimeRequestsBody">
                <tr><td colspan="10" class="overtime-empty">Loading overtime requests...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="overtime-modal-overlay" id="overtimeDetailsModal" aria-hidden="true">
    <div class="overtime-modal" role="dialog" aria-modal="true">
        <div class="overtime-modal-header">
            <h2 id="overtimeDetailsTitle">Overtime Request Details</h2>
        </div>
        <div class="overtime-modal-body" id="overtimeDetailsBody"></div>
        <div class="overtime-modal-footer">
            <button type="button" class="overtime-action-btn view" id="closeOvertimeDetailsBtn">Close</button>
            <button type="button" class="overtime-action-btn approve" id="updateOvertimeStatusBtn">Update Status</button>
        </div>
    </div>
</div>

    <script src="../js/action_result_modal.js?v=20260920-access-1" defer></script>
<?php if (!$embeddedDashboard): ?>
<script src="../js/overtime_requests.js?v=20260907-3" defer></script>
</body>
</html>
<?php endif; ?>
