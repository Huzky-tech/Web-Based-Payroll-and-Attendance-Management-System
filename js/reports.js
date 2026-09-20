const payrollSummaryState = {
    loading: false,
    lastPayload: null,
    selectedSiteId: '',
};

const reportPaginationState = {};
const REPORT_ROWS_PER_PAGE = 10;

function formatReportCurrency(value) {
    return `₱${Number(value || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
}

function escapeReportHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function getPaginatedReportRows(tbody) {
    return Array.from(tbody.querySelectorAll('tr')).filter((row) => {
        const firstCell = row.cells[0];
        if (row.dataset.filterEmpty === 'true' || row.querySelector('.reports-empty-row') || (row.cells.length === 1 && firstCell?.colSpan > 1)) {
            return false;
        }
        return row.dataset.filterMatch !== 'false';
    });
}

function paginateReportTable(tbodyId, paginationId, rowsPerPage = REPORT_ROWS_PER_PAGE, resetPage = false) {
    const tbody = document.getElementById(tbodyId);
    const pagination = document.getElementById(paginationId);
    if (!tbody || !pagination) {
        return;
    }

    const allRows = Array.from(tbody.querySelectorAll('tr')).filter((row) => row.dataset.filterEmpty !== 'true');
    const rows = getPaginatedReportRows(tbody);
    const totalPages = Math.max(1, Math.ceil(rows.length / rowsPerPage));
    const current = resetPage ? 1 : Math.min(reportPaginationState[tbodyId] || 1, totalPages);
    reportPaginationState[tbodyId] = current;

    allRows.forEach((row) => {
        if (!row.querySelector('.reports-empty-row')) {
            row.hidden = true;
        }
    });

    rows.slice((current - 1) * rowsPerPage, current * rowsPerPage).forEach((row) => {
        row.hidden = false;
    });

    if (rows.length === 0) {
        allRows.forEach((row) => {
            const firstCell = row.cells[0];
            if (row.querySelector('.reports-empty-row') || (row.cells.length === 1 && firstCell?.colSpan > 1)) {
                row.hidden = false;
            }
        });
    }

    pagination.hidden = rows.length === 0;
    pagination.innerHTML = `
        <button type="button" class="report-page-btn" data-page-action="prev" ${current <= 1 ? 'disabled' : ''}>Previous</button>
        <span class="report-page-label">Page ${current} of ${totalPages}</span>
        <button type="button" class="report-page-btn" data-page-action="next" ${current >= totalPages ? 'disabled' : ''}>Next</button>
    `;

    pagination.querySelector('[data-page-action="prev"]')?.addEventListener('click', () => {
        reportPaginationState[tbodyId] = Math.max(1, current - 1);
        paginateReportTable(tbodyId, paginationId, rowsPerPage);
    });
    pagination.querySelector('[data-page-action="next"]')?.addEventListener('click', () => {
        reportPaginationState[tbodyId] = Math.min(totalPages, current + 1);
        paginateReportTable(tbodyId, paginationId, rowsPerPage);
    });
}

function updateDateTime() {
    const dateEl = document.getElementById('currentDate');
    const timeEl = document.getElementById('currentTime');
    if (!dateEl || !timeEl) {
        return;
    }

    const now = new Date();
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    let hours = now.getHours();
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12 || 12;

    dateEl.textContent = `${days[now.getDay()]}, ${months[now.getMonth()]} ${now.getDate()}, ${now.getFullYear()}`;
    timeEl.textContent = `${hours}:${minutes} ${ampm}`;
}

function selectReportType(reportType) {
    const role = String(document.body?.dataset?.dashboardRole || 'admin').toLowerCase();
    const routeRole = role === 'assistant' ? 'assistant' : (role === 'payroll' ? 'payroll' : (role === 'hr' ? 'hr' : 'admin'));
    // Keep the report page at one path level.  The dashboard and report scripts
    // use relative asset/API URLs (for example, ../api/...), which resolve
    // incorrectly from /reports/payroll or another nested report URL.
    const targetUrl = new URL(`/capstone/${routeRole}/reports`, window.location.origin);
    targetUrl.searchParams.set('report', reportType);

    window.location.assign(targetUrl.toString());
}

function toggleCustomDateRange() {
    const range = document.getElementById('dateRange')?.value || 'current-month';
    const customWrap = document.getElementById('customDateRange');
    if (!customWrap) {
        return;
    }
    customWrap.hidden = range !== 'custom';
}

function renderPayrollSummaryTable(sites) {
    const tbody = document.getElementById('payrollTableBody');
    if (!tbody) {
        return;
    }

    if (!Array.isArray(sites) || sites.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="reports-empty-row">No payroll records found for the selected filters.</td></tr>';
        const pagination = document.getElementById('payrollReportPagination');
        if (pagination) {
            pagination.hidden = true;
            pagination.innerHTML = '';
        }
        return;
    }

    tbody.innerHTML = sites.map((site) => `
        <tr data-site-id="${Number(site.site_id || 0)}">
            <td class="department-name">${escapeReportHtml(site.name)}</td>
            <td>${Number(site.employees || 0)}</td>
            <td class="amount">${formatReportCurrency(site.gross_pay)}</td>
            <td class="amount">${formatReportCurrency(site.deductions)}</td>
            <td class="amount">${formatReportCurrency(site.net_pay)}</td>
            <td>${escapeReportHtml(site.status)}</td>
        </tr>
    `).join('');
    paginateReportTable('payrollTableBody', 'payrollReportPagination', REPORT_ROWS_PER_PAGE, true);
}

function renderPayrollWorkerDetails(workers) {
    const tbody = document.getElementById('payrollWorkerReportBody');
    if (!tbody) return;
    const siteId = payrollSummaryState.selectedSiteId;
    const visible = (Array.isArray(workers) ? workers : []).filter((worker) => !siteId || String(worker.site_id) === siteId);
    tbody.innerHTML = visible.length ? visible.map((worker) => `
        <tr data-site-id="${Number(worker.site_id || 0)}">
            <td>${escapeReportHtml(worker.worker_name)}</td>
            <td>${escapeReportHtml(worker.period_start)} - ${escapeReportHtml(worker.period_end)}</td>
            <td class="amount">${formatReportCurrency(worker.gross_pay)}</td>
            <td class="amount">${formatReportCurrency(worker.deductions)}</td>
            <td class="amount">${formatReportCurrency(worker.net_pay)}</td>
            <td>${escapeReportHtml(worker.status)}</td>
        </tr>`).join('') : `<tr><td colspan="6" class="reports-empty-row">${siteId ? 'No worker payroll records found for this site.' : 'No worker payroll records found.'}</td></tr>`;
    paginateReportTable('payrollWorkerReportBody', 'payrollWorkerReportPagination', REPORT_ROWS_PER_PAGE, true);
}

function openReportWorkersModal(modalId, siteName, subtitleId, reportLabel) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    const subtitle = document.getElementById(subtitleId);
    if (subtitle) subtitle.textContent = `${reportLabel} for ${siteName}`;
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('reports-modal-open');
    modal.querySelector('.reports-modal-close')?.focus();
}

function closeReportWorkersModal(modal) {
    if (!modal) return;
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('reports-modal-open');
}

function renderPayrollSummary(payload) {
    const summary = payload?.summary || {};
    const period = payload?.period || {};

    const headerEl = document.getElementById('summaryHeader');
    if (headerEl) {
        headerEl.textContent = period.label || 'Selected Period';
    }

    const grossEl = document.getElementById('totalGrossPay');
    const deductionsEl = document.getElementById('totalDeductions');
    const netEl = document.getElementById('totalNetPay');
    const countEl = document.getElementById('employeeCount');

    if (grossEl) grossEl.textContent = formatReportCurrency(summary.total_gross_pay);
    if (deductionsEl) deductionsEl.textContent = formatReportCurrency(summary.total_deductions);
    if (netEl) netEl.textContent = formatReportCurrency(summary.total_net_pay);
    if (countEl) countEl.textContent = Number(summary.employee_count || 0);

    renderPayrollSummaryTable(payload?.sites || []);
    renderPayrollWorkerDetails(payload?.workers || []);
    payrollSummaryState.lastPayload = payload;
}

async function fetchPayrollSummary() {
    const date = document.getElementById('payrollReportDate')?.value || '';
    const site = (document.getElementById('payrollSiteSelect')?.value || '').trim();
    const search = (document.getElementById('payrollSearchFilter')?.value || '').trim();
    const status = document.getElementById('payrollStatusFilter')?.value || '';
    const range = document.getElementById('dateRange')?.value || 'current-month';
    const params = new URLSearchParams();

    if (date) {
        params.set('date', date);
    } else {
        params.set('range', range);
    }

    if (site) {
        params.set('site', site);
    }
    if (search) {
        params.set('search', search);
    }
    if (status) {
        params.set('status', status);
    }

    if (!date && range === 'custom') {
        const start = document.getElementById('customStart')?.value || '';
        const end = document.getElementById('customEnd')?.value || '';
        if (!start || !end) {
            window.alert('Please select both start and end dates for the custom range.');
            return;
        }
        params.set('start', start);
        params.set('end', end);
    }

    payrollSummaryState.loading = true;

    try {
        const response = await fetch(`../api/get_payroll_summary.php?${params.toString()}`);
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Failed to load payroll summary.');
        }

        renderPayrollSummary(result);
    } catch (error) {
        console.error('Payroll summary error:', error);
        const tbody = document.getElementById('payrollTableBody');
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="6" class="reports-empty-row">${escapeReportHtml(error.message || 'Failed to load payroll summary.')}</td></tr>`;
        }
        const pagination = document.getElementById('payrollReportPagination');
        if (pagination) {
            pagination.hidden = true;
            pagination.innerHTML = '';
        }
    } finally {
        payrollSummaryState.loading = false;
    }
}

