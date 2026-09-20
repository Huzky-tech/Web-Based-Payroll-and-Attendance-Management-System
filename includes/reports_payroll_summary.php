<?php
$todayValue = date('Y-m-d');
require_once __DIR__ . '/report_scope_helpers.php';
$payrollReportSites = isset($conn) && $conn instanceof mysqli ? report_site_options($conn) : [];
?>
<div class="payroll-summary-report" data-payroll-summary-report>
    <div class="payroll-summary" id="payrollSummary">
        <div class="report-heading-row report-summary-heading">
            <div class="report-table-filters report-filter-bar" aria-label="Payroll summary filters">
                <label class="report-filter-field" for="payrollReportDate">
                    <span>Date</span>
                    <input type="date" id="payrollReportDate" value="<?php echo htmlspecialchars($todayValue); ?>" aria-label="Payroll date">
                </label>
                <label class="report-filter-field" for="payrollSiteSelect">
                    <span>Site</span>
                    <select id="payrollSiteSelect" aria-label="Payroll site">
                        <option value="">All Sites</option>
                        <?php foreach ($payrollReportSites as $siteName): ?>
                        <option value="<?php echo htmlspecialchars($siteName); ?>"><?php echo htmlspecialchars($siteName); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="report-filter-field report-search-field" for="payrollSearchFilter">
                    <span>Search</span>
                    <span class="report-search-control">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <input type="search" id="payrollSearchFilter" placeholder="Search records">
                    </span>
                </label>
                <label class="report-filter-field" for="payrollStatusFilter">
                    <span>Status</span>
                    <select id="payrollStatusFilter" aria-label="Payroll status">
                        <option value="" selected>All statuses</option>
                        <option value="Approved">Approved</option>
                        <option value="Pending">Pending</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </label>
                <div class="report-filter-actions">
                    <button type="button" class="btn-export" id="btnExportPayrollSummary">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                </div>
            </div>
        </div>

        <div class="summary-cards">
            <div class="summary-card yellow">
                <div class="summary-card-label">Total Gross Pay</div>
                <div class="summary-card-value" id="totalGrossPay">—</div>
            </div>
            <div class="summary-card pink">
                <div class="summary-card-label">Total Deductions</div>
                <div class="summary-card-value" id="totalDeductions">—</div>
            </div>
            <div class="summary-card green">
                <div class="summary-card-label">Total Net Pay</div>
                <div class="summary-card-value" id="totalNetPay">—</div>
            </div>
            <div class="summary-card purple">
                <div class="summary-card-label">Employee Count</div>
                <div class="summary-card-value" id="employeeCount">—</div>
            </div>
        </div>
    </div>

    <div class="table-section">
        <h3 class="table-title">Payroll Summary by Site</h3>
        <div class="table-container">
            <table class="payroll-table">
                <thead>
                    <tr>
                        <th>Site</th>
                        <th>Employees</th>
                        <th>Gross Pay</th>
                        <th>Deductions</th>
                        <th>Net Pay</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="payrollTableBody">
                    <tr>
                        <td colspan="6" class="reports-empty-row">Loading payroll summary...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="report-pagination" id="payrollReportPagination" aria-label="Payroll table pagination"></div>
    </div>

    <div class="reports-modal-overlay" id="payrollWorkersModal" aria-hidden="true">
        <div class="reports-modal" role="dialog" aria-modal="true" aria-labelledby="payrollWorkersModalTitle">
            <div class="reports-modal-header">
                <div>
                    <h3 id="payrollWorkersModalTitle">Worker Payroll Details</h3>
                    <p id="payrollWorkersModalSubtitle">Payroll records for the selected site</p>
                </div>
                <button type="button" class="reports-modal-close" data-close-report-modal aria-label="Close">&times;</button>
            </div>
            <div class="reports-modal-body">
                <div class="table-container">
                    <table class="payroll-table">
                        <thead><tr><th>Worker</th><th>Payroll Period</th><th>Gross Pay</th><th>Deductions</th><th>Net Pay</th><th>Status</th></tr></thead>
                        <tbody id="payrollWorkerReportBody"><tr><td colspan="6" class="reports-empty-row">No worker payroll records found.</td></tr></tbody>
                    </table>
                </div>
                <div class="report-pagination" id="payrollWorkerReportPagination"></div>
            </div>
        </div>
    </div>
</div>
