const tkReportsState = {
    role: document.querySelector('[data-tk-reports-role]')?.dataset?.tkReportsRole
        || document.body?.dataset?.tkReportsRole
        || 'admin',
    items: [],
    stats: {},
    reportTypes: [],
    search: '',
    status: '',
    reportType: '',
    site: '',
    todayOnly: false,
    selectedReport: null,
};

function escapeTkHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function isAdminRole() {
    return ['admin', 'assistant', 'payroll', 'hr', 'reviewer'].includes(String(tkReportsState.role).toLowerCase());
}

function getTkReportSubject(report) {
    const subject = String(report?.subject || '').trim();
    const reportType = String(report?.report_type || '').trim();
    const withoutTrailingDate = subject.replace(/\s*-\s*\d{1,2}\/\d{1,2}\/\d{4}\s*$/, '').trim();
    return withoutTrailingDate || reportType || '-';
}

function getTkReportStatusLabel(status) {
    const value = String(status || '').trim();
    if (value === 'Pending') return 'For Review';
    if (value === 'Resolved') return 'Reviewed';
    return value || 'For Review';
}

function getFilteredTkReports() {
    const search = tkReportsState.search.toLowerCase();
    return tkReportsState.items.filter((item) => {
        const haystack = [
            item.site_name,
            item.timekeeper_name,
            item.report_type,
            item.subject,
            item.status,
        ].join(' ').toLowerCase();
        const itemStatus = String(item.status || '');
        const matchesStatus = !tkReportsState.status
            || itemStatus === tkReportsState.status
            || (tkReportsState.status === 'Reviewed' && itemStatus === 'Resolved');
        return (!search || haystack.includes(search))
            && (!tkReportsState.todayOnly || item.is_today)
            && (!tkReportsState.site || String(item.site_id) === tkReportsState.site)
            && matchesStatus;
    });
}

function renderTkStats() {
    const stats = tkReportsState.stats || {};
    document.querySelectorAll('[data-stat]').forEach((node) => {
        const key = node.dataset.stat;
        node.textContent = stats[key] ?? 0;
    });
    document.querySelectorAll('[data-stat-filter]').forEach((card) => {
        const filter = card.dataset.statFilter;
        const active = filter === 'today'
            ? tkReportsState.todayOnly
            : !tkReportsState.todayOnly && (filter === 'all' ? !tkReportsState.status : tkReportsState.status === filter);
        card.classList.toggle('active', active);
        card.setAttribute('aria-pressed', String(active));
    });
}

function populateTypeFilter() {
    const select = document.getElementById('tkReportsTypeFilter');
    if (!select) return;

    const current = select.value;
    const options = ['<option value="">All Report Types</option>']
        .concat((tkReportsState.reportTypes || []).map((type) =>
            `<option value="${escapeTkHtml(type)}">${escapeTkHtml(type)}</option>`
        ));

    select.innerHTML = options.join('');
    select.value = current;
}

function populateSiteFilter() {
    const select = document.getElementById('tkReportsSiteFilter');
    if (!select) return;
    const sites = new Map();
    tkReportsState.items.forEach((item) => {
        if (item.site_id && item.site_name) sites.set(String(item.site_id), item.site_name);
    });
    select.innerHTML = '<option value="">All Sites</option>' + Array.from(sites.entries())
        .sort((left, right) => String(left[1]).localeCompare(String(right[1])))
        .map(([id, name]) => `<option value="${escapeTkHtml(id)}">${escapeTkHtml(name)}</option>`)
        .join('');
    select.value = tkReportsState.site;
}

