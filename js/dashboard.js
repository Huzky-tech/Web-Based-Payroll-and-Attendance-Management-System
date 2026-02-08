document.addEventListener('DOMContentLoaded', () => {
    console.log('Dashboard script loaded');

    /* ===============================
       FETCH DASHBOARD SUMMARY
       =============================== */
    fetch('../api/get_dashboard_summary.php')
        .then(res => res.json())
        .then(data => {
            // Populate summary grid
            document.querySelectorAll('.summary-value')[0].textContent = data.summary.total_users;
            document.querySelectorAll('.summary-value')[1].textContent = data.summary.system_health;
            document.querySelectorAll('.summary-value')[2].textContent = data.summary.last_backup;
            document.querySelectorAll('.summary-value')[3].textContent = data.summary.active_users;

            // Populate KPIs
            document.querySelector('.kpi-active-sites').textContent = data.kpis.active_sites;
            document.querySelector('.kpi-at-capacity').innerHTML =
                `<i class="fas fa-circle-check" style="color:#16a34a;"></i> ${data.kpis.at_capacity}`;
            document.querySelector('.kpi-needs-workers').innerHTML =
                `<i class="fas fa-circle-exclamation" style="color:#d97706;"></i> ${data.kpis.needs_workers}`;
            document.querySelector('.kpi-attendance').innerHTML =
                `<span style="color:#6d28d9;">${data.kpis.avg_attendance}%</span>`;

            // Populate site grid
            const siteGrid = document.querySelector('.site-grid');
            siteGrid.innerHTML = '';
            data.sites.forEach(site => {
                const workersNeeded = site.Required_Workers - site.Current_Workers;
                const badgeClass = workersNeeded > 0 ? 'badge amber' : 'badge blue';
                const badgeText = workersNeeded > 0
                    ? `<i class="fas fa-exclamation-circle"></i> Needs ${workersNeeded} Worker${workersNeeded > 1 ? 's' : ''}`
                    : `<i class="fas fa-users"></i> At Capacity`;
                const workerPercent = Math.min((site.Current_Workers / site.Required_Workers) * 100, 100);

                const siteCard = `
                    <div class="site-card">
                        <div class="site-name">${site.Site_Name}</div>
                        <div class="site-meta"><i class="fas fa-location-dot"></i>${site.Location}</div>
                        <div class="site-row">
                            <span class="site-label">Workers</span>
                            <span class="site-value">${site.Current_Workers} / ${site.Required_Workers}</span>
                        </div>
                        <div class="bar-track"><div class="bar-fill bar-amber" style="width:${workerPercent}%;"></div></div>
                        <div class="site-row" style="margin-top:10px;">
                            <span class="site-label">Attendance Rate</span>
                            <span class="site-value" style="color:#16a34a;">${site.attendance_rate}%</span>
                        </div>
                        <div class="bar-track"><div class="bar-fill bar-green" style="width:${site.attendance_rate}%;"></div></div>
                        <div class="site-footer">
                            <div class="manager">${site.Site_Manager}</div>
                            <div class="${badgeClass}">${badgeText}</div>
                        </div>
                    </div>
                `;
                siteGrid.innerHTML += siteCard;
            });

            // Populate recent activity
            const tbody = document.querySelector('.table-card tbody');
            tbody.innerHTML = '';
            data.recent_activity.forEach(log => {
                tbody.innerHTML += `
                    <tr>
                        <td>${log.Action}</td>
                        <td>${log.email}</td>
                        <td>${log.Details ?? '-'}</td>
                        <td>${log.Date}</td>
                    </tr>
                `;
            });
        })
        .catch(err => console.error('Dashboard summary error:', err));
});