function generateReport() {
    fetchPayrollSummary();
}

function updateDateRange() {
    toggleCustomDateRange();
    if (document.getElementById('dateRange')?.value !== 'custom') {
        fetchPayrollSummary();
    }
}

function exportReportCsvLegacy() {
    const table = document.querySelector('[data-payroll-summary-report] .payroll-table');
    if (!table) {
        return;
    }

    const range = document.getElementById('dateRange')?.value || 'current-month';
    let csv = 'Site,Employees,Gross Pay,Deductions,Net Pay,Status\n';

    table.querySelectorAll('tbody tr').forEach((row) => {
        const cols = row.querySelectorAll('td');
        if (cols.length < 6 || row.querySelector('.reports-empty-row')) {
            return;
        }
        const rowData = Array.from(cols).map((col) => col.textContent.trim().replace(/₱/g, '').replace(/,/g, ''));
        csv += rowData.join(',') + '\n';
    });

    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `payroll-summary-${range}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
}

async function exportReport() {
    const table = document.querySelector('[data-payroll-summary-report] .payroll-table');
    if (!table) return;
    const range = document.getElementById('payrollReportDate')?.value || document.getElementById('dateRange')?.value || 'payroll';
    const rows = [];
    table.querySelectorAll('tbody tr').forEach((row) => {
        const cols = row.querySelectorAll('td');
        if (cols.length >= 6 && !row.querySelector('.reports-empty-row')) {
            rows.push(Array.from(cols).map((col) => col.textContent.trim()));
        }
    });
    if (!rows.length) return window.alert('No payroll summary records are available for export.');

    try {
        const response = await fetch('../api/export_excel.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                filename: `payroll-summary-${range}`, sheet: 'Payroll Summary',
                headers: ['Site', 'Employees', 'Gross Pay', 'Deductions', 'Net Pay', 'Status'], rows
            })
        });
        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            throw new Error(data.message || 'Excel export failed');
        }
        const url = URL.createObjectURL(await response.blob());
        const link = document.createElement('a');
        link.href = url;
        link.download = `payroll-summary-${range}.xlsx`;
        document.body.appendChild(link); link.click(); link.remove(); URL.revokeObjectURL(url);
    } catch (error) {
        window.alert(`Could not export payroll summary: ${error.message}`);
    }
}

function printReport() {
    const reportRoot = document.querySelector('[data-payroll-summary-report]');
    if (!reportRoot) {
        return;
    }

    const printWindow = window.open('', '_blank');
    if (!printWindow) {
        return;
    }

    printWindow.document.write(`
        <html>
            <head>
                <title>Payroll Summary Report</title>
                <style>
                    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; color: #111827; }
                    h2, h3 { margin-bottom: 12px; }
                    .summary-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
                    .summary-card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; }
                    .summary-card-label { font-size: 13px; color: #6b7280; margin-bottom: 8px; }
                    .summary-card-value { font-size: 24px; font-weight: 700; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; text-align: left; }
                    th { background: #f9fafb; }
                </style>
            </head>
            <body>
                ${reportRoot.innerHTML}
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

function bindPayrollSummaryEvents() {
    const reportRoot = document.querySelector('[data-payroll-summary-report]');
    if (!reportRoot) {
        return;
    }

    document.getElementById('dateRange')?.addEventListener('change', updateDateRange);
    document.getElementById('payrollReportDate')?.addEventListener('change', fetchPayrollSummary);
    document.getElementById('payrollSiteSelect')?.addEventListener('change', fetchPayrollSummary);
    document.getElementById('payrollSearchFilter')?.addEventListener('input', fetchPayrollSummary);
    document.getElementById('payrollStatusFilter')?.addEventListener('change', fetchPayrollSummary);
    document.getElementById('btnExportPayrollSummary')?.addEventListener('click', exportReport);
    document.getElementById('payrollTableBody')?.addEventListener('click', (event) => {
        const row = event.target.closest('tr[data-site-id]');
        if (!row) return;
        payrollSummaryState.selectedSiteId = row.dataset.siteId;
        document.querySelectorAll('#payrollTableBody tr[data-site-id]').forEach((siteRow) => {
            siteRow.classList.toggle('report-site-selected', payrollSummaryState.selectedSiteId !== '' && siteRow.dataset.siteId === payrollSummaryState.selectedSiteId);
        });
        renderPayrollWorkerDetails(payrollSummaryState.lastPayload?.workers || []);
        openReportWorkersModal('payrollWorkersModal', row.cells[0]?.textContent.trim() || 'selected site', 'payrollWorkersModalSubtitle', 'Payroll records');
    });

    toggleCustomDateRange();
    fetchPayrollSummary();
}

function bindTableFilters() {
    const attendanceSite = document.getElementById('attendanceSiteSelect');
    const attendanceSearch = document.getElementById('attendanceSearchFilter');
    const attendanceDate = document.getElementById('attendanceDateFilter');
    const attendanceStatus = document.getElementById('attendanceStatusFilter');
    const attendanceBody = document.getElementById('attendanceReportBody');
    const deductionSite = document.getElementById('deductionSiteSelect');
    const deductionSearch = document.getElementById('deductionSearchFilter');
    const deductionDate = document.getElementById('deductionDateFilter');
    const deductionType = document.getElementById('deductionTypeFilter');
    const deductionStatus = document.getElementById('deductionStatusFilter');
    const deductionBody = document.getElementById('deductionsReportBody');
    const evidenceBody = document.getElementById('attendanceEvidenceReportBody');
    const deductionWorkerBody = document.getElementById('deductionWorkerReportBody');
    let selectedAttendanceSiteId = '';
    let selectedDeductionSiteId = '';

    const filterDeductionWorkers = (siteId) => {
        if (!deductionWorkerBody) return;
        const rows = Array.from(deductionWorkerBody.querySelectorAll('tr[data-site-id]'));
        let count = 0;
        rows.forEach((row) => {
            const visible = !siteId || row.dataset.siteId === siteId;
            row.dataset.filterMatch = visible ? 'true' : 'false';
            row.hidden = !visible;
            if (visible) count++;
        });
        const empty = deductionWorkerBody.querySelector('[data-selection-empty]');
        if (empty) {
            empty.hidden = count > 0;
            empty.querySelector('td').textContent = siteId ? 'No worker deduction records found for this site.' : 'No worker deduction records found.';
        }
        paginateReportTable('deductionWorkerReportBody', 'deductionWorkerReportPagination', REPORT_ROWS_PER_PAGE, true);
    };

    const filterEvidenceBySite = (siteId) => {
        if (!evidenceBody) return;
        const rows = Array.from(evidenceBody.querySelectorAll('tr')).filter((row) => row.dataset.filterEmpty !== 'true');
        let visibleCount = 0;
        rows.forEach((row) => {
            const visible = !siteId || row.dataset.siteId === siteId;
            row.dataset.filterMatch = visible ? 'true' : 'false';
            row.hidden = !visible;
            if (visible) visibleCount += 1;
        });

        let emptyRow = evidenceBody.querySelector('[data-filter-empty]');
        if (!emptyRow && visibleCount === 0) {
            emptyRow = document.createElement('tr');
            emptyRow.dataset.filterEmpty = 'true';
            emptyRow.innerHTML = '<td colspan="8" class="reports-empty-row">No worker records found for this site.</td>';
            evidenceBody.appendChild(emptyRow);
        }
        if (emptyRow) emptyRow.hidden = visibleCount > 0 || !siteId;
        paginateReportTable('attendanceEvidenceReportBody', 'attendanceEvidenceReportPagination', REPORT_ROWS_PER_PAGE, true);
    };

    attendanceBody?.addEventListener('click', (event) => {
        const row = event.target.closest('tr[data-site-id]');
        if (!row) return;
        selectedAttendanceSiteId = selectedAttendanceSiteId === row.dataset.siteId ? '' : row.dataset.siteId;
        attendanceBody.querySelectorAll('tr[data-site-id]').forEach((siteRow) => {
            const selected = selectedAttendanceSiteId !== '' && siteRow.dataset.siteId === selectedAttendanceSiteId;
            siteRow.classList.toggle('attendance-site-selected', selected);
            siteRow.setAttribute('aria-selected', String(selected));
        });
        filterEvidenceBySite(selectedAttendanceSiteId);
    });

    deductionBody?.addEventListener('click', (event) => {
        const row = event.target.closest('tr[data-site-id]');
        if (!row) return;
        selectedDeductionSiteId = row.dataset.siteId;
        deductionBody.querySelectorAll('tr[data-site-id]').forEach((siteRow) => {
            siteRow.classList.toggle('report-site-selected', selectedDeductionSiteId !== '' && siteRow.dataset.siteId === selectedDeductionSiteId);
        });
        filterDeductionWorkers(selectedDeductionSiteId);
        openReportWorkersModal('deductionWorkersModal', row.cells[0]?.textContent.trim() || 'selected site', 'deductionWorkersModalSubtitle', 'Deduction records');
    });

    const filterRows = (tbody, matchesRow, columnCount) => {
        if (!tbody) return;
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const reportRows = rows.filter((row) => row.dataset.filterEmpty !== 'true' && row.cells.length >= columnCount);
        if (!reportRows.length) return;
        let visibleCount = 0;

        reportRows.forEach((row) => {
            const visible = matchesRow(row);
            row.dataset.filterMatch = visible ? 'true' : 'false';
            row.hidden = !visible;
            if (visible) visibleCount += 1;
        });

        let emptyRow = tbody.querySelector('[data-filter-empty]');
        if (!emptyRow && visibleCount === 0) {
            emptyRow = document.createElement('tr');
            emptyRow.dataset.filterEmpty = 'true';
            emptyRow.innerHTML = `<td colspan="${columnCount}" class="reports-empty-row">No records match the selected filters.</td>`;
            tbody.appendChild(emptyRow);
        }
        if (emptyRow) emptyRow.hidden = visibleCount > 0;
    };

    const filterAttendance = () => {
        const site = (attendanceSite?.value || '').trim().toLowerCase();
        const search = (attendanceSearch?.value || '').trim().toLowerCase();
        const date = attendanceDate?.value || '';
        const status = attendanceStatus?.value || '';
        filterRows(attendanceBody, (row) => {
            const rowSite = (row.cells[0]?.textContent || '').trim().toLowerCase();
            const firstDate = row.dataset.firstDate || '';
            const latestDate = row.dataset.latestDate || '';
            const presentCount = Number(row.cells[3]?.textContent || 0);
            const lateCount = Number(row.cells[4]?.textContent || 0);
            const absentCount = Number(row.cells[5]?.textContent || 0);
            const matchesDate = !date || (firstDate && latestDate && firstDate <= date && latestDate >= date);
            const matchesStatus = !status
                || (status === 'Present' && presentCount > 0)
                || (status === 'Late' && lateCount > 0)
                || (status === 'Absent' && absentCount > 0);
            const rowText = row.textContent.trim().toLowerCase();
            return (!site || rowSite === site) && (!search || rowText.includes(search)) && matchesDate && matchesStatus;
        }, 10);
    };

    const filterDeductions = () => {
        const site = (deductionSite?.value || '').trim().toLowerCase();
        const search = (deductionSearch?.value || '').trim().toLowerCase();
        const date = deductionDate?.value || '';
        const type = deductionType?.value || '';
        const status = deductionStatus?.value || '';
        filterRows(deductionBody, (row) => {
            const rowSite = (row.cells[0]?.textContent || '').trim().toLowerCase();
            const periodStart = (row.cells[1]?.textContent || '').trim();
            const periodEnd = (row.cells[2]?.textContent || '').trim();
            const deductionAmount = Number(row.dataset.deductionAmount || 0);
            const rowStatus = (row.cells[7]?.textContent || '').trim();
            const matchesDate = !date || (periodStart && periodEnd && periodStart <= date && periodEnd >= date);
            const matchesType = !type
                || (type === 'with-deductions' && deductionAmount > 0)
                || (type === 'no-deductions' && deductionAmount <= 0);
            const rowText = row.textContent.trim().toLowerCase();
            return (!site || rowSite === site) && (!search || rowText.includes(search)) && matchesDate && matchesType && (!status || rowStatus === status);
        }, 8);
    };

    const runAttendanceFilter = () => {
        filterAttendance();
        paginateReportTable('attendanceReportBody', 'attendanceReportPagination', REPORT_ROWS_PER_PAGE, true);
    };
    const runDeductionFilter = () => {
        filterDeductions();
        paginateReportTable('deductionsReportBody', 'deductionsReportPagination', REPORT_ROWS_PER_PAGE, true);
    };

    attendanceSite?.addEventListener('change', runAttendanceFilter);
    attendanceSearch?.addEventListener('input', runAttendanceFilter);
    attendanceDate?.addEventListener('change', runAttendanceFilter);
    attendanceStatus?.addEventListener('change', runAttendanceFilter);
    deductionSite?.addEventListener('change', runDeductionFilter);
    deductionSearch?.addEventListener('input', runDeductionFilter);
    deductionDate?.addEventListener('change', runDeductionFilter);
    deductionType?.addEventListener('change', runDeductionFilter);
    deductionStatus?.addEventListener('change', runDeductionFilter);

    paginateReportTable('attendanceReportBody', 'attendanceReportPagination');
    paginateReportTable('attendanceEvidenceReportBody', 'attendanceEvidenceReportPagination');
    paginateReportTable('deductionsReportBody', 'deductionsReportPagination');
    document.querySelectorAll('[data-close-report-modal]').forEach((button) => {
        button.addEventListener('click', () => closeReportWorkersModal(button.closest('.reports-modal-overlay')));
    });
    document.querySelectorAll('.reports-modal-overlay').forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) closeReportWorkersModal(modal);
        });
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeReportWorkersModal(document.querySelector('.reports-modal-overlay.active'));
    });
    filterDeductionWorkers('');
}

document.addEventListener('DOMContentLoaded', () => {
    updateDateTime();
    setInterval(updateDateTime, 60000);
    bindPayrollSummaryEvents();
    bindTableFilters();
});
