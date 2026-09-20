function escapeOvertimeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

const overtimePaginationState = {
    page: 1,
    rowsPerPage: 10,
};

function paginateOvertimeReport(resetPage = false) {
    const tbody = document.getElementById('overtimeReportBody');
    const pagination = document.getElementById('overtimeReportPagination');
    if (!tbody || !pagination) {
        return;
    }

    const rows = Array.from(tbody.querySelectorAll('tr')).filter((row) => !row.querySelector('.overtime-empty'));
    const totalPages = Math.max(1, Math.ceil(rows.length / overtimePaginationState.rowsPerPage));
    if (resetPage) {
        overtimePaginationState.page = 1;
    }
    overtimePaginationState.page = Math.min(overtimePaginationState.page, totalPages);

    rows.forEach((row) => {
        row.hidden = true;
    });
    rows
        .slice((overtimePaginationState.page - 1) * overtimePaginationState.rowsPerPage, overtimePaginationState.page * overtimePaginationState.rowsPerPage)
        .forEach((row) => {
            row.hidden = false;
        });

    pagination.hidden = rows.length === 0;
    pagination.innerHTML = `
        <button type="button" class="report-page-btn" data-page-action="prev" ${overtimePaginationState.page <= 1 ? 'disabled' : ''}>Previous</button>
        <span class="report-page-label">Page ${overtimePaginationState.page} of ${totalPages}</span>
        <button type="button" class="report-page-btn" data-page-action="next" ${overtimePaginationState.page >= totalPages ? 'disabled' : ''}>Next</button>
    `;

    pagination.querySelector('[data-page-action="prev"]')?.addEventListener('click', () => {
        overtimePaginationState.page = Math.max(1, overtimePaginationState.page - 1);
        paginateOvertimeReport();
    });
    pagination.querySelector('[data-page-action="next"]')?.addEventListener('click', () => {
        overtimePaginationState.page = Math.min(totalPages, overtimePaginationState.page + 1);
        paginateOvertimeReport();
    });
}

async function generateOvertimeReport() {
    const date = document.getElementById('overtimeReportDate')?.value || '';
    const site = (document.getElementById('overtimeReportSite')?.value || '').trim();
    const search = (document.getElementById('overtimeReportSearch')?.value || '').trim();
    const status = document.getElementById('overtimeReportStatus')?.value || '';
    const tbody = document.getElementById('overtimeReportBody');

    const params = new URLSearchParams();
    if (date) params.set('date', date);
    if (site) params.set('site', site);
    if (search) params.set('search', search);
    if (status) params.set('status', status);

    let result;
    try {
        const response = await fetch(`../api/get_overtime_report.php?${params.toString()}`);
        result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Failed to generate overtime report.');
        }
    } catch (error) {
        if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="overtime-empty">Could not load the overtime report.</td></tr>';
        window.showActionNoticeModal?.(error.message || 'Failed to generate overtime report.', 'error', 'Report Error');
        return;
    }

    const items = Array.isArray(result.items) ? result.items : [];
    if (!tbody) return;
    populateOvertimeSiteFilter(Array.isArray(result.sites) ? result.sites : []);

    if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="overtime-empty">No overtime records found for the selected filters.</td></tr>';
        paginateOvertimeReport(true);
        return;
    }

    tbody.innerHTML = items.map((item) => {
        const statusClass = String(item.status || '').toLowerCase();
        return `
            <tr>
                <td>${escapeOvertimeHtml(item.worker_name)}</td>
                <td>${escapeOvertimeHtml(item.site_name)}</td>
                <td>${escapeOvertimeHtml(item.overtime_type)}</td>
                <td>${Number(item.total_hours || 0).toFixed(2)}</td>
                <td>${escapeOvertimeHtml(item.request_date_label || item.request_date)}</td>
                <td><span class="overtime-status ${statusClass}">${escapeOvertimeHtml(item.status)}</span></td>
            </tr>
        `;
    }).join('');
    paginateOvertimeReport(true);
}

function populateOvertimeSiteFilter(sites) {
    const siteSelect = document.getElementById('overtimeReportSite');
    if (!siteSelect || siteSelect.dataset.loaded === 'true') {
        return;
    }

    sites.forEach((site) => {
        const siteName = String(site || '').trim();
        if (!siteName) return;
        const option = document.createElement('option');
        option.value = siteName;
        option.textContent = siteName;
        siteSelect.appendChild(option);
    });
    siteSelect.dataset.loaded = 'true';
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('overtimeReportSite')?.addEventListener('change', generateOvertimeReport);
    document.getElementById('overtimeReportSearch')?.addEventListener('input', generateOvertimeReport);
    document.getElementById('overtimeReportStatus')?.addEventListener('change', generateOvertimeReport);
    document.getElementById('overtimeReportDate')?.addEventListener('change', generateOvertimeReport);
    generateOvertimeReport();
});
