<?php
include '../api/connection/db_config.php';
include '../includes/auth.php';

$currentRole = require_auth($conn, ['Assistant Admin', 'Payroll Staff', 'Timekeeper']);
$user_id = (int) ($_SESSION['user_id'] ?? 0);
$currentPayrollStaffId = 0;
$embeddedDashboard = $embeddedDashboard ?? false;

if ($currentRole === 'Payroll Staff') {
    $staffStmt = $conn->prepare("SELECT PayrollStaff_ID FROM payrollstaff WHERE UserID = ? LIMIT 1");
    if ($staffStmt) {
        $staffStmt->bind_param("i", $user_id);
        $staffStmt->execute();
        $staffResult = $staffStmt->get_result();
        if ($staffResult && $staffResult->num_rows > 0) {
            $currentPayrollStaffId = (int) ($staffResult->fetch_assoc()['PayrollStaff_ID'] ?? 0);
        }
        $staffStmt->close();
    }
}
?>
<?php if (!$embeddedDashboard): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Staff Site Assignment - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/site_assign.css?v=20260906-1">
    <script src="../js/action_result_modal.js?v=20260920-access-1" defer></script>
<script src="../js/site_assign.js?v=20260913-security-1" defer></script>
</head>
<body>
    <div class="main-content">
<?php endif; ?>
    <div class="content-area">
            <!-- Page Header -->
            <div class="page-header">
                <h1><?php echo $currentRole === 'Payroll Staff' ? 'My Site Assignments' : 'Site Assignments'; ?></h1>
            </div>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-icon blue">
                        <i class="fas fa-user-group"></i>
                    </div>
                        <div class="summary-content">
                        <div class="summary-label"><?php echo $currentRole === 'Payroll Staff' ? 'My Staff Profile' : 'Total Payroll Staff'; ?></div>
                            <div class="summary-value" id="staffCount" data-api="../api/count_payroll_staff.php">0</div>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon green">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-label">Active Sites</div>
                        <div class="summary-value" id="siteCount" data-api="../api/count_active_sites.php">0</div>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon purple">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-label"><?php echo $currentRole === 'Payroll Staff' ? 'My Assignments' : 'Total Assignments'; ?></div>
                        <div class="summary-value" id="assignmentCount" data-api="../api/count_assignments.php">0</div>
                    </div>
                </div>
            </div>

            <!-- Staff Assignment Management -->
            <div class="assignment-section">
                <div class="assignment-header">
                    <div class="assignment-title-section">
                        <h3><?php echo $currentRole === 'Payroll Staff' ? 'Assigned Site Details' : 'Staff Assignment Management'; ?></h3>
                        <p class="assignment-subtitle">
                            <?php echo $currentRole === 'Payroll Staff'
                                ? 'Check whether you are assigned to any construction site.'
                                : 'Assign payroll staff to specific construction sites'; ?>
                        </p>
                    </div>
                </div>

                <div class="assignment-panels">
                    <!-- Left Panel - Staff List -->
                    <div class="staff-list-panel">
                        <?php if ($currentRole !== 'Payroll Staff'): ?>
                        <div class="staff-search">
                            <i class="fas fa-search"></i>
                            <input type="text" id="staffSearch" placeholder="Search payroll staff..." onkeyup="filterStaff()">
                        </div>
                        <?php endif; ?>
                        <div class="staff-list" id="staffList">
                            <input type="hidden" id="currentUserId" value="<?php echo $user_id; ?>">
                            <input type="hidden" id="currentUserRole" value="<?php echo htmlspecialchars($currentRole, ENT_QUOTES); ?>">
                            <input type="hidden" id="currentPayrollStaffId" value="<?php echo $currentPayrollStaffId; ?>">
                            <!-- Staff list loaded dynamically via AJAX -->
                        </div>
                    </div>

                    <!-- Right Panel - Site Assignments -->
                    <div class="sites-panel" id="sitesPanel">
                        <!-- Empty State -->
                        <div class="sites-placeholder" id="emptyState">
                            <i class="fas fa-users-cog"></i>
                            <div class="sites-placeholder-title"><?php echo $currentRole === 'Payroll Staff' ? 'No Site Assignment Yet' : 'Select a Staff Member'; ?></div>
                            <div class="sites-placeholder-text"><?php echo $currentRole === 'Payroll Staff'
                                ? 'You are not assigned to any site yet. Contact your administrator for assignment updates.'
                                : 'Choose a payroll staff member from the list to manage their site assignments.'; ?></div>
                            <div style="font-size: 12px; color: #6b7280; margin-top: 16px;">
                                 </div>
                        </div>

                        <!-- Content Panel -->
                        <div id="sitesContent" style="display: none;">
                            <div class="sites-panel-header">
                                <h4 class="sites-panel-title" id="panelTitle">Manage Assignments</h4>
                                <p class="sites-panel-subtitle" id="panelSubtitle">0 sites assigned</p>
                            </div>
                            <div class="site-search">
                                <i class="fas fa-search" aria-hidden="true"></i>
                                <input type="search" id="siteAssignmentSearch" placeholder="Search site names..." aria-label="Search site names">
                            </div>
                            <div id="sitesList">
                                <!-- Dynamic content -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php if (!$embeddedDashboard): ?>
</body>
</html>
<?php endif; ?>
