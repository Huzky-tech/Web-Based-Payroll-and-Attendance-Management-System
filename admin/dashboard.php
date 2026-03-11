<?php
include '../api/connection/db_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
     <script src="../js/dashboard.js" defer></script>
</head>
<body>
    <!-- Sidebar -->
  <div class="sidebar">
        <div class="sidebar-header">
            <h2>Philippians CDO</h2>
        </div>
        <nav class="nav-menu">
            <div class="nav-section">OVERVIEW</div>
            <a href="dashboard.php"class="nav-item <?php echo !isset($_GET['page']) ? 'active' : ''; ?>"><i class="fas fa-border-all"></i><span>Dashboard</span></a>
            
            <div class="nav-section">MANAGEMENT</div>
            <a href="dashboard.php?page=employee"  class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'employee') ? 'active' : ''; ?>"><i class="far fa-user"></i><span>Employees</span></a>
            <a href="dashboard.php?page=site_assign" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'site_assign') ? 'active' : ''; ?>"><i class="fas fa-map-marker-alt"></i><span>Site Assignments</span></a>
            <a href="dashboard.php?page=attendance" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'attendance') ? 'active' : ''; ?>"><i class="far fa-calendar-check"></i><span>Attendance</span></a>
            
            <div class="nav-section">OPERATIONS</div>
            <a href="dashboard.php?page=payroll_status" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'payroll_status') ? 'active' : ''; ?>"><i class="fas fa-check-circle"></i><span>Payroll Status</span></a>
            <a href="dashboard.php?page=payroll" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'payroll') ? 'active' : ''; ?>"><i class="fas fa-file-invoice-dollar"></i><span>Payroll Processing</span></a>
            <a href="dashboard.php?page=active_site" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'active_site') ? 'active' : ''; ?>"><i class="far fa-building"></i><span>Active Sites</span></a>
            
            <div class="nav-section">REPORTS & SETTINGS</div>
            <a href="dashboard.php?page=reports" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'reports') ? 'active' : ''; ?>"><i class="far fa-chart-bar"></i><span>Reports</span></a>
            <a href="dashboard.php?page=history" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'history') ? 'active' : ''; ?>"><i class="fas fa-history"></i><span>History</span></a>
            <a href="dashboard.php?page=archive" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'archive') ? 'active' : ''; ?>"><i class="fas fa-archive"></i><span>Archive</span></a>
            <a href="dashboard.php?page=audit" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'audit') ? 'active' : ''; ?>"><i class="fas fa-search"></i><span>Audit Logs</span></a>
            <a href="dashboard.php?page=setting" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'setting') ? 'active' : ''; ?>"><i class="fas fa-cog"></i><span>Settings</span></a>
            <a href="../index.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </nav>
    </div>


    <div class="main-content">
        <div class="top-header">
            <h1 class="page-title"></h1>
            <div class="header-right">
                <div class="date-time">
                    <div class="date" id="currentDate">Sunday, January 4, 2026</div>
                    <div class="time" id="currentTime">06:48 AM</div>
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

       
        <div class="dashboard-content">
            <?php if (!isset($_GET['page'])): ?>  
        <!-- SUMMARY GRID -->
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-icon blue"><i class="fas fa-user-group"></i></div>
                    <div class="summary-text">
                        <span class="summary-label">Total Users</span>
                        <span class="summary-value summary-total-users">-</span>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon green"><i class="fas fa-shield-heart"></i></div>
                    <div class="summary-text">
                       <span class="summary-label">System Health</span>
                        <span class="summary-value summary-system-health">-</span>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon purple"><i class="fas fa-hdd"></i></div>
                    <div class="summary-text">
                        <span class="summary-label">Last Backup</span>
                        <span class="summary-value summary-last-backup">-</span>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon orange"><i class="fas fa-chart-line"></i></div>
                    <div class="summary-text">
                        <span class="summary-label">Active Users</span>
                        <span class="summary-value summary-active-users">-</span>
                    </div>
                </div>
            </div>

            <!-- KPI STRIP -->
            <div class="section-header">
                <div class="section-title">Site Operations Overview</div>
            </div>
            <div class="kpi-strip">
                <div class="kpi-card">
                    <div class="kpi-label">Active Sites</div>
                    <div class="kpi-value kpi-active-sites">0</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">At Capacity</div>
                    <div class="kpi-value kpi-at-capacity"><i class="fas fa-circle-check" style="color:#16a34a;"></i> 0</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Needs Workers</div>
                    <div class="kpi-value kpi-needs-workers"><i class="fas fa-circle-exclamation" style="color:#d97706;"></i> 0</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Avg Attendance</div>
                    <div class="kpi-value kpi-attendance"><span style="color:#6d28d9;">0%</span></div>
                </div>
            </div>

            <!-- SITE GRID -->
            <div class="site-grid">
                <!-- Site cards will be populated dynamically by JavaScript -->
            </div>

            <!-- TABLE CARD -->
            <div class="table-card">
                <div class="section-header">
                    <div class="section-title">Recent Activity</div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ACTION</th>
                            <th>USER</th>
                            <th>TARGET</th>
                            <th>TIME</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4" style="text-align:center;">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- SYSTEM ACTIONS -->
            <div class="section-header">
                <div class="section-title">System Management</div>
            </div>
            <div class="system-actions">
                <div class="action-tile">
                    <div class="summary-icon blue"><i class="fas fa-user-gear"></i></div>
                    <div class="action-label">User Management</div>
                </div>
                <div class="action-tile">
                    <div class="summary-icon purple"><i class="fas fa-database"></i></div>
                    <div class="action-label">Backup Database</div>
                </div>
                <div class="action-tile">
                    <div class="summary-icon green"><i class="fas fa-gear"></i></div>
                    <div class="action-label">System Settings</div>
                </div>
                <div class="action-tile">
                    <div class="summary-icon orange"><i class="fas fa-building"></i></div>
                    <div class="action-label">Add New Site</div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$allowedPages = ['employee','site_assign','active_site','worker','reports','audit','setting','payroll','attendance'];
if (isset($_GET['page']) && in_array($_GET['page'], $allowedPages)) {
    include $_GET['page'] . '.php';
}
?>

</body>
</html>
