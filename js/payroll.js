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

const payrollState = {
    sites: [],
    summary: null,
    period: null,
    selectedSiteId: null,
    siteFilter: 'all',
    searchTerm: '',
    statusFilter: 'all',
    workerSearchTerm: '',
    workerRoleFilter: 'all'
};

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '<')
        .replace(/>/g, '>')
        .replace(/"/g, '"')
        .replace(/'/g, '&#39;');
}

function formatCurrency(value) {
    return `PHP ${Number(value || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })}`;
}

function formatShortCurrency(value) {
    return `PHP ${Number(value || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    })}`;
}

function formatNegativeCurrency(value) {
    return `- ${formatCurrency(value)}`;
}

function formatHours(value) {
    return `${Number(value || 0).toLocaleString('en-PH', {
        minimumFractionDigits: Number(value || 0) % 1 === 0 ? 0 : 2,
        maximumFractionDigits: 2
    })}h`;
}

function setTextContentById(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = value;
    }
}

function getSelectedSite() {
    return payrollState.sites.find((site) => Number(site.SiteID) === Number(payrollState.selectedSiteId)) || null;
}

function getFilteredSites() {
    return payrollState.sites.filter((site) => {
        const siteName = String(site.Site_Name || '').toLowerCase();
        const location = String(site.Location || '').toLowerCase();
        const status = String(site.Status || '').toLowerCase();
        const matchesSearch = !payrollState.searchTerm
            || siteName.includes(payrollState.searchTerm)
            || location.includes(payrollState.searchTerm);
        const matchesSite = payrollState.siteFilter === 'all'
            || String(site.SiteID) === payrollState.siteFilter;

        let matchesStatus = true;
        if (payrollState.statusFilter === 'processed') {
            matchesStatus = !!site.processed;
        } else if (payrollState.statusFilter === 'ready') {
            matchesStatus = !site.processed;
        } else if (payrollState.statusFilter !== 'all') {
            matchesStatus = status === payrollState.statusFilter;
        }

        return matchesSearch && matchesSite && matchesStatus;
    });
}

function populateSiteFilter() {
    const siteFilter = document.getElementById('payrollSiteFilter');
    if (!siteFilter) {
        return;
    }

    const sites = [...payrollState.sites].sort((a, b) =>
        String(a.Site_Name || '').localeCompare(String(b.Site_Name || ''))
    );

    siteFilter.innerHTML = '<option value="all">All Sites</option>' + sites.map((site) =>
        `<option value="${escapeHtml(site.SiteID)}">${escapeHtml(site.Site_Name || 'Unnamed site')}</option>`
    ).join('');
    siteFilter.value = payrollState.siteFilter;
}

function getFilteredWorkers(site) {
    if (!site || !Array.isArray(site.workers)) {
        return [];
    }

    return site.workers.filter((worker) => {
        const name = String(worker.full_name || '').toLowerCase();
        const role = String(worker.role_on_site || '').toLowerCase();
        const workerId = String(worker.WorkerID || '').toLowerCase();
        const matchesSearch = !payrollState.workerSearchTerm
            || name.includes(payrollState.workerSearchTerm)
            || role.includes(payrollState.workerSearchTerm)
            || workerId.includes(payrollState.workerSearchTerm);
        const matchesRole = payrollState.workerRoleFilter === 'all'
            || String(worker.role_on_site || '') === payrollState.workerRoleFilter;

        return matchesSearch && matchesRole;
    });
}

function getWorkerRoleOptions(site) {
    const roles = new Set();
    (site?.workers || []).forEach((worker) => {
        const role = String(worker.role_on_site || '').trim();
        if (role) {
            roles.add(role);
        }
    });

    return Array.from(roles).sort((a, b) => a.localeCompare(b));
}

function getAttendanceRate(worker) {
    const recordedDays = Number(worker.present_days || 0) + Number(worker.late_days || 0);
    const absentDays = Array.isArray(worker.attendance_trail)
        ? worker.attendance_trail.filter((entry) => String(entry.status || '').toLowerCase() === 'absent').length
        : 0;
    const totalMarkedDays = recordedDays + absentDays;

    if (totalMarkedDays === 0) {
        return 0;
    }

    return Math.round((recordedDays / totalMarkedDays) * 100);
}

