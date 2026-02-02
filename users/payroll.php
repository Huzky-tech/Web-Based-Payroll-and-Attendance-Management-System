<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link rel="stylesheet" href="../css/payroll.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Philippians CDO</h2>
        </div>
        <nav class="nav-menu">
            <a href="dashboard.html" class="nav-item"><i class="fas fa-gauge"></i><span>Dashboard</span></a>
            <a href="employee.html" class="nav-item"><i class="fas fa-users"></i><span>Employees</span></a>
            <a href="site_assign.html" class="nav-item"><i class="fas fa-map-marker-alt"></i><span>Site Assignments</span></a>
            <a href="active_site.html" class="nav-item"><i class="fas fa-building"></i><span>Active Sites</span></a>
            <a href="worker.html" class="nav-item"><i class="fas fa-user-group"></i><span>Worker Directory</span></a>
            <a href="#" class="nav-item"><i class="fas fa-calendar-check"></i><span>Attendance</span></a>
            <a href="payroll.html" class="nav-item active"><i class="fas fa-wallet"></i><span>Payroll</span></a>
            <a href="reports.html" class="nav-item"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
            <a href="audit.html" class="nav-item"><i class="fas fa-clipboard-list"></i><span>Audit Logs</span></a>
            <a href="setting.html" class="nav-item"><i class="fas fa-cog"></i><span>Settings</span></a>
            <a href="../login.html" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </nav>
        <div class="sidebar-footer">© 2023 Philippians CDO</div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-header">
            <h1 class="page-title">Payroll</h1>
            <div class="header-right">
                <div class="date-time">
                    <div class="date" id="currentDate">Thursday, January 8, 2026</div>
                    <div class="time" id="currentTime">02:46 PM</div>
                </div>
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

        <div class="content-area">
            <!-- Site List Page -->
            <div class="page-view active" id="sitesPage">
                <div class="page-header">
                    <h1>Construction Sites Assigned</h1>
                    <p>Select a site to view and process payroll</p>
                </div>

                <div class="sites-container">
                    <div class="site-card">
                        <div class="site-card-header">
                            <div class="site-card-icon"><i class="fas fa-building"></i></div>
                            <span class="site-status-badge">Active</span>
                        </div>
                        <h3 class="site-card-title">Main Street Project</h3>
                        <div class="site-info">
                            <div class="site-info-item">Workers: <strong>15</strong></div>
                            <div class="site-info-item">Today's Attendance: <strong>12/15</strong></div>
                        </div>
                        <button class="btn-view-details" onclick="viewPayrollDetails('Main Street Project')">
                            View Details
                        </button>
                    </div>

                    <div class="site-card">
                        <div class="site-card-header">
                            <div class="site-card-icon"><i class="fas fa-building"></i></div>
                            <span class="site-status-badge">Active</span>
                        </div>
                        <h3 class="site-card-title">Downtown Office Complex</h3>
                        <div class="site-info">
                            <div class="site-info-item">Workers: <strong>10</strong></div>
                            <div class="site-info-item">Today's Attendance: <strong>9/10</strong></div>
                        </div>
                        <button class="btn-view-details" onclick="viewPayrollDetails('Downtown Office Complex')">
                            View Details
                        </button>
                    </div>
                </div>
            </div>

            <!-- Payroll Details Page -->
            <div class="page-view" id="payrollDetailsPage">
                <div class="payroll-details-header">
                    <button class="btn-back" onclick="goBackToSites()">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <div class="payroll-header-info">
                        <h1 id="siteNameHeader">Main Street Project</h1>
                        <p id="payrollPeriod">Worker Payroll Details - July 1-15, 2023</p>
                    </div>
                    <button class="btn-print-download" onclick="printDownloadPayroll()">
                        <i class="fas fa-download"></i>
                        Print/Download All Payroll
                    </button>
                </div>

                <div class="summary-cards">
                    <div class="summary-card">
                        <div class="summary-card-icon green">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="summary-card-content">
                            <div class="summary-card-label">Total Gross Pay</div>
                            <div class="summary-card-value">₱41,050</div>
                        </div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-icon red">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <div class="summary-card-content">
                            <div class="summary-card-label">Total Deductions</div>
                            <div class="summary-card-value">₱3,000</div>
                        </div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-icon blue">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="summary-card-content">
                            <div class="summary-card-label">Total Net Pay</div>
                            <div class="summary-card-value">₱38,050</div>
                        </div>
                    </div>
                </div>

                <h3 class="section-title" id="workersSectionTitle">Workers Assigned to Main Street Project</h3>

                <div class="payroll-table-container">
                    <table class="payroll-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Attendance</th>
                                <th>Gross Pay</th>
                                <th>Deductions</th>
                                <th>Net Pay</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="payrollTableBody">
                            <tr>
                                <td>
                                    <div class="employee-info">
                                        <div class="employee-name">John Doe</div>
                                        <div class="employee-id">ID: 001 • Construction Worker</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="attendance-info">
                                        <div class="attendance-days">14 days</div>
                                        <div class="attendance-details">1 absent • 2h late • 5h OT</div>
                                    </div>
                                </td>
                                <td><span class="amount">₱12,500</span></td>
                                <td>
                                    <span class="amount negative">-₱1,000</span>
                                    <button class="btn-view-breakdown">View Breakdown</button>
                                </td>
                                <td><span class="amount">₱11,500</span></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action-icon orange" title="View Document"><i class="fas fa-file-alt"></i></button>
                                        <button class="btn-action-icon green" title="Print"><i class="fas fa-print"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="employee-info">
                                        <div class="employee-name">Jane Smith</div>
                                        <div class="employee-id">ID: 002 • Electrician</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="attendance-info">
                                        <div class="attendance-days">15 days</div>
                                        <div class="attendance-details">0 absent • 0h late • 8h OT</div>
                                    </div>
                                </td>
                                <td><span class="amount">₱17,000</span></td>
                                <td>
                                    <span class="amount negative">-₱0</span>
                                    <button class="btn-view-breakdown">View Breakdown</button>
                                </td>
                                <td><span class="amount">₱17,000</span></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action-icon orange" title="View Document"><i class="fas fa-file-alt"></i></button>
                                        <button class="btn-action-icon green" title="Print"><i class="fas fa-print"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="employee-info">
                                        <div class="employee-name">Michael Johnson</div>
                                        <div class="employee-id">ID: 003 • Plumber</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="attendance-info">
                                        <div class="attendance-days">13 days</div>
                                        <div class="attendance-details">2 absent • 3h late • 2h OT</div>
                                    </div>
                                </td>
                                <td><span class="amount">₱11,550</span></td>
                                <td>
                                    <span class="amount negative">-₱2,000</span>
                                    <button class="btn-view-breakdown">View Breakdown</button>
                                </td>
                                <td><span class="amount">₱9,550</span></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action-icon orange" title="View Document"><i class="fas fa-file-alt"></i></button>
                                        <button class="btn-action-icon green" title="Print"><i class="fas fa-print"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="table-total-row">
                                <td><strong>TOTAL (3 workers)</strong></td>
                                <td></td>
                                <td><strong class="amount">₱41,050</strong></td>
                                <td><strong class="amount negative">-₱3,000</strong></td>
                                <td><strong class="amount">₱38,050</strong></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="process-payroll-section">
                    <span class="process-note">Note: Payroll will be submitted and wait for approval from HR</span>
                    <button class="btn-process-payroll" id="btnProcessPayroll" onclick="processPayroll()">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span id="processPayrollText">Process Payroll for Main Street Project</span>
                    </button>
                </div>

                <div class="success-message" id="successMessage">
                    <div class="success-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="success-text">
                        Payroll processed successfully! Waiting for HR approval. You can now print individual or all payslips.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/payroll.js"></script>
</body>
</html>
