<?php
include '../api/connection/db_config.php';
include '../includes/auth.php';

require_auth($conn, ['Assistant Admin', 'Timekeeper', 'Payroll Staff', 'HR']);
$embeddedDashboard = $embeddedDashboard ?? false;
$currentRole = $_SESSION['role'] ?? 'Timekeeper';
$isPayrollView = in_array($currentRole, ['Assistant Admin', 'Payroll Staff', 'HR'], true);
$overtimeUiRole = match ($currentRole) {
    'Assistant Admin' => 'assistant',
    'Payroll Staff' => 'payroll',
    'HR' => 'hr',
    default => 'timekeeper',
};
?>
<?php if (!$embeddedDashboard): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overtime Requests - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/overtime_requests.css">
</head>
<body data-overtime-role="<?php echo htmlspecialchars($overtimeUiRole); ?>">
<?php endif; ?>

<div class="content-area overtime-page" data-overtime-role="<?php echo htmlspecialchars($overtimeUiRole); ?>">
    <div class="overtime-header">
        <div>
            <h1>Overtime Requests</h1>
            <p><?php echo $isPayrollView
                ? 'Review, approve, or reject overtime requests from all project sites.'
                : 'Submit and track overtime requests for workers on your assigned site.'; ?></p>
        </div>
        <?php if (!$isPayrollView): ?>
        <button type="button" class="overtime-primary-btn" id="openOvertimeRequestBtn">
            <i class="fa-solid fa-plus"></i>
            <span>New Overtime Request</span>
        </button>
        <?php endif; ?>
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
                    <?php if ($isPayrollView): ?><th>Submitted By</th><?php endif; ?>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="overtimeRequestsBody">
                <tr><td colspan="9" class="overtime-empty">Loading overtime requests...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php if (!$isPayrollView): ?>
<div class="overtime-modal-overlay" id="overtimeFormModal" aria-hidden="true">
    <div class="overtime-modal" role="dialog" aria-modal="true">
        <div class="overtime-modal-header">
            <h2>New Overtime Request</h2>
        </div>
        <form id="overtimeRequestForm">
            <div class="overtime-modal-body">
                <div class="overtime-form-group">
                    <label for="overtimeWorker">Worker</label>
                    <select id="overtimeWorker" required></select>
                </div>
                <div class="overtime-form-grid">
                    <div class="overtime-form-group">
                        <label for="overtimeSite">Site</label>
                        <input type="text" id="overtimeSite" readonly>
                        <input type="hidden" id="overtimeSiteId">
                    </div>
                    <div class="overtime-form-group">
                        <label for="overtimeDate">Date</label>
                        <input type="date" id="overtimeDate" required>
                    </div>
                    <div class="overtime-form-group">
                        <label for="overtimeType">Overtime Type</label>
                        <select id="overtimeType" required></select>
                    </div>
                    <div class="overtime-form-group">
                        <label>Total Hours</label>
                        <div class="overtime-total-hours" id="overtimeTotalHoursPreview">0.00</div>
                    </div>
                    <div class="overtime-form-group">
                        <label for="overtimeStart">Overtime Start</label>
                        <input type="time" id="overtimeStart" required>
                    </div>
                    <div class="overtime-form-group">
                        <label for="overtimeEnd">Overtime End</label>
                        <input type="time" id="overtimeEnd" required>
                    </div>
                </div>
                <div class="overtime-note" id="overtimeLunchNote" hidden>
                    Lunch break for this site: <strong id="overtimeLunchSchedule">-</strong>. Use Lunch Overtime when the worker rendered work during the scheduled lunch period.
                </div>
                <div class="overtime-form-group">
                    <label for="overtimeReason">Reason</label>
                    <textarea id="overtimeReason" rows="4" required placeholder="Describe why overtime was rendered"></textarea>
                </div>
            </div>
            <div class="overtime-modal-footer">
                <button type="button" class="overtime-action-btn view" id="cancelOvertimeFormBtn">Cancel</button>
                <button type="submit" class="overtime-primary-btn">Submit Request</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="overtime-modal-overlay" id="overtimeDetailsModal" aria-hidden="true">
    <div class="overtime-modal" role="dialog" aria-modal="true">
        <div class="overtime-modal-header">
            <h2 id="overtimeDetailsTitle">Overtime Request Details</h2>
        </div>
        <div class="overtime-modal-body" id="overtimeDetailsBody"></div>
        <div class="overtime-modal-footer">
            <button type="button" class="overtime-action-btn view" id="closeOvertimeDetailsBtn">Close</button>
            <?php if ($isPayrollView): ?>
            <button type="button" class="overtime-action-btn approve" id="updateOvertimeStatusBtn">Update Status</button>
            <?php endif; ?>
        </div>
    </div>
</div>

    <script src="../js/action_result_modal.js?v=20260920-access-1" defer></script>
<?php if (!$embeddedDashboard): ?>
<script src="../js/overtime_requests.js?v=20260907-3" defer></script>
</body>
</html>
<?php endif; ?>
