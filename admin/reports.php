<?php
include '../api/connection/db_config.php';
include '../includes/auth.php';

require_auth($conn, ['Admin']);
$embeddedDashboard = $embeddedDashboard ?? false;
$selectedReport = $_GET['report'] ?? 'attendance';
$validReports = ['attendance', 'payroll', 'deductions', 'overtime'];
if (!in_array($selectedReport, $validReports, true)) {
    $selectedReport = 'attendance';
}
?>
<?php if (!$embeddedDashboard): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
      <link rel="stylesheet" href="../css/reports.css?v=20260907-5">
    <script src="../js/action_result_modal.js?v=20260920-access-1" defer></script>
      <?php if ($selectedReport === 'overtime'): ?>
      <link rel="stylesheet" href="../css/overtime_requests.css?v=20260905-1">
<script src="../js/overtime_report.js?v=20260905-1" defer></script>
      <?php endif; ?>
<script src="../js/reports.js?v=20260907-5" defer></script>
</head>
<body>
<?php endif; ?>

    <!-- Main Content -->
    <?php if (!$embeddedDashboard): ?>
    <div class="main-content">
    <?php endif; ?>
        <!-- Content Area -->
        <div class="content-area">
            <h1 class="page-title">Reports</h1>
                <div class="report-type-cards">
                    <div class="report-type-card <?php echo $selectedReport === 'payroll' ? 'active' : ''; ?>" data-report="payroll" onclick="selectReportType('payroll')">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <div class="report-type-card-title">Payroll Summary</div>
                    </div>
                    <div class="report-type-card <?php echo $selectedReport === 'attendance' ? 'active' : ''; ?>" data-report="attendance" onclick="selectReportType('attendance')">
                        <i class="fas fa-calendar-check"></i>
                        <div class="report-type-card-title">Attendance Summary</div>
                    </div>
                    <div class="report-type-card <?php echo $selectedReport === 'deductions' ? 'active' : ''; ?>" data-report="deductions" onclick="selectReportType('deductions')">
                        <i class="fas fa-chart-bar"></i>
                        <div class="report-type-card-title">Deductions Report</div>
                    </div>
                    <div class="report-type-card <?php echo $selectedReport === 'overtime' ? 'active' : ''; ?>" data-report="overtime" onclick="selectReportType('overtime')">
                        <i class="fas fa-business-time"></i>
                        <div class="report-type-card-title">Overtime Report</div>
                    </div>
                </div>

                <?php if ($selectedReport === 'attendance'): ?>
                <?php
                $embeddedReport = true;
                include __DIR__ . '/attendance_report.php';
                unset($embeddedReport);
                ?>
                <?php elseif ($selectedReport === 'deductions'): ?>
                <?php
                $embeddedReport = true;
                include __DIR__ . '/deductions_report.php';
                unset($embeddedReport);
                ?>
                <?php elseif ($selectedReport === 'overtime'): ?>
                <?php
                $overtimeReportInnerOnly = true;
                include __DIR__ . '/overtime_report.php';
                unset($overtimeReportInnerOnly);
                ?>
                <?php else: ?>
                <?php include __DIR__ . '/../includes/reports_payroll_summary.php'; ?>
                <?php endif; ?>
        </div>
<?php if (!$embeddedDashboard): ?>
    </div>
<?php endif; ?>
<?php if (!$embeddedDashboard): ?>
</body>
</html>
<?php endif; ?>
