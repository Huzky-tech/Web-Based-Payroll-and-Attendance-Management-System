<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
      <link rel="stylesheet" href="../css/reports.css">
      <script src="../js/reports.js" defer></script>
</head>
<body>


    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <div class="top-header">
            <div class="page-title">Reports</div>
            <div class="header-right">
                <div class="date-time">
                    <div class="date" id="currentDate">Thursday, January 8, 2026</div>
                    <div class="time" id="currentTime">02:00 PM</div>
                </div>
                <button class="btn-action" onclick="exportReport()">
                    <i class="fas fa-download"></i>
                    Export Report
                </button>
                <button class="btn-action" onclick="printReport()">
                    <i class="fas fa-print"></i>
                    Print
                </button>
                <div class="user-profile">
                    <div class="user-avatar"><i class="fas fa-user"></i></div>
                    <div class="user-info">
                        <div class="user-name">Admin User</div>
                        <div class="user-role">Admin</div>
                    </div>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <h1 class="page-title">Reports</h1>

            <!-- Report Selection Section -->
            <div class="report-selection">
                <h2 class="section-title">Reports</h2>
                
                <div class="report-type-label">Report Type</div>
                <div class="report-type-cards">
                    <div class="report-type-card active" data-report="payroll" onclick="selectReportType('payroll')">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <div class="report-type-card-title">Payroll Summary</div>
                    </div>
                    <div class="report-type-card" data-report="attendance" onclick="selectReportType('attendance')">
                        <i class="fas fa-calendar-check"></i>
                        <div class="report-type-card-title">Attendance Summary</div>
                    </div>
                    <div class="report-type-card" data-report="deductions" onclick="selectReportType('deductions')">
                        <i class="fas fa-chart-bar"></i>
                        <div class="report-type-card-title">Deductions Report</div>
                    </div>
                    <div class="report-type-card" data-report="leave" onclick="selectReportType('leave')">
                        <i class="fas fa-clock"></i>
                        <div class="report-type-card-title">Leave Report</div>
                    </div>
                </div>

                <div class="date-range-section">
                    <div class="date-range-label">Date Range:</div>
                    <select class="date-range-select" id="dateRange" onchange="updateDateRange()">
                        <option value="current-month">Current Month (July 2023)</option>
                        <option value="last-month">Last Month (June 2023)</option>
                        <option value="last-3-months">Last 3 Months</option>
                        <option value="last-6-months">Last 6 Months</option>
                        <option value="this-year">This Year (2023)</option>
                        <option value="custom">Custom Range</option>
                    </select>
                    <button class="btn-generate" onclick="generateReport()">
                        Generate Report
                    </button>
                </div>
            </div>

            <!-- Payroll Summary Section -->
            <div class="payroll-summary" id="payrollSummary">
                <h2 class="summary-header" id="summaryHeader">Payroll Summary - Current Month (July 2023)</h2>
                
                <div class="summary-cards">
                    <div class="summary-card yellow">
                        <div class="summary-card-label">Total Gross Pay</div>
                        <div class="summary-card-value" id="totalGrossPay">₱350,000</div>
                    </div>
                    <div class="summary-card pink">
                        <div class="summary-card-label">Total Deductions</div>
                        <div class="summary-card-value" id="totalDeductions">₱87,500</div>
                    </div>
                    <div class="summary-card green">
                        <div class="summary-card-label">Total Net Pay</div>
                        <div class="summary-card-value" id="totalNetPay">₱262,500</div>
                    </div>
                    <div class="summary-card purple">
                        <div class="summary-card-label">Employee Count</div>
                        <div class="summary-card-value" id="employeeCount">45</div>
                    </div>
                </div>
            </div>

            <!-- Payroll Summary Table -->
            <div class="table-section">
                <h3 class="table-title">Payroll Summary Table</h3>
                <div class="table-container">
                    <table class="payroll-table">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Employees</th>
                                <th>Gross Pay</th>
                                <th>Deductions</th>
                                <th>Net Pay</th>
                            </tr>
                        </thead>
                        <tbody id="payrollTableBody">
                            <tr>
                                <td class="department-name">Site A</td>
                                <td>12</td>
                                <td class="amount">₱120,000</td>
                                <td class="amount">₱30,000</td>
                                <td class="amount">₱90,000</td>
                            </tr>
                            <tr>
                                <td class="department-name">Site B</td>
                                <td>18</td>
                                <td class="amount">₱140,000</td>
                                <td class="amount">₱35,000</td>
                                <td class="amount">₱105,000</td>
                            </tr>
                            <tr>
                                <td class="department-name">Site C</td>
                                <td>8</td>
                                <td class="amount">₱60,000</td>
                                <td class="amount">₱15,000</td>
                                <td class="amount">₱45,000</td>
                            </tr>
                            <tr>
                                <td class="department-name">Office</td>
                                <td>7</td>
                                <td class="amount">₱30,000</td>
                                <td class="amount">₱7,500</td>
                                <td class="amount">₱22,500</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>