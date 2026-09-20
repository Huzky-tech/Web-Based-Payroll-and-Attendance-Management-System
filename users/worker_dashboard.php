<?php

require_once '../api/connection/db_config.php';
require_once '../includes/auth.php';

require_auth($conn, ['Worker']);
require_once __DIR__ . '/../api/attendance_schema_helpers.php';
attendance_schema_ensure_table($conn);

$userId = (int) $_SESSION['user_id'];
$page = $_GET['page'] ?? 'dashboard';

$allowedPages = ['dashboard', 'attendance', 'payslip'];

if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}

/* Get worker information */
$stmt = $conn->prepare("
    SELECT
        w.*,
        COALESCE(
            NULLIF(wa.Role_On_Site, ''),
            NULLIF(w.Position, ''),
            'Worker'
        ) AS display_position,
        ps.Site_Name,
        ps.ShiftStart,
        ps.ShiftEnd
    FROM worker w
    LEFT JOIN workerassignment wa
        ON wa.WorkerID = w.WorkerID
    LEFT JOIN projectsite ps
        ON ps.SiteID = wa.SiteID
    WHERE w.UserID = ?
    LIMIT 1
");

$stmt->bind_param('i', $userId);
$stmt->execute();

$worker = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$worker) {
    session_destroy();
    header('Location: ../index.php');
    exit;
}

$workerId = (int) $worker['WorkerID'];
$fullName = trim(
    $worker['First_Name'] . ' ' . $worker['Last_Name']
);

/* Get attendance records */
$attendance = [];

