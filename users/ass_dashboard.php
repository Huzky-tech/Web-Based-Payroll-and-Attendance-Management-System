<?php
include '../api/connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/../includes/user_profile_photo.php';

require_auth($conn, ['Assistant Admin']);
ensure_user_profile_photo_column($conn);
$assistantDashboardUrl = '/capstone/assistant/dashboard';

$assistantProfilePhoto = '';
if (!empty($_SESSION['user_id'])) {
    $photoStmt = $conn->prepare("SELECT profile_photo FROM users WHERE id = ? LIMIT 1");
    if ($photoStmt) {
        $profileUserId = (int) $_SESSION['user_id'];
        $photoStmt->bind_param('i', $profileUserId);
        $photoStmt->execute();
        $photoRow = $photoStmt->get_result()->fetch_assoc();
        $assistantProfilePhoto = user_profile_photo_url($photoRow['profile_photo'] ?? '');
        $photoStmt->close();
    }
}

$companyName = 'Philippians CDO';
$companyStmt = $conn->prepare("SELECT company_name FROM company_settings WHERE id = 1");
if ($companyStmt) {
    $companyStmt->execute();
    $companyResult = $companyStmt->get_result();
    if ($companyResult && ($companyRow = $companyResult->fetch_assoc())) {
        $companyName = trim((string) ($companyRow['company_name'] ?? '')) ?: $companyName;
    }
    $companyStmt->close();
}

$displayName = trim((string) ($_SESSION['full_name'] ?? ''));
if ($displayName === '') {
    $displayName = trim((string) ($_SESSION['email'] ?? 'Assistant Admin User'));
}

$pageTitles = [
    'worker' => 'Workers',
    'positions' => 'Positions & Salaries',
    'site_assign' => 'Site Assignments',
    'attendance' => 'Attendance',
    'overtime_requests' => 'Overtime Requests',
    'timekeeper_reports' => 'Timekeeper Reports',
    'payroll_status' => 'Payroll Approval',
    'payroll' => 'Payroll Processing',
    'active_site' => 'Active Sites',
    'reports' => 'Reports',
    'history' => 'History',
    'archive' => 'Archive',
    'audit' => 'Audit Logs',
    'setting' => 'System Settings',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Philippians CDO</title>
    <link rel="icon" type="image/png" href="../images/company-building-logo.png?v=20260907-1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/ass_dashboard.css?v=20260821-1">
    <link rel="stylesheet" href="../css/dashboard_shell.css?v=20260908-1">
    <script src="../js/action_result_modal.js?v=20260912-1" defer></script>
    <?php if (isset($_GET['page']) && $_GET['page'] == 'site_assign'): ?>
    <link rel="stylesheet" href="../css/site_assign.css?v=20260906-1">
<script src="../js/site_assign.js?v=20260913-security-1" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] == 'worker'): ?>
    <link rel="stylesheet" href="../css/worker.css?v=20260913-4">
<script src="../js/worker.js?v=20260913-image-1" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] == 'attendance'): ?>
    <link rel="stylesheet" href="../css/attendance.css?v=20260907-1">
<script src="../js/attendance.js?v=20260913-security-1" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] == 'active_site'): ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="../css/active_site.css?v=20260907-1">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>
<script src="../js/active_site.js?v=20260907-2" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] == 'reports'): ?>
    <link rel="stylesheet" href="../css/reports.css?v=20260907-5">
    <?php if (($_GET['report'] ?? '') === 'overtime'): ?>
    <link rel="stylesheet" href="../css/overtime_requests.css?v=20260905-1">
<script src="../js/overtime_report.js?v=20260905-1" defer></script>
    <?php endif; ?>
<script src="../js/reports.js?v=20260907-5" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] == 'setting'): ?>
    <link rel="stylesheet" href="../css/setting.css?v=20260906-3">
