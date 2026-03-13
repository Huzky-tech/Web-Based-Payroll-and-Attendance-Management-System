<?php
include '../api/connection/db_config.php';
session_start();
$user_id = $_SESSION['user_id'] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Staff Site Assignment - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/site_assign.css">
    <script src="../js/site_assign.js" defer></script>
</head>
<body>
    <div class="main-content">
        <div class="content-area">
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-sitemap"></i> Site Assignments</h1>
                <p class="page-subtitle">Manage payroll staff assignments to construction sites</p>
            </div>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-icon blue">
                        <i class="fas fa-user-group"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-label">Total Payroll Staff</div>
                        <div class="summary-value" id="staffCount" data-api="../api/count_payroll_staff.php">Loading...</div>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon green">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-label">Active Sites</div>
                        <div class="summary-value" id="siteCount" data-api="../api/count_active_sites.php">Loading...</div>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon purple">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-label">Total Assignments</div>
                        <div class="summary-value" id="assignmentCount" data-api="../api/count_assignments.php">Loading...</div>
                    </div>
                </div>
            </div>

            <!-- Staff Assignment Management -->
            <div class="assignment-section">
                <div class="assignment-header">
                    <div class="assignment-title-section">
                        <h3>Staff Assignment Management</h3>
                        <p class="assignment-subtitle">Assign payroll staff to specific construction sites</p>
                    </div>
                    <button class="btn-audit" onclick="viewAuditLog()">
                        <i class="fas fa-clipboard-list"></i> View Audit Log
                    </button>
                </div>

                <div class="assignment-panels">
                    <!-- Left Panel - Staff List -->
                    <div class="staff-list-panel">
                        <div class="staff-search">
                            <i class="fas fa-search"></i>
                            <input type="text" id="staffSearch" placeholder="Search payroll staff..." onkeyup="filterStaff()">
                        </div>
                        <div class="staff-list" id="staffList">
                            <input type="hidden" id="currentUserId" value="<?php echo $user_id; ?>">
                            <!-- Staff list loaded dynamically -->
                        </div>
                    </div>

                    <!-- Right Panel - Site Assignments -->
                    <div class="sites-panel" id="sitesPanel">
                        <!-- Empty State (shown by default) -->
                        <div class="sites-placeholder" id="emptyState">
                            <i class="fas fa-user-group"></i>
                            <div class="sites-placeholder-title">Select a Staff Member</div>
                            <div class="sites-placeholder-text">Select a payroll staff member from the left panel to view and manage their site assignments.</div>
                            <div class="sites-placeholder-hint">💡 Import database/sample_staff_assign.sql for test data</div>
                        </div>

                        <!-- Site Assignments Content (hidden by default) -->
                        <div id="sitesContent" style="display: none;">
                            <div class="sites-panel-header">
                                <h4 class="sites-panel-title" id="panelTitle">Select a staff member</h4>
                                <p class="sites-panel-subtitle" id="panelSubtitle">0 active assignments</p>
                            </div>
                            <div id="sitesList">
                                <!-- Sites will be dynamically loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

