
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