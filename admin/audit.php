<?php
include '../api/connection/db_config.php';
include '../includes/auth.php';

require_auth($conn, ['Admin', 'Assistant Admin']);
$embeddedDashboard = $embeddedDashboard ?? false;
?>
<?php if (!$embeddedDashboard): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/audit.css?v=20260906-1">
    <script src="../js/action_result_modal.js?v=20260920-access-1" defer></script>
    <script src="../js/audit.js?v=20260913-security-1" defer></script>
</head>
<body>
<?php endif; ?>

<?php if (!$embeddedDashboard): ?>
    <div class="main-content">
        <div class="top-header">
            <div>
                <div class="page-title">Audit Logs</div>
                <div class="section-sub">Monitor all system activities, security events, and user actions</div>
            </div>
        </div>
    </div>
<?php endif; ?>
    <div class="content-area">
        <div class="tabs">
            <button class="tab active" data-tab="activity" onclick="switchTab('activity')"><i class="fas fa-list"></i>Activity Logs</button>
            <button class="tab" data-tab="analytics" onclick="switchTab('analytics')"><i class="fas fa-chart-line"></i>Analytics & Trends</button>
        </div>

        <div class="panel" id="activityPanel">
            <div class="filter-row">
                <input class="search-input" id="searchInput" placeholder="Search by user, action, or details..." oninput="onSearchInput()">
                <div class="filter-group">
                    <select id="actionFilter" class="filter-select" onchange="applyFilters()">
                        <option value="">All Actions</option>
                        <option value="Login">Login</option>
                        <option value="Logout">Logout</option>
                        <option value="Failed">Failed Login</option>
                        <option value="Create">Created</option>
                        <option value="Update">Updated</option>
                        <option value="Delete">Deleted</option>
                        <option value="Assign">Assigned</option>
                        <option value="Remove">Remove</option>
                        <option value="Process">Processed</option>
                        <option value="Backup">Backup</option>
                        <option value="Config">Configuration</option>
                    </select>
                </div>
                <div class="filter-group">
                    <select id="statusFilter" class="filter-select" onchange="applyFilters()">
                        <option value="">All Status</option>
                        <option value="success">Success</option>
                        <option value="failure">Failure</option>
                        <option value="warning">Warning</option>
                    </select>
                </div>
                <div class="filter-group">
                    <select id="severityFilter" class="filter-select" onchange="applyFilters()">
                        <option value="">All Severity</option>
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Info">Info</option>
                    </select>
                </div>
                <div class="filter-group">
                    <input type="date" id="startDate" class="filter-input" title="Filter by Date" onchange="applyFilters()">
                </div>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Resource</th>
                            <th>Status</th>
                            <th>Severity</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody id="logBody"></tbody>
                </table>
            </div>
            <div class="pagination-bar">
                <div class="pagination-info" id="paginationInfo">Showing 0-0 of 0</div>
                <div class="pagination-controls">
                    <button class="btn-pagination" id="prevPage" onclick="changePage(-1)" disabled><i class="fas fa-chevron-left"></i> Prev</button>
                    <span class="pagination-pages" id="pageNumbers"></span>
                    <button class="btn-pagination" id="nextPage" onclick="changePage(1)" disabled>Next <i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        </div>

        <div class="panel" id="analyticsPanel" style="display:none;">
            <div class="panel-header">
                <div class="panel-title">Analytics & Trends</div>
            </div>
            <div style="display:grid; grid-template-columns: 2fr 1fr; gap:18px;">
                <div class="panel" style="margin:0;">
                    <div class="panel-title" style="margin-bottom:12px;">Activity Trends (Last 7 Days)</div>
                    <div class="bar-list" id="trendBars">
                        <div class="bar-item"><span class="bar-label">Day 1</span><div class="bar-track"><div class="bar-fill" style="width:65%; background:#f59e0b;"></div></div></div>
                        <div class="bar-item"><span class="bar-label">Day 2</span><div class="bar-track"><div class="bar-fill" style="width:58%; background:#f59e0b;"></div></div></div>
                        <div class="bar-item"><span class="bar-label">Day 3</span><div class="bar-track"><div class="bar-fill" style="width:72%; background:#f59e0b;"></div></div></div>
                        <div class="bar-item"><span class="bar-label">Day 4</span><div class="bar-track"><div class="bar-fill" style="width:54%; background:#f59e0b;"></div></div></div>
                        <div class="bar-item"><span class="bar-label">Day 5</span><div class="bar-track"><div class="bar-fill" style="width:80%; background:#f59e0b;"></div></div></div>
                        <div class="bar-item"><span class="bar-label">Day 6</span><div class="bar-track"><div class="bar-fill" style="width:60%; background:#f59e0b;"></div></div></div>
                        <div class="bar-item"><span class="bar-label">Day 7</span><div class="bar-track"><div class="bar-fill" style="width:70%; background:#f59e0b;"></div></div></div>
                    </div>
                </div>
                <div class="panel" style="margin:0;">
                    <div class="panel-title" style="margin-bottom:12px;">Action Distribution</div>
                    <div class="bar-list" id="actionBars">
                        <div class="bar-item"><span class="bar-label">User Management</span><div class="bar-track"><div class="bar-fill" style="width:35%; background:#2563eb;"></div></div></div>
                        <div class="bar-item"><span class="bar-label">System Configuration</span><div class="bar-track"><div class="bar-fill" style="width:25%; background:#a855f7;"></div></div></div>
                        <div class="bar-item"><span class="bar-label">Data Modification</span><div class="bar-track"><div class="bar-fill" style="width:20%; background:#22c55e;"></div></div></div>
                        <div class="bar-item"><span class="bar-label">Security Events</span><div class="bar-track"><div class="bar-fill" style="width:15%; background:#ef4444;"></div></div></div>
                        <div class="bar-item"><span class="bar-label">Other</span><div class="bar-track"><div class="bar-fill" style="width:5%; background:#9ca3af;"></div></div></div>
                    </div>
                </div>
            </div>
            <div class="panel" style="margin-top:18px;">
                <div class="panel-title" style="margin-bottom:12px;">Top Active Users</div>
                <div id="topUsersGrid" style="display:grid; grid-template-columns: repeat(4,1fr); gap:12px;">
                    <div class="rule">
                        <div class="rule-info"><span class="user-avatar" style="background:#fef3c7; color:#b45309;">A</span>
                            <div><div class="rule-title">Admin User</div><div class="rule-meta">145 actions this week</div></div></div>
                        <div class="pill success">+5%</div>
                    </div>
                    <div class="rule">
                        <div class="rule-info"><span class="user-avatar" style="background:#e0f2fe; color:#075985;">H</span>
                            <div><div class="rule-title">Assistant Admin</div><div class="rule-meta">98 actions this week</div></div></div>
                        <div class="pill success">+3%</div>
                    </div>
                    <div class="rule">
                        <div class="rule-info"><span class="user-avatar" style="background:#ecfccb; color:#166534;">P</span>
                            <div><div class="rule-title">Payroll Staff A</div><div class="rule-meta">87 actions this week</div></div></div>
                        <div class="pill failure">-2%</div>
                    </div>
                    <div class="rule">
                        <div class="rule-info"><span class="user-avatar" style="background:#fef3c7; color:#92400e;">F</span>
                            <div><div class="rule-title">Foreman Mike</div><div class="rule-meta">65 actions this week</div></div></div>
                        <div class="pill success">+8%</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel" id="alertsPanel" style="display:none;">
            <div class="panel-header">
                <div class="panel-title">Configured Alert Rules</div>
                <button class="btn-action" onclick="alert('Add New Rule placeholder')"><i class="fas fa-plus"></i> Add New Rule</button>
            </div>
            <div id="rulesList"></div>
        </div>
    </div>
<?php if (!$embeddedDashboard): ?>
</body>
</html>
<?php endif; ?>

