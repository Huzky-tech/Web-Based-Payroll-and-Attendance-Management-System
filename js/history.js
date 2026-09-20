const historyState = {
    logs: [],
    projects: [],
    projectSummary: null,
    activeAction: null,
    activeProject: null,
    tab: 'action',
    filter: 'all',
    search: '',
    projectSearch: ''
};

function escapeHistoryHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function normalizeText(value) {
    return String(value ?? '').toLowerCase();
}

function getLogCategory(log) {
    return log.category || classifyHistoryLog(log);
}

function classifyHistoryLog(log) {
    const haystack = [
        log.action,
        log.details,
        log.resource
    ].map(normalizeText).join(' ');

    if (haystack.includes('payroll')) {
        return 'payroll';
    }
    if (haystack.includes('worker') || haystack.includes('employee')) {
        return 'workers';
    }
    if (haystack.includes('site') || haystack.includes('project')) {
        return 'sites';
    }
    return 'general';
}

function iconMarkupForCategory(category) {
    if (category === 'payroll') {
        return '<i class="fa-solid fa-dollar-sign"></i>';
    }
    if (category === 'workers') {
        return '<i class="fa-solid fa-users"></i>';
    }
    if (category === 'sites') {
        return '<i class="fa-solid fa-building"></i>';
    }
    return '<i class="fa-regular fa-calendar"></i>';
}

function titleForLog(log) {
    if (String(log.title || '').trim() !== '') {
        return log.title;
    }
    return log.action || 'Completed Action';
}

function subtitleForLog(log, category) {
    if (String(log.site || '').trim() !== '') {
        return log.site;
    }
    if (category === 'payroll') {
        return 'Payroll History';
    }
    if (category === 'workers') {
        return 'Worker History';
    }
    if (category === 'sites') {
        return 'Project / Site History';
    }
    return log.user || 'System History';
}

function descriptionForLog(log) {
    if (String(log.details || '').trim() !== '') {
        return log.details;
    }
    if (String(log.description || '').trim() !== '') {
        return log.description;
    }
    return `${log.action || 'Action'} recorded by ${log.user || 'System'}.`;
}

function formatHistoryDate(value) {
    if (!value) {
        return 'Unknown time';
    }

    const date = new Date(value.replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('en-PH', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    }).format(date);
}

function getProjectHistoryLogs() {
    return historyState.logs.filter((log) => getLogCategory(log) === 'sites');
}

function getVisibleProjects() {
    return historyState.projects.filter((project) => {
        const searchHaystack = [
            project.title,
            project.location,
            project.manager,
            project.description,
            project.status
        ].map(normalizeText).join(' ');

        return !historyState.projectSearch || searchHaystack.includes(historyState.projectSearch);
    });
}

function getVisibleHistoryLogs() {
    const source = historyState.tab === 'project' ? getProjectHistoryLogs() : historyState.logs;

    return source.filter((log) => {
        const category = getLogCategory(log);
        const matchesFilter = historyState.filter === 'all' || category === historyState.filter;
        const searchHaystack = [
            log.title,
            log.site,
            log.description,
            log.performed_by,
            log.action,
            log.details,
            log.user,
            log.resource,
            category
        ].map(normalizeText).join(' ');
        const matchesSearch = !historyState.search || searchHaystack.includes(historyState.search);

        return matchesFilter && matchesSearch;
    });
}

function updateHistoryStats() {
    const payrollCount = historyState.logs.filter((log) => getLogCategory(log) === 'payroll').length;
    const workerCount = historyState.logs.filter((log) => getLogCategory(log) === 'workers').length;
    const siteCount = historyState.logs.filter((log) => getLogCategory(log) === 'sites').length;

    const totalEl = document.getElementById('historyTotalActions');
    const payrollEl = document.getElementById('historyPayrollActions');
    const workerEl = document.getElementById('historyWorkerActions');
    const siteEl = document.getElementById('historySiteActions');

    if (totalEl) totalEl.textContent = historyState.logs.length;
    if (payrollEl) payrollEl.textContent = payrollCount;
    if (workerEl) workerEl.textContent = workerCount;
    if (siteEl) siteEl.textContent = siteCount;
}

