<?php
include '../api/connection/db_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <link rel="stylesheet" href="../css/site_assign.css">
      <script src="../js/site_assign.js" defer></script>
</head>
<body>
  <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <div class="top-header">
            <h1 class="page-title">Site Assignments</h1>
            <div class="header-right">
                <div class="date-time">
                    <div class="date" id="currentDate">Wednesday, January 7, 2026</div>
                    <div class="time" id="currentTime">05:23 PM</div>
                </div>
                <div>
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
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Page Header -->
            <div class="page-header">
                <h1>Site Assignments</h1>
                <p class="page-subtitle">Manage payroll staff access to construction sites</p>
            </div>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-icon blue">
                        <i class="fas fa-user-group"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-label">Total Payroll Staff</div>
                        <div class="summary-value">4</div>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon green">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-label">Active Sites</div>
                        <div class="summary-value">3</div>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon purple">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-label">Total Assignments</div>
                        <div class="summary-value">1</div>
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
                            <div class="staff-item" data-staff="staff-a" onclick="selectStaff('staff-a', 'Payroll Staff A')">
                                <div class="staff-item-header">
                                    <div class="staff-name">Payroll Staff A</div>
                                    <span class="staff-sites-badge">1 Sites</span>
                                </div>
                                <div class="staff-email">staff.a@company.com</div>
                            </div>
                            <div class="staff-item" data-staff="staff-b" onclick="selectStaff('staff-b', 'Payroll Staff B')">
                                <div class="staff-item-header">
                                    <div class="staff-name">Payroll Staff B</div>
                                </div>
                                <div class="staff-email">staff.b@company.com</div>
                            </div>
                            <div class="staff-item" data-staff="staff-c" onclick="selectStaff('staff-c', 'Payroll Staff C')">
                                <div class="staff-item-header">
                                    <div class="staff-name">Payroll Staff C</div>
                                </div>
                                <div class="staff-email">staff.c@company.com</div>
                            </div>
                            <div class="staff-item" data-staff="staff-d" onclick="selectStaff('staff-d', 'Payroll Staff D')">
                                <div class="staff-item-header">
                                    <div class="staff-name">Payroll Staff D</div>
                                </div>
                                <div class="staff-email">staff.d@company.com</div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Panel - Site Assignments -->
                    <div class="sites-panel" id="sitesPanel">
                        <!-- Empty State (shown by default) -->
                        <div class="sites-placeholder" id="emptyState">
                            <i class="fas fa-user-group"></i>
                            <div class="sites-placeholder-title">Select a Staff Member</div>
                            <div class="sites-placeholder-text">Select a payroll staff member from the list to view and manage their site assignments.</div>
                        </div>

                        <!-- Site Assignments Content (hidden by default) -->
                        <div id="sitesContent" style="display: none;">
                            <div class="sites-panel-header">
                                <h4 class="sites-panel-title" id="panelTitle">Assign Sites to Payroll Staff A</h4>
                                <p class="sites-panel-subtitle" id="panelSubtitle">1 active assignments</p>
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
    </div>
</div>
</body>
</html> 
