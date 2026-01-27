<?php
include '../api/connection/db_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/audit.css">
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
                        <div class="bar-list">
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
                        <div class="bar-list">
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
                    <div style="display:grid; grid-template-columns: repeat(4,1fr); gap:12px;">
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

    <script>
        const logs = [
            { ts: '2023-07-10 14:30:25', user: 'Admin User', role: 'Admin', action: 'User Role Update', resource: 'John Doe (User)', status: 'success', severity: 'Medium', details: 'Changed role to Manager' },
            { ts: '2023-07-10 14:15:10', user: 'HR Manager', role: 'HR', action: 'New Employee Added', resource: 'Sarah Smith', status: 'success', severity: 'Info', details: 'Employee onboarded' },
            { ts: '2023-07-10 13:45:00', user: 'System', role: 'System', action: 'Failed Login Attempt', resource: 'Auth Service', status: 'failure', severity: 'High', details: 'IP 10.0.0.24 blocked' },
            { ts: '2023-07-10 12:30:15', user: 'Payroll Staff A', role: 'Payroll', action: 'Payroll Processed', resource: 'Site A Payroll', status: 'success', severity: 'Medium', details: 'Batch 2023-07-10' },
            { ts: '2023-07-10 11:20:05', user: 'Foreman Mike', role: 'Foreman', action: 'Attendance Modified', resource: 'Worker #452', status: 'warning', severity: 'Medium', details: 'Adjusted hours' },
            { ts: '2023-07-10 10:05:30', user: 'Admin User', role: 'Admin', action: 'System Config Change', resource: 'Global Settings', status: 'success', severity: 'High', details: 'Password policy update' },
            { ts: '2023-07-10 09:15:00', user: 'HR Manager', role: 'HR', action: 'Site Assignment', resource: 'Downtown Project', status: 'success', severity: 'Info', details: 'Reassigned staff' },
            { ts: '2023-07-10 08:00:00', user: 'System', role: 'System', action: 'Database Backup', resource: 'Main DB', status: 'success', severity: 'Info', details: 'Full backup' }
        ];

        const rules = [
            { title: 'Multiple Failed Logins', meta: 'Failed Login > 5 · Threshold: 5 · Period: 10 mins', active: true },
            { title: 'High Value Payroll', meta: 'Payroll Amount > $100k · Threshold: 100000 · Period: Transaction', active: true },
            { title: 'Admin Role Changes', meta: 'Role Change = Admin · Threshold: 1 · Period: Immediate', active: true }
        ];

        function updateDateTime() {
            const now = new Date();
            const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
            const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            const day = days[now.getDay()];
            const month = months[now.getMonth()];
            const date = now.getDate();
            const year = now.getFullYear();
            let hours = now.getHours();
            const minutes = now.getMinutes();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12; hours = hours ? hours : 12;
            const minutesStr = minutes < 10 ? '0' + minutes : minutes;
            document.getElementById('currentDate').textContent = `${day}, ${month} ${date}, ${year}`;
            document.getElementById('currentTime').textContent = `${hours}:${minutesStr} ${ampm}`;
        }

        function renderLogs(list = logs) {
            const body = document.getElementById('logBody');
            body.innerHTML = '';
            list.forEach(log => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${log.ts}</td>
                    <td><strong>${log.user}</strong><div style="font-size:12px;color:#6b7280;">${log.role}</div></td>
                    <td>${log.action}</td>
                    <td>${log.resource}</td>
                    <td>${pill(log.status)}</td>
                    <td>${pillSeverity(log.severity)}</td>
                    <td>${log.details}</td>
                `;
                body.appendChild(row);
            });
        }

        function pill(status) {
            if (status === 'success') return '<span class="pill success">Success</span>';
            if (status === 'failure') return '<span class="pill failure">Failure</span>';
            if (status === 'warning') return '<span class="pill warning">Warning</span>';
            return '<span class="pill info">Info</span>';
        }

        function pillSeverity(sev) {
            const map = { High: 'failure', Medium: 'warning', Info: 'info' };
            const cls = map[sev] || 'info';
            return `<span class="pill ${cls}">${sev}</span>`;
        }

        function filterLogs() {
            const term = document.getElementById('searchInput').value.toLowerCase();
            const filtered = logs.filter(l =>
                l.user.toLowerCase().includes(term) ||
                l.action.toLowerCase().includes(term) ||
                l.resource.toLowerCase().includes(term) ||
                l.details.toLowerCase().includes(term)
            );
            renderLogs(filtered);
        }

        function renderRules() {
            const container = document.getElementById('rulesList');
            container.innerHTML = '';
            rules.forEach(rule => {
                const div = document.createElement('div');
                div.className = 'rule';
                div.innerHTML = `
                    <div class="rule-info">
                        <i class="fas fa-bell" style="color:#d97706;"></i>
                        <div>
                            <div class="rule-title">${rule.title}</div>
                            <div class="rule-meta">${rule.meta}</div>
                        </div>
                    </div>
                    <span class="badge-active">${rule.active ? 'Active' : 'Paused'}</span>
                `;
                container.appendChild(div);
            });
        }

        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
            document.getElementById('activityPanel').style.display = tab === 'activity' ? 'block' : 'none';
            document.getElementById('analyticsPanel').style.display = tab === 'analytics' ? 'block' : 'none';
            document.getElementById('alertsPanel').style.display = tab === 'alerts' ? 'block' : 'none';
        }

        function exportCSV() {
            const rows = [['Timestamp','User','Action','Resource','Status','Severity','Details']];
            logs.forEach(l => rows.push([l.ts, l.user, l.action, l.resource, l.status, l.severity, l.details]));
            const csv = rows.map(r => r.map(v => `"${String(v).replace(/"/g,'""')}"`).join(',')).join('\\n');
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url; a.download = 'audit-logs.csv'; document.body.appendChild(a); a.click(); document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        function createAlert() { alert('Create Alert placeholder'); }

        document.addEventListener('DOMContentLoaded', () => {
            updateDateTime();
            setInterval(updateDateTime, 60000);
            renderLogs();
            renderRules();
        });
    </script>
</body>
</html>
