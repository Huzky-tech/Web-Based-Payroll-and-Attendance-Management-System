<?php
include '../api/connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/../includes/user_profile_photo.php';

require_auth($conn, ['Admin']);
ensure_user_profile_photo_column($conn);
$adminProfilePhoto = '';
if (!empty($_SESSION['user_id'])) {
    $profilePhotoStmt = $conn->prepare("SELECT profile_photo FROM users WHERE id = ? LIMIT 1");
    if ($profilePhotoStmt) {
        $headerUserId = (int) $_SESSION['user_id'];
        $profilePhotoStmt->bind_param('i', $headerUserId);
        $profilePhotoStmt->execute();
        $profilePhotoRow = $profilePhotoStmt->get_result()->fetch_assoc();
        $adminProfilePhoto = user_profile_photo_url($profilePhotoRow['profile_photo'] ?? '');
        $profilePhotoStmt->close();
    }
}

// Get company name from settings
$companyName = 'Philippians CDO';
$companyStmt = $conn->prepare("SELECT company_name FROM company_settings WHERE id = 1");
$companyStmt->execute();
$companyResult = $companyStmt->get_result();
if ($companyRow = $companyResult->fetch_assoc()) {
    $companyName = $companyRow['company_name'] ?: 'Philippians CDO';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Philippians CDO</title>
    <link rel="icon" type="image/png" href="../images/company-building-logo.png?v=20260907-1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="../css/dashboard.css?v=20260908-1">
    <link rel="stylesheet" href="../css/active_site.css?v=20260921-manager-1">
    <?php if (!isset($_GET['page'])): ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/dashboard_home.css?v=20260906-6">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
    <?php endif; ?>
    <script src="../js/action_result_modal.js?v=20260920-access-1" defer></script>
    <?php if (isset($_GET['page']) && $_GET['page'] == 'site_assign'): ?>
    <link rel="stylesheet" href="../css/site_assign.css?v=20260911-1">
<script src="../js/site_assign.js?v=20260913-security-1" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] == 'worker'): ?>
    <link rel="stylesheet" href="../css/worker.css?v=20260921-manager-1">
<script src="../js/worker.js?v=20260921-manager-1" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] == 'attendance'): ?>
    <link rel="stylesheet" href="../css/attendance.css?v=20260907-1">
<script src="../js/attendance.js?v=20260913-security-1" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] == 'active_site'): ?>
    <link rel="stylesheet" href="../css/active_site.css?v=20260921-manager-1">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>
<script src="../js/active_site.js?v=20260921-manager-1" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] == 'reports'): ?>
    <link rel="stylesheet" href="../css/reports.css?v=20260907-5">
<script src="../js/reports.js?v=20260907-5" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] == 'reports' && ($_GET['report'] ?? '') === 'overtime'): ?>
    <link rel="stylesheet" href="../css/overtime_requests.css?v=20260905-1">
<script src="../js/overtime_report.js?v=20260905-1" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] === 'setting'): ?>
    <link rel="stylesheet" href="../css/setting.css?v=20260916-payroll-validation-1">
<script src="../js/user_email_validation.js?v=20260920-availability-3" defer></script>
<script src="../js/setting.js?v=20260921-admin-1" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] === 'employee'): ?>
    <link rel="stylesheet" href="../css/employee.css?v=20260910-2">
<script src="../js/employee.js?v=20260921-admin-1" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] === 'positions'): ?>
    <link rel="stylesheet" href="../css/positions.css?v=20260913-3">
<script src="../js/positions.js?v=20260913-2" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] == 'audit'): ?>
    <link rel="stylesheet" href="../css/audit.css?v=20260906-1">
<script src="../js/audit.js?v=20260913-security-1" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] == 'history'): ?>
    <link rel="stylesheet" href="../css/history.css?v=20260523-4">
<script src="../js/history.js?v=20260523-4" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] == 'archive'): ?>
    <link rel="stylesheet" href="../css/archive.css?v=20260814-2">
<script src="../js/archive.js?v=20260814-2" defer></script>
    <?php endif; ?>                      
    <?php if (isset($_GET['page']) && $_GET['page'] === 'payroll'): ?>
    <link rel="stylesheet" href="../css/payroll.css?v=20260908-1">
<script src="../js/payroll.js?v=20260913-security-1" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] === 'payroll_status'): ?>
    <link rel="stylesheet" href="../css/payroll_approval.css?v=20260906-3">
