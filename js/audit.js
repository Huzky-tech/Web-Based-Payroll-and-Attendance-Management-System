

        let logs = [];

        async function fetchLogs() {
            try {
                console.log('Fetching logs...');
                const response = await fetch('../api/get_audit_logs.php');
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                logs = await response.json();
                console.log('Logs fetched:', logs.length);
                const totalLogsEl = document.getElementById("totalLogs");
                if (totalLogsEl) {
                    totalLogsEl.textContent = logs.length;
                } else {
                    console.error("Element #totalLogs not found");
                }
                renderLogs();
            } catch (error) {
                console.error('Error fetching logs:', error);
            }
        }

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
                    <td>${escapeHTML(log.details)}</td>
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

        function escapeHTML(str = '') {
            return str.replace(/[&<>"']/g, m => ({
                '&':'&amp;', '<':'<', '>':'>', '"':'"', "'":'&#039;'
            })[m]);
        }

        function filterLogs() {
            const term = document.getElementById('searchInput').value.toLowerCase();
            const safe = v => (v ?? '').toLowerCase();
            const filtered = logs.filter(l =>
                safe(l.user).includes(term) ||
                safe(l.action).includes(term) ||
                safe(l.resource).includes(term) ||
                safe(l.details).includes(term)
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

        async function fetchAnalytics() {
            try {
                const response = await fetch('../api/get_audit_analytics.php');
                const data = await response.json();
                renderAnalytics(data);
            } catch (error) {
                console.error('Error fetching analytics:', error);
            }
        }

        function renderAnalytics(data) {
            // Update trends
            const trendsContainer = document.getElementById('trendBars');
            trendsContainer.innerHTML = '';
            const maxCount = data.trends.length ? Math.max(...data.trends.map(t => t.count)) : 0;
            data.trends.forEach(trend => {
                const width = maxCount > 0 ? (trend.count / maxCount) * 100 : 0;
                const item = document.createElement('div');
                item.className = 'bar-item';
                item.innerHTML = `<span class="bar-label">${trend.day}</span><div class="bar-track"><div class="bar-fill" style="width:${width}%; background:#f59e0b;"></div></div>`;
                trendsContainer.appendChild(item);
            });

            // Update action distribution
            const actionContainer = document.getElementById('actionBars');
            actionContainer.innerHTML = '';
            const maxAction = data.actionDistribution.length ? Math.max(...data.actionDistribution.map(a => a.count)) : 0;
            data.actionDistribution.slice(0, 5).forEach(action => { // Top 5
                const width = maxAction > 0 ? (action.count / maxAction) * 100 : 0;
                const item = document.createElement('div');
                item.className = 'bar-item';
                item.innerHTML = `<span class="bar-label">${action.action}</span><div class="bar-track"><div class="bar-fill" style="width:${width}%; background:#2563eb;"></div></div>`;
                actionContainer.appendChild(item);
            });

            // Update top users
            const usersContainer = document.getElementById('topUsersGrid');
            usersContainer.innerHTML = '';
            data.topUsers.forEach(user => {
                const div = document.createElement('div');
                div.className = 'rule';
                div.innerHTML = `
                    <div class="rule-info"><span class="user-avatar" style="background:#fef3c7; color:#b45309;">${user.user.charAt(0).toUpperCase()}</span>
                        <div><div class="rule-title">${user.user}</div><div class="rule-meta">${user.actions} actions this week</div></div></div>
                    <div class="pill success">+0%</div>
                `;
                usersContainer.appendChild(div);
            });
        }

        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
            document.getElementById('activityPanel').style.display = tab === 'activity' ? 'block' : 'none';
            document.getElementById('analyticsPanel').style.display = tab === 'analytics' ? 'block' : 'none';
            document.getElementById('alertsPanel').style.display = tab === 'alerts' ? 'block' : 'none';
            if (tab === 'analytics') {
                fetchAnalytics();
            }
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
            fetchLogs();
            renderRules();
        });
   