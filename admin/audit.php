<?php
include '../api/connection/db_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - Philippians CDO</title>
    <link rel="stylesheet" href="../css/font-awesome.css">
    <link rel="stylesheet" href="../css/audit.css">
     <script src="../js/audit.js" defer></script>
</head>
<body>


    <!-- Main Content -->
    <div class="main-content">
        <div class="top-header">
            <div class="page-title">Audit Logs</div>
            <div class="header-right">
                <div class="date-time">
                    <div class="date" id="currentDate">Thursday, January 8, 2026</div>
                    <div class="time" id="currentTime">02:05 PM</div>
                </div>
                <button class="btn-light" onclick="exportCSV()"><i class="fas fa-file-export"></i>Export CSV</button>
                <button class="btn-action" onclick="createAlert()"><i class="fas fa-bell"></i>Create Alert</button>
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

        <div class="content-area">
            <div class="section-sub">Monitor system usage, security events, and administrative actions.</div>

            <div class="tabs">
                <button class="tab active" data-tab="activity" onclick="switchTab('activity')"><i class="fas fa-list"></i>Activity Logs</button>
                <button class="tab" data-tab="analytics" onclick="switchTab('analytics')"><i class="fas fa-chart-line"></i>Analytics & Trends</button>
                <button class="tab" data-tab="alerts" onclick="switchTab('alerts')"><i class="fas fa-bell"></i>Alert Rules</button>
            </div>

            <!-- Activity Logs -->
            <div class="panel" id="activityPanel">
                <div class="filter-row">
                    <input class="search-input" id="searchInput" placeholder="Search by user, action, resource, or IP address..." oninput="filterLogs()">
                    <button class="btn-light" onclick="alert('Filters panel placeholder');"><i class="fas fa-filter"></i>Filters</button>
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
            </div>

            <!-- Analytics -->
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
                                <div><div class="rule-title">HR Manager</div><div class="rule-meta">98 actions this week</div></div></div>
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

            <!-- Alert Rules -->
            <div class="panel" id="alertsPanel" style="display:none;">
                <div class="panel-header">
                    <div class="panel-title">Configured Alert Rules</div>
                    <button class="btn-action" onclick="alert('Add New Rule placeholder')"><i class="fas fa-plus"></i> Add New Rule</button>
                </div>
                <div id="rulesList"></div>
            </div>
        </div>
    </div>
</body>
</html>