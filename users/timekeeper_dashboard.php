<?php
include '../api/connection/db_config.php';
include '../includes/auth.php';

require_auth($conn, ['Timekeeper']);

$displayName = trim((string) ($_SESSION['full_name'] ?? $_SESSION['email'] ?? 'Timekeeper'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timekeeper - Philippians CDO</title>
    <link rel="icon" type="image/png" href="../images/company-building-logo.png?v=20260907-1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/payroll_dashboard.css?v=20260424-2">
    <link rel="stylesheet" href="../css/dashboard_shell.css?v=20260424-1">
    <?php if (isset($_GET['page']) && $_GET['page'] === 'attendance'): ?>
    <link rel="stylesheet" href="../css/attendance.css?v=20260907-1">
    <script src="../js/action_result_modal.js?v=20260912-1" defer></script>
<script src="../js/attendance.js?v=20260913-security-1" defer></script>
    <?php endif; ?>
    <?php if (isset($_GET['page']) && $_GET['page'] === 'overtime_requests'): ?>
    <link rel="stylesheet" href="../css/overtime_requests.css">
<script src="../js/overtime_requests.js?v=20260813-2" defer></script>
    <?php endif; ?>
</head>
<body data-dashboard-role="timekeeper">
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Philippians CDO</h2>
        </div>
        <nav class="nav-menu">
            <div class="nav-section">TIMEKEEPER</div>
            <a href="/capstone/timekeeper/attendance" class="nav-item <?php echo (($_GET['page'] ?? 'attendance') === 'attendance') ? 'active' : ''; ?>">
                <i class="far fa-calendar-check"></i><span>Attendance</span>
            </a>
            <a href="/capstone/timekeeper/overtime_requests" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] === 'overtime_requests') ? 'active' : ''; ?>">
                <i class="far fa-clock"></i><span>Overtime Requests</span>
            </a>
            <a href="../api/logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </nav>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1 class="page-title">
                <?php
                echo (isset($_GET['page']) && $_GET['page'] === 'overtime_requests') ? 'Overtime Requests' : 'Attendance';
                ?>
            </h1>
            <div class="header-right">
                <div class="date-time">
                    <div class="date" id="currentDate"></div>
                    <div class="time" id="currentTime"></div>
                </div>
                <div class="user-profile">
                    <div class="user-avatar"><i class="fas fa-user"></i></div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($displayName); ?></div>
                        <div class="user-role">Timekeeper</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-content">
            <?php
            $allowedPages = ['attendance', 'overtime_requests'];
            $page = $_GET['page'] ?? 'attendance';
            if (in_array($page, $allowedPages, true)) {
                $embeddedDashboard = true;
                include $page . '.php';
                unset($embeddedDashboard);
            } else {
                $embeddedDashboard = true;
                include 'attendance.php';
                unset($embeddedDashboard);
            }
            ?>
        </div>
    </div>

    <script>
        function updateDateTime() {
            const now = new Date();
            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            let hours = now.getHours();
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            document.getElementById('currentDate').textContent = `${days[now.getDay()]}, ${months[now.getMonth()]} ${now.getDate()}, ${now.getFullYear()}`;
            document.getElementById('currentTime').textContent = `${hours}:${minutes} ${ampm}`;
        }
        updateDateTime();
        setInterval(updateDateTime, 60000);
    </script>
</body>
</html>
