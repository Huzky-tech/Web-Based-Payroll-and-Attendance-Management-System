<?php
include '../api/connection/db_config.php';
include '../includes/auth.php';

require_auth($conn, ['Admin']);

$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Staff Site Assignment - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/site_assign.css?v=20260911-1">
    <script src="../js/action_result_modal.js?v=20260920-access-1" defer></script>
<script src="../js/site_assign.js?v=20260913-security-1" defer></script>
</head>
<body>
    <div class="main-content">
        <div class="content-area">
            <!-- Page Header -->
            <div class="page-header">
                <h1>Payroll staff Assignments</h1>
            </div>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-icon blue">
                        <i class="fas fa-user-group"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-label">Total Payroll Staff</div>
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
                        <div class="summary-label">Total Assignments</div>
                        <div class="summary-value" id="assignmentCount" data-api="../api/count_assignments.php">0</div>
                    </div>
                </div>
            </div>

            <!-- Staff Assignment Management -->
            <div class="assignment-section">
                <div class="assignment-header">
                    <div class="assignment-title-section">
                        <h3>Payroll Staff Assignment Management</h3>
                        <p class="assignment-subtitle">Assign payroll staff to specific construction sites</p>
                    </div>
                </div>

                <div class="assignment-panels">
                    <!-- Left Panel - Staff List -->
                    <div class="staff-list-panel">
                        <div class="staff-search">
                            <i class="fas fa-search"></i>
                            <input type="search" id="staffSearch" placeholder="Search payroll staff or assigned site..." aria-label="Search payroll staff">
                        </div>
                        <div class="staff-filters" role="group" aria-label="Filter payroll staff by assignment status">
                            <button type="button" class="staff-filter active" data-assignment-filter="all">All</button>
                            <button type="button" class="staff-filter" data-assignment-filter="assigned">Assigned</button>
                            <button type="button" class="staff-filter" data-assignment-filter="unassigned">Unassigned</button>
                        </div>
                        <div class="staff-list" id="staffList">
                            <input type="hidden" id="currentUserId" value="<?php echo $user_id; ?>">
                            <!-- Staff list loaded dynamically via AJAX -->
                        </div>
                    </div>

                    <!-- Right Panel - Site Assignments -->
                    <div class="sites-panel" id="sitesPanel">
                        <!-- Empty State -->
                        <div class="sites-placeholder" id="emptyState">
                            <i class="fas fa-users-cog"></i>
                            <div class="sites-placeholder-title">Select a Staff Member</div>
                            <div class="sites-placeholder-text">Choose a payroll staff member from the list to manage their site assignments.</div>
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
</body>
</html>