function renderTkReportsTable() {
    const tbody = document.getElementById('tkReportsBody');
    if (!tbody) return;

    const items = getFilteredTkReports();
    if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="tk-reports-empty">No reports found.</td></tr>';
        return;
    }

    tbody.innerHTML = items.map((item) => {
        const statusClass = String(item.status || 'pending').toLowerCase();
        const rowClass = item.is_new ? 'is-new' : '';
        const newBadge = item.is_new ? '<span class="tk-new-badge">New</span>' : '';

        return `
            <tr class="${rowClass}">
                <td data-label="Site">${escapeTkHtml(item.site_name)}</td>
                <td data-label="Timekeeper">${escapeTkHtml(item.timekeeper_name)}</td>
                <td data-label="Report type">${escapeTkHtml(item.report_type)}</td>
                <td data-label="Subject">${escapeTkHtml(getTkReportSubject(item))}${newBadge}</td>
                <td data-label="Date submitted">${escapeTkHtml(item.date_submitted)}</td>
                <td data-label="Status"><span class="tk-status ${statusClass}">${escapeTkHtml(getTkReportStatusLabel(item.status))}</span></td>
                <td data-label="Actions">
                    <div class="tk-reports-actions">
                        <button type="button" class="tk-reports-action-btn view" data-tk-view="${Number(item.id) || 0}">Review</button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function renderDelayFields(report) {
    if (!report.is_site_delay) {
        return '';
    }

    return `
        <div class="tk-detail-section">
            <h3>Delay Details</h3>
            <div class="tk-detail-grid">
                <div class="tk-detail-item">
                    <label>Delay Category</label>
                    <span>${escapeTkHtml(report.delay_category || '-')}</span>
                </div>
                <div class="tk-detail-item">
                    <label>Estimated Hours Lost</label>
                    <span>${report.hours_lost != null ? Number(report.hours_lost).toFixed(2) : '-'}</span>
                </div>
                <div class="tk-detail-item">
                    <label>Workers Affected</label>
                    <span>${escapeTkHtml(report.workers_affected ?? '-')}</span>
                </div>
                <div class="tk-detail-item full">
                    <label>Cause of Delay</label>
                    <p>${escapeTkHtml(report.cause_of_delay || '-')}</p>
                </div>
                <div class="tk-detail-item full">
                    <label>Recommended Action</label>
                    <p>${escapeTkHtml(report.recommended_action || '-')}</p>
                </div>
            </div>
        </div>
    `;
}

function renderAdminControls(report) {
    if (!isAdminRole() || !report.can_update) {
        return '';
    }

    return `
        <div class="tk-admin-form">
            <label for="tkReportRemarksInput">Review Notes</label>
            <textarea id="tkReportRemarksInput" placeholder="Add review notes or follow-up instructions...">${escapeTkHtml(report.admin_remarks || '')}</textarea>
        </div>
    `;
}

function renderTkReportDetails(report) {
    const body = document.getElementById('tkReportDetailsBody');
    const footer = document.getElementById('tkReportDetailsFooter');
    if (!body || !report) return;

    const photoBlock = report.attachment_url
        ? `<div class="tk-detail-item full"><label>Attached Photo</label><img class="tk-report-photo" src="${escapeTkHtml(report.attachment_url)}" alt="Report attachment"></div>`
        : '';

    body.innerHTML = `
        <div class="tk-detail-grid">
            <div class="tk-detail-item">
                <label>Current Status</label>
                <span>${escapeTkHtml(getTkReportStatusLabel(report.status))}</span>
            </div>
            <div class="tk-detail-item">
                <label>Site Name</label>
                <span>${escapeTkHtml(report.site_name)}</span>
            </div>
            <div class="tk-detail-item">
                <label>Site Location</label>
                <span>${escapeTkHtml(report.site_location || '-')}</span>
            </div>
            <div class="tk-detail-item">
                <label>Timekeeper Name</label>
                <span>${escapeTkHtml(report.timekeeper_name)}</span>
            </div>
            <div class="tk-detail-item">
                <label>Timekeeper Email</label>
                <span>${escapeTkHtml(report.timekeeper_email || '-')}</span>
            </div>
            <div class="tk-detail-item">
                <label>Report Type</label>
                <span>${escapeTkHtml(report.report_type)}</span>
            </div>
            <div class="tk-detail-item">
                <label>Subject</label>
                <span>${escapeTkHtml(getTkReportSubject(report))}</span>
            </div>
            <div class="tk-detail-item">
                <label>Date Submitted</label>
                <span>${escapeTkHtml(report.date_submitted)}</span>
            </div>
            <div class="tk-detail-item full">
                <label>Full Description</label>
                <p>${escapeTkHtml(report.description || '-')}</p>
            </div>
            ${photoBlock}
        </div>
        ${renderDelayFields(report)}
        ${report.admin_remarks && !isAdminRole() ? `<div class="tk-detail-section"><h3>Admin Remarks</h3><p>${escapeTkHtml(report.admin_remarks)}</p></div>` : ''}
        ${renderAdminControls(report)}
    `;

    if (footer) {
        footer.querySelector('#reviewTkReportBtn')?.toggleAttribute('hidden', !isAdminRole());
    }
}

async function fetchTkReports() {
    const params = new URLSearchParams();
    if (tkReportsState.reportType) params.set('report_type', tkReportsState.reportType);
    if (tkReportsState.search) params.set('search', tkReportsState.search);

    const response = await fetch(`../api/get_timekeeper_reports.php?${params.toString()}`);
    const result = await response.json();
    if (!response.ok || !result.success) {
        throw new Error(result.message || 'Failed to load timekeeper reports');
    }

    tkReportsState.items = Array.isArray(result.items) ? result.items : [];
    tkReportsState.stats = result.stats || {};
    tkReportsState.reportTypes = Array.isArray(result.report_types) ? result.report_types : [];
    renderTkStats();
    populateTypeFilter();
    populateSiteFilter();
    renderTkReportsTable();
}

async function openTkReportDetails(reportId) {
    const response = await fetch(`../api/get_timekeeper_report.php?report_id=${encodeURIComponent(reportId)}`);
    const result = await response.json();
    if (!response.ok || !result.success) {
        alert(result.message || 'Failed to load report details');
        return;
    }

    tkReportsState.selectedReport = result.report;
    renderTkReportDetails(result.report);

    const modal = document.getElementById('tkReportDetailsModal');
    if (modal) {
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
    }
}

function closeTkReportDetails() {
    const modal = document.getElementById('tkReportDetailsModal');
    if (!modal) return;
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    tkReportsState.selectedReport = null;
}

async function reviewTkReport() {
    const report = tkReportsState.selectedReport;
    if (!report) return;

    const adminRemarks = document.getElementById('tkReportRemarksInput')?.value?.trim() || '';

    const response = await fetch('../api/update_timekeeper_report.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            report_id: report.id,
            status: 'Reviewed',
            admin_remarks: adminRemarks,
            mark_resolved: false,
        }),
    });

    const result = await response.json();
    if (!response.ok || !result.success) {
        window.showCrudResultModal?.(
            false,
            result.message || 'Failed to update report',
            'Timekeeper Report Update'
        );
        return;
    }

    closeTkReportDetails();
    await fetchTkReports();
    window.showCrudResultModal?.(
        true,
        result.message || 'Report updated successfully.',
        'Timekeeper Report Update'
    );
}

function bindTkReportsEvents() {
    document.getElementById('tkReportsSearchInput')?.addEventListener('input', (event) => {
        tkReportsState.search = event.target.value.trim();
        renderTkReportsTable();
    });

    document.getElementById('tkReportsSiteFilter')?.addEventListener('change', (event) => {
        tkReportsState.site = event.target.value;
        renderTkReportsTable();
    });

    document.getElementById('tkReportsTypeFilter')?.addEventListener('change', async (event) => {
        tkReportsState.reportType = event.target.value;
        await fetchTkReports();
    });

    document.getElementById('tkReportsStats')?.addEventListener('click', async (event) => {
        const card = event.target.closest('[data-stat-filter]');
        if (!card) return;
        const filter = card.dataset.statFilter;
        tkReportsState.todayOnly = filter === 'today';
        tkReportsState.status = filter === 'all' || filter === 'today' ? '' : filter;
        await fetchTkReports();
    });

    document.getElementById('tkReportsBody')?.addEventListener('click', async (event) => {
        const viewBtn = event.target.closest('[data-tk-view]');
        if (viewBtn) {
            await openTkReportDetails(viewBtn.dataset.tkView);
        }
    });

    document.getElementById('closeTkReportDetailsBtn')?.addEventListener('click', closeTkReportDetails);
    document.getElementById('cancelTkReportDetailsBtn')?.addEventListener('click', closeTkReportDetails);
    document.getElementById('reviewTkReportBtn')?.addEventListener('click', reviewTkReport);

    document.getElementById('tkReportDetailsModal')?.addEventListener('click', (event) => {
        if (event.target.id === 'tkReportDetailsModal') {
            closeTkReportDetails();
        }
    });
}

document.addEventListener('DOMContentLoaded', async () => {
    bindTkReportsEvents();
    try {
        await fetchTkReports();
    } catch (error) {
        const tbody = document.getElementById('tkReportsBody');
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="7" class="tk-reports-empty">${escapeTkHtml(error.message)}</td></tr>`;
        }
    }
});