<script src="../js/setting.js?v=20260920-availability-3" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] == 'positions'): ?>
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
    <link rel="stylesheet" href="../css/payroll.css?v=20260906-2">
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
<link rel="stylesheet" href="../css/assistant_admin_refresh.css?v=20260814-2">
<script src="../js/dashboard.js?v=20260901-3" defer></script>
<script src="../js/ass_dashboard.js?v=20260715-1" defer></script>
<script src="../js/admin_notifications.js?v=20260913-security-1" defer></script>
<script src="../js/responsive_mobile.js?v=20260913-1" defer></script>
<link rel="stylesheet" href="../css/responsive_mobile.css?v=20260905-1">
<link rel="stylesheet" href="../css/dashboard_shell_stability.css?v=20260814-1">
</head>
<body data-dashboard-role="assistant" class="<?php echo !isset($_GET['page']) ? 'assistant-dashboard-home' : 'assistant-module-page'; ?>">

    <div class="sidebar">

        <div class="sidebar-header">
            <h2><?php echo htmlspecialchars($companyName); ?></h2>
        </div>
        <nav class="nav-menu">
            <div class="nav-section">OVERVIEW</div>
            <a href="<?php echo $assistantDashboardUrl; ?>" class="nav-item <?php echo !isset($_GET['page']) ? 'active' : ''; ?>"><i class="fas fa-border-all"></i><span>Dashboard</span></a>
            
            <div class="nav-section">MANAGEMENT</div>
            <a href="<?php echo $assistantDashboardUrl; ?>?page=worker" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'worker') ? 'active' : ''; ?>"><i class="far fa-user"></i><span>Workers</span></a>
            <a href="<?php echo $assistantDashboardUrl; ?>?page=positions" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'positions') ? 'active' : ''; ?>"><i class="fas fa-briefcase"></i><span>Positions &amp; Salaries</span></a>
            <a href="<?php echo $assistantDashboardUrl; ?>?page=active_site" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'active_site') ? 'active' : ''; ?>"><i class="far fa-building"></i><span>Active Sites</span></a>
            <a href="<?php echo $assistantDashboardUrl; ?>?page=site_assign" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'site_assign') ? 'active' : ''; ?>"><i class="fas fa-map-marker-alt"></i><span>Site Assignments</span></a>
            <a href="<?php echo $assistantDashboardUrl; ?>?page=attendance" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'attendance') ? 'active' : ''; ?>"><i class="far fa-calendar-check"></i><span>Attendance</span></a>
            <a href="<?php echo $assistantDashboardUrl; ?>?page=overtime_requests" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'overtime_requests') ? 'active' : ''; ?>"><i class="far fa-clock"></i><span>Overtime Requests</span></a>
            <a href="<?php echo $assistantDashboardUrl; ?>?page=timekeeper_reports" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'timekeeper_reports') ? 'active' : ''; ?>"><i class="fas fa-clipboard-list"></i><span>Timekeeper Reports</span></a>

            <div class="nav-section">PAYROLL</div>
            <a href="<?php echo $assistantDashboardUrl; ?>?page=payroll_status" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'payroll_status') ? 'active' : ''; ?>"><i class="fas fa-check-circle"></i><span>Payroll Approval</span></a>
           
            <div class="nav-section">REPORTS & SETTINGS</div>
            <a href="<?php echo $assistantDashboardUrl; ?>?page=reports" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'reports') ? 'active' : ''; ?>"><i class="far fa-chart-bar"></i><span>Reports</span></a>
            <a href="<?php echo $assistantDashboardUrl; ?>?page=history" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'history') ? 'active' : ''; ?>"><i class="fas fa-history"></i><span>History</span></a>
            <a href="<?php echo $assistantDashboardUrl; ?>?page=archive" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'archive') ? 'active' : ''; ?>"><i class="fas fa-archive"></i><span>Archive</span></a>
            <a href="<?php echo $assistantDashboardUrl; ?>?page=audit" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'audit') ? 'active' : ''; ?>"><i class="fas fa-search"></i><span>Audit Logs</span></a>
            <a href="<?php echo $assistantDashboardUrl; ?>?page=setting" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'setting') ? 'active' : ''; ?>"><i class="fas fa-cog"></i><span>System Settings</span></a>
            <a href="../api/logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </nav>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1 class="page-title"><?php echo htmlspecialchars($pageTitles[$_GET['page'] ?? ''] ?? 'Dashboard'); ?></h1>
            <div class="header-right">
                <div class="admin-notification-center" id="adminNotificationCenter">
                    <button type="button" class="admin-notification-button" id="adminNotificationButton" aria-label="Notifications" aria-expanded="false">
                        <i class="far fa-bell"></i><span class="admin-notification-badge" id="adminNotificationBadge" hidden>0</span>
                    </button>
                    <div class="admin-notification-panel" id="adminNotificationPanel" hidden>
                        <div class="admin-notification-panel-head"><strong>Notifications</strong><button type="button" id="markAllNotificationsRead">Mark all read</button></div>
                        <div class="admin-notification-tabs"><button type="button" class="active" data-notification-filter="all">All</button><button type="button" data-notification-filter="unread">Unread</button></div>
                        <div class="admin-notification-list" id="adminNotificationList"><div class="admin-notification-empty">Loading notifications...</div></div>
                    </div>
                </div>
                <div class="date-time">
                    <div class="date" id="currentDate">Sunday, January 4, 2026</div>
                    <div class="time" id="currentTime">06:48 AM</div>
                </div>
                <div class="user-profile" id="userProfileDropdown">
                    <button type="button" class="user-profile-toggle" id="userProfileToggle" aria-haspopup="true" aria-expanded="false">
                        <div class="user-avatar"><?php if ($assistantProfilePhoto !== ''): ?><img src="<?php echo htmlspecialchars($assistantProfilePhoto); ?>" alt="Assistant Admin profile" style="width:100%;height:100%;object-fit:cover;border-radius:50%;"><?php else: ?><i class="fas fa-user"></i><?php endif; ?></div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($displayName); ?></div>

                            <div class="user-role">Assistant Admin</div>
                        </div>
                        <i class="fas fa-chevron-down" aria-hidden="true"></i>
                    </button>

                    <div class="user-profile-menu" id="userProfileMenu" role="menu" aria-label="User actions" style="display:none;">
                        <a class="user-profile-item" role="menuitem" href="<?php echo $assistantDashboardUrl; ?>?page=setting">
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

            <section class="dashboard-hero">
                <div class="hero-copy">
                    <span class="hero-eyebrow">Operations Command Center</span>
                    <h2>Manage workforce, site activity, payroll flow, and operational priorities from one place.</h2>
                    <p>A focused overview of the workforce, project sites, attendance, and reports requiring action.</p>
                </div>
                <div class="hero-panel">
                    <div class="hero-stat">
                        <span class="hero-stat-label">Operations Status</span>
                        <strong class="hero-stat-value">Running Smoothly</strong>
                    </div>
                    <div class="hero-divider"></div>
                    <div class="hero-stat">
                        <span class="hero-stat-label">Pending Reports</span>
                        <strong class="hero-stat-value"><span class="summary-pending-reports">0</span> Items</strong>
                    </div>
                </div>
            </section>

            <div>
                <div class="section-header">
                    <div>
                        <div class="section-kicker">Executive Snapshot</div>
                        <div class="section-title">Analytics Overview</div>
                    </div>
                </div>
                <div class="analytics-grid">
                    <div class="analytics-card">
                        <div class="icon-wrapper" style="background: var(--blue-light); color: var(--blue-icon);"><i class="far fa-user"></i></div>
                        <div class="info"><div class="label">Total Users</div><div class="value summary-total-users">0</div></div>
                    </div>
                    <div class="analytics-card">
                        <div class="info"><div class="label">Attendance Trends</div><div class="value summary-attendance-rate">0% <span style="font-size: 12px; font-weight:400; color:var(--text-muted); display:block;">today average</span></div></div>
                        <div class="trend"><i class="fas fa-arrow-up"></i></div>
                    </div>
                    <div class="analytics-card">
                        <div class="icon-wrapper" style="background: var(--purple-light); color: var(--purple-icon);"><i class="fas fa-calculator"></i></div>
                        <div class="info"><div class="label">Current Payroll</div><div class="value summary-payroll-net">PHP 0.00</div></div>
                    </div>
                    <div class="analytics-card">
                        <div class="icon-wrapper" style="background: var(--yellow-light); color: var(--yellow-icon);"><i class="fas fa-arrow-trend-up"></i></div>
                        <div class="info"><div class="label">Active Users</div><div class="value summary-active-users">0</div></div>
                    </div>
                    <div class="analytics-bottom-row">
                        <div class="analytics-card">
                            <div class="info"><div class="label">Labor Cost Efficiency</div><div class="value summary-labor-efficiency">0% <span style="font-size: 12px; font-weight:400; color:var(--text-muted); margin-left: 4px;">net vs gross</span></div></div>
                            <div class="trend"><i class="fas fa-bars-staggered"></i></div>
                        </div>
                        <div class="analytics-card">
                            <div class="icon-wrapper" style="background: var(--yellow-light); color: var(--yellow-icon);"><i class="far fa-file-alt"></i></div>
                            <div class="info"><div class="label">Pending Reports</div><div class="value summary-pending-reports">0</div></div>
                        </div>
                        <div class="analytics-card">
                            <div class="icon-wrapper" style="background: var(--red-light); color: var(--red-icon);"><i class="fas fa-exclamation"></i></div>
                            <div class="info"><div class="label">Pending Incidents</div><div class="value summary-pending-incidents">0</div></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-container" style="display:none;">
                </div>

            <section class="operations-overview" aria-labelledby="siteOperationsTitle">
                <div class="section-header">
                    <div>
                        <div class="section-kicker">Live Metrics</div>
                        <div class="section-title" id="siteOperationsTitle">Site Operations Overview</div>
                        <p class="operations-subtitle">Monitor staffing levels and quickly identify sites that need attention.</p>
                    </div>
                    <a class="primary-btn" href="<?php echo $assistantDashboardUrl; ?>?page=active_site"><i class="fas fa-plus"></i> Add New Site</a>
                </div>

                <div class="kpi-strip">
                    <div class="kpi-card kpi-blue">
                        <div class="kpi-info">
                            <div class="kpi-label">Active Sites</div>
                            <div class="kpi-value kpi-active-sites">0</div>
                        </div>
                        <div class="kpi-icon"><i class="far fa-building"></i></div>
                    </div>
                    <div class="kpi-card kpi-green">
                        <div class="kpi-info">
                            <div class="kpi-label">At Capacity</div>
                            <div class="kpi-value kpi-at-capacity">0</div>
                        </div>
                        <div class="kpi-icon"><i class="far fa-check-circle"></i></div>
                    </div>
                    <div class="kpi-card kpi-yellow">
                        <div class="kpi-info">
                            <div class="kpi-label">Needs Workers</div>
                            <div class="kpi-value kpi-needs-workers">0</div>
                        </div>
                        <div class="kpi-icon"><i class="fas fa-exclamation-circle"></i></div>
                    </div>
                    <div class="kpi-card kpi-purple">
                        <div class="kpi-info">
                            <div class="kpi-label">Avg Attendance</div>
                            <div class="kpi-value kpi-attendance">0%</div>
                        </div>
                        <div class="kpi-icon"><i class="fas fa-chart-line"></i></div>
                    </div>
                </div>

                <div class="site-grid assistant-sites-panel"></div>
            </section>

            <div class="table-container">
                <div class="section-header">
                    <div>
                        <div class="section-kicker">Action Required</div>
                        <div class="section-title"><i class="fas fa-exclamation-triangle" style="color: var(--yellow-icon);"></i> Incident & Damage Reports</div>
                    </div>
                    <div class="badge yellow reports-pending-badge" style="font-size: 13px;">0 Pending</div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Site</th>
                            <th>Reported By</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="reports-table-body">
                        <tr>
                            <td colspan="6" style="text-align:center;">Loading reports...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <?php else: ?>
            <?php
            $allowedPages = ['worker','positions','site_assign','active_site','reports','audit','history','archive','setting','payroll','payroll_status','attendance','overtime_requests','timekeeper_reports'];
                if (in_array($_GET['page'], $allowedPages, true)) {
                $embeddedDashboard = true;
                if ($_GET['page'] === 'history') {
                    include '../admin/history.php';
                } elseif (in_array($_GET['page'], ['worker', 'positions', 'active_site', 'attendance', 'audit', 'archive'], true)) {
                    include '../admin/' . $_GET['page'] . '.php';
                } else {
                    include $_GET['page'] . '.php';
                }
                // Do NOT unset/clear embeddedDashboard before included markup/JS finishes.
                // The embedded child pages rely on this flag to decide whether to output <html>/<body> tags.
                // (JS modal crashes are unrelated, but this keeps the embedded layout consistent.)
                // unset($embeddedDashboard);
            }
            ?>
            <?php endif; ?>
            </div>
    </div>

</body>
</html>