function getAttendanceBadgeMarkup(worker) {
    const trail = Array.isArray(worker.attendance_trail) ? worker.attendance_trail : [];
    const site = getSelectedSite();
    const startValue = site?.period_start || payrollState.period?.start;
    const startDate = startValue ? new Date(`${startValue}T00:00:00`) : null;
    const recordsByDate = new Map(trail.map((entry) => [String(entry.date || '').slice(0, 10), entry]));
    const letterMap = { present: 'P', late: 'L', absent: 'A', 'rest day': 'RD', restday: 'RD' };
    const dayNames = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];

    if (!startDate || Number.isNaN(startDate.getTime())) {
        return '<span class="attendance-empty">No attendance records</span>';
    }

    const badgeMarkup = Array.from({ length: 7 }, (_, index) => {
        const date = new Date(startDate);
        date.setDate(startDate.getDate() + index);
        const dateKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
        const entry = recordsByDate.get(dateKey);
        const status = String(entry?.status || '').trim().toLowerCase();
        const isRestDay = !entry && date.getDay() === 0;
        const cssStatus = isRestDay ? 'rest-day' : (status || 'unrecorded');
        const letter = isRestDay ? 'RD' : (letterMap[status] || '—');
        const label = entry?.status || (isRestDay ? 'Rest Day' : 'No record');

        return `<span class="attendance-day">
            <small>${dayNames[date.getDay()]}</small>
            <span class="attendance-badge ${cssStatus}" title="${escapeHtml(dateKey)} - ${escapeHtml(label)}">${letter}</span>
        </span>`;
    }).join('');

    return `
        <div class="attendance-badges weekly">${badgeMarkup}</div>
    `;
}

function renderSiteCards() {
    const container = document.getElementById('payrollSitesContainer');
    if (!container) {
        return;
    }

    const filteredSites = getFilteredSites();

    if (!Array.isArray(payrollState.sites) || payrollState.sites.length === 0) {
        container.innerHTML = `
            <div class="site-card payroll-site-card">
                <div class="site-card-title">No sites available</div>
                <div class="site-info-item">Create site assignments to begin payroll processing.</div>
            </div>
        `;
        return;
    }

    if (filteredSites.length === 0) {
        container.innerHTML = `
            <div class="site-card payroll-site-card">
                <div class="site-card-title">No matching sites</div>
                <div class="site-info-item">Try another search term or status filter.</div>
            </div>
        `;
        return;
    }

    container.innerHTML = filteredSites.map((site) => {
        const workers = Array.isArray(site.workers) ? site.workers : [];
        const workerCount = Number(site.assigned_workers || workers.length || 0);
        const attendedWorkers = workers.filter((worker) => Number(worker.attendance_days || 0) > 0).length;
        const statusLabel = site.processed ? 'processed' : String(site.Status || 'active').toLowerCase();

        return `
            <div class="site-card payroll-site-card">
                <div class="payroll-card-header">
                    <div class="payroll-site-title">
                        <i class="fa-solid fa-building"></i>
                        <span>${escapeHtml(site.Site_Name)}</span>
                    </div>
                    <span class="payroll-status ${site.processed ? 'processed' : ''}">${escapeHtml(statusLabel)}</span>
                </div>

                <div class="payroll-site-meta">${escapeHtml(site.Location || 'No location')} &bull; ${escapeHtml(site.period_label || payrollState.period?.label || '')}</div>

                <div class="payroll-staff-assignment">
                    <i class="fa-solid fa-user-tie" aria-hidden="true"></i>
                    <span><strong>Payroll staff:</strong> ${escapeHtml(site.payroll_staff_names || 'Unassigned')}</span>
                </div>

                <div class="payroll-card-stats">
                    <div>
                        <p>Workers:</p>
                        <h3>${workerCount}</h3>
                    </div>
                    <div>
                        <p>Attendance:</p>
                        <h3>${attendedWorkers} / ${workerCount}</h3>
                    </div>
                </div>

                <div class="payroll-card-amounts">
                    <span>Gross: ${formatShortCurrency(site.gross_pay)}</span>
                    <span>Net: ${formatShortCurrency(site.net_pay)}</span>
                </div>

                <button class="btn-view-details payroll-view-btn" type="button" data-site-id="${Number(site.SiteID) || 0}">
                    View Details
                </button>
            </div>
        `;
    }).join('');
}