<script src="../js/payroll_approval.js?v=20260908-1" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] === 'overtime_requests'): ?>
    <link rel="stylesheet" href="../css/overtime_requests.css?v=20260905-1">
<script src="../js/overtime_requests.js?v=20260907-3" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] === 'timekeeper_reports'): ?>
    <link rel="stylesheet" href="../css/timekeeper_reports.css?v=20260904-2">
<script src="../js/timekeeper_reports.js?v=20260913-security-1" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] === 'overtime_report'): ?>
    <link rel="stylesheet" href="../css/overtime_requests.css?v=20260905-1">
<script src="../js/overtime_report.js?v=20260905-1" defer></script>
    <?php endif; ?>
<script src="../js/dashboard.js?v=20260916-widget-guard-1" defer></script>
<script src="../js/responsive_mobile.js?v=20260913-1" defer></script>
<script src="../js/admin_notifications.js?v=20260913-security-1" defer></script>
<link rel="stylesheet" href="../css/responsive_mobile.css?v=20260905-1">
<link rel="stylesheet" href="../css/dashboard_shell_stability.css?v=20260814-1">

</head>
<body data-dashboard-role="admin">

  <div class="sidebar">
        <div class="sidebar-header">
            <h2><?php echo htmlspecialchars($companyName); ?></h2>
        </div>
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-item <?php echo !isset($_GET['page']) ? 'active' : ''; ?>"><i class="fas fa-th-large"></i><span>Dashboard</span></a>

            <div class="nav-section">MANAGEMENT</div>
            <a href="dashboard.php?page=worker" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'worker') ? 'active' : ''; ?>"><i class="fas fa-users"></i><span>Workers</span></a>
            <a href="dashboard.php?page=employee" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'employee') ? 'active' : ''; ?>"><i class="fas fa-user-tie"></i><span>Employee</span></a>
            <a href="dashboard.php?page=positions" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'positions') ? 'active' : ''; ?>"><i class="fas fa-briefcase"></i><span>Positions &amp; Salaries</span></a>
            <a href="dashboard.php?page=active_site" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'active_site') ? 'active' : ''; ?>"><i class="far fa-building"></i><span>Active Sites</span></a>
            <a href="dashboard.php?page=site_assign" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'site_assign') ? 'active' : ''; ?>"><i class="fas fa-map-marker-alt"></i><span>Site Assignments</span></a>
            <a href="dashboard.php?page=attendance" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'attendance') ? 'active' : ''; ?>"><i class="far fa-calendar-check"></i><span>Attendance</span></a>
            <a href="dashboard.php?page=overtime_requests" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'overtime_requests') ? 'active' : ''; ?>"><i class="far fa-clock"></i><span>Overtime Requests</span></a>
            <a href="dashboard.php?page=timekeeper_reports" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'timekeeper_reports') ? 'active' : ''; ?>"><i class="fas fa-clipboard-list"></i><span>Timekeeper Reports</span></a>

            <div class="nav-section">PAYROLL</div>
            <a href="dashboard.php?page=payroll" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'payroll') ? 'active' : ''; ?>"><i class="fas fa-file-invoice-dollar"></i><span>Payroll Processing</span></a>
            <a href="dashboard.php?page=payroll_status" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'payroll_status') ? 'active' : ''; ?>"><i class="fas fa-check-circle"></i><span>Payroll Approval</span></a>

            <div class="nav-section">REPORTS</div>
            <a href="dashboard.php?page=reports&report=payroll" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'reports') ? 'active' : ''; ?>"><i class="fas fa-business-time"></i><span>Reports</span></a>
            <a href="dashboard.php?page=history" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'history') ? 'active' : ''; ?>"><i class="fas fa-history"></i><span>History</span></a>
            <a href="dashboard.php?page=audit" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'audit') ? 'active' : ''; ?>"><i class="fas fa-search"></i><span>Audit Logs</span></a>

            <div class="nav-section">SETTINGS</div>
            <a href="dashboard.php?page=archive" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'archive') ? 'active' : ''; ?>"><i class="fas fa-archive"></i><span>Archive</span></a>
            <a href="dashboard.php?page=setting" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'setting') ? 'active' : ''; ?>"><i class="fas fa-cog"></i><span>System Settings</span></a>
            <a href="../api/logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
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
                <div class="admin-notification-center" id="adminNotificationCenter">
                    <button type="button" class="admin-notification-button" id="adminNotificationButton" aria-label="Notifications" aria-expanded="false">
                        <i class="far fa-bell"></i><span class="admin-notification-badge" id="adminNotificationBadge" hidden>0</span>
                    </button>
                    <div class="admin-notification-panel" id="adminNotificationPanel" hidden>
                        <div class="admin-notification-panel-head"><strong>Notifications</strong><button type="button" id="markAllNotificationsRead">Mark all read</button></div>
                        <div class="admin-notification-tabs">
                            <button type="button" class="active" data-notification-filter="all">All</button>
                            <button type="button" data-notification-filter="unread">Unread</button>
                        </div>
                        <div class="admin-notification-list" id="adminNotificationList"><div class="admin-notification-empty">Loading notifications...</div></div>
                    </div>
                </div>
                <div class="user-profile" id="userProfileDropdown">
                    <button type="button" class="user-profile-toggle" id="userProfileToggle" aria-haspopup="true" aria-expanded="false">
                        <div class="user-avatar"><?php if ($adminProfilePhoto !== ''): ?><img src="<?php echo htmlspecialchars($adminProfilePhoto); ?>" alt="Admin profile" style="width:100%;height:100%;object-fit:cover;border-radius:50%;"><?php else: ?><i class="fas fa-user"></i><?php endif; ?></div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin User'); ?></div>

                            <div class="user-role">Admin</div>
                        </div>
                        <i class="fas fa-chevron-down" aria-hidden="true"></i>
                    </button>

                    <div class="user-profile-menu" id="userProfileMenu" role="menu" aria-label="User actions" style="display:none;">
                        <a class="user-profile-item" role="menuitem" href="dashboard.php?page=setting">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                        <a class="user-profile-item user-profile-logout" role="menuitem" href="../api/logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>

            </div>
        </div>

       
        <div class="dashboard-content">
            <?php if (!isset($_GET['page'])): ?>
            <div class="dashboard-home">
                <div class="pagetitle">
                    <h1>Dashboard</h1>
                    <p>Payroll and QR attendance overview across all project sites.</p>
                </div>

                <!-- KPI Statistics -->
                <div class="row g-3 mb-4">
                    <div class="col-xl-2 col-lg-4 col-md-6 col-6">
                        <div class="info-card">
                            <div class="card-icon blue"><i class="fas fa-users"></i></div>
                            <div>
                                <h6>Total Workers</h6>
                                <div class="kpi-value kpi-total-workers">—</div>
                                <div class="kpi-sub">Workers</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-4 col-md-6 col-6">
                        <div class="info-card">
                            <div class="card-icon orange"><i class="far fa-building"></i></div>
                            <div>
                                <h6>Active Sites</h6>
                                <div class="kpi-value kpi-active-sites">—</div>
                                <div class="kpi-sub">Active Sites</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-4 col-md-6 col-6">
                        <div class="info-card">
                            <div class="card-icon green"><i class="fas fa-qrcode"></i></div>
                            <div>
                                <h6>Present Today</h6>
                                <div class="kpi-value kpi-present-today">—</div>
                                <div class="kpi-sub">Present</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-4 col-md-6 col-6">
                        <div class="info-card payroll-due-card">
                            <div class="card-icon purple"><i class="fas fa-money-bill-wave"></i></div>
                            <div>
                                <h6>Payroll Due</h6>
                                <div class="kpi-value kpi-payroll-due">—</div>
                                <div class="kpi-sub">Current period</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-4 col-md-6 col-6">
                        <div class="info-card">
                            <div class="card-icon red"><i class="far fa-clock"></i></div>
                            <div>
                                <h6>Pending Overtime</h6>
                                <div class="kpi-value kpi-pending-overtime">—</div>
                                <div class="kpi-sub">Requests</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-4 col-md-6 col-6">
                        <div class="info-card">
                            <div class="card-icon teal"><i class="fas fa-user-shield"></i></div>
                            <div>
                                <h6>Timekeepers</h6>
                                <div class="kpi-value kpi-timekeepers">—</div>
                                <div class="kpi-sub">Active</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts & Payroll row -->
                <div class="row g-3 mb-4">
                    <div class="col-lg-8">
                        <div class="card h-100 attendance-chart-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title"><i class="bi bi-pie-chart-fill"></i> Attendance Overview</h5>
                                <span class="text-muted small attendance-date-label">Today</span>
                            </div>
                            <div class="card-body">
                                <div class="chart-wrap">
                                    <canvas id="attendanceOverviewChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title"><i class="bi bi-cash-stack"></i> Payroll Overview</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small mb-2">Total Payroll This Week</p>
                                <div class="payroll-week-total payroll-week-net mb-3">—</div>
                                <p class="text-muted small mb-3 payroll-week-label">—</p>
                                <div class="payroll-breakdown">
                                    <div class="row-item">
                                        <span>Regular Pay</span>
                                        <span class="amount payroll-week-regular">—</span>
                                    </div>
                                    <div class="row-item">
                                        <span>Overtime Pay</span>
                                        <span class="amount payroll-week-overtime">—</span>
                                    </div>
                                    <div class="row-item">
                                        <span>Deductions</span>
                                        <span class="amount payroll-week-deductions">—</span>
                                    </div>
                                    <div class="row-item net">
                                        <span>Net Payroll</span>
                                        <span class="amount payroll-week-net-row">—</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Sites -->
                <div class="card mb-4 active-sites-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title">Active Sites</h5>
                        <a href="dashboard.php?page=active_site" class="active-sites-link">Manage</a>
                    </div>
                    <div class="card-body p-0 sites-scroll-row">
                        <div class="sites-panel-grid">
                            <div class="empty-state site-list-empty">Loading active sites…</div>
                        </div>
                    </div>
                </div>

                <!-- Attendance & Overtime row -->
                <div class="row g-3 mb-4">
                    <div class="col-lg-7">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title"><i class="bi bi-calendar-check"></i> Today's Attendance</h5>
                                <a href="dashboard.php?page=attendance" class="btn btn-sm btn-outline-warning">View All</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Worker</th>
                                                <th>Site</th>
                                                <th>Time In</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="today-attendance-body">
                                            <tr><td colspan="4" class="text-center text-muted py-4">Loading…</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title"><i class="bi bi-hourglass-split"></i> Pending Approvals</h5>
                                <a href="dashboard.php?page=overtime_requests" class="btn btn-sm btn-outline-warning">View All</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Worker</th>
                                                <th>Site</th>
                                                <th>Hrs</th>
                                                <th>Status</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody class="pending-overtime-body">
                                            <tr><td colspan="5" class="text-center text-muted py-4">Loading…</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions + Recent Activity -->
                <div class="row g-2 dashboard-bottom-row">
                    <div class="col-lg-7 col-md-6">
                        <div class="card dashboard-panel-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title"><i class="bi bi-lightning-charge-fill"></i> Quick Actions</h5>
                            </div>
                            <div class="card-body dashboard-panel-body">
                                <div class="quick-actions-grid quick-actions-grid-balanced">
                                    <a href="dashboard.php?page=worker" class="quick-action-btn"><i class="fas fa-user-plus"></i><span>Add Worker</span></a>
                                    <a href="dashboard.php?page=active_site" class="quick-action-btn"><i class="fas fa-user-check"></i><span>Assign Worker</span></a>
                                    <a href="dashboard.php?page=site_assign" class="quick-action-btn"><i class="fas fa-user-tag"></i><span>Assign Payroll Staff</span></a>
                                    <a href="dashboard.php?page=payroll" class="quick-action-btn"><i class="fas fa-file-invoice-dollar"></i><span>View Payroll</span></a>
                                    <a href="dashboard.php?page=active_site" class="quick-action-btn"><i class="fas fa-plus-circle"></i><span>Create Site</span></a>
                                    <a href="dashboard.php?page=overtime_requests" class="quick-action-btn"><i class="fas fa-clock"></i><span>Overtime Request</span></a>
                                    <a href="dashboard.php?page=reports" class="quick-action-btn"><i class="fas fa-chart-line"></i><span>View Reports</span></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-6">
                        <div class="card dashboard-panel-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title"><i class="bi bi-activity"></i> Recent Activity</h5>
                                <a href="dashboard.php?page=audit" class="btn btn-sm btn-outline-warning">View All</a>
                            </div>
                            <div class="card-body dashboard-panel-body">
                                <ul class="activity-timeline activity-timeline-balanced activity-timeline-list">
                                    <li class="empty-state">Loading…</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$allowedPages = ['employee','positions','worker','site_assign','active_site','reports','audit','history','archive','setting','payroll','payroll_status','attendance','overtime_requests','overtime_report','timekeeper_reports'];
if (isset($_GET['page']) && in_array($_GET['page'], $allowedPages)) {
    $embeddedDashboard = true;
    include $_GET['page'] . '.php';
    unset($embeddedDashboard);
}
?>

</body>
</html>
