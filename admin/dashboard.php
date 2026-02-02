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
            <a href="dashboard.php" class="nav-item active">
                <i class="fas fa-gauge"></i>
                <span>Dashboard</span>
            </a>
            <a href="dashboard.php?page=employee" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'employee') ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Employees</span>
            </a>
            <a href="dashboard.php?page=site_assign" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'site_assign') ? 'active' : ''; ?>">
                <i class="fas fa-map-marker-alt"></i>
                <span>Site Assignments</span>
            </a>
            <a href="dashboard.php?page=active_site" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'active_site') ? 'active' : ''; ?>">
                <i class="fas fa-building"></i>
                <span>Active Sites</span>
            </a>
            <a href="dashboard.php?page=worker" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'worker') ? 'active' : ''; ?>">
                <i class="fas fa-user-group"></i>
                <span>Worker Directory</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-calendar-check"></i>
                <span>Attendance</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-wallet"></i>
                <span>Payroll</span>
            </a>
            <a href="dashboard.php?page=reports" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'reports') ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <a href="dashboard.php?page=audit" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'audit') ? 'active' : ''; ?>" >
                <i class="fas fa-clipboard-list"></i>
                <span>Audit Logs</span>
            </a>
            <a href="dashboard.php?page=setting" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'setting') ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            <a href="../index.php" class="nav-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            © 2023 Philippians CDO
        </div>
    </div>

<?php if (!isset($_GET['page'])): ?>
    <div class="main-content">
        <div class="top-header">
            <h1 class="page-title">Dashboard</h1>
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
            <!-- SUMMARY GRID -->
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-icon blue"><i class="fas fa-user-group"></i></div>
                    <div class="summary-text">
                        <span class="summary-label">Total Users</span>
                        <span class="summary-value">52</span>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon green"><i class="fas fa-shield-heart"></i></div>
                    <div class="summary-text">
                        <span class="summary-label">System Health</span>
                        <span class="summary-value">Good</span>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon purple"><i class="fas fa-hdd"></i></div>
                    <div class="summary-text">
                        <span class="summary-label">Last Backup</span>
                        <span class="summary-value">2023-07-05 03:00 AM</span>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon orange"><i class="fas fa-chart-line"></i></div>
                    <div class="summary-text">
                        <span class="summary-label">Active Users</span>
                        <span class="summary-value">45</span>
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
                <div class="site-card">
                    <div class="site-name">Main Street Project</div>
                    <div class="site-meta"><i class="fas fa-location-dot"></i>123 Main St, Downtown</div>
                    <div class="site-row">
                        <span class="site-label">Workers</span>
                        <span class="site-value">0 / 30</span>
                    </div>
                    <div class="bar-track"><div class="bar-fill bar-amber" style="width:0%;"></div></div>
                    <div class="site-row" style="margin-top:10px;">
                        <span class="site-label">Attendance Rate</span>
                        <span class="site-value" style="color:#16a34a;">0%</span>
                    </div>
                    <div class="bar-track"><div class="bar-fill bar-green" style="width:0%;"></div></div>
                    <div class="site-footer">
                        <div class="manager">John Smith</div>
                        <div class="badge green"><i class="fas fa-check-circle"></i> On Track</div>
                    </div>
                </div>
                <div class="site-card">
                    <div class="site-name">Downtown Office Complex</div>
                    <div class="site-meta"><i class="fas fa-location-dot"></i>456 Business Ave</div>
                    <div class="site-row">
                        <span class="site-label">Workers</span>
                        <span class="site-value">0 / 15</span>
                    </div>
                    <div class="bar-track"><div class="bar-fill bar-amber" style="width:0%;"></div></div>
                    <div class="site-row" style="margin-top:10px;">
                        <span class="site-label">Attendance Rate</span>
                        <span class="site-value" style="color:#d97706;">0%</span>
                    </div>
                    <div class="bar-track"><div class="bar-fill bar-amber" style="width:0%;"></div></div>
                    <div class="site-footer">
                        <div class="manager">Sarah Johnson</div>
                        <div class="badge amber"><i class="fas fa-exclamation-circle"></i> Needs Workers</div>
                    </div>
                </div>
                <div class="site-card">
                    <div class="site-name">Riverside Apartments</div>
                    <div class="site-meta"><i class="fas fa-location-dot"></i>789 River Rd</div>
                    <div class="site-row">
                        <span class="site-label">Workers</span>
                        <span class="site-value">0 / 15</span>
                    </div>
                    <div class="bar-track"><div class="bar-fill bar-green" style="width:0%;"></div></div>
                    <div class="site-row" style="margin-top:10px;">
                        <span class="site-label">Attendance Rate</span>
                        <span class="site-value" style="color:#16a34a;">0%</span>
                    </div>
                    <div class="bar-track"><div class="bar-fill bar-green" style="width:0%;"></div></div>
                    <div class="site-footer">
                        <div class="manager">Mike Brown</div>
                        <div class="badge blue"><i class="fas fa-users"></i> At Capacity</div>
                    </div>
                </div>
                <div class="site-card">
                    <div class="site-name">Park Avenue Mall</div>
                    <div class="site-meta"><i class="fas fa-location-dot"></i>101 Park Ave</div>
                    <div class="site-row">
                        <span class="site-label">Workers</span>
                        <span class="site-value">0 / 20</span>
                    </div>
                    <div class="bar-track"><div class="bar-fill bar-green" style="width:0%;"></div></div>
                    <div class="site-row" style="margin-top:10px;">
                        <span class="site-label">Attendance Rate</span>
                        <span class="site-value" style="color:#16a34a;">0%</span>
                    </div>
                    <div class="bar-track"><div class="bar-fill bar-green" style="width:0%;"></div></div>
                    <div class="site-footer">
                        <div class="manager">Emily Davis</div>
                        <div class="badge blue"><i class="fas fa-users"></i> At Capacity</div>
                    </div>
                </div>
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
$allowedPages = ['employee','site_assign','active_site','worker','reports','audit','setting'];
if (isset($_GET['page']) && in_array($_GET['page'], $allowedPages)) {
    include $_GET['page'] . '.php';
}
?>

</body>
</html>