function updateWorkerRoleFilter(site) {
    const roleFilter = document.getElementById('payrollWorkerRoleFilter');
    if (!roleFilter) {
        return;
    }

    const options = getWorkerRoleOptions(site);
    roleFilter.innerHTML = '<option value="all">All Positions</option>' + options.map((role) => (
        `<option value="${escapeHtml(role)}">${escapeHtml(role)}</option>`
    )).join('');

    if (!options.includes(payrollState.workerRoleFilter)) {
        payrollState.workerRoleFilter = 'all';
        roleFilter.value = 'all';
    } else {
        roleFilter.value = payrollState.workerRoleFilter;
    }
}

function renderPayrollDetails() {
    const site = getSelectedSite();
    if (!site) {
        return;
    }

    const filteredWorkers = getFilteredWorkers(site);
    const workers = Array.isArray(site.workers) ? site.workers : [];

    setTextContentById('siteNameHeader', site.Site_Name);
    setTextContentById('sitePeriodHeader', 'Worker Payroll Details');
    setTextContentById('workersSectionTitle', `Workers Assigned to ${site.Site_Name}`);
    setTextContentById('workersCountLabel', `Showing ${filteredWorkers.length} of ${workers.length} workers`);
    setTextContentById('processPayrollText', site.processed
        ? `Payroll for ${site.Site_Name} has already been processed for ${site.period_label}.`
        : `Submit payroll for ${site.Site_Name}. Once submitted, it will wait for approval from an authorized reviewer.`);
    setTextContentById('payrollWorkerPeriodLabel', site.period_label || payrollState.period?.label || '');

    setTextContentById('grossPayValue', formatCurrency(site.gross_pay));
    setTextContentById('deductionsValue', formatCurrency(site.deductions));
    setTextContentById('netPayValue', formatCurrency(site.net_pay));

    updateWorkerRoleFilter(site);

    const processBtn = document.getElementById('btnProcessPayroll');
    if (processBtn) {
        processBtn.disabled = !!site.processed;
        processBtn.style.opacity = site.processed ? '0.6' : '1';
        const processBtnText = processBtn.querySelector('span');
        if (processBtnText) {
            processBtnText.textContent = `Process Payroll for ${site.Site_Name}`;
        }
    }

    const tbody = document.getElementById('payrollTableBody');
    if (!tbody) {
        return;
    }

    if (workers.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" style="text-align:center;">No workers assigned to this site.</td>
            </tr>
        `;
        return;
    }

    if (filteredWorkers.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" style="text-align:center;">No workers match the current filters.</td>
            </tr>
        `;
        return;
    }

    let totalGross = 0;
    let totalDeductions = 0;
    let totalNet = 0;

    tbody.innerHTML = filteredWorkers.map((worker) => {
        totalGross += Number(worker.gross_pay || 0);
        totalDeductions += Number(worker.deductions || 0);
        totalNet += Number(worker.net_pay || 0);

        return `
            <tr>
                <td data-label="Employee">
                    <div class="employee-info">
                        <div class="employee-name">${escapeHtml(worker.full_name)}</div>
                        <div class="employee-id">${escapeHtml(worker.role_on_site || 'Construction Worker')}</div>
                    </div>
                </td>
                <td data-label="Base rate">${formatShortCurrency(worker.rate_amount)}<div class="base-rate-type">${escapeHtml(worker.rate_type)}</div></td>
                <td data-label="Rate %"><span class="rate-percent">${getAttendanceRate(worker)}%</span></td>
                <td data-label="Attendance">${getAttendanceBadgeMarkup(worker)}</td>
                <td data-label="OT hours">${formatHours(worker.overtime_hours)}</td>
                <td data-label="Gross pay" class="amount">${formatCurrency(worker.gross_pay)}</td>
                <td data-label="Deduction status">${escapeHtml(worker.government_deduction_label || 'With Government Deductions')}</td>
                <td data-label="Deductions" class="amount negative">${formatNegativeCurrency(worker.deductions)}</td>
                <td data-label="Net pay" class="amount">${formatCurrency(worker.net_pay)}</td>
                <td data-label="Actions">
                    <div class="table-actions">
                        <button class="action-icon" type="button" data-action="summary" data-worker-id="${Number(worker.WorkerID) || 0}" title="View breakdown">
                            <i class="fa-regular fa-file-lines"></i>
                        </button>
                        <button class="action-icon" type="button" data-action="correct-attendance" data-worker-id="${Number(worker.WorkerID) || 0}" title="Correct attendance">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('') + `
        <tr class="table-total-row">
            <td data-label="Total">TOTAL (${filteredWorkers.length} worker${filteredWorkers.length === 1 ? '' : 's'})</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td data-label="Gross pay">${formatCurrency(totalGross)}</td>
            <td></td>
            <td data-label="Deductions" class="amount negative">${formatNegativeCurrency(totalDeductions)}</td>
            <td data-label="Net pay">${formatCurrency(totalNet)}</td>
            <td></td>
        </tr>
    `;
}

function viewPayrollDetails(siteId) {
    payrollState.selectedSiteId = Number(siteId);
    payrollState.workerSearchTerm = '';
    payrollState.workerRoleFilter = 'all';

    const searchInput = document.getElementById('payrollWorkerSearch');
    const roleFilter = document.getElementById('payrollWorkerRoleFilter');
    if (searchInput) {
        searchInput.value = '';
    }
    if (roleFilter) {
        roleFilter.value = 'all';
    }

    renderPayrollDetails();
    document.getElementById('sitesPage')?.classList.remove('active');
    document.getElementById('payrollDetailsPage')?.classList.add('active');
    document.getElementById('successMessage')?.classList.remove('active');
}

function goBackToSites() {
    document.getElementById('payrollDetailsPage')?.classList.remove('active');
    document.getElementById('sitesPage')?.classList.add('active');
    document.getElementById('successMessage')?.classList.remove('active');
}

function updateSummaryCards() {
    const summary = payrollState.summary;
    if (!summary) {
        return;
    }

    const sitesEl = document.getElementById('payrollSitesCount');
    const workersEl = document.getElementById('payrollWorkersCount');
    const processedEl = document.getElementById('processedSitesCount');

    if (sitesEl) {
        sitesEl.textContent = summary.sites;
    }
    if (workersEl) {
        workersEl.textContent = summary.workers;
    }
    if (processedEl) {
        processedEl.textContent = summary.processed;
    }
}

function bindPayrollFilters() {
    const searchInput = document.getElementById('payrollSiteSearch');
    const siteFilter = document.getElementById('payrollSiteFilter');
    const statusFilter = document.getElementById('payrollStatusFilter');
    const workerSearchInput = document.getElementById('payrollWorkerSearch');
    const workerRoleFilter = document.getElementById('payrollWorkerRoleFilter');

    searchInput?.addEventListener('input', (event) => {
        payrollState.searchTerm = event.target.value.trim().toLowerCase();
        renderSiteCards();
    });

    siteFilter?.addEventListener('change', (event) => {
        payrollState.siteFilter = event.target.value || 'all';
        renderSiteCards();
    });

    statusFilter?.addEventListener('change', (event) => {
        payrollState.statusFilter = event.target.value || 'all';
        renderSiteCards();
    });

    workerSearchInput?.addEventListener('input', (event) => {
        payrollState.workerSearchTerm = event.target.value.trim().toLowerCase();
        renderPayrollDetails();
    });

    workerRoleFilter?.addEventListener('change', (event) => {
        payrollState.workerRoleFilter = event.target.value || 'all';
        renderPayrollDetails();
    });
}

async function processPayroll() {
    const site = getSelectedSite();
    if (!site || site.processed) {
        return;
    }

    // Show confirmation modal before processing
    const confirmed = await window.showConfirmModal(
        'Are you sure you want to submit payroll for ' + site.Site_Name + '?',
        {
            title: 'Confirm Payroll Submission',
            confirmText: 'OK',
            cancelText: 'Cancel',
            type: 'success'
        }
    );

    if (!confirmed) {
        return;
    }

    const button = document.getElementById('btnProcessPayroll');
    const successMessage = document.getElementById('successMessage');
    const successText = document.getElementById('successMessageText');

    if (button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Processing...</span>';
    }

    try {
        const response = await fetch('../api/process_payroll.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                site_id: site.SiteID,
                period_start: site.period_start || payrollState.period.start,
                period_end: site.period_end || payrollState.period.end
            })
        });

        const responseText = await response.text();
        let result = null;

        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            const plainMessage = responseText
                .replace(/<script[\s\S]*?<\/script>/gi, '')
                .replace(/<style[\s\S]*?<\/style>/gi, '')
                .replace(/<[^>]+>/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();

            throw new Error(plainMessage || 'Payroll processing failed. Please log in again and try once more.');
        }

        if (!result.success) {
            throw new Error(result.message || 'Payroll processing failed');
        }

        site.processed = true;
        payrollState.summary.processed += 1;
        updateSummaryCards();
        renderSiteCards();
        renderPayrollDetails();

        if (successText) {
            successText.textContent = '';
        }
        successMessage?.classList.remove('active');
        window.showCrudResultModal?.(
            true,
            result.message || 'Payroll processed successfully.',
            'Payroll Processing'
        );
    } catch (error) {
        window.showCrudResultModal?.(
            false,
            error.message || 'Failed to process payroll',
            'Payroll Processing'
        );
    } finally {
        if (button) {
            button.disabled = getSelectedSite()?.processed || false;
            button.innerHTML = `<i class="fas fa-money-check-dollar"></i><span>Process Payroll for ${escapeHtml(getSelectedSite()?.Site_Name || 'Site')}</span>`;
        }
    }
}

async function printDownloadPayroll() {
    const site = getSelectedSite();
    if (!site) return alert('Select a site before exporting payroll.');
    const workers = getFilteredWorkers(site);
    if (!workers.length) return alert('No payroll records are available for export.');

    const headers = ['Employee', 'Role', 'Rate', 'Rate Type', 'Attendance Rate', 'Overtime Hours', 'Gross Pay', 'Deduction Status', 'Deductions', 'Net Pay'];
    const rows = workers.map((worker) => [
        worker.full_name || '', worker.role_on_site || 'Construction Worker',
        Number(worker.rate_amount || 0).toFixed(2), worker.rate_type || '', `${getAttendanceRate(worker)}%`,
        Number(worker.overtime_hours || 0).toFixed(2), Number(worker.gross_pay || 0).toFixed(2),
        worker.government_deduction_label || 'With Government Deductions',
        Number(worker.deductions || 0).toFixed(2), Number(worker.net_pay || 0).toFixed(2)
    ]);
    try {
        const response = await fetch('../api/export_excel.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ filename: `payroll-${site.Site_Name || 'site'}`, sheet: 'Site Payroll', headers, rows })
        });
        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            throw new Error(data.message || 'Excel export failed');
        }
        const url = URL.createObjectURL(await response.blob());
        const link = document.createElement('a');
        link.href = url;
        link.download = `payroll-${String(site.Site_Name || 'site').replace(/[^a-z0-9_-]+/gi, '-')}.xlsx`;
        document.body.appendChild(link); link.click(); link.remove(); URL.revokeObjectURL(url);
    } catch (error) {
        alert(`Could not export payroll: ${error.message}`);
    }
}

function showWorkerSummary(workerId) {
    const site = getSelectedSite();
    const worker = site?.workers?.find((entry) => Number(entry.WorkerID) === Number(workerId));
    if (!worker) {
        return;
    }

    const modal = document.getElementById('payrollWorkerBreakdownModal');
    const body = document.getElementById('payrollWorkerBreakdownBody');
    if (!modal || !body) {
        return;
    }

    body.innerHTML = `
        <div style="padding:16px;line-height:1.6;">
            <div style="font-weight:700;font-size:16px;margin-bottom:6px;">${escapeHtml(worker.full_name)}</div>
            <div style="color:#4b5563;margin-bottom:14px;">${escapeHtml(worker.role_on_site || 'Construction Worker')}</div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px 24px;">
                <div><b>Base Rate:</b> ${escapeHtml(formatCurrency(worker.rate_amount))} <span style="color:#6b7280">(${escapeHtml(worker.rate_type)})</span></div>
                <div><b>Attendance:</b> ${escapeHtml(worker.attendance_days)} day(s), ${escapeHtml(worker.late_days)} late</div>
                <div><b>Overtime:</b> ${escapeHtml(formatHours(worker.overtime_hours))}</div>
                <div><b>Deduction Status:</b> ${escapeHtml(worker.government_deduction_label || 'With Government Deductions')}</div>
                <div><b>Gross Pay:</b> ${escapeHtml(formatCurrency(worker.gross_pay))}</div>

                <div><b>SSS:</b> ${escapeHtml(formatCurrency(worker.sss_deduction || 0))}</div>
                <div><b>PhilHealth:</b> ${escapeHtml(formatCurrency(worker.philhealth_deduction || 0))}</div>
                <div><b>Pag-IBIG:</b> ${escapeHtml(formatCurrency(worker.pagibig_deduction || 0))}</div>
                <div><b>Tax:</b> ${escapeHtml(formatCurrency(worker.tax_deduction || 0))}</div>
                <div><b>Late Deduction:</b> ${escapeHtml(formatCurrency(worker.late_deduction || 0))}</div>
                <div><b>Position Deduction:</b> ${escapeHtml(formatCurrency(worker.position_deduction || 0))}</div>
                <div><b>Total Deductions:</b> ${escapeHtml(formatCurrency(worker.deductions))}</div>
                <div><b>Net Pay:</b> ${escapeHtml(formatCurrency(worker.net_pay))}</div>
            </div>
        </div>
    `;

    modal.setAttribute('aria-hidden', 'false');
    modal.classList.add('active');

    // wire close buttons (once per open)
    const closeBtn = document.getElementById('payrollWorkerBreakdownCloseBtn');
    const closeBtn2 = document.getElementById('payrollWorkerBreakdownCloseBtn2');

    const closeModal = () => {
        modal.setAttribute('aria-hidden', 'true');
        modal.classList.remove('active');
        body.innerHTML = '';
    };

    if (closeBtn && !closeBtn.dataset.bound) {
        closeBtn.dataset.bound = '1';
        closeBtn.addEventListener('click', closeModal);
    }

    if (closeBtn2 && !closeBtn2.dataset.bound) {
        closeBtn2.dataset.bound = '1';
        closeBtn2.addEventListener('click', closeModal);
    }
}


function correctWorkerAttendance(workerId) {
    const site = getSelectedSite();
    const worker = site?.workers?.find((entry) => Number(entry.WorkerID) === Number(workerId));
    if (!worker) {
        return;
    }

    const dashboardFile = window.location.pathname.split('/').pop() || 'payroll_dashboard.php';
    const params = new URLSearchParams({
        page: 'attendance',
        site_id: String(site.SiteID),
        search: String(worker.full_name || '')
    });
    window.location.href = `${dashboardFile}?${params.toString()}`;
}

function bindPayrollEvents() {
    document.addEventListener('click', (event) => {
        const siteButton = event.target.closest('.btn-view-details');
        if (siteButton) {
            viewPayrollDetails(siteButton.dataset.siteId);
            return;
        }

        const actionButton = event.target.closest('.action-icon');
        if (actionButton) {
            const action = actionButton.dataset.action;
            const workerId = actionButton.dataset.workerId;

            if (action === 'summary') {
                showWorkerSummary(workerId);
            } else if (action === 'correct-attendance') {
                correctWorkerAttendance(workerId);
            }
        }
    });

    document.getElementById('btnBackToSites')?.addEventListener('click', goBackToSites);
    document.getElementById('btnProcessPayroll')?.addEventListener('click', processPayroll);
    document.getElementById('btnPrintPayroll')?.addEventListener('click', printDownloadPayroll);
    bindPayrollFilters();
}

document.addEventListener('DOMContentLoaded', () => {
    updateDateTime();
    setInterval(updateDateTime, 60000);

    if (!window.payrollPageData) {
        return;
    }

    payrollState.sites = Array.isArray(window.payrollPageData.sites) ? window.payrollPageData.sites : [];
    payrollState.summary = window.payrollPageData.summary || null;
    payrollState.period = window.payrollPageData.period || null;

    populateSiteFilter();
    renderSiteCards();
    updateSummaryCards();
    bindPayrollEvents();

    const requestedSiteId = Number(new URLSearchParams(window.location.search).get('site_id'));
    if (Number.isInteger(requestedSiteId)
        && requestedSiteId > 0
        && payrollState.sites.some((site) => Number(site.SiteID) === requestedSiteId)) {
        payrollState.siteFilter = String(requestedSiteId);
        const siteFilter = document.getElementById('payrollSiteFilter');
        if (siteFilter) {
            siteFilter.value = String(requestedSiteId);
        }
        renderSiteCards();
        viewPayrollDetails(requestedSiteId);
    }
});
