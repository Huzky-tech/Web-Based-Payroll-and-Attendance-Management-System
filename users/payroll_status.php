<?php
if (!isset($conn) || !($conn instanceof mysqli)) {
    include __DIR__ . '/../api/connection/db_config.php';
}

include __DIR__ . '/../includes/auth.php';

$currentRole = require_auth($conn, ['Assistant Admin', 'Payroll Staff', 'HR']);
$isSubmissionUser = in_array($currentRole, ['Payroll Staff', 'HR'], true);
?>
<div class="content-area payroll-approval-page" data-payroll-approval-role="<?php echo $isSubmissionUser ? 'payroll' : 'assistant'; ?>" data-payroll-submission-mode="<?php echo $isSubmissionUser ? 'true' : 'false'; ?>">
    <div class="payroll-approval-header">
        <h1><?php echo $isSubmissionUser ? 'My Payroll Submissions' : 'Payroll Approval'; ?></h1>
        <p><?php echo $isSubmissionUser
            ? 'Track the approval status of payroll batches you have submitted.'
            : 'Review payroll submission statuses across all sites.'; ?></p>
    </div>

    <div class="payroll-approval-summary">
        <div class="payroll-approval-summary-card">
            <div class="payroll-approval-summary-icon pending"><i class="far fa-clock"></i></div>
            <div>
                <div class="payroll-approval-summary-label">Pending Approval</div>
                <div class="payroll-approval-summary-value pending" id="payrollApprovalPendingCount">0</div>
            </div>
        </div>
        <div class="payroll-approval-summary-card">
            <div class="payroll-approval-summary-icon approved"><i class="fas fa-check-circle"></i></div>
            <div>
                <div class="payroll-approval-summary-label">Approved</div>
                <div class="payroll-approval-summary-value approved" id="payrollApprovalApprovedCount">0</div>
            </div>
        </div>
        <div class="payroll-approval-summary-card">
            <div class="payroll-approval-summary-icon rejected"><i class="fas fa-times-circle"></i></div>
            <div>
                <div class="payroll-approval-summary-label">Rejected</div>
                <div class="payroll-approval-summary-value rejected" id="payrollApprovalRejectedCount">0</div>
            </div>
        </div>
    </div>

    <div class="payroll-approval-toolbar">
        <div class="payroll-approval-filters">
            <button type="button" class="payroll-approval-filter-btn active" data-status="all">All</button>
            <button type="button" class="payroll-approval-filter-btn" data-status="Pending">Pending</button>
            <button type="button" class="payroll-approval-filter-btn" data-status="Approved">Approved</button>
            <button type="button" class="payroll-approval-filter-btn" data-status="Rejected">Rejected</button>
        </div>
        <div class="payroll-approval-search">
            <i class="fas fa-search"></i>
            <input
                type="text"
                id="payrollApprovalSearch"
                placeholder="Search by site or period..."
                aria-label="Search payroll submissions"
            >
        </div>
    </div>

    <div class="payroll-approval-list" id="payrollApprovalList">
        <div class="payroll-approval-empty">Loading payroll submissions...</div>
    </div>
</div>

<div class="payroll-approval-modal-overlay" id="payrollApprovalDetailsModal" aria-hidden="true">
    <div class="payroll-approval-modal" role="dialog" aria-modal="true">
        <div class="payroll-approval-modal-header">
            <h2>Payroll Submission Details</h2>
        </div>
        <div class="payroll-approval-modal-body" id="payrollApprovalDetailsBody"></div>
        <div class="payroll-approval-modal-footer">
            <button type="button" class="payroll-approval-btn reject" id="correctRejectedPayrollBtn" hidden>
                <i class="fas fa-pen"></i> Correct Payroll
            </button>
            <button type="button" class="payroll-approval-btn export" id="exportPayrollExcelBtn" hidden>
                <i class="fas fa-file-excel"></i> Download Payroll Excel
            </button>
            <button type="button" class="payroll-approval-btn view" id="closePayrollApprovalDetailsBtn">Close</button>
        </div>
    </div>
</div>

<div class="payroll-approval-modal-overlay" id="payrollRejectModal" aria-hidden="true">
    <div class="payroll-approval-modal" role="dialog" aria-modal="true" aria-labelledby="payrollRejectModalTitle">
        <div class="payroll-approval-modal-header">
            <h2 id="payrollRejectModalTitle">Reject Payroll Submission</h2>
        </div>
        <div class="payroll-approval-modal-body">
            <p>Please provide a reason for rejecting this payroll submission.</p>
            <textarea id="payrollRejectReason" placeholder="Enter rejection reason..." aria-label="Rejection reason"></textarea>
        </div>
        <div class="payroll-approval-modal-footer">
            <button type="button" class="payroll-approval-btn reject" id="cancelPayrollRejectBtn">Cancel</button>
            <button type="button" class="payroll-approval-btn approve" id="confirmPayrollRejectBtn">Reject Payroll</button>
        </div>
    </div>
</div>