function renderHistoryList() {
    const listEl = document.getElementById('historyList');
    if (!listEl) {
        return;
    }

    const visibleLogs = getVisibleHistoryLogs();
    if (visibleLogs.length === 0) {
        listEl.innerHTML = '<div class="history-empty">No history records match the current filters.</div>';
        return;
    }

    listEl.innerHTML = visibleLogs.map((log) => {
        const category = log.category || classifyHistoryLog(log);
        const performedBy = log.performed_by || log.user || 'System';

        return `
            <div class="history-card">
                <div class="history-top">
                    <div class="history-left">
                        <div class="history-icon ${category}">
                            ${iconMarkupForCategory(category)}
                        </div>

                        <div class="history-info">
                            <h3>${escapeHistoryHtml(titleForLog(log))}</h3>
                            <div class="site-name">${escapeHistoryHtml(subtitleForLog(log, category))}</div>
                            <div class="description">${escapeHistoryHtml(descriptionForLog(log))}</div>
                        </div>
                    </div>

                    <div class="history-right">
                        <div class="time">
                            <i class="fa-regular fa-clock"></i>
                            ${escapeHistoryHtml(formatHistoryDate(log.ts))}
                        </div>

                        <div class="status">
                            <i class="fa-regular fa-circle-check"></i>
                        </div>
                    </div>
                </div>

                <div class="history-bottom">
                    <div class="history-performed">
                        Performed by ${escapeHistoryHtml(performedBy)}
                    </div>

                    <button class="history-view-link" type="button" data-history-title="${escapeHistoryHtml(titleForLog(log))}" data-history-ts="${escapeHistoryHtml(log.ts || '')}">
                        <i class="fa-regular fa-eye"></i>
                        View Details
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

function formatProjectPayroll(value) {
    return `PHP ${Number(value || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    })}`;
}

function formatProjectDateRange(project) {
    const start = project.start_date || 'Unknown';
    const end = project.end_date || 'Ongoing';
    return `${start} -> ${end}`;
}

function getProjectByTitle(title) {
    return historyState.projects.find((project) => String(project.title || '') === String(title || '')) || null;
}

function setTextContent(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = value;
    }
}

function findActionLog(title, timestamp) {
    return historyState.logs.find((log) => (
        String(titleForLog(log)) === String(title || '')
        && String(log.ts || '') === String(timestamp || '')
    )) || null;
}

function openActionModal(log) {
    const modal = document.getElementById('actionDetailsModal');
    if (!modal || !log) {
        return;
    }

    historyState.activeAction = log;

    setTextContent('actionDetailsType', titleForLog(log));
    setTextContent('actionDetailsEntity', log.site || subtitleForLog(log, getLogCategory(log)));
    setTextContent('actionDetailsDescription', descriptionForLog(log));
    setTextContent('actionDetailsTimestamp', formatHistoryDate(log.ts));
    setTextContent('actionDetailsPerformedBy', log.performed_by || log.user || 'System');
    setTextContent('actionDetailsStatusText', log.status || 'Completed');

    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('action-modal-open');
}

function closeActionModal() {
    const modal = document.getElementById('actionDetailsModal');
    if (!modal) {
        return;
    }

    historyState.activeAction = null;
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('action-modal-open');
}

function openProjectModal(project) {
    const modal = document.getElementById('projectDetailsModal');
    if (!modal || !project) {
        return;
    }

    historyState.activeProject = project;

    setTextContent('projectDetailsTitle', project.title || 'Project Details');
    setTextContent('projectDetailsSubtitle', `${project.status || 'Project'} Project Details`);
    setTextContent('projectDetailsLocation', project.location || 'No location provided');
    setTextContent('projectDetailsStartDate', project.start_date || 'Not set');
    setTextContent('projectDetailsDuration', project.duration_days ? `${project.duration_days} days` : 'Not set');
    setTextContent('projectDetailsType', project.project_type || 'Construction');
    setTextContent('projectDetailsManager', project.manager || 'Not assigned');
    setTextContent('projectDetailsCompletionDate', project.end_date || 'Ongoing');
    setTextContent('projectDetailsCompletedBy', project.completed_by || 'Not recorded');
    setTextContent('projectDetailsRequiredWorkers', Number(project.required_workers || 0).toLocaleString('en-PH'));
    setTextContent('projectDetailsDescription', project.description || 'No description provided.');
    setTextContent('projectDetailsWorkers', Number(project.workers || 0).toLocaleString('en-PH'));
    setTextContent('projectDetailsPayroll', formatProjectPayroll(project.payroll));
    setTextContent('projectDetailsAttendance', Number(project.attendance_records || 0).toLocaleString('en-PH'));

    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('project-modal-open');
}

function closeProjectModal() {
    const modal = document.getElementById('projectDetailsModal');
    if (!modal) {
        return;
    }

    historyState.activeProject = null;
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('project-modal-open');
}

function renderProjectStats() {
    const summary = historyState.projectSummary || {
        completed_projects: 0,
        total_workers: 0,
        total_payroll: 0
    };

    const completedEl = document.getElementById('projectCompletedCount');
    const workersEl = document.getElementById('projectWorkerCount');
    const payrollEl = document.getElementById('projectPayrollTotal');

    if (completedEl) completedEl.textContent = summary.completed_projects;
    if (workersEl) workersEl.textContent = summary.total_workers;
    if (payrollEl) payrollEl.textContent = formatProjectPayroll(summary.total_payroll);
}

function renderProjectGrid() {
    const gridEl = document.getElementById('projectHistoryGrid');
    if (!gridEl) {
        return;
    }

    const visibleProjects = getVisibleProjects();
    if (visibleProjects.length === 0) {
        gridEl.innerHTML = '<div class="history-empty">No projects match the current search.</div>';
        return;
    }

    gridEl.innerHTML = visibleProjects.map((project) => {
        const payrollDetail = window.historyHidePayroll ? '' : `
                <div>
                    <i class="fa-solid fa-dollar-sign"></i>
                    ${escapeHistoryHtml(formatProjectPayroll(project.payroll))}
                </div>`;

        return `
        <div class="project-card">
            <div class="project-top">
                <div class="project-title">${escapeHistoryHtml(project.title)}</div>
                <div class="project-status">${escapeHistoryHtml(project.status || 'In Progress')}</div>
            </div>

            <div class="project-location">
                <i class="fa-solid fa-location-dot"></i>
                ${escapeHistoryHtml(project.location)}
            </div>

            <div class="project-description">
                ${escapeHistoryHtml(project.description)}
            </div>

            <div class="project-info-grid">
                <div class="project-info-box">
                    <span>Duration</span>
                    <strong>${escapeHistoryHtml(project.duration_days ? `${project.duration_days} days` : 'Not set')}</strong>
                </div>

                <div class="project-info-box">
                    <span>Workers</span>
                    <strong>${escapeHistoryHtml(project.workers)}</strong>
                </div>
            </div>

            <div class="project-details">
                <div>
                    <i class="fa-regular fa-calendar"></i>
                    ${escapeHistoryHtml(formatProjectDateRange(project))}
                </div>

                <div>
                    <i class="fa-regular fa-user"></i>
                    ${escapeHistoryHtml(project.manager)}
                </div>

                ${payrollDetail}
            </div>

            <button class="view-btn" type="button" data-project-title="${escapeHistoryHtml(project.title)}">
                <i class="fa-regular fa-eye"></i>
                View Project Details
            </button>
        </div>
    `;
    }).join('');
}

function syncActiveTab() {
    document.querySelectorAll('.history-tab').forEach((tabButton) => {
        tabButton.classList.toggle('active', tabButton.dataset.tab === historyState.tab);
    });

    document.getElementById('actionHistoryView')?.classList.toggle('active', historyState.tab === 'action');
    document.getElementById('projectHistoryView')?.classList.toggle('active', historyState.tab === 'project');
}

function syncActiveFilter() {
    document.querySelectorAll('.filter-btn').forEach((filterButton) => {
        filterButton.classList.toggle('active', filterButton.dataset.filter === historyState.filter);
    });
}

function bindHistoryEvents() {
    document.querySelectorAll('.history-tab').forEach((tabButton) => {
        tabButton.addEventListener('click', () => {
            historyState.tab = tabButton.dataset.tab || 'action';
            syncActiveTab();
            if (historyState.tab === 'project') {
                renderProjectStats();
                renderProjectGrid();
            } else {
                renderHistoryList();
            }
        });
    });

    document.querySelectorAll('.filter-btn').forEach((filterButton) => {
        filterButton.addEventListener('click', () => {
            historyState.filter = filterButton.dataset.filter || 'all';
            syncActiveFilter();
            renderHistoryList();
        });
    });

    document.getElementById('historySearchInput')?.addEventListener('input', (event) => {
        historyState.search = normalizeText(event.target.value.trim());
        renderHistoryList();
    });

    document.getElementById('projectHistorySearchInput')?.addEventListener('input', (event) => {
        historyState.projectSearch = normalizeText(event.target.value.trim());
        renderProjectGrid();
    });

    document.getElementById('closeActionDetailsModal')?.addEventListener('click', closeActionModal);
    document.getElementById('closeActionDetailsFooter')?.addEventListener('click', closeActionModal);
    document.getElementById('closeProjectDetailsModal')?.addEventListener('click', closeProjectModal);
    document.getElementById('closeProjectDetailsFooter')?.addEventListener('click', closeProjectModal);

    document.getElementById('actionDetailsModal')?.addEventListener('click', (event) => {
        if (event.target.id === 'actionDetailsModal') {
            closeActionModal();
        }
    });

    document.getElementById('projectDetailsModal')?.addEventListener('click', (event) => {
        if (event.target.id === 'projectDetailsModal') {
            closeProjectModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeActionModal();
            closeProjectModal();
        }
    });

    document.addEventListener('click', (event) => {
        const historyViewButton = event.target.closest('.history-view-link');
        if (historyViewButton) {
            const title = historyViewButton.dataset.historyTitle || '';
            const timestamp = historyViewButton.dataset.historyTs || '';
            const log = findActionLog(title, timestamp);
            openActionModal(log);
        }

        const viewButton = event.target.closest('.view-btn');
        if (viewButton) {
            const projectTitle = viewButton.dataset.projectTitle || '';
            const project = getProjectByTitle(projectTitle);
            openProjectModal(project);
        }
    });
}

async function fetchHistoryLogs() {
    historyState.logs = Array.isArray(window.historyActionData) ? window.historyActionData : [];
    updateHistoryStats();
    renderHistoryList();
}

function loadProjectHistoryData() {
    const payload = window.projectHistoryData || {};
    historyState.projectSummary = payload.summary || null;
    historyState.projects = Array.isArray(payload.projects) ? payload.projects : [];
    renderProjectStats();
    renderProjectGrid();
}

document.addEventListener('DOMContentLoaded', () => {
    if (!document.querySelector('.history-container')) {
        return;
    }

    bindHistoryEvents();
    syncActiveTab();
    fetchHistoryLogs();
    loadProjectHistoryData();
});
