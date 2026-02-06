document.addEventListener('DOMContentLoaded', () => {
    console.log('Dashboard script loaded');

    /* ===============================
       ACTIVE SITES & KPIs
       =============================== */
    fetch('../api/get_sites.php')
        .then(res => res.json())
        .then(sites => {
            const activeSites = sites.filter(s => s.status === 'active');
            const atCapacity = sites.filter(s => s.status === 'at_capacity');
            const needsWorkers = sites.filter(s => s.status === 'needs_workers');

            document.querySelector('.kpi-active-sites').textContent = activeSites.length;
            document.querySelector('.kpi-at-capacity').innerHTML =
                `<i class="fas fa-circle-check" style="color:#16a34a;"></i> ${atCapacity.length}`;
            document.querySelector('.kpi-needs-workers').innerHTML =
                `<i class="fas fa-circle-exclamation" style="color:#d97706;"></i> ${needsWorkers.length}`;
        })
        .catch(err => console.error('Sites error:', err));

  /* ===============================
   WORKERS PER SITE & NEEDS WORKERS KPI
   =============================== */
fetch('../api/get_workers.php')
    .then(res => res.json())
    .then(workers => {
        const siteCounts = {};
        let totalWorkersNeeded = 0; // Total for KPI

        // Count workers per site
        workers.forEach(w => {
            if (!siteCounts[w.site_name]) {
                siteCounts[w.site_name] = { count: 0, attendance: 0 };
            }
            siteCounts[w.site_name].count++;
            siteCounts[w.site_name].attendance += Number(w.attendance || 0);
        });

        // Update each site card
        document.querySelectorAll('.site-card').forEach(card => {
            const siteName = card.querySelector('.site-name').textContent.trim();
            const valueEl = card.querySelector('.site-value');
            const bars = card.querySelectorAll('.bar-fill');
            const badge = card.querySelector('.badge');

            // Extract capacity from text "0 / 30"
            const capacity = parseInt(valueEl.textContent.split('/')[1]) || 1;

            const data = siteCounts[siteName] || { count: 0, attendance: 0 };
            const percent = Math.min((data.count / capacity) * 100, 100);
            const attendance = data.count
                ? Math.round((data.attendance / data.count) * 100)
                : 0;

            // Update worker count and bars
            valueEl.textContent = `${data.count} / ${capacity}`;
            bars[0].style.width = percent + '%'; // worker fill
            bars[1].style.width = attendance + '%'; // attendance fill

            // Calculate workers needed
            const workersNeeded = capacity - data.count;
            if (workersNeeded > 0) {
                badge.className = 'badge amber';
                badge.innerHTML = `<i class="fas fa-exclamation-circle"></i> Needs ${workersNeeded} Worker${workersNeeded > 1 ? 's' : ''}`;
                totalWorkersNeeded += workersNeeded;
            } else {
                badge.className = 'badge blue';
                badge.innerHTML = `<i class="fas fa-users"></i> At Capacity`;
            }
        });

        // Update top KPI strip
        document.querySelector('.kpi-needs-workers').innerHTML =
            `<i class="fas fa-circle-exclamation" style="color:#d97706;"></i> ${totalWorkersNeeded}`;
    })
    .catch(err => console.error('Workers error:', err));

    /* ===============================
       RECENT ACTIVITY (AUDIT LOGS)
       =============================== */
    fetch('../api/get_audit_logs.php')
        .then(res => res.json())
        .then(logs => {
            const tbody = document.querySelector('.table-card tbody');
            tbody.innerHTML = '';

            logs.slice(0, 5).forEach(log => {
                tbody.innerHTML += `
                    <tr>
                        <td>${log.action}</td>
                        <td>${log.user}</td>
                        <td>${log.target ?? '-'}</td>
                        <td>${log.time}</td>
                    </tr>
                `;
            });
        })
        .catch(err => console.error('Audit logs error:', err));
});