$attendanceStmt = $conn->prepare("
    SELECT
        Date,
        Time_In,
        Time_Out,
        Hours_Worked,
        Overtime_Hours,
        AttendanceStatus, IsLate
    FROM attendance
    WHERE WorkerID = ?
    ORDER BY Date DESC
    LIMIT 60
");

$attendanceStmt->bind_param('i', $workerId);
$attendanceStmt->execute();

$attendance = $attendanceStmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$attendanceStmt->close();

/* Get payroll and payslip records */
$payroll = [];

$payrollStmt = $conn->prepare("
    SELECT
        p.*,
        ps.PayslipID,
        ps.Payslip_Number
    FROM payroll p
    LEFT JOIN payslip ps
        ON ps.PayrollID = p.PayrollID
    WHERE p.WorkerID = ?
    ORDER BY p.Pay_Period_End DESC
");

$payrollStmt->bind_param('i', $workerId);
$payrollStmt->execute();

$payroll = $payrollStmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$payrollStmt->close();

/* Calculate next payday */
$nextPayday = (int) date('j') <= 15
    ? date('F 15, Y')
    : date('F t, Y');

/* Escape HTML output */
function wh($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

/* Format time */
function wt($value)
{
    if (!$value || $value === '00:00:00') {
        return '—';
    }

    return date('g:i A', strtotime($value));
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Worker Portal</title>
    <link rel="icon" type="image/png" href="../images/company-building-logo.png?v=20260907-1">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <link
        rel="stylesheet"
        href="../css/worker_dashboard.css?v=20260806-1"
    >
    <style>
        .worker-header-right{margin-left:auto;display:flex;align-items:center;gap:18px}.worker-date-time{text-align:right;color:#667085;font-size:12px;line-height:1.35}.worker-date-time strong{display:block;color:#d66c00;font-size:12px}.worker-header-popover{position:relative}.worker-main header .worker-header-icon{display:grid!important;place-items:center;border:0;background:#f3f5f8;color:#526174;width:42px;height:42px;border-radius:12px;margin:0;cursor:pointer}.worker-main header .worker-profile-toggle{display:flex!important;align-items:center;gap:10px;border:0;background:transparent;color:#182234;width:auto;height:auto;border-radius:8px;margin:0;padding:4px;cursor:pointer}.worker-avatar{display:grid;place-items:center;width:42px;height:42px;border-radius:50%;overflow:hidden;background:#edf0f4;color:#697586}.worker-avatar img{width:100%;height:100%;object-fit:cover}.worker-user-copy{display:flex;flex-direction:column;align-items:flex-start;min-width:90px}.worker-user-copy strong{max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:14px}.worker-user-copy small{color:#667085}.worker-profile-chevron{font-size:11px;color:#667085}.worker-header-menu{position:absolute;z-index:30;right:0;top:calc(100% + 12px);width:220px;padding:12px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;box-shadow:0 12px 30px #0002}.worker-header-menu[hidden]{display:none}.worker-notification-menu p{margin:12px 0 2px;color:#667085;font-size:13px}.worker-profile-menu{padding:7px}.worker-profile-menu a{display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:7px;color:#27364a;text-decoration:none;font-size:14px}.worker-profile-menu a:hover{background:#f5f6f8}@media(max-width:650px){.worker-date-time{display:none}.worker-header-right{gap:8px}.worker-user-copy,.worker-profile-chevron{display:none}}
    </style>
</head>

<body>

    <aside class="worker-sidebar">
        <h1>Philippians CDO</h1>

        <nav>
            <a
                class="<?= $page === 'dashboard' ? 'active' : '' ?>"
                href="/capstone/worker/dashboard"
            >
                <i class="fas fa-grip"></i>
                Dashboard
            </a>

            <a
                class="<?= $page === 'attendance' ? 'active' : '' ?>"
                href="/capstone/worker/attendance"
            >
                <i class="far fa-clipboard"></i>
                Attendance
            </a>

            <a
                class="<?= $page === 'payslip' ? 'active' : '' ?>"
                href="/capstone/worker/payslip"
            >
                <i class="fas fa-peso-sign"></i>
                Payslip
            </a>

            <a href="../api/logout.php">
                <i class="fas fa-arrow-right-from-bracket"></i>
                Logout
            </a>
        </nav>
    </aside>

    <main class="worker-main">

        <header class="worker-topbar">
            <button
                type="button"
                id="workerMenu"
                aria-label="Open navigation menu"
            >
                <i class="fas fa-bars"></i>
            </button>

            <h2><?= wh(ucfirst($page)) ?></h2>

            <div class="worker-header-right">
                <div class="worker-date-time" aria-label="Current date and time">
                    <div id="workerCurrentDate"><?= wh(date('l, F j, Y')) ?></div>
                    <strong id="workerCurrentTime"><?= wh(date('h:i:s A')) ?></strong>
                </div>
                <div class="worker-header-popover worker-user" id="workerProfileDropdown">
                    <button type="button" class="worker-profile-toggle" id="workerProfileToggle" aria-expanded="false">
                        <span class="worker-avatar">
                            <?php if (!empty($worker['photo_path'])): ?><img src="../<?= wh($worker['photo_path']) ?>" alt=""><?php else: ?><i class="fas fa-user"></i><?php endif; ?>
                        </span>
                        <span class="worker-user-copy"><strong><?= wh($fullName) ?></strong><small>Worker</small></span>
                        <i class="fas fa-chevron-down worker-profile-chevron"></i>
                    </button>
                    <div class="worker-header-menu worker-profile-menu" id="workerProfileMenu" hidden>
                        <a href="/capstone/worker/dashboard"><i class="far fa-user"></i> My Dashboard</a>
                        <a href="../api/logout.php"><i class="fas fa-arrow-right-from-bracket"></i> Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <section class="worker-content">

            <?php if ($page === 'dashboard'): ?>

                <div class="worker-welcome">
                    <h2>
                        Welcome, <?= wh($worker['First_Name']) ?>!
                    </h2>

                    <p>
                        <?= wh($worker['display_position']) ?>

                        <span>|</span>

                        Site:
                        <?= wh($worker['Site_Name'] ?: 'Not assigned') ?>
                    </p>
                </div>

                <div class="worker-stats">

                    <div>
                        <i class="far fa-clock orange"></i>

                        <span>
                            Today's Schedule

                            <strong>
                                <?= wt($worker['ShiftStart']) ?>
                                –
                                <?= wt($worker['ShiftEnd']) ?>
                            </strong>
                        </span>
                    </div>

                    <div>
                        <i class="far fa-credit-card green"></i>

                        <span>
                            Next Payday

                            <strong>
                                <?= wh($nextPayday) ?>
                            </strong>
                        </span>
                    </div>

                    <div>
                        <i class="far fa-calendar purple"></i>

                        <span>
                            Attendance Records

                            <strong>
                                <?= count($attendance) ?>
                            </strong>
                        </span>
                    </div>

                    <a href="/capstone/worker/payslip">
                        <i class="far fa-file-lines yellow"></i>

                        <span>
                            Quick Action

                            <strong>View Payslip</strong>
                        </span>
                    </a>

                </div>

                <div class="worker-card">
                    <h3>Your Attendance QR Code</h3>

                    <p class="center">
                        Show this QR code to your supervisor to record
                        your attendance.
                    </p>

                    <div class="qr-box">

                        <?php if (!empty($worker['qr_code_path'])): ?>

                            <img
                                src="../<?= wh(ltrim($worker['qr_code_path'], '/')) ?>"
                                alt="Worker attendance QR code"
                            >

                        <?php else: ?>

                            <p>QR code is being prepared.</p>

                        <?php endif; ?>

                    </div>
                </div>

            <?php elseif ($page === 'attendance'): ?>

                <div class="worker-page-title">
                    <div>
                        <h2>Attendance Tracking</h2>
                        <p>Your recorded attendance history</p>
                    </div>

                    <a href="/capstone/worker/dashboard">
                        My QR Code
                    </a>
                </div>

                <div class="worker-card table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Hours</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (!$attendance): ?>
                                <tr>
                                    <td colspan="5">
                                        No attendance records yet.
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($attendance as $row): ?>
                                <tr>
                                    <td>
                                        <?= wh(
                                            date(
                                                'M j, Y',
                                                strtotime($row['Date'])
                                            )
                                        ) ?>
                                    </td>

                                    <td>
                .                        <?= wt($row['Time_In']) ?>
                
                                    </td>

                                    <td>
                                        <?= wt($row['Time_Out']) ?>
                                    </td>

                                    <td>
                                        <?= wh($row['Hours_Worked']) ?>
                                    </td>

                                    <td>
                                        <span class="worker-status">
                                            <?= wh($row['AttendanceStatus'] . ((int) ($row['IsLate'] ?? 0) === 1 ? ' (Late)' : '')) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        </tbody>
                    </table>
                </div>

            <?php else: ?>

                <div class="worker-page-title">
                    <div>
                        <h2>Payslip</h2>
                        <p>View your processed payroll history</p>
                    </div>
                </div>

                <div class="worker-card table-wrap">
                    <h3>Payslip History</h3>

                    <table>
                        <thead>
                            <tr>
                                <th>Pay Period</th>
                                <th>Payment Date</th>
                                <th>Gross Pay</th>
                                <th>Deductions</th>
                                <th>Net Pay</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (!$payroll): ?>
                                <tr>
                                    <td colspan="6">
                                        No payslips available yet.
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($payroll as $row): ?>
                                <tr>
                                    <td>
                                        <?= wh(
                                            date(
                                                'M j',
                                                strtotime(
                                                    $row['Pay_Period_Start']
                                                )
                                            ) .
                                            ' – ' .
                                            date(
                                                'M j, Y',
                                                strtotime(
                                                    $row['Pay_Period_End']
                                                )
                                            )
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= wh(
                                            date(
                                                'M j, Y',
                                                strtotime(
                                                    $row['Date_Processed']
                                                )
                                            )
                                        ) ?>
                                    </td>

                                    <td>
                                        ₱<?= number_format(
                                            (float) $row['Gross_Pay'],
                                            2
                                        ) ?>
                                    </td>

                                    <td>
                                        ₱<?= number_format(
                                            (float) $row['Total_Deductions'],
                                            2
                                        ) ?>
                                    </td>

                                    <td>
                                        <strong>
                                            ₱<?= number_format(
                                                (float) $row['Net_Pay'],
                                                2
                                            ) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <span class="worker-status">
                                            Processed
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

        </section>
    </main>

    <div
        class="worker-backdrop"
        id="workerBackdrop"
    ></div>

    <script>
        const body = document.body;
        const menuButton = document.getElementById('workerMenu');
        const backdrop = document.getElementById('workerBackdrop');
        const profileToggle = document.getElementById('workerProfileToggle');
        const profileMenu = document.getElementById('workerProfileMenu');

        function updateWorkerClock() {
            const now = new Date();
            document.getElementById('workerCurrentDate').textContent = now.toLocaleDateString('en-US', {
                weekday: 'long', month: 'long', day: 'numeric', year: 'numeric'
            });
            document.getElementById('workerCurrentTime').textContent = now.toLocaleTimeString('en-US', {
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
            });
        }

        function closeWorkerHeaderMenus(exception = null) {
            [[profileToggle, profileMenu]].forEach(([toggle, menu]) => {
                if (menu !== exception) {
                    menu.hidden = true;
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        }

        function toggleWorkerHeaderMenu(toggle, menu) {
            const willOpen = menu.hidden;
            closeWorkerHeaderMenus(menu);
            menu.hidden = !willOpen;
            toggle.setAttribute('aria-expanded', String(willOpen));
        }

        updateWorkerClock();
        setInterval(updateWorkerClock, 1000);
        profileToggle.addEventListener('click', (event) => {
            event.stopPropagation();
            toggleWorkerHeaderMenu(profileToggle, profileMenu);
        });
        document.addEventListener('click', () => closeWorkerHeaderMenus());

        menuButton.addEventListener('click', () => {
            body.classList.toggle('worker-menu-open');
        });

        backdrop.addEventListener('click', () => {
            body.classList.remove('worker-menu-open');
        });
    </script>

</body>
</html>
