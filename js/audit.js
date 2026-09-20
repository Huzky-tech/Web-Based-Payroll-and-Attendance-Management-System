let logs = [];
let currentPage = 1;
const currentLimit = 10;
let totalLogs = 0;
let totalPages = 1;
let activeLogRequest = null;

async function fetchLogs() {
    if (activeLogRequest) activeLogRequest.abort();
    const requestController = new AbortController();
    activeLogRequest = requestController;

    try {
        const params = new URLSearchParams();
        params.set('page', currentPage);
        params.set('limit', currentLimit);

        const searchVal = document.getElementById('searchInput')?.value?.trim();
        if (searchVal) params.set('search', searchVal);

        const actionVal = document.getElementById('actionFilter')?.value;
        if (actionVal) params.set('action_filter', actionVal);

        const statusVal = document.getElementById('statusFilter')?.value;
        if (statusVal) params.set('status_filter', statusVal);

        const severityVal = document.getElementById('severityFilter')?.value;
        if (severityVal) params.set('severity_filter', severityVal);

        const startDate = document.getElementById('startDate')?.value;
        if (startDate) params.set('start_date', startDate);

        const response = await fetch(`../api/get_audit_logs.php?${params.toString()}`, {
            signal: requestController.signal
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const data = await response.json();

        if (data.error) {
            console.error('API Error:', data.error);
            showEmptyState('Error loading logs: ' + data.error);
            return;
        }

        logs = data.logs || [];
        totalLogs = data.total || 0;
        totalPages = data.totalPages || 1;

        const totalLogsEl = document.getElementById('totalLogs');
        if (totalLogsEl) {
            totalLogsEl.textContent = totalLogs;
        }

        renderLogs();
        renderPagination();
    } catch (error) {
        if (error.name === 'AbortError') return;
        console.error('Error fetching logs:', error);
        showEmptyState('Failed to load audit logs. Please try again.');
    } finally {
        if (activeLogRequest === requestController) activeLogRequest = null;
    }
}

function showEmptyState(message) {
    const body = document.getElementById('logBody');
    if (!body) return;
    body.innerHTML = `
        <tr>
            <td colspan="7" style="text-align:center; padding:40px 20px; color:#9ca3af;">
                <i class="fas fa-inbox" style="font-size:32px; display:block; margin-bottom:12px;"></i>
                ${message || 'No audit logs found.'}
            </td>
        </tr>
    `;
}

const rules = [
    { title: 'Multiple Failed Logins', meta: 'Failed Login > 5 · Threshold: 5 · Period: 10 mins', active: true },
    { title: 'High Value Payroll', meta: 'Payroll Amount > $100k · Threshold: 100000 · Period: Transaction', active: true },
    { title: 'Admin Role Changes', meta: 'Role Change = Admin · Threshold: 1 · Period: Immediate', active: true }
];

function updateDateTime() {
    const dateEl = document.getElementById('currentDate');
    const timeEl = document.getElementById('currentTime');
    if (!dateEl || !timeEl) {
        return;
    }

    const now = new Date();
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const day = days[now.getDay()];
    const month = months[now.getMonth()];
    const date = now.getDate();
    const year = now.getFullYear();
    let hours = now.getHours();
    const minutes = now.getMinutes();
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12;
    const minutesStr = minutes < 10 ? '0' + minutes : minutes;
    dateEl.textContent = `${day}, ${month} ${date}, ${year}`;
    timeEl.textContent = `${hours}:${minutesStr} ${ampm}`;
}

function renderLogs(list) {
    const body = document.getElementById('logBody');
    if (!body) return;

    const data = list || logs;

    if (!data || data.length === 0) {
        showEmptyState('No audit logs match your filters.');
        return;
    }

    body.innerHTML = '';
    data.forEach((log) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td style="white-space:nowrap;">${escapeHTML(formatDate(log.ts))}</td>
            <td><strong>${escapeHTML(log.user)}</strong><div style="font-size:12px;color:#6b7280;">${escapeHTML(log.role)}</div></td>
            <td>${escapeHTML(log.action)}</td>
            <td>${escapeHTML(log.resource)}</td>
            <td>${pill(log.status)}</td>
            <td>${pillSeverity(log.severity)}</td>
            <td style="max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${escapeHTML(log.details)}">${escapeHTML(log.details)}</td>
        `;
        body.appendChild(row);
    });
}

function formatDate(ts) {
    if (!ts) return '';
    const d = new Date(ts);
    if (isNaN(d.getTime())) return ts;
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const month = months[d.getMonth()];
    const day = String(d.getDate()).padStart(2, '0');
    const year = d.getFullYear();
    const hours = d.getHours();
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const h12 = hours % 12 || 12;
    return `${month} ${day}, ${year} ${h12}:${minutes} ${ampm}`;
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
    return `<span class="pill ${cls}">${escapeHTML(sev)}</span>`;
}

function escapeHTML(str = '') {
    return String(str).replace(/[&<>"']/g, (m) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[m]);
}

// ============ Filter Functions ============
function onSearchInput() {
    // Debounced search - wait for user to stop typing
    clearTimeout(window._searchTimer);
    window._searchTimer = setTimeout(() => {
        currentPage = 1;
        fetchLogs();
    }, 400);
}

function applyFilters() {
    currentPage = 1;
    fetchLogs();
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('actionFilter').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('severityFilter').value = '';
    document.getElementById('startDate').value = '';
    currentPage = 1;
    fetchLogs();
}

// ============ Pagination Functions ============
function renderPagination() {
    const infoEl = document.getElementById('paginationInfo');
    const prevBtn = document.getElementById('prevPage');
    const nextBtn = document.getElementById('nextPage');
    const pageNumbersEl = document.getElementById('pageNumbers');

    if (!infoEl || !prevBtn || !nextBtn || !pageNumbersEl) return;

    const start = totalLogs === 0 ? 0 : (currentPage - 1) * currentLimit + 1;
    const end = Math.min(currentPage * currentLimit, totalLogs);

    infoEl.textContent = `Showing ${start}-${end} of ${totalLogs}`;

    prevBtn.disabled = currentPage <= 1;
    nextBtn.disabled = currentPage >= totalPages;

    // Generate page numbers
    pageNumbersEl.innerHTML = '';
    const maxVisible = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let endPage = Math.min(totalPages, startPage + maxVisible - 1);

    if (endPage - startPage + 1 < maxVisible) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }

    if (startPage > 1) {
        const firstBtn = createPageButton(1);
        pageNumbersEl.appendChild(firstBtn);
        if (startPage > 2) {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'page-ellipsis';
            ellipsis.textContent = '...';
            pageNumbersEl.appendChild(ellipsis);
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        const btn = createPageButton(i);
        if (i === currentPage) btn.classList.add('active');
        pageNumbersEl.appendChild(btn);
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'page-ellipsis';
            ellipsis.textContent = '...';
            pageNumbersEl.appendChild(ellipsis);
        }
        const lastBtn = createPageButton(totalPages);
        pageNumbersEl.appendChild(lastBtn);
    }
}

function createPageButton(pageNum) {
    const btn = document.createElement('button');
    btn.className = 'page-num';
    btn.textContent = pageNum;
    btn.onclick = () => goToPage(pageNum);
    return btn;
}

function goToPage(page) {
    if (page < 1 || page > totalPages || page === currentPage) return;
    currentPage = page;
    fetchLogs();
    // Scroll to top of table
    const tableContainer = document.querySelector('.table-container');
    if (tableContainer) tableContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function changePage(delta) {
    goToPage(currentPage + delta);
}

document.addEventListener('DOMContentLoaded', () => {
    ['actionFilter', 'statusFilter', 'severityFilter', 'startDate'].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', () => {
            currentPage = 1;
            fetchLogs();
        });
    });

});

function renderRules() {
    const container = document.getElementById('rulesList');
    if (!container) return;
    container.innerHTML = '';
    rules.forEach((rule) => {
        const div = document.createElement('div');
        div.className = 'rule';
        div.innerHTML = `
            <div class="rule-info">
                <i class="fas fa-bell" style="color:#d97706;"></i>
                <div>
                    <div class="rule-title">${escapeHTML(rule.title)}</div>
                    <div class="rule-meta">${escapeHTML(rule.meta)}</div>
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
    const trendsContainer = document.getElementById('trendBars');
    const actionContainer = document.getElementById('actionBars');
    const usersContainer = document.getElementById('topUsersGrid');
    if (!trendsContainer || !actionContainer || !usersContainer) return;

    trendsContainer.innerHTML = '';
    const maxCount = data.trends.length ? Math.max(...data.trends.map((t) => t.count)) : 0;
    data.trends.forEach((trend) => {
        const width = maxCount > 0 ? (trend.count / maxCount) * 100 : 0;
        const item = document.createElement('div');
        item.className = 'bar-item';
        item.innerHTML = `<span class="bar-label">${escapeHTML(trend.day)}</span><div class="bar-track"><div class="bar-fill" style="width:${width}%; background:#f59e0b;"></div></div>`;
        trendsContainer.appendChild(item);
    });

    actionContainer.innerHTML = '';
    const maxAction = data.actionDistribution.length ? Math.max(...data.actionDistribution.map((a) => a.count)) : 0;
    data.actionDistribution.slice(0, 5).forEach((action) => {
        const width = maxAction > 0 ? (action.count / maxAction) * 100 : 0;
        const item = document.createElement('div');
        item.className = 'bar-item';
        item.innerHTML = `<span class="bar-label">${escapeHTML(action.action)}</span><div class="bar-track"><div class="bar-fill" style="width:${width}%; background:#2563eb;"></div></div>`;
        actionContainer.appendChild(item);
    });

    usersContainer.innerHTML = '';
    data.topUsers.forEach((user) => {
        const initial = (user.user || '?').charAt(0).toUpperCase();
        const colors = ['#fef3c7,#b45309', '#e0f2fe,#075985', '#ecfccb,#166534', '#fce7f3,#9d174d'];
        const colorPair = colors[Math.floor(Math.random() * colors.length)];
        const [bg, fg] = colorPair.split(',');
        const div = document.createElement('div');
        div.className = 'rule';
        div.innerHTML = `
            <div class="rule-info"><span class="user-avatar" style="background:${bg}; color:${fg};">${escapeHTML(initial)}</span>
                <div><div class="rule-title">${escapeHTML(user.user)}</div><div class="rule-meta">${escapeHTML(user.actions)} actions this week</div></div></div>
            <div class="pill success">+0%</div>
        `;
        usersContainer.appendChild(div);
    });
}

function switchTab(tab) {
    document.querySelectorAll('.tab').forEach((t) => t.classList.remove('active'));
    const activeTab = document.querySelector(`[data-tab="${tab}"]`);
    if (activeTab) activeTab.classList.add('active');

    const activityPanel = document.getElementById('activityPanel');
    const analyticsPanel = document.getElementById('analyticsPanel');
    const alertsPanel = document.getElementById('alertsPanel');
    if (activityPanel) activityPanel.style.display = tab === 'activity' ? 'block' : 'none';
    if (analyticsPanel) analyticsPanel.style.display = tab === 'analytics' ? 'block' : 'none';
    if (alertsPanel) alertsPanel.style.display = tab === 'alerts' ? 'block' : 'none';
    if (tab === 'analytics') {
        fetchAnalytics();
    }
}

function exportCSV() {
    if (!logs || logs.length === 0) {
        alert('No data to export.');
        return;
    }
    const rows = [['Timestamp', 'User', 'Role', 'Action', 'Resource', 'Status', 'Severity', 'Details']];
    logs.forEach((l) => rows.push([l.ts, l.user, l.role, l.action, l.resource, l.status, l.severity, l.details]));
    const csv = rows.map((r) => r.map((v) => `"${String(v).replace(/"/g, '""')}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'audit-logs.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function createAlert() {
    alert('Create Alert placeholder');
}

document.addEventListener('DOMContentLoaded', () => {
    updateDateTime();
    setInterval(updateDateTime, 60000);
    fetchLogs();
    renderRules();
});
