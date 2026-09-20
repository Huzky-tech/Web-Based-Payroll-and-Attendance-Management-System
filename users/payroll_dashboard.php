<?php
include '../api/connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/../includes/user_profile_photo.php';

$currentRole = require_auth($conn, ['Payroll Staff', 'HR']);
$hrDashboardMode = !empty($hrDashboardMode);
if ($currentRole === 'HR' && !$hrDashboardMode) {
    $query = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: /capstone/hr/dashboard' . ($query !== '' ? '?' . $query : ''));
    exit;
}
if ($currentRole !== 'HR' && $hrDashboardMode) {
    header('Location: /capstone/payroll/dashboard');
    exit;
}
$isHr = $currentRole === 'HR';
$dashboardFile = $isHr ? '/capstone/hr/dashboard' : '/capstone/payroll/dashboard';
ensure_user_profile_photo_column($conn);

$payrollProfilePhoto = '';
if (!empty($_SESSION['user_id'])) {
    $photoStmt = $conn->prepare("SELECT profile_photo FROM users WHERE id = ? LIMIT 1");
    if ($photoStmt) {
        $profileUserId = (int) $_SESSION['user_id'];
        $photoStmt->bind_param('i', $profileUserId);
        $photoStmt->execute();
        $photoRow = $photoStmt->get_result()->fetch_assoc();
        $payrollProfilePhoto = user_profile_photo_url($photoRow['profile_photo'] ?? '');
        $photoStmt->close();
    }
}

$displayName = trim((string) ($_SESSION['full_name'] ?? ''));
if ($displayName === '' && !empty($_SESSION['user_id'])) {
    $userStmt = $conn->prepare("SELECT full_name, email FROM users WHERE id = ? LIMIT 1");
    if ($userStmt) {
        $userId = (int) $_SESSION['user_id'];
        $userStmt->bind_param('i', $userId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        if ($userResult && $userRow = $userResult->fetch_assoc()) {
            $displayName = trim((string) ($userRow['full_name'] ?? ''));
            if ($displayName !== '') {
                $_SESSION['full_name'] = $displayName;
            }
            if (empty($_SESSION['email']) && !empty($userRow['email'])) {
                $_SESSION['email'] = $userRow['email'];
            }
        }
        $userStmt->close();
    }
}
if ($displayName === '') {
    $displayName = trim((string) ($_SESSION['email'] ?? ($isHr ? 'HR User' : 'Payroll Staff User')));
}
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
    <link rel="stylesheet" href="../css/payroll_dashboard.css?v=20260901-2">
    <link rel="stylesheet" href="../css/dashboard_shell.css?v=20260908-1">
    <script src="../js/action_result_modal.js?v=20260920-access-1" defer></script>
    <?php if (isset($_GET['page']) && $_GET['page'] == 'attendance'): ?>
    <link rel="stylesheet" href="../css/attendance.css?v=20260907-1">
<script src="../js/attendance.js?v=20260913-security-1" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] == 'worker'): ?>
    <link rel="stylesheet" href="../css/worker.css?v=20260921-manager-1">
<script src="../js/worker.js?v=20260921-manager-2" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] == 'site_assign'): ?>
    <link rel="stylesheet" href="../css/site_assign.css?v=20260906-1">
<script src="../js/site_assign.js?v=20260913-security-1" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] == 'active_site'): ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="../css/active_site.css?v=20260921-manager-1">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>
<script src="../js/active_site.js?v=20260913-hr-access-1" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] == 'reports'): ?>
    <link rel="stylesheet" href="../css/reports.css?v=20260907-6">
    <?php if (($_GET['report'] ?? '') === 'overtime'): ?>
    <link rel="stylesheet" href="../css/overtime_requests.css?v=20260905-1">
<script src="../js/overtime_report.js?v=20260905-1" defer></script>
    <?php endif; ?>
<script src="../js/reports.js?v=20260907-5" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] == 'setting'): ?>
    <link rel="stylesheet" href="../css/setting.css?v=20260906-3">
