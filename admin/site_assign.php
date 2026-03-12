<?php
include '../api/connection/db_config.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1>Site Assignments</h1>
</div>

<!-- Summary Cards with IDs for JavaScript -->
<div class="summary-cards">
    <div class="summary-card">
        <div class="summary-icon blue">
            <i class="fas fa-user-group"></i>
        </div>
        <div class="summary-content">
            <div class="summary-label">Total Payroll Staff</div>
            <div class="summary-value" id="totalPayrollStaff">0</div>
        </div>
    </div>
    <div class="summary-card">
        <div class="summary-icon green">
            <i class="fas fa-map-marker-alt"></i>
        </div>
        <div class="summary-content">
            <div class="summary-label">Active Sites</div>
            <div class="summary-value" id="activeSitesCount">0</div>
        </div>
    </div>
    <div class="summary-card">
        <div class="summary-icon purple">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="summary-content">
            <div class="summary-label">Total Assignments</div>
            <div class="summary-value" id="totalAssignments">0</div>
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
                <!-- Staff will be dynamically loaded here -->
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
                    <h4 class="sites-panel-title" id="panelTitle">Select a Staff Member</h4>
                    <p class="sites-panel-subtitle" id="panelSubtitle">0 active assignments</p>
                </div>
                <div id="sitesList">
                    <!-- Sites will be dynamically loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