<script src="../js/setting.js?v=20260921-admin-1" defer></script>
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
    <script src="../js/dashboard.js?v=20260901-2" defer></script>
<script src="../js/payroll_dashboard.js?v=20260715-1" defer></script>
<script src="../js/admin_notifications.js?v=20260913-security-1" defer></script>
<script src="../js/responsive_mobile.js?v=20260913-1" defer></script>
<link rel="stylesheet" href="../css/responsive_mobile.css?v=20260905-1">
</head>
<body data-dashboard-role="<?php echo $isHr ? 'hr' : 'payroll'; ?>" class="<?php echo !isset($_GET['page']) ? 'payroll-dashboard-home' : 'payroll-module-page'; ?>">

    <div class="sidebar">

        <div class="sidebar-header">
            <h2>Philippians CDO</h2>
        </div>
        <nav class="nav-menu">
            <div class="nav-section">OVERVIEW</div>
            <a href="<?php echo $dashboardFile; ?>" class="nav-item <?php echo !isset($_GET['page']) ? 'active' : ''; ?>"><i class="fas fa-border-all"></i><span>Dashboard</span></a>

            <div class="nav-section">MANAGEMENT</div>
             <a href="<?php echo $dashboardFile; ?>?page=worker"  class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'worker') ? 'active' : ''; ?>"><i class="far fa-user"></i><span>Workers</span></a>
            <a href="<?php echo $dashboardFile; ?>?page=active_site" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'active_site') ? 'active' : ''; ?>"><i class="far fa-building"></i><span>Active Sites</span></a>
            <a href="<?php echo $dashboardFile; ?>?page=attendance" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'attendance') ? 'active' : ''; ?>"><i class="far fa-calendar-check"></i><span>Attendance</span></a>
            <a href="<?php echo $dashboardFile; ?>?page=overtime_requests" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'overtime_requests') ? 'active' : ''; ?>"><i class="far fa-clock"></i><span>Overtime Requests</span></a>
            <a href="<?php echo $dashboardFile; ?>?page=timekeeper_reports" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'timekeeper_reports') ? 'active' : ''; ?>"><i class="fas fa-clipboard-list"></i><span>Timekeeper Reports</span></a>

            <?php if (!$isHr): ?>
            <div class="nav-section">OPERATIONS</div>
            <a href="<?php echo $dashboardFile; ?>?page=payroll_status" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'payroll_status') ? 'active' : ''; ?>"><i class="fas fa-check-circle"></i><span>My Submissions</span></a>
            <a href="<?php echo $dashboardFile; ?>?page=payroll" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'payroll') ? 'active' : ''; ?>"><i class="fas fa-file-invoice-dollar"></i><span>Payroll Processing</span></a>
            <?php endif; ?>
            <?php if ($isHr): ?>
            <div class="nav-section">PAYROLL</div>
            <a href="/capstone/hr/payroll" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'payroll') ? 'active' : ''; ?>"><i class="fas fa-file-invoice-dollar"></i><span>Payroll Processing</span></a>
            <a href="<?php echo $dashboardFile; ?>?page=payroll_status" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'payroll_status') ? 'active' : ''; ?>"><i class="fas fa-check-circle"></i><span>My Submissions</span></a>
            <?php endif; ?>
            <div class="nav-section">REPORTS & SETTINGS</div>
            <a href="<?php echo $dashboardFile; ?>?page=reports" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'reports') ? 'active' : ''; ?>"><i class="far fa-chart-bar"></i><span>Reports</span></a>
            <a href="<?php echo $dashboardFile; ?>?page=setting" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'setting') ? 'active' : ''; ?>"><i class="fas fa-cog"></i><span>Settings</span></a>
            <a href="../api/logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </nav>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1 class="page-title">
                <?php
                $pageTitles = [
                    'employee' => 'Employees',
                    'site_assign' => 'My Site Assignments',
                    'attendance' => 'Attendance',
                    'payroll_status' => 'My Submissions',
                    'payroll' => 'Payroll Processing',
                    'active_site' => 'Active Sites',
                    'reports' => 'Reports',
                    'overtime_requests' => 'Overtime Requests',
                    'timekeeper_reports' => 'Timekeeper Reports',
                    'setting' => 'Settings',
                ];
                echo $pageTitles[$_GET['page'] ?? ''] ?? 'Dashboard';
                ?>
            </h1>
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
                        <div class="user-avatar"><?php if ($payrollProfilePhoto !== ''): ?><img src="<?php echo htmlspecialchars($payrollProfilePhoto); ?>" alt="Payroll Staff profile" style="width:100%;height:100%;object-fit:cover;border-radius:50%;"><?php else: ?><i class="fas fa-user"></i><?php endif; ?></div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($displayName); ?></div>
                            <div class="user-role"><?php echo $isHr ? 'HR' : 'Payroll Staff'; ?></div>
                        </div>
                        <i class="fas fa-chevron-down" aria-hidden="true"></i>
                    </button>

                    <div class="user-profile-menu" id="userProfileMenu" role="menu" aria-label="User actions" style="display:none;">
                        <a class="user-profile-item" role="menuitem" href="<?php echo $dashboardFile; ?>?page=setting">
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
                    <span class="hero-eyebrow"><?php echo $isHr ? 'HR Workforce Desk' : 'Payroll Control Desk'; ?></span>
                    <h2><?php echo $isHr ? 'Monitor employees, approvals, assignments, and site coverage in one place.' : 'Manage active payroll work, attendance-linked totals, and site updates with better clarity.'; ?></h2>
                    <p><?php echo $isHr ? 'Focus on employee records, worker status, basic attendance visibility, and project site assignments.' : 'Keep payroll processing focused with quick access to workforce counts, open reports, and everyday actions.'; ?></p>
                </div>
                <div class="hero-panel">
                    <div class="hero-stat">
                        <span class="hero-stat-label"><?php echo $isHr ? 'Pending Employee Approvals' : 'Current Queue'; ?></span>
                        <strong class="hero-stat-value <?php echo $isHr ? 'hr-pending-approvals-text' : 'payroll-current-queue'; ?>">0 Pending</strong>
                    </div>
                    <div class="hero-divider"></div>
                    <div class="hero-stat">
                        <span class="hero-stat-label">Attendance Sync</span>
                        <strong class="hero-stat-value payroll-attendance-sync">Checking</strong>
                    </div>
                </div>
            </section>

            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-icon yellow"><i class="far fa-building"></i></div>
                    <div class="metric-info">
                        <span class="metric-label"><?php echo $isHr ? 'Total Employees' : 'Sites'; ?></span>
                        <span class="metric-value payroll-sites-count">0</span>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon blue"><i class="fas fa-user-friends"></i></div>
                    <div class="metric-info">
                        <span class="metric-label"><?php echo $isHr ? 'Active Workers' : 'Total Workers'; ?></span>
                        <span class="metric-value payroll-workers-count">0</span>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon green"><i class="far fa-file-alt"></i></div>
                    <div class="metric-info">
                        <span class="metric-label"><?php echo $isHr ? 'Pending Employee Approvals' : 'Pending Payrolls'; ?></span>
                        <span class="metric-value <?php echo $isHr ? 'hr-pending-approvals-count' : 'payroll-pending-count'; ?>">0</span>
                    </div>
                </div>
                <?php if ($isHr): ?>
                <div class="metric-card">
                    <div class="metric-icon blue"><i class="fas fa-user-check"></i></div>
                    <div class="metric-info">
                        <span class="metric-label">Assigned Workers</span>
                        <span class="metric-value hr-assigned-workers">0</span>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon yellow"><i class="fas fa-user-clock"></i></div>
                    <div class="metric-info">
                        <span class="metric-label">Unassigned Workers</span>
                        <span class="metric-value hr-unassigned-workers">0</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <section class="assigned-sites-overview" aria-labelledby="assignedSitesTitle">
                <div class="assigned-sites-heading">
                    <div>
                        <div class="section-kicker">Coverage Overview</div>
                        <h2 class="section-title" id="assignedSitesTitle"><?php echo $isHr ? 'Workers per Project Site' : 'My Assigned Sites'; ?></h2>
                        <p class="assigned-sites-subtitle">Track staffing capacity, attendance, and site coverage at a glance.</p>
                    </div>
                    <?php if (!$isHr): ?><a class="assigned-sites-link" href="<?php echo $dashboardFile; ?>?page=site_assign">Manage assignments <i class="fas fa-arrow-right" aria-hidden="true"></i></a><?php endif; ?>
                </div>
                <div class="sites-grid payroll-sites-grid"></div>
            </section>

            <?php if ($isHr): ?>
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-icon green"><i class="fas fa-calendar-check"></i></div>
                    <div class="metric-info">
                        <span class="metric-label">Attendance Present Today</span>
                        <span class="metric-value hr-present-today">0</span>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon blue"><i class="fas fa-chart-pie"></i></div>
                    <div class="metric-info">
                        <span class="metric-label">Attendance Rate</span>
                        <span class="metric-value hr-attendance-rate">0%</span>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon yellow"><i class="fas fa-id-badge"></i></div>
                    <div class="metric-info">
                        <span class="metric-label">Active / Inactive / Archived</span>
                        <span class="metric-value hr-status-breakdown">0 / 0 / 0</span>
                    </div>
                </div>
            </div>

            <div class="table-card">
                <div class="table-header">
                    <div>
                        <div class="section-kicker">Employee Records</div>
                        <h2><i class="fas fa-user-plus" style="color: var(--text-main);"></i> Recently Added Employees</h2>
                    </div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Status</th>
                            <th>Assignment</th>
                            <th>Date Hired</th>
                        </tr>
                    </thead>
                    <tbody class="hr-recent-employees-body">
                        <tr><td colspan="4" style="text-align:center;">Loading employees...</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="table-card">
                <div class="table-header">
                    <div>
                        <div class="section-kicker">Notifications</div>
                        <h2><i class="fas fa-bell" style="color: var(--text-main);"></i> Employee and Assignment Updates</h2>
                    </div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Update</th>
                            <th>Details</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody class="hr-updates-body">
                        <tr><td colspan="3" style="text-align:center;">Loading updates...</td></tr>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="table-card">
                <div class="table-header">
                    <div>
                        <div class="section-kicker">Report Queue</div>
                        <h2><i class="fas fa-exclamation-triangle" style="color: var(--text-main);"></i> Recent Incident Reports</h2>
                    </div>
                    <span class="badge-pending reports-pending-badge">0 Pending</span>
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
            <?php endif; ?>

            <section class="quick-actions-section" aria-labelledby="quickActionsTitle">
                <div class="section-kicker">Shortcuts</div>
                <h2 class="section-title" id="quickActionsTitle">Quick Actions</h2>
                <div class="actions-grid">
                    <?php if ($isHr): ?>
                    <a class="action-card blue" href="<?php echo $dashboardFile; ?>?page=worker&action=add">
                        <span class="action-icon"><i class="fas fa-user-plus"></i></span>
                        <span class="action-copy"><strong>Add New Employee</strong><small>Create a worker profile for approval and assignment</small></span>
                        <i class="fas fa-arrow-right action-arrow" aria-hidden="true"></i>
                    </a>
                    <a class="action-card green" href="<?php echo $dashboardFile; ?>?page=worker">
                        <span class="action-icon"><i class="fas fa-users"></i></span>
                        <span class="action-copy"><strong>View Employees</strong><small>Review employee records and status</small></span>
                        <i class="fas fa-arrow-right action-arrow" aria-hidden="true"></i>
                    </a>
                    <a class="action-card purple" href="<?php echo $dashboardFile; ?>?page=worker&filter=pending">
                        <span class="action-icon"><i class="fas fa-user-check"></i></span>
                        <span class="action-copy"><strong>Approve Employee</strong><small>Review pending employee approvals</small></span>
                        <i class="fas fa-arrow-right action-arrow" aria-hidden="true"></i>
                    </a>
                    <a class="action-card blue" href="<?php echo $dashboardFile; ?>?page=active_site">
                        <span class="action-icon"><i class="fas fa-map-location-dot"></i></span>
                        <span class="action-copy"><strong>Assign Worker to Site</strong><small>Manage project site assignments</small></span>
                        <i class="fas fa-arrow-right action-arrow" aria-hidden="true"></i>
                    </a>
                    <a class="action-card purple" href="<?php echo $dashboardFile; ?>?page=worker">
                        <span class="action-icon"><i class="fas fa-user-pen"></i></span>
                        <span class="action-copy"><strong>Update Employee Information</strong><small>Edit workforce profile details</small></span>
                        <i class="fas fa-arrow-right action-arrow" aria-hidden="true"></i>
                    </a>
                    <a class="action-card green" href="/capstone/hr/payroll">
                        <span class="action-icon"><i class="fas fa-file-invoice-dollar"></i></span>
                        <span class="action-copy"><strong>Process Payroll</strong><small>Prepare payroll for a site and submit it for approval</small></span>
                        <i class="fas fa-arrow-right action-arrow" aria-hidden="true"></i>
                    </a>
                    <?php else: ?>
                    <a class="action-card blue" href="<?php echo $dashboardFile; ?>?page=site_assign">
                        <span class="action-icon"><i class="fas fa-map-location-dot"></i></span>
                        <span class="action-copy"><strong>My Site Assignments</strong><small>Review your assigned projects and coverage</small></span>
                        <i class="fas fa-arrow-right action-arrow" aria-hidden="true"></i>
                    </a>
                    <a class="action-card green" href="<?php echo $dashboardFile; ?>?page=payroll">
                        <span class="action-icon"><i class="fas fa-file-invoice-dollar"></i></span>
                        <span class="action-copy"><strong>Process Payroll</strong><small>Prepare and submit the current payroll</small></span>
                        <i class="fas fa-arrow-right action-arrow" aria-hidden="true"></i>
                    </a>
                    <a class="action-card purple" href="<?php echo $dashboardFile; ?>?page=attendance">
                        <span class="action-icon"><i class="fas fa-calendar-check"></i></span>
                        <span class="action-copy"><strong>Review Attendance</strong><small>Check daily records and attendance issues</small></span>
                        <i class="fas fa-arrow-right action-arrow" aria-hidden="true"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </section>

            <?php else: ?>
            <?php
            $allowedPages = ['worker','site_assign','active_site','reports','setting','payroll','payroll_status','attendance','overtime_requests','timekeeper_reports','history'];
            if ($isHr) {
                $allowedPages = ['worker','active_site','reports','setting','payroll','attendance','overtime_requests','timekeeper_reports','payroll_status'];
            }
            $requestedPage = (string) $_GET['page'];
            if ($isHr && $requestedPage === 'employee') {
                $requestedPage = 'worker';
            }
            if (in_array($requestedPage, $allowedPages, true)) {
                $embeddedDashboard = true;
                if (in_array($requestedPage, ['worker', 'active_site', 'attendance', 'payroll'], true)) {
                    include '../admin/' . $requestedPage . '.php';
                } else {
                    include $requestedPage . '.php';
                }
                unset($embeddedDashboard);
            } else {
                http_response_code(403);
                echo '<div class="table-card"><div class="table-header"><div><div class="section-kicker">Access restricted</div><h2>This page is not available to HR users.</h2></div></div><p>Use the sidebar to open an authorized HR module.</p></div>';
            }
            ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
