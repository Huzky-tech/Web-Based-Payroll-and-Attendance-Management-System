const employeePageRole = String(window.employeePageRole || '').trim();

// HR owns employee records and approvals. Archive permissions are kept separate.
// The server role strings come from includes/auth.php and are expected to be:
// - "Admin"
// - "Assistant Admin"
// - "Payroll Staff"
const canManageEmployeeRecords = ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR'].includes(employeePageRole);
const canArchiveEmployeeRecords = employeePageRole === 'Admin';
const canApproveEmployeeRecords = ['Admin', 'Assistant Admin', 'HR'].includes(employeePageRole);
console.debug('[employee.js] employeePageRole:', employeePageRole, 'canManageEmployeeRecords:', canManageEmployeeRecords);

let currentUserId = 1;
let currentCardWorkerId = null;
let currentCardWorkerName = '';
const employeeNameCheckTimers = new WeakMap();

const employeeListState = {
    employees: [],
    page: 1,
    pageSize: 20,
    search: '',
    status: '',
    position: '',
    assignment: '',
    position: ''
};

const viewEmployeeState = {
    employee: null,
    workerStatuses: [],
    sites: [],
    positions: [],
    activeTab: 'profile',
    editMode: false,
    attendanceLoaded: false,
    historyLoaded: false,
    historyLoading: false,
    attendanceDays: 30,
    historyTimeline: []
};

const assignmentState = {
    workers: [],
    sites: [],
    selectedWorkerId: null,
    workerAssignments: new Map(),
    loadedWorkers: new Set(),
    searchTerm: '',
    dragWorkerId: null
};

function updateDateTime() {
    const now = new Date();
    const dateEl = document.getElementById('currentDate');
    const timeEl = document.getElementById('currentTime');
    if (!dateEl || !timeEl) {
        return;
    }

    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    let hours = now.getHours();
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12 || 12;

    dateEl.textContent = `${days[now.getDay()]}, ${months[now.getMonth()]} ${now.getDate()}, ${now.getFullYear()}`;
    timeEl.textContent = `${hours}:${minutes} ${ampm}`;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatCurrency(value) {
    return `PHP ${parseFloat(value || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })}`;
}

function resolveAssetPath(path) {
    if (!path) {
        return '';
    }

    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }

    return `../${String(path).replace(/^\/+/, '')}`;
}

function getStatusClass(status) {
    switch (status) {
        case 'Active':
            return 'active';
        case 'Inactive':
            return 'inactive';
        case 'OnLeave':
            return 'on-leave';
        default:
            return 'active';
    }
}

function formatAttendanceDisplayDate(dateStr) {
    if (!dateStr) {
        return '';
    }
    const date = new Date(`${dateStr}T12:00:00`);
    if (Number.isNaN(date.getTime())) {
        return dateStr;
    }
    return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
}

function formatTimelineDate(dateStr) {
    if (!dateStr) {
        return '';
    }
    const date = new Date(`${dateStr}T12:00:00`);
    if (Number.isNaN(date.getTime())) {
        return dateStr;
    }
    return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
}

function formatAttendanceTime(timeStr) {
    if (!timeStr) {
        return '—';
    }
    const parts = String(timeStr).match(/^(\d{1,2}):(\d{2})(?::\d{2})?/);
    if (!parts) {
        return timeStr;
    }
    let hours = parseInt(parts[1], 10);
    const minutes = parts[2];
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    if (hours === 0) {
        hours = 12;
    }
    return `${hours}:${minutes} ${ampm}`;
}

function formatAttendanceHours(hours) {
    if (hours === null || hours === undefined || hours === '') {
        return '—';
    }
    const value = parseFloat(hours);
    if (Number.isNaN(value)) {
        return '—';
    }
    return `${value.toFixed(2)}h`;
}

function renderAttendanceStatusPill(status) {
    const normalized = String(status || '').trim();
    if (normalized === 'Present') {
        return '<span class="attendance-pill attendance-pill-present"><i class="fas fa-check"></i> Present</span>';
    }
    if (normalized === 'Absent') {
        return '<span class="attendance-pill attendance-pill-absent"><i class="fas fa-times"></i> Absent</span>';
    }
    if (normalized === 'Late') {
        return '<span class="attendance-pill attendance-pill-late"><i class="fas fa-clock"></i> Late</span>';
    }
    return `<span class="attendance-pill">${escapeHtml(normalized || 'Unknown')}</span>`;
}

function renderEmploymentHistory(panel) {
    const eventType = document.getElementById('historyEventFilter')?.value || '';
    const dateFrom = document.getElementById('historyDateFrom')?.value || '';
    const dateTo = document.getElementById('historyDateTo')?.value || '';
    const timeline = viewEmployeeState.historyTimeline.filter((event) => {
        const date = String(event.event_date || '');
        return (!eventType || String(event.title || '') === eventType)
            && (!dateFrom || date >= dateFrom)
            && (!dateTo || date <= dateTo);
    });
    const list = document.getElementById('employmentTimelineList');
    if (!list) return;
    if (!timeline.length) {
        list.innerHTML = '<li class="profile-tab-empty">No employment history records match these filters.</li>';
        return;
    }
    list.innerHTML = timeline.map((event) => `
        <li class="employment-timeline-item">
            <span class="employment-timeline-dot" aria-hidden="true"></span>
            <div class="employment-timeline-body">
                <div class="employment-timeline-main">
                    <h5 class="employment-timeline-event-title">${escapeHtml(event.title || '')}</h5>
                    <span class="employment-timeline-date-badge">${escapeHtml(formatTimelineDate(event.event_date))}</span>
                </div>
                <p class="employment-timeline-desc">${escapeHtml(event.description || '')}</p>
                <p class="employment-timeline-meta"><i class="fas fa-user" aria-hidden="true"></i> Processed by <strong>${escapeHtml(event.processed_by || 'System')}</strong></p>
            </div>
        </li>`).join('');
}

function bindEmploymentHistoryFilters(panel) {
    panel.querySelectorAll('[data-history-filter]').forEach((input) => {
        input.addEventListener('change', () => renderEmploymentHistory(panel));
    });
}

async function fetchJson(url, options = {}) {
    const response = await fetch(url, options);
    const contentType = response.headers.get('content-type') || '';
    const payload = await response.text();

    if (!response.ok) {
        throw new Error(response.status >= 500
            ? 'The server is temporarily unavailable. Please try again when hosting is back online.'
            : 'The request could not be completed. Please try again.');
    }

    try {
        return JSON.parse(payload);
    } catch (error) {
        if (contentType.includes('text/html') || /^\s*</.test(payload)) {
            throw new Error('The server is temporarily unavailable. Please try again when hosting is back online.');
        }
        throw new Error('The server returned an invalid response. Please try again.');
    }
}

function friendlyConnectionMessage(error, fallback = 'Unable to connect to the server. Please try again when hosting is back online.') {
    const message = String(error?.message || '');
    if (/failed to fetch|networkerror|load failed|unexpected token|invalid response|server is temporarily unavailable/i.test(message)) {
        return 'Unable to reach the server. Please try again when hosting is back online.';
    }
    return message || fallback;
}

function showAssignmentFeedback(message, type = 'success') {
    const feedbackEl = document.getElementById('assignmentFeedback');
    if (!feedbackEl) {
        alert(message);
        return;
    }

    feedbackEl.className = `assignment-feedback show ${type}`;
    feedbackEl.textContent = message;

    clearTimeout(showAssignmentFeedback.timeoutId);
    showAssignmentFeedback.timeoutId = setTimeout(() => {
        feedbackEl.className = 'assignment-feedback';
        feedbackEl.textContent = '';
    }, 3000);
}

function getFilteredEmployees() {
    const search = employeeListState.search.toLowerCase();
    const pendingApprovalsOnly = new URLSearchParams(window.location.search).get('filter') === 'pending';
    return employeeListState.employees.filter((employee) => {
        const fullName = employee.full_name || `${employee.First_Name || ''} ${employee.Last_Name || ''}`.trim();
        const isAssigned = Number(employee.SiteID || 0) > 0;
        const approvalStatus = String(employee.approval_status || employee.approval?.Approval_Status || 'Pending').toLowerCase();
        return (!search || `${fullName} ${employee.WorkerID || ''}`.toLowerCase().includes(search))
            && (!pendingApprovalsOnly || approvalStatus !== 'approved')
            && (!employeeListState.status || String(employee.worker_status || 'Active') === employeeListState.status)
            && (!employeeListState.position || String(employee.position || 'Not Assigned') === employeeListState.position)
            && (!employeeListState.assignment || (employeeListState.assignment === 'assigned' ? isAssigned : !isAssigned));
    });
}

function renderEmployeePagination(totalItems) {
    const container = document.getElementById('employeePagination');
    if (!container) return;
    const totalPages = Math.max(1, Math.ceil(totalItems / employeeListState.pageSize));
    employeeListState.page = Math.min(employeeListState.page, totalPages);
    const start = totalItems ? ((employeeListState.page - 1) * employeeListState.pageSize) + 1 : 0;
    const end = Math.min(employeeListState.page * employeeListState.pageSize, totalItems);
    container.innerHTML = `
        <span class="employee-pagination-info">Showing ${start}-${end} of ${totalItems} employees</span>
        <div class="employee-pagination-controls">
            <button type="button" class="employee-page-btn" data-employee-page="prev" ${employeeListState.page === 1 ? 'disabled' : ''}>Previous</button>
            <span>Page ${employeeListState.page} of ${totalPages}</span>
            <button type="button" class="employee-page-btn" data-employee-page="next" ${employeeListState.page === totalPages ? 'disabled' : ''}>Next</button>
        </div>`;
}

function renderEmployeeTable(employees = getFilteredEmployees()) {
    const tbody = document.getElementById('employeeTableBody');
    if (!tbody) {
        return;
    }

    if (!employees || employees.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align:center; padding:20px;">
                    No employees found. Click "Add New Employee" to create one.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = employees.map((employee) => {
        const fullName = employee.full_name || `${employee.First_Name || ''} ${employee.Last_Name || ''}`.trim();
        const initial = (employee.First_Name || fullName || 'E').charAt(0).toUpperCase();

        const approval = formatApprovalStatus(employee.approval || (employee.approval_status ? {
            Approval_Status: employee.approval_status,
            approved_by_name: employee.approved_by_name,
            Date: employee.approval_date
        } : (String(employee.worker_status || '').toLowerCase() === 'active' ? { Approval_Status: 'Approved' } : null)));
        const approvalClass = String(approval.label || 'Pending').toLowerCase().replace(/\s+/g, '-');

        return `
            <tr data-worker-id="${Number(employee.WorkerID) || 0}">
                <td>
                    <div class="employee-info">
                        <div class="employee-avatar">
                            ${employee.photo_path
                                ? `<img src="${escapeHtml(resolveAssetPath(employee.photo_path))}" alt="${escapeHtml(fullName)}">`
                                : escapeHtml(initial)}
                        </div>
                        <div class="employee-details">
                            <div class="employee-name">${escapeHtml(fullName)}</div>
                        </div>
                    </div>
                </td>
                <td>${escapeHtml(employee.position || 'Not Assigned')}</td>
                <td><span class="status-badge ${getStatusClass(employee.worker_status)}">${escapeHtml(employee.worker_status || 'Active')}</span></td>
                <td class="salary">${formatCurrency(employee.salary)}</td>
                <td>${escapeHtml(employee.join_date || 'N/A')}</td>
                <td>
                    <div class="approval-info">
                        <span class="approval-badge ${escapeHtml(approvalClass)}">${escapeHtml(approval.label)}</span>
                        <span class="approval-by">${escapeHtml(approval.detail)}</span>
                    </div>
                </td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-action view" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${canManageEmployeeRecords ? `
                        <button class="btn-action edit" title="Edit Employee" aria-label="Edit ${escapeHtml(fullName)}">
                            <i class="fas fa-pen"></i>
                        </button>` : ''}
                        <button class="btn-action id-card" title="Worker ID Card">
                            <i class="fas fa-id-card"></i>
                        </button>
                        ${canApproveEmployeeRecords && approval.label.toLowerCase() !== 'approved' ? `
                        <button class="btn-action approve" title="Approve Employee" aria-label="Approve ${escapeHtml(fullName)}">
                            <i class="fas fa-user-check"></i>
                        </button>` : ''}
                        ${canArchiveEmployeeRecords ? `
                        <button class="btn-action delete" title="Archive">
                            <i class="fas fa-box-archive"></i>
                        </button>` : ''}
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function renderEmployeeList() {
    const filtered = getFilteredEmployees();
    const start = (employeeListState.page - 1) * employeeListState.pageSize;
    renderEmployeeTable(filtered.slice(start, start + employeeListState.pageSize));
    renderEmployeePagination(filtered.length);
}

function populateEmployeeFilters() {
    const populate = (id, values, fallback) => {
        const select = document.getElementById(id);
        if (!select) return;
        const selected = select.value;
        select.innerHTML = fallback + values.sort().map((value) => `<option value="${escapeHtml(value)}">${escapeHtml(value)}</option>`).join('');
        select.value = selected;
    };
    populate('roleFilter', [...new Set(employeeListState.employees.map((employee) => employee.position).filter(Boolean))], '<option value="">All Positions</option>');
}

function applyEmployeeFilters() {
    employeeListState.search = document.getElementById('searchInput')?.value.trim() || '';
    employeeListState.status = document.getElementById('statusFilter')?.value || '';
    employeeListState.position = document.getElementById('roleFilter')?.value || '';
    employeeListState.assignment = document.getElementById('siteAssignmentFilter')?.value || '';
    employeeListState.page = 1;
    renderEmployeeList();
}

async function approveEmployee(employeeId) {
    if (!canApproveEmployeeRecords || !employeeId) return;
    try {
        const result = await fetchJson(`${API_BASE}approve_employee.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ employee_id: employeeId, action_type: 'Employee Approval' })
        });
        if (!result.success) throw new Error(result.message || 'Failed to approve employee.');
        window.showCrudResultModal?.(true, result.message || 'Employee approved successfully.', 'Employee Approval');
        await loadEmployees();
    } catch (error) {
        window.showCrudResultModal?.(false, error.message || 'Unable to approve employee.', 'Employee Approval');
    }
}

// API base path injected from PHP pages. If missing, default to relative ../api/
// IMPORTANT: Some pages/environments were still throwing `ReferenceError: API_BASE is not defined`.
// To make this 100% safe, we also attach it to `window.API_BASE`.
let API_BASE = window.API_BASE;
if (API_BASE == null) {
    // If this page is /admin/worker.php, js/worker.js is loaded from ../js/...
    // In that case, ../api/ reaches /api/
    API_BASE = '../api/';
    window.API_BASE = API_BASE;
}


async function loadEmployees() {
    try {
        const data = await fetchJson(`${API_BASE}get_employees.php`);

        if (data.success) {
            employeeListState.employees = data.employees || [];
            populateEmployeeFilters();
            renderEmployeeList();
        }
    } catch (error) {
        console.error('Error loading employees:' , error);
    }
}

async function searchEmployees(term) {
    employeeListState.search = term || '';
    employeeListState.page = 1;
    renderEmployeeList();
}

async function filterBySite(siteId) {
    try {
        if (!siteId) {
            await loadEmployees();
            return;
        }

        const data = await fetchJson(`${API_BASE}filter_employees_by_site.php?site_id=${encodeURIComponent(siteId)}`);
        if (data.success) {
            renderEmployeeTable(data.employees);
        }
    } catch (error) {
        console.error('Site filter failed:', error);
    }
}

async function filterByStatus(status) {
    employeeListState.status = status || '';
    employeeListState.page = 1;
    renderEmployeeList();
}

function formatApprovalStatus(approval) {
    if (!approval) {
        return { label: 'Pending', detail: 'Not approved yet' };
    }

    const status = approval.Approval_Status || 'Pending';
    const approvedBy = approval.approved_by_name ? ` by ${approval.approved_by_name}` : '';
    const date = approval.Date ? ` on ${approval.Date}` : '';
    return {
        label: status,
        detail: `${status}${approvedBy}${date}`.trim()
    };
}

function openViewEmployeeModal() {
    const modal = document.getElementById('viewEmployeeModal');
    if (!modal) {
        return;
    }

    modal.classList.add('active');
    modal.style.display = 'flex';
}

function closeViewEmployeeModal() {
    const modal = document.getElementById('viewEmployeeModal');
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
    }
    viewEmployeeState.employee = null;
    viewEmployeeState.editMode = false;
    viewEmployeeState.attendanceLoaded = false;
    viewEmployeeState.historyLoaded = false;
    viewEmployeeState.historyLoading = false;
}

function profileDisplayValue(value) {
    const text = String(value ?? '').trim();
    return text === '' ? 'Not provided' : text;
}

function buildSiteOptions(selectedSiteId) {
    const options = ['<option value="">Not assigned</option>'];
    viewEmployeeState.sites.forEach((site) => {
        const id = site.SiteID ?? site.SiteId ?? site.site_id;
        const name = site.Site_Name || site.site_name || `Site ${id}`;
        const selected = Number(id) === Number(selectedSiteId) ? ' selected' : '';
        options.push(`<option value="${escapeHtml(String(id))}"${selected}>${escapeHtml(name)}</option>`);
    });
    return options.join('');
}

function buildStatusOptions(selectedStatusId) {
    if (viewEmployeeState.workerStatuses.length === 0) {
        return '<option value="">No statuses available</option>';
    }

    return viewEmployeeState.workerStatuses.map((status) => {
        const selected = Number(status.WorkerStatusID) === Number(selectedStatusId) ? ' selected' : '';
        return `<option value="${escapeHtml(String(status.WorkerStatusID))}"${selected}>${escapeHtml(status.Status)}</option>`;
    }).join('');
}

function buildPositionOptions(selectedPosition) {
    const currentPosition = String(selectedPosition || '').trim();
    const positions = [...viewEmployeeState.positions];
    if (currentPosition && !positions.some((position) => position.position_name === currentPosition)) {
        positions.unshift({ position_name: currentPosition });
    }
    const placeholder = currentPosition ? '' : '<option value="" selected disabled>Select role</option>';
    return placeholder + positions.map((position) => {
        const name = String(position.position_name || '').trim();
        const selected = name === currentPosition ? ' selected' : '';
        return `<option value="${escapeHtml(name)}"${selected}>${escapeHtml(name)}</option>`;
    }).join('');
}

function getGovernmentDeductionStatus(emp) {
    return String(emp?.GovernmentDeductionStatus || emp?.government_deduction_status || 'With Deductions');
}

function getGovernmentDeductionTypes(emp) {
    const rawTypes = emp?.GovernmentDeductionTypes ?? emp?.government_deduction_types;
    if (Array.isArray(rawTypes)) {
        return rawTypes.map((type) => String(type).toLowerCase());
    }
    if (typeof rawTypes === 'string' && rawTypes.trim()) {
        try {
            const parsed = JSON.parse(rawTypes);
            if (Array.isArray(parsed)) {
                return parsed.map((type) => String(type).toLowerCase());
            }
        } catch (error) {
            console.warn('Invalid government deduction type data:', error);
        }
    }
    return ['sss', 'philhealth', 'pagibig'];
}

function isGovernmentDeductionsEnabled(emp) {
    return getGovernmentDeductionStatus(emp) !== 'No Deductions';
}

function getGovernmentDeductionLabel(emp) {
    return isGovernmentDeductionsEnabled(emp)
        ? 'Government Deductions Enabled'
        : 'No Government Deductions';
}

function getGovernmentDeductionBadgeClass(emp) {
    return isGovernmentDeductionsEnabled(emp)
        ? 'government-deduction-badge enabled'
        : 'government-deduction-badge exempt';
}

function renderGovernmentDeductionBadge(emp) {
    return `<span class="${getGovernmentDeductionBadgeClass(emp)}">${escapeHtml(getGovernmentDeductionLabel(emp))}</span>`;
}

function renderGovernmentDeductionField(emp, editMode) {
    const status = getGovernmentDeductionStatus(emp);
    const selectedTypes = getGovernmentDeductionTypes(emp);
    const canEdit = editMode && employeePageRole === 'Admin';

    if (!canEdit) {
        return `
            <div class="profile-field">
                <label>Government Deductions</label>
                <div class="profile-field-readonly">${renderGovernmentDeductionBadge(emp)}</div>
            </div>`;
    }

    return `
        <div class="profile-field profile-field-full">
            <label>Government Deductions</label>
            <div class="government-deduction-options">
                <label class="government-deduction-option">
                    <input type="radio" name="government_deduction_status" value="With Deductions"${status !== 'No Deductions' ? ' checked' : ''}>
                    <span>Subject to Government Deductions</span>
                </label>
                <label class="government-deduction-option">
                    <input type="radio" name="government_deduction_status" value="No Deductions"${status === 'No Deductions' ? ' checked' : ''}>
                    <span>No Government Deductions</span>
                </label>
            </div>
            <div class="government-deduction-types" data-profile-deduction-types${status === 'No Deductions' ? ' hidden' : ''}>
                <span class="government-deduction-types-label">Select applicable deductions</span>
                <div class="government-deduction-type-options">
                    ${[
                        ['sss', 'SSS'],
                        ['philhealth', 'PhilHealth'],
                        ['pagibig', 'Pag-IBIG']
                    ].map(([value, label]) => `
                        <label class="government-deduction-type-option">
                            <input type="checkbox" name="government_deduction_types[]" value="${value}"${selectedTypes.includes(value) ? ' checked' : ''}>
                            <span>${label}</span>
                        </label>`).join('')}
                </div>
            </div>
        </div>`;
}

function bindProfileDeductionToggles(form) {
    const radios = form?.querySelectorAll('input[name="government_deduction_status"]') || [];
    const typeContainer = form?.querySelector('[data-profile-deduction-types]');
    const checkboxes = form?.querySelectorAll('input[name="government_deduction_types[]"]') || [];
    const applyState = () => {
        const withDeductions = form?.querySelector('input[name="government_deduction_status"]:checked')?.value === 'With Deductions';
        if (typeContainer) typeContainer.hidden = !withDeductions;
        checkboxes.forEach((checkbox) => {
            checkbox.disabled = !withDeductions;
            checkbox.setCustomValidity('');
        });
        if (withDeductions && checkboxes.length > 0) {
            const hasSelection = Array.from(checkboxes).some((checkbox) => checkbox.checked);
            checkboxes[0].setCustomValidity(hasSelection ? '' : 'Select at least one government deduction.');
        }
    };
    radios.forEach((radio) => radio.addEventListener('change', applyState));
    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', applyState));
    applyState();
}

function renderProfileFields(emp, profile, editMode) {
    const siteLabel = emp.Site_Name
        ? `${emp.Site_Name}${emp.Location ? ` (${emp.Location})` : ''}`
        : 'Not assigned';

    const field = (label, viewValue, editHtml = '') => {
        if (editMode && editHtml) {
            return `
                <div class="profile-field">
                    <label>${escapeHtml(label)}</label>
                    ${editHtml}
                </div>`;
        }
        return `
            <div class="profile-field">
                <label>${escapeHtml(label)}</label>
                <div class="profile-field-readonly">${escapeHtml(profileDisplayValue(viewValue))}</div>
            </div>`;
    };

    const selectOptions = (values, selectedValue, placeholder) => {
        const selected = String(selectedValue || '');
        const options = values.slice();
        if (selected && !options.includes(selected)) options.push(selected);
        return [`<option value="">${escapeHtml(placeholder)}</option>`]
            .concat(options.map((value) => `<option value="${escapeHtml(value)}"${value === selected ? ' selected' : ''}>${escapeHtml(value)}</option>`))
            .join('');
    };
    const countryOptions = selectOptions(
        ['Philippines', 'Australia', 'Canada', 'Japan', 'Singapore', 'United Arab Emirates', 'United Kingdom', 'United States', 'Other'],
        profile.country,
        'Select country'
    );
    const relationshipOptions = selectOptions(
        ['Mother', 'Father', 'Parent', 'Sibling', 'Spouse', 'Child', 'Guardian', 'Relative', 'Friend', 'Other'],
        profile.emergency_contact_relationship,
        'Select relationship'
    ).replace('<option value="">', '<option value="" disabled hidden>');

    const personalHtml = `
        <div class="profile-section">
            <div class="profile-section-head">
                <h4>Personal Information</h4>
                <p>View personal details, address, and emergency contact information.</p>
            </div>
            <div class="profile-section-body profile-grid-2">
                ${field('Full Name', `${emp.First_Name || ''} ${emp.Last_Name || ''}`.trim(), `
                    <div class="profile-input-row">
                        <input type="text" name="first_name" class="profile-field-input" value="${escapeHtml(emp.First_Name || '')}" placeholder="First name">
                        <input type="text" name="last_name" class="profile-field-input" value="${escapeHtml(emp.Last_Name || '')}" placeholder="Last name">
                    </div>`)}
                ${field('Email Address', profile.email, `<input type="email" name="email" class="profile-field-input" value="${escapeHtml(profile.email || '')}">`)}
                ${field('Phone Number', emp.Phone, `<input type="text" name="phone" class="profile-field-input js-phone-input" value="${escapeHtml(emp.Phone || '')}" inputmode="numeric" pattern="[0-9]{11}" maxlength="11" title="Phone number must be exactly 11 digits">`)}
                ${field('Date of Birth', profile.date_of_birth, `<input type="date" name="date_of_birth" class="profile-field-input" value="${escapeHtml(profile.date_of_birth || '')}">`)}
            </div>
        </div>
        <div class="profile-section">
            <h4>Address</h4>
            <div class="profile-section-body profile-grid-2">
                ${field('Street Address', profile.street_address, `<input type="text" name="street_address" class="profile-field-input" value="${escapeHtml(profile.street_address || '')}">`)}
                ${field('City', profile.city, `<input type="text" name="city" class="profile-field-input" value="${escapeHtml(profile.city || '')}">`)}
                ${field('State / Province', profile.state_province, `<input type="text" name="state_province" class="profile-field-input" value="${escapeHtml(profile.state_province || '')}">`)}
                ${field('Postal / ZIP Code', profile.postal_code, `<input type="text" name="postal_code" class="profile-field-input" value="${escapeHtml(profile.postal_code || '')}">`)}
                ${field('Country', profile.country, `<select name="country" class="profile-field-input">${countryOptions}</select>`)}
            </div>
        </div>
        <div class="profile-section">
            <h4>Emergency Contact</h4>
            <div class="profile-section-body profile-grid-2">
                ${field('Contact Name', profile.emergency_contact_name, `<input type="text" name="emergency_contact_name" class="profile-field-input" value="${escapeHtml(profile.emergency_contact_name || '')}">`)}
                ${field('Contact Phone', profile.emergency_contact_phone, `<input type="text" name="emergency_contact_phone" class="profile-field-input js-phone-input" value="${escapeHtml(profile.emergency_contact_phone || '')}" inputmode="numeric" pattern="[0-9]{11}" maxlength="11" title="Emergency contact phone must be exactly 11 digits">`)}
                ${field('Relationship', profile.emergency_contact_relationship, `<select name="emergency_contact_relationship" class="profile-field-input">${relationshipOptions}</select>`)}
            </div>
        </div>`;

    const systemHtml = `
        <div class="profile-section">
            <h4>System Information</h4>
            <div class="profile-section-body profile-grid-2">
                ${field('Role', emp.position || 'Not Assigned', `<select name="position" class="profile-field-input" required>${buildPositionOptions(emp.position)}</select>`)}
                ${field('Current Site', siteLabel, `<select name="site_id" class="profile-field-input">${buildSiteOptions(emp.SiteID)}</select>`)}
                ${field('Join Date', emp.join_date, `<input type="date" name="join_date" class="profile-field-input" value="${escapeHtml(emp.join_date || '')}">`)}
                ${field('Employment Status', emp.worker_status, `<select name="worker_status_id" class="profile-field-input">${buildStatusOptions(emp.WorkerStatusID)}</select>`)}
                ${field('Rate Type', emp.RateType, `<select name="rate_type" class="profile-field-input">
                    <option value="Hourly"${emp.RateType === 'Hourly' ? ' selected' : ''}>Hourly</option>
                    <option value="Salary"${emp.RateType === 'Salary' ? ' selected' : ''}>Salary</option>
                </select>`)}
                ${field('Salary', formatCurrency(Math.max(0, Number(emp.salary) || 0)), `<input type="number" name="salary" class="profile-field-input" step="0.01" min="0" value="${escapeHtml(Math.max(0, Number(emp.salary) || 0))}">`)}
                ${renderGovernmentDeductionField(emp, editMode)}
            </div>
        </div>`;

    return personalHtml + systemHtml;
}

function normalizePhoneValue(value) {
    return String(value || '').replace(/\D/g, '').slice(0, 11);
}

function normalizePhoneInputs(scope = document) {
    scope.querySelectorAll('input[name="phone"], input[name="emergency_contact_phone"]').forEach((input) => {
        input.value = normalizePhoneValue(input.value);
    });
}

function showEmployeeProfileFieldValidation(input, message) {
    if (!input) return false;
    input.setCustomValidity(message);
    input.setAttribute('aria-invalid', message ? 'true' : 'false');
    input.classList.toggle('employee-field-invalid', Boolean(message));
    const field = input.closest('.profile-field');
    let error = field?.querySelector('.employee-profile-field-error');
    if (message && field) {
        if (!error) {
            error = document.createElement('small');
            error.className = 'employee-profile-field-error';
            input.insertAdjacentElement('afterend', error);
        }
        error.textContent = message;
    } else if (error) {
        error.remove();
    }
    return !message;
}

function validateEmployeeProfileTextInput(input) {
    if (!input) return true;
    const rules = {
        street_address: { label: 'Street address', allowed: /[^\p{L}\p{N}\s,.-]/gu, pattern: /^[\p{L}\p{N}\s,.-]+$/u },
        city: { label: 'City', allowed: /[^\p{L}\s]/gu, pattern: /^[\p{L}]+(?: [\p{L}]+)*$/u },
        state_province: { label: 'State / Province', allowed: /[^\p{L}\s]/gu, pattern: /^[\p{L}]+(?: [\p{L}]+)*$/u },
        postal_code: { label: 'Postal / ZIP Code', allowed: /\D/gu, pattern: /^\d{4,10}$/u }
    };
    const rule = rules[input.name];
    if (!rule) return true;

    const originalValue = input.value;
    input.value = originalValue.replace(rule.allowed, '');
    let message = '';
    if (originalValue !== input.value) {
        message = `${rule.label} cannot contain special characters.`;
    } else if (!input.value.trim()) {
        message = `${rule.label} is required.`;
    } else if (!rule.pattern.test(input.value)) {
        message = input.name === 'postal_code'
            ? 'Postal / ZIP Code must contain 4 to 10 digits.'
            : `${rule.label} has an invalid format.`;
    }
    return showEmployeeProfileFieldValidation(input, message);
}

function bindEmployeeProfileTextValidation(form) {
    if (!form || form.dataset.profileTextValidationBound === '1') return;
    form.dataset.profileTextValidationBound = '1';
    form.querySelectorAll('input[name="street_address"], input[name="city"], input[name="state_province"], input[name="postal_code"]').forEach((input) => {
        input.addEventListener('input', () => validateEmployeeProfileTextInput(input));
        input.addEventListener('blur', () => validateEmployeeProfileTextInput(input));
    });
}

function validateEmployeeProfileTextFields(form) {
    return Array.from(form?.querySelectorAll('input[name="street_address"], input[name="city"], input[name="state_province"], input[name="postal_code"]') || [])
        .every((input) => validateEmployeeProfileTextInput(input));
}

function adultDateLimit() {
    const today = new Date();
    const limit = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
    return `${limit.getFullYear()}-${String(limit.getMonth() + 1).padStart(2, '0')}-${String(limit.getDate()).padStart(2, '0')}`;
}

function validateEmployeeDateOfBirth(input) {
    if (!input) return true;
    input.dataset.noLiveValidation = 'true';
    input.setAttribute('aria-label', 'Date of Birth');
    const maximumDate = adultDateLimit();
    input.max = maximumDate;
    const message = !input.value
        ? 'Date of birth is required.'
        : (input.value > maximumDate ? 'Employee must be at least 18 years old.' : '');
    input.setCustomValidity(message);
    input.classList.toggle('employee-field-invalid', Boolean(message));
    const container = input.closest('.form-group, .profile-field');
    let error = container?.querySelector('.employee-date-of-birth-error');
    if (message && container) {
        if (!error) {
            error = document.createElement('small');
            error.className = 'employee-date-of-birth-error';
            input.insertAdjacentElement('afterend', error);
        }
        error.textContent = message;
    } else if (error) {
        error.remove();
    }
    return !message;
}

const employeeEmailChecks = new WeakMap();

function showEmployeeEmailValidation(input, message, valid = false) {
    input.dataset.noLiveValidation = 'true';
    input.setAttribute('aria-label', 'Email Address');
    input.setCustomValidity(message);
    input.setAttribute('aria-invalid', message ? 'true' : 'false');
    input.classList.toggle('employee-field-invalid', Boolean(message));
    input.classList.toggle('field-live-valid', valid);
    input.classList.remove('field-live-invalid');
    let error = input.parentElement.querySelector('.employee-email-error');
    if (!error) {
        error = document.createElement('small');
        error.className = 'employee-email-error';
        error.style.color = '#dc2626';
        error.setAttribute('aria-live', 'polite');
        input.insertAdjacentElement('afterend', error);
    }
    error.textContent = message;
}

async function checkEmployeeEmail(form, excludeWorkerId = 0) {
    const input = form?.querySelector('input[name="email"]');
    if (!input) return true;
    const email = input.value.trim();
    const request = {};
    employeeEmailChecks.set(input, request);
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        const message = !email && !input.required ? '' : 'Enter a valid email address.';
        showEmployeeEmailValidation(input, message);
        return !message;
    }
    showEmployeeEmailValidation(input, 'Checking email availability…');
    try {
        const params = new URLSearchParams({ email, exclude_worker_id: String(excludeWorkerId) });
        const result = await fetchJson(`${API_BASE}check_worker_email.php?${params}`);
        if (employeeEmailChecks.get(input) !== request || input.value.trim() !== email) return false;
        if (!result.success || typeof result.duplicate !== 'boolean') throw new Error('Check failed');
        showEmployeeEmailValidation(input, result.duplicate ? 'This email address is already registered.' : '', !result.duplicate);
        return !result.duplicate;
    } catch {
        if (employeeEmailChecks.get(input) === request) {
            showEmployeeEmailValidation(input, 'Unable to verify email availability. Please try again.');
        }
        return false;
    }
}

function bindEmployeeEmailValidation(form, excludeWorkerId = 0) {
    const input = form?.querySelector('input[name="email"]');
    if (!input || input.dataset.emailCheckBound) return;
    input.dataset.emailCheckBound = 'true';
    input.dataset.noLiveValidation = 'true';
    let timer;
    input.addEventListener('input', () => {
        clearTimeout(timer);
        employeeEmailChecks.set(input, {});
        showEmployeeEmailValidation(input, input.value ? 'Checking email availability…' : (input.required ? 'Email address is required.' : ''));
        timer = setTimeout(() => checkEmployeeEmail(form, excludeWorkerId), 300);
    });
    input.addEventListener('change', () => {
        clearTimeout(timer);
        checkEmployeeEmail(form, excludeWorkerId);
    });
}

function bindEmployeeDateOfBirthValidation(form) {
    if (!form || form.dataset.employeeDobCheckBound === '1') return;
    form.dataset.employeeDobCheckBound = '1';
    const input = form.querySelector('input[name="date_of_birth"]');
    if (!input) return;
    input.dataset.noLiveValidation = 'true';
    input.setAttribute('aria-label', 'Date of Birth');
    input.required = true;
    input.max = adultDateLimit();
    input.addEventListener('input', () => validateEmployeeDateOfBirth(input));
    input.addEventListener('change', () => validateEmployeeDateOfBirth(input));
    input.addEventListener('blur', () => validateEmployeeDateOfBirth(input));
}

function syncAddEmployeePhoneFields(sourceInput = null) {
    const form = document.getElementById('addEmployeeForm');
    if (!form) {
        return;
    }

    const phoneInputs = Array.from(form.querySelectorAll('input[name="phone"]'));
    if (phoneInputs.length < 2) {
        return;
    }

    const phoneValue = normalizePhoneValue(sourceInput?.value || phoneInputs.find((input) => input.value.trim())?.value || '');
    phoneInputs.forEach((input) => {
        input.value = phoneValue;
    });
}

function validateWorkerNameInput(input, stripInvalid = false) {
    const original = input.value;
    const label = input.name === 'emergency_contact_name' ? 'Emergency contact name' : input.name === 'first_name' ? 'First name' : 'Last name';
    if (stripInvalid) input.value = original.replace(/[^\p{L}\s]/gu, '');
    const value = input.value;
    let message = '';
    if (stripInvalid && /[^\p{L}\s]/u.test(original)) message = `${label} cannot contain numbers or special characters.`;
    else if (!value.trim()) message = `${label} is required.`;
    else if (!/^[\p{L}]+(?: [\p{L}]+)*$/u.test(value)) message = `${label} must contain letters and single spaces only, without leading or trailing spaces.`;
    else if (value.length > 50) message = `${label} must not exceed 50 characters.`;
    input.setCustomValidity(message);
    input.setAttribute('aria-label', label);
    input.dispatchEvent(new Event('change', { bubbles: true }));
    return !message;
}

// Delegation also covers edit fields recreated by modal rendering.
document.addEventListener('input', event => {
    const input = event.target;
    if (!input.matches?.('#addEmployeeForm input[name="first_name"], #addEmployeeForm input[name="last_name"], #addEmployeeForm input[name="emergency_contact_name"], #viewEmployeeProfileForm input[name="first_name"], #viewEmployeeProfileForm input[name="last_name"], #viewEmployeeProfileForm input[name="emergency_contact_name"]')) return;
    if (/[^\p{L}\s]/u.test(input.value)) {
        event.workerNameRejected = true;
        clearTimeout(employeeNameCheckTimers.get(input.form));
        validateWorkerNameInput(input, true);
    } else if (input.name === 'emergency_contact_name') {
        validateWorkerNameInput(input);
    }
}, true);

function setEmployeeNameValidity(form, message = '') {
    const firstNameInput = form?.querySelector('input[name="first_name"]');
    const lastNameInput = form?.querySelector('input[name="last_name"]');
    if (firstNameInput) {
        validateWorkerNameInput(firstNameInput);
    }
    if (lastNameInput) {
        validateWorkerNameInput(lastNameInput);
        if (message) lastNameInput.setCustomValidity(message);
        lastNameInput.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

async function checkEmployeeNameDuplicate(form, excludeWorkerId = 0) {
    const firstName = form?.querySelector('input[name="first_name"]')?.value.trim() || '';
    const lastName = form?.querySelector('input[name="last_name"]')?.value.trim() || '';
    setEmployeeNameValidity(form, '');

    if (!firstName || !lastName) return false;
    if (!Array.from(form.querySelectorAll('input[name="first_name"], input[name="last_name"]')).every(input => input.checkValidity())) return true;

    const params = new URLSearchParams({ first_name: firstName, last_name: lastName });
    if (excludeWorkerId > 0) params.set('exclude_worker_id', String(excludeWorkerId));

    try {
        const result = await fetchJson(`${API_BASE}check_duplicate_employee.php?${params.toString()}`);
        if (form.querySelector('input[name="first_name"]').value.trim() !== firstName ||
            form.querySelector('input[name="last_name"]').value.trim() !== lastName) return true;
        const message = result.duplicate
            ? (result.message || 'An employee with this first name and last name already exists.')
            : '';
        setEmployeeNameValidity(form, message);
        return Boolean(result.duplicate);
    } catch (error) {
        console.warn('Could not check employee name:', error);
        return false;
    }
}

function bindEmployeeNameDuplicateValidation(form, excludeWorkerId = 0) {
    if (!form || form.dataset.employeeNameCheckBound === '1') return;
    form.dataset.employeeNameCheckBound = '1';

    const inputs = form.querySelectorAll('input[name="first_name"], input[name="last_name"]');
    const scheduleCheck = (event) => {
        clearTimeout(employeeNameCheckTimers.get(form));
        if (event.workerNameRejected) return;
        if (!validateWorkerNameInput(event.target, true)) return;
        if (!Array.from(inputs).every(input => validateWorkerNameInput(input))) return;
        employeeNameCheckTimers.set(form, setTimeout(() => {
            checkEmployeeNameDuplicate(form, excludeWorkerId);
        }, 250));
    };

    inputs.forEach((input) => {
        input.maxLength = 50;
        input.required = true;
        input.setAttribute('aria-label', input.name === 'first_name' ? 'First name' : 'Last name');
        input.addEventListener('input', scheduleCheck);
        input.addEventListener('blur', () => checkEmployeeNameDuplicate(form, excludeWorkerId));
    });
}

function applySuggestedEmployeeSalary() {
    const positionSelect = document.getElementById('employeePosition');
    const rateTypeSelect = document.querySelector('#addEmployeeForm select[name="rate_type"]');
    const salaryInput = document.getElementById('employeeSalary');
    const selectedOption = positionSelect?.selectedOptions?.[0];
    if (!selectedOption || !salaryInput) return;

    const rateType = rateTypeSelect?.value === 'Salary' ? 'salaryRate' : 'hourlyRate';
    const suggestedAmount = selectedOption.dataset[rateType] || '';
    if (suggestedAmount) salaryInput.value = suggestedAmount;
}

async function loadEmployeePositionCatalog() {
    const positionSelect = document.getElementById('employeePosition');
    if (!positionSelect) return;
    try {
        const data = await fetchJson(`${API_BASE}get_position_catalog.php`);
        if (!data.success || !Array.isArray(data.positions) || !data.positions.length) return;
        positionSelect.innerHTML = '<option value="" selected disabled>Select position</option>' + data.positions.map((position) => `
            <option value="${escapeHtml(position.position_name)}"
                data-hourly-rate="${Number(position.hourly_rate)}"
                data-salary-rate="${Number(position.salary_rate)}">${escapeHtml(position.position_name)}</option>
        `).join('');
    } catch (error) {
        console.error('Unable to load employee positions:', error);
    }
}

function renderViewEmployeeModal(emp, workerStatuses = []) {
    const body = document.getElementById('viewEmployeeBody');
    if (!body) {
        return;
    }

    viewEmployeeState.employee = emp;
    viewEmployeeState.workerStatuses = workerStatuses;
    viewEmployeeState.activeTab = 'profile';
    viewEmployeeState.editMode = false;
    viewEmployeeState.attendanceLoaded = false;
    viewEmployeeState.historyLoaded = false;
    viewEmployeeState.historyLoading = false;
    viewEmployeeState.attendanceDays = 30;
    viewEmployeeState.historyTimeline = [];

    const fullName = emp.full_name || `${emp.First_Name || ''} ${emp.Last_Name || ''}`.trim();
    const initial = (emp.First_Name || fullName || 'E').charAt(0).toUpperCase();
    const profile = emp.profile || {};
    const photoHtml = emp.photo_path
        ? `<img src="${escapeHtml(resolveAssetPath(emp.photo_path))}" alt="${escapeHtml(fullName)}">`
        : escapeHtml(initial);

    const qrDownloadBtn = emp.qr_code_path
        ? `<a class="btn-profile-qr" href="${escapeHtml(resolveAssetPath(emp.qr_code_path))}" target="_blank" rel="noopener"><i class="fas fa-download"></i> Download QR</a>`
        : '';

    body.innerHTML = `
        <div class="view-employee-header">
            <div class="view-employee-avatar">${photoHtml}</div>
            <div class="view-employee-intro">
                <h3 class="view-employee-name">${escapeHtml(fullName)}</h3>
                <p class="view-employee-role"><i class="fas fa-briefcase"></i> ${escapeHtml(emp.position || 'Not Assigned')}</p>
                <div class="view-employee-badges">
                    <span class="status-badge ${getStatusClass(emp.worker_status)}">${escapeHtml(emp.worker_status || 'Active')}</span>
                    ${renderGovernmentDeductionBadge(emp)}
                </div>
            </div>
            <div class="view-employee-header-actions">
                ${qrDownloadBtn}
            </div>
        </div>
        <div class="profile-tabs" id="profileTabs">
            <button type="button" class="profile-tab active" data-profile-tab="profile">Profile Details</button>
            <button type="button" class="profile-tab" data-profile-tab="attendance">Attendance Records</button>
            <button type="button" class="profile-tab" data-profile-tab="history">Employment History</button>
        </div>
        <div class="profile-tab-panel active" id="profileTabProfile">
            <form id="viewEmployeeProfileForm" class="profile-form">
                ${renderProfileFields(emp, profile, false)}
            </form>
        </div>
        <div class="profile-tab-panel" id="profileTabAttendance">
            <p class="profile-tab-loading">Loading attendance records...</p>
        </div>
        <div class="profile-tab-panel" id="profileTabHistory">
            <p class="profile-tab-loading">Loading employment history...</p>
        </div>
        <div class="view-employee-footer-btns">
            <button type="button" class="btn-profile-save" id="viewEmployeeSaveBtn" hidden><i class="fas fa-save"></i> Save</button>
            <button type="button" class="btn-profile-cancel" id="viewEmployeeCancelBtn" hidden>Cancel</button>
            <button type="button" class="btn-view-id-card" id="viewEmployeeIdCardBtn">View ID Card</button>
            <button type="button" class="btn-submit view-close-btn" id="viewEmployeeCloseBtn">Close</button>
        </div>
    `;

    document.getElementById('viewEmployeeCloseBtn')?.addEventListener('click', closeViewEmployeeModal);
    document.getElementById('viewEmployeeIdCardBtn')?.addEventListener('click', () => {
        closeViewEmployeeModal();
        openWorkerIdCardModal(emp.WorkerID);
    });
    document.getElementById('viewEmployeeCancelBtn')?.addEventListener('click', async () => {
        if (viewEmployeeState.employee?.WorkerID) {
            await viewEmployee(viewEmployeeState.employee.WorkerID);
        }
    });
    document.getElementById('viewEmployeeSaveBtn')?.addEventListener('click', saveEmployeeProfile);

    document.querySelectorAll('[data-profile-tab]').forEach((tab) => {
        tab.addEventListener('click', () => switchProfileTab(tab.dataset.profileTab));
    });
}

function setProfileEditMode(enabled) {
    if (!viewEmployeeState.employee) {
        return;
    }

    viewEmployeeState.editMode = enabled;
    const emp = viewEmployeeState.employee;
    const profile = emp.profile || {};
    const form = document.getElementById('viewEmployeeProfileForm');
    if (form) {
        form.innerHTML = renderProfileFields(emp, profile, enabled);
        if (enabled) {
            bindEmployeeNameDuplicateValidation(form, Number(emp.WorkerID || 0));
            bindEmployeeDateOfBirthValidation(form);
            bindEmployeeEmailValidation(form, Number(emp.WorkerID || 0));
            bindEmployeeProfileTextValidation(form);
        }
        if (enabled) bindProfileDeductionToggles(form);
    }

    document.getElementById('viewEmployeeEditBtn')?.toggleAttribute('hidden', enabled);
    document.getElementById('viewEmployeeSaveBtn')?.toggleAttribute('hidden', !enabled);
    document.getElementById('viewEmployeeCancelBtn')?.toggleAttribute('hidden', !enabled);
}

function collectProfileFormData() {
    const form = document.getElementById('viewEmployeeProfileForm');
    if (form) {
        normalizePhoneInputs(form);
    }
    const getValue = (name) => form?.querySelector(`[name="${name}"]`)?.value ?? '';
    const governmentDeductionInput = form?.querySelector('input[name="government_deduction_status"]:checked');

    return {
        employee_id: viewEmployeeState.employee.WorkerID,
        first_name: getValue('first_name'),
        last_name: getValue('last_name'),
        phone: getValue('phone'),
        position: getValue('position'),
        join_date: getValue('join_date'),
        rate_type: getValue('rate_type'),
        salary: getValue('salary'),
        worker_status_id: Number(getValue('worker_status_id') || 0),
        site_id: getValue('site_id') === '' ? null : Number(getValue('site_id')),
        government_deduction_status: governmentDeductionInput?.value || getGovernmentDeductionStatus(viewEmployeeState.employee),
        government_deduction_types: Array.from(form?.querySelectorAll('input[name="government_deduction_types[]"]:checked') || [])
            .map((checkbox) => checkbox.value),
        profile: {
            email: getValue('email'),
            date_of_birth: getValue('date_of_birth'),
            street_address: getValue('street_address'),
            city: getValue('city'),
            state_province: getValue('state_province'),
            postal_code: getValue('postal_code'),
            country: getValue('country'),
            emergency_contact_name: getValue('emergency_contact_name'),
            emergency_contact_phone: getValue('emergency_contact_phone'),
            emergency_contact_relationship: getValue('emergency_contact_relationship')
        }
    };
}

async function saveEmployeeProfile() {
    if (!viewEmployeeState.employee) {
        return;
    }

    const profileForm = document.getElementById('viewEmployeeProfileForm');
    if (!await checkEmployeeEmail(profileForm, Number(viewEmployeeState.employee.WorkerID || 0))) {
        profileForm?.reportValidity();
        return;
    }
    validateEmployeeDateOfBirth(profileForm?.querySelector('input[name="date_of_birth"]'));
    validateEmployeeProfileTextFields(profileForm);
    if (!profileForm?.checkValidity()) {
        profileForm?.reportValidity();
        return;
    }
    if (await checkEmployeeNameDuplicate(profileForm, Number(viewEmployeeState.employee.WorkerID || 0))) {
        profileForm?.reportValidity();
        return;
    }
    const payload = collectProfileFormData();
    try {
        const result = await fetchJson(`${API_BASE}update_employee.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (!result.success) {
            window.showCrudResultModal?.(
                false,
                result.message || 'Failed to update employee profile.',
                'Employee Update'
            );
            return;
        }

        window.showCrudResultModal?.(
            true,
            result.message || 'Employee profile updated successfully.',
            'Employee Update'
        );
        viewEmployeeState.historyLoaded = false;
        await viewEmployee(viewEmployeeState.employee.WorkerID, true);
        await loadEmployees();
    } catch (error) {
        console.error('Save employee profile failed:', error);
        window.showCrudResultModal?.(
            false,
            error.message || 'Unable to save employee profile. Please try again.',
            'Employee Update'
        );
    }
}

function switchProfileTab(tabName) {
    viewEmployeeState.activeTab = tabName;
    document.querySelectorAll('[data-profile-tab]').forEach((tab) => {
        tab.classList.toggle('active', tab.dataset.profileTab === tabName);
    });
    document.getElementById('profileTabProfile')?.classList.toggle('active', tabName === 'profile');
    document.getElementById('profileTabAttendance')?.classList.toggle('active', tabName === 'attendance');
    document.getElementById('profileTabHistory')?.classList.toggle('active', tabName === 'history');

    const editBtn = document.getElementById('viewEmployeeEditBtn');
    const saveBtn = document.getElementById('viewEmployeeSaveBtn');
    const cancelBtn = document.getElementById('viewEmployeeCancelBtn');
    const showEditControls = tabName === 'profile';
    editBtn?.toggleAttribute('hidden', !showEditControls || viewEmployeeState.editMode);
    saveBtn?.toggleAttribute('hidden', !showEditControls || !viewEmployeeState.editMode);
    cancelBtn?.toggleAttribute('hidden', !showEditControls || !viewEmployeeState.editMode);

    if (tabName === 'attendance' && !viewEmployeeState.attendanceLoaded) {
        loadEmployeeAttendanceTab(viewEmployeeState.employee.WorkerID);
    }
    if (tabName === 'history'
        && viewEmployeeState.employee?.WorkerID
        && !viewEmployeeState.historyLoaded
        && !viewEmployeeState.historyLoading) {
        loadEmployeeHistoryTab(viewEmployeeState.employee.WorkerID);
    }
}

function bindAttendanceFilter(workerId) {
    const select = document.getElementById('attendanceDaysFilter');
    if (!select || select.dataset.bound === '1') {
        return;
    }
    select.dataset.bound = '1';
    select.addEventListener('change', async () => {
        viewEmployeeState.attendanceDays = Number(select.value) || 30;
        viewEmployeeState.attendanceLoaded = false;
        await loadEmployeeAttendanceTab(workerId);
        viewEmployeeState.attendanceLoaded = true;
    });
}

async function loadEmployeeAttendanceTab(workerId) {
    const panel = document.getElementById('profileTabAttendance');
    if (!panel) {
        return;
    }

    const days = viewEmployeeState.attendanceDays || 30;
    panel.innerHTML = '<p class="profile-tab-loading">Loading attendance records...</p>';

    try {
        const data = await fetchJson(
            `${API_BASE}get_worker_attendance.php?worker_id=${workerId}&limit=30&days=${days}`
        );
        viewEmployeeState.attendanceLoaded = true;
        if (!data.success) {
            panel.innerHTML = `<p class="profile-tab-empty">${escapeHtml(data.message || 'Unable to load attendance.')}</p>`;
            return;
        }

        const summary = data.summary || {};
        const records = data.records || [];
        const totalDays = summary.total_days ?? records.length;
        const attendanceRate = summary.attendance_rate ?? 0;
        const avgHours = summary.avg_hours ?? 0;
        const lateArrivals = summary.late_arrivals ?? 0;
        const absences = summary.absences ?? 0;

        const rows = records.length === 0
            ? '<tr><td colspan="5" class="attendance-empty-row">No attendance records in this period.</td></tr>'
            : records.map((row) => {
                const isAbsent = row.AttendanceStatus === 'Absent';
                return `
            <tr>
                <td class="attendance-date-cell">${escapeHtml(formatAttendanceDisplayDate(row.Date))}</td>
                <td>${renderAttendanceStatusPill(row.AttendanceStatus)}</td>
                <td>${isAbsent ? '—' : escapeHtml(formatAttendanceTime(row.Time_In))}</td>
                <td>${isAbsent ? '—' : escapeHtml(formatAttendanceTime(row.Time_Out))}</td>
                <td class="attendance-hours-cell">${isAbsent ? '—' : escapeHtml(formatAttendanceHours(row.Hours_Worked))}</td>
            </tr>`;
            }).join('');

        panel.innerHTML = `
            <div class="attendance-tab-content">
                <div class="attendance-tab-toolbar">
                    <h4 class="attendance-tab-title">Attendance History</h4>
                    <div class="attendance-tab-filter">
                        <i class="fas fa-filter" aria-hidden="true"></i>
                        <select id="attendanceDaysFilter" class="attendance-days-select" aria-label="Attendance period">
                            <option value="7"${days === 7 ? ' selected' : ''}>Last 7 Days</option>
                            <option value="30"${days === 30 ? ' selected' : ''}>Last 30 Days</option>
                            <option value="90"${days === 90 ? ' selected' : ''}>Last 90 Days</option>
                        </select>
                    </div>
                </div>
                <div class="attendance-stats-row">
                    <div class="attendance-stat-card">
                        <span class="attendance-stat-value">${escapeHtml(String(attendanceRate))}%</span>
                        <span class="attendance-stat-label">Attendance Rate</span>
                        <span class="attendance-stat-sub attendance-stat-sub-green">Based on ${totalDays} days</span>
                    </div>
                    <div class="attendance-stat-card">
                        <span class="attendance-stat-value">${escapeHtml(String(avgHours))}h</span>
                        <span class="attendance-stat-label">Avg. Hours</span>
                        <span class="attendance-stat-sub">Per worked day</span>
                    </div>
                    <div class="attendance-stat-card">
                        <span class="attendance-stat-value attendance-stat-warn">${escapeHtml(String(lateArrivals))}</span>
                        <span class="attendance-stat-label">Late Arrivals</span>
                        <span class="attendance-stat-sub">Days late</span>
                    </div>
                    <div class="attendance-stat-card">
                        <span class="attendance-stat-value attendance-stat-danger">${escapeHtml(String(absences))}</span>
                        <span class="attendance-stat-label">Absences</span>
                        <span class="attendance-stat-sub">Days absent</span>
                    </div>
                </div>
                <div class="attendance-table-wrap">
                    <table class="attendance-records-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Hours</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            </div>`;

        bindAttendanceFilter(workerId);
    } catch (error) {
        console.error('Load attendance tab failed:', error);
        panel.innerHTML = '<p class="profile-tab-empty">Unable to load attendance records.</p>';
    }
}

async function loadEmployeeHistoryTab(workerId) {
    const panel = document.getElementById('profileTabHistory');
    if (!panel) {
        return;
    }

    viewEmployeeState.historyLoading = true;
    panel.innerHTML = '<p class="profile-tab-loading">Loading employment history...</p>';

    try {
        const data = await fetchJson(`${API_BASE}get_worker_history.php?worker_id=${workerId}`);
        if (Number(viewEmployeeState.employee?.WorkerID) !== Number(workerId)) {
            return;
        }
        viewEmployeeState.historyLoaded = true;
        if (!data.success) {
            panel.innerHTML = `<p class="profile-tab-empty">${escapeHtml(data.message || 'Unable to load history.')}</p>`;
            return;
        }

        const timeline = data.timeline || data.history || [];
        if (timeline.length === 0) {
            panel.innerHTML = `
                <div class="employment-timeline-panel">
                    <h4 class="employment-timeline-title">Employment Timeline</h4>
                    <p class="profile-tab-empty">No employment history records found for this employee.</p>
                </div>`;
            return;
        }

        viewEmployeeState.historyTimeline = timeline;
        const eventTypes = [...new Set(timeline.map((event) => event.title).filter(Boolean))];
        panel.innerHTML = `
            <div class="employment-timeline-panel">
                <div class="employment-timeline-header">
                    <h4 class="employment-timeline-title">Employment Timeline</h4>
                    <div class="history-filters">
                        <select id="historyEventFilter" data-history-filter aria-label="Filter employment history event">
                            <option value="">All events</option>
                            ${eventTypes.map((title) => `<option value="${escapeHtml(title)}">${escapeHtml(title)}</option>`).join('')}
                        </select>
                        <input type="date" id="historyDateFrom" data-history-filter aria-label="History start date">
                        <input type="date" id="historyDateTo" data-history-filter aria-label="History end date">
                    </div>
                </div>
                <ul class="employment-timeline-list" id="employmentTimelineList"></ul>
            </div>`;
        renderEmploymentHistory(panel);
        bindEmploymentHistoryFilters(panel);
    } catch (error) {
        console.error('Load history tab failed:', error);
        panel.innerHTML = '<p class="profile-tab-empty">Unable to load employment history.</p>';
    } finally {
        viewEmployeeState.historyLoading = false;
    }
}

async function loadSitesForProfile() {
    try {
        const sites = await fetchJson(`${API_BASE}get_sites.php`);
        viewEmployeeState.sites = Array.isArray(sites) ? sites : (sites.sites || []);
    } catch (error) {
        console.error('Load sites for profile failed:', error);
        viewEmployeeState.sites = [];
    }
}

async function loadPositionsForProfile() {
    try {
        const data = await fetchJson(`${API_BASE}get_position_catalog.php`);
        viewEmployeeState.positions = data.success && Array.isArray(data.positions) ? data.positions : [];
    } catch (error) {
        console.error('Load positions for profile failed:', error);
        viewEmployeeState.positions = [];
    }
}

async function viewEmployee(id, editMode = false) {
    try {
        await Promise.all([loadSitesForProfile(), loadPositionsForProfile()]);
        const data = await fetchJson(`${API_BASE}get_employee.php?id=${id}`);
        if (!data.success) {
            alert(`Failed: ${data.message}`);
            return;
        }

        renderViewEmployeeModal(data.employee, data.worker_statuses || []);
        openViewEmployeeModal();
        if (editMode && canManageEmployeeRecords) {
            setProfileEditMode(true);
        }
    } catch (error) {
        console.error('View employee failed:', error);
        alert('Unable to load employee details. Please try again.');
    }
}


function getCompanyShortName(companyName) {
    if (!companyName) {
        return 'Philippians CDO';
    }
    if (companyName.toLowerCase().includes('philippians')) {
        return 'Philippians CDO';
    }
    return companyName;
}

function closeWorkerIdCardModal() {
    document.getElementById('workerIdCardModal')?.classList.remove('active');
}

function renderWorkerIdCardModal(emp, company = {}) {
    const body = document.getElementById('workerIdCardBody');
    const downloadBtn = document.getElementById('workerIdCardDownloadBtn');
    if (!body) {
        return;
    }

    const fullName = emp.full_name || `${emp.First_Name || ''} ${emp.Last_Name || ''}`.trim();
    const initial = (emp.First_Name || fullName || 'W').charAt(0).toUpperCase();
    const companyLabel = getCompanyShortName(company.company_name);
    const subtitle = emp.position || 'Construction Worker';
    const siteName = emp.Site_Name || 'Not Assigned';
    const siteLocation = emp.Location || emp.site_location || 'Cagayan de Oro City';
    const scanLocation = siteLocation || company.address || 'N/A';
    const hasQr = Boolean(emp.qr_code_path);

    const photoHtml = emp.photo_path
        ? `<img src="${escapeHtml(resolveAssetPath(emp.photo_path))}" alt="${escapeHtml(fullName)}">`
        : escapeHtml(initial);

    const qrHtml = hasQr
        ? `<img class="id-card-qr-image" src="${escapeHtml(resolveAssetPath(emp.qr_code_path))}" alt="QR Code">`
        : '<p class="id-card-qr-missing">QR code not generated yet.</p>';

    body.innerHTML = `
        <p class="id-card-section-label">FRONT</p>
        <div class="id-card-front">
            <div class="id-card-front-top">
                <span>${escapeHtml(companyLabel)}</span>
                <span>${escapeHtml(subtitle)}</span>
            </div>
            <div class="id-card-front-main">
                <div class="id-card-photo">${photoHtml}</div>
                <div class="id-card-front-info">
                    <h3>${escapeHtml(fullName)}</h3>
                    <p class="id-card-site">${escapeHtml(siteName)}</p>
                    <p class="id-card-location"><i class="fas fa-map-marker-alt"></i> ${escapeHtml(siteLocation)}</p>
                </div>
            </div>
            <div class="id-card-front-bottom">
                <div>
                    <span class="id-card-meta-label">Scan Location</span>
                    <span class="id-card-meta-value">${escapeHtml(scanLocation)}</span>
                </div>
            </div>
        </div>
        <p class="id-card-section-label">BACK</p>
        <div class="id-card-back">
            ${qrHtml}
            <p class="id-card-back-caption">Scan this QR code at the Timekeeper</p>
        </div>
    `;

    if (downloadBtn) {
        downloadBtn.disabled = !hasQr;
        downloadBtn.title = hasQr ? 'Download PNG image' : 'QR code required before download';
    }
}

async function openWorkerIdCardModal(workerId) {
    currentCardWorkerId = Number(workerId);
    if (!currentCardWorkerId) {
        return;
    }

    try {
        const [employeeData, companyData] = await Promise.all([
            fetchJson(`${API_BASE}get_employee.php?id=${currentCardWorkerId}`),
            fetchJson(`${API_BASE}get_company_settings.php`)
        ]);

        if (!employeeData.success) {
            alert(`Failed: ${employeeData.message}`);
            return;
        }

        const company = companyData.success ? companyData.data : {};
        const employee = employeeData.employee || {};
        currentCardWorkerName = String(employee.full_name || `${employee.First_Name || ''} ${employee.Last_Name || ''}`.trim());
        renderWorkerIdCardModal(employee, company);
        document.getElementById('workerIdCardModal')?.classList.add('active');
    } catch (error) {
        console.error('Open worker ID card failed:', error);
        alert('Unable to load worker ID card. Please try again.');
    }
}

async function downloadWorkerIdCardPdf(workerId) {
    const id = Number(workerId || currentCardWorkerId);
    if (!id) {
        return;
    }

    try {
        if (id !== currentCardWorkerId) throw new Error('Open this worker’s ID card before downloading.');
        const body = document.getElementById('workerIdCardBody');
        const qr = body?.querySelector('.id-card-qr-image');
        const photo = body?.querySelector('.id-card-photo img');
        if (!qr) throw new Error('The worker ID needs a QR code before downloading.');
        await Promise.all([qr, photo].filter(Boolean).map(image => image.decode()));
        const canvas = document.createElement('canvas');
        canvas.width = 1200;
        canvas.height = 1500;
        const context = canvas.getContext('2d');
        if (!context) throw new Error('Image downloads are not supported by this browser.');
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        const text = selector => body.querySelector(selector)?.textContent.trim() || '';
        const write = (value, x, y, width, size = 30, color = '#1f2937') => {
            context.fillStyle = color;
            context.font = `${size}px Arial, sans-serif`;
            const words = String(value).split(/\s+/);
            let line = '';
            for (const word of words) {
                const candidate = line ? `${line} ${word}` : word;
                if (line && context.measureText(candidate).width > width) {
                    context.fillText(line, x, y, width);
                    y += size * 1.25;
                    line = word;
                } else line = candidate;
            }
            context.fillText(line, x, y, width);
            return y + size * 1.25;
        };
        context.strokeStyle = '#d1d5db';
        context.lineWidth = 3;
        context.strokeRect(40, 40, 1120, 650);
        context.strokeRect(40, 730, 1120, 730);
        context.fillStyle = '#b45309';
        context.fillRect(40, 40, 1120, 125);
        write(text('.id-card-front-top span:first-child'), 75, 95, 1040, 36, '#ffffff');
        write(text('.id-card-front-top span:last-child'), 75, 140, 1040, 25, '#ffffff');
        if (photo) {
            const crop = Math.min(photo.naturalWidth, photo.naturalHeight);
            context.drawImage(photo, (photo.naturalWidth - crop) / 2, (photo.naturalHeight - crop) / 2, crop, crop, 80, 210, 240, 240);
        } else {
            context.fillStyle = '#e0f2fe';
            context.fillRect(80, 210, 240, 240);
            write(text('.id-card-photo'), 155, 355, 100, 85);
        }
        let y = write(text('.id-card-front-info h3'), 360, 250, 730, 42);
        y = write(text('.id-card-site'), 360, y + 25, 730, 30);
        write(text('.id-card-location'), 360, y + 15, 730, 26);
        write('Scan Location', 80, 530, 1040, 24, '#6b7280');
        write(text('.id-card-meta-value'), 80, 575, 1040, 29);
        write('WORKER ID — BACK', 80, 790, 1040, 25, '#6b7280');
        context.imageSmoothingEnabled = false;
        context.drawImage(qr, 350, 840, 500, 500);
        write('Scan this QR code at the Timekeeper', 260, 1410, 720, 27);
        const blob = await new Promise((resolve, reject) => canvas.toBlob(value =>
            value ? resolve(value) : reject(new Error('Could not create the ID image.')), 'image/png'));
        const safeName = String(currentCardWorkerName || 'worker')
            .trim()
            .replace(/[^\w\-]+/g, '-')
            .replace(/^-+|-+$/g, '') || 'worker';
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `${safeName}-id-card.png`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    } catch (error) {
        console.error('Download worker ID card failed:', error);
        alert(error.message || 'Unable to download the ID card image. Please try again.');
    }
}

async function deleteEmployee(id) {
    if (!canArchiveEmployeeRecords) {
        alert('Only admins can archive employees.');
        return;
    }

    if (!await confirmEmployeeArchive()) {
        return;
    }

    try {
        const data = await fetchJson(`${API_BASE}delete_employee.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, user_id: currentUserId })
        });

        if (!data.success) {
            window.showCrudResultModal?.(
                false,
                data.message || 'Failed to archive employee.',
                'Employee Archive'
            );
            return;
        }

        await loadEmployees();
        window.showCrudResultModal?.(
            true,
            data.message || 'Employee archived successfully.',
            'Employee Archive'
        );
    } catch (error) {
        console.error('Archive employee failed:', error);
        window.showCrudResultModal?.(
            false,
            error.message || 'Unable to archive employee. Please try again.',
            'Employee Archive'
        );
    }
}

function confirmEmployeeArchive() {
    const modal = document.getElementById('archiveEmployeeModal');
    const confirmButton = document.getElementById('confirmArchiveEmployee');
    const cancelButton = document.getElementById('cancelArchiveEmployee');
    if (!modal || !confirmButton || !cancelButton) {
        return Promise.resolve(false);
    }

    modal.classList.add('active');
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
    confirmButton.focus();

    return new Promise((resolve) => {
        const finish = (confirmed) => {
            modal.classList.remove('active');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            confirmButton.removeEventListener('click', confirmArchive);
            cancelButton.removeEventListener('click', cancelArchive);
            modal.removeEventListener('click', cancelFromOverlay);
            document.removeEventListener('keydown', cancelFromEscape);
            resolve(confirmed);
        };
        const confirmArchive = () => finish(true);
        const cancelArchive = () => finish(false);
        const cancelFromOverlay = (event) => {
            if (event.target === modal) finish(false);
        };
        const cancelFromEscape = (event) => {
            if (event.key === 'Escape') finish(false);
        };

        confirmButton.addEventListener('click', confirmArchive);
        cancelButton.addEventListener('click', cancelArchive);
        modal.addEventListener('click', cancelFromOverlay);
        document.addEventListener('keydown', cancelFromEscape);
    });
}

function initializeAssignmentState() {
    const initialData = window.employeeAssignmentData || {};

    assignmentState.workers = Array.isArray(initialData.workers) ? initialData.workers.map((worker) => ({
        WorkerID: Number(worker.WorkerID),
        full_name: worker.full_name || 'Unknown Worker',
        phone: worker.phone || '',
        assigned_sites_count: Number(worker.assigned_sites_count || 0)
    })) : [];

    assignmentState.sites = Array.isArray(initialData.sites) ? initialData.sites.map((site) => ({
        SiteID: Number(site.SiteID),
        Site_Name: site.Site_Name || 'Unnamed Site',
        Location: site.Location || 'No location',
        Status: site.Status || 'Inactive',
        Required_Workers: Number(site.Required_Workers || 0),
        current_workers: Number(site.current_workers || 0)
    })) : [];

    assignmentState.selectedWorkerId = Number(initialData.selectedWorkerId || 0) || null;

    if (initialData.assignmentsByWorker && typeof initialData.assignmentsByWorker === 'object') {
        Object.entries(initialData.assignmentsByWorker).forEach(([workerId, siteIds]) => {
            if (Array.isArray(siteIds) && siteIds.length > 0) {
                setAssignedSiteIds(Number(workerId), siteIds.map((siteId) => Number(siteId)));
            }
        });
    }

    if (assignmentState.workers.length === 0) {
        assignmentState.workers = Array.from(document.querySelectorAll('#staffList .staff-item[data-worker-id], #staffList .staff-item[data-staff]')).map((item) => {
            const workerId = Number(item.dataset.workerId || item.dataset.staff);
            const name = item.querySelector('.staff-name')?.textContent?.trim() || 'Unknown Worker';
            const detailText = item.querySelector('.staff-email')?.textContent?.trim() || '';
            const badgeText = item.querySelector('.staff-sites-badge')?.textContent?.trim() || '0';
            const badgeMatch = badgeText.match(/\d+/);

            return {
                WorkerID: workerId,
                full_name: name,
                phone: detailText.replace(/^ID:\s*\d+\s*[.\-|Â·]\s*/u, ''),
                assigned_sites_count: badgeMatch ? Number(badgeMatch[0]) : 0
            };
        }).filter((worker) => worker.WorkerID);
    }

    const assignmentsEl = document.getElementById('assignmentsCount');
    if (assignmentsEl && typeof initialData.assignmentCount !== 'undefined') {
        assignmentsEl.textContent = initialData.assignmentCount;
    }
}

function getWorkerById(workerId) {
    return assignmentState.workers.find((worker) => worker.WorkerID === Number(workerId)) || null;
}

function getSiteById(siteId) {
    return assignmentState.sites.find((site) => site.SiteID === Number(siteId)) || null;
}

function getAssignedSiteIds(workerId) {
    return assignmentState.workerAssignments.get(Number(workerId)) || new Set();
}

function setAssignedSiteIds(workerId, siteIds) {
    assignmentState.workerAssignments.set(Number(workerId), new Set(siteIds.map((siteId) => Number(siteId))));
    assignmentState.loadedWorkers.add(Number(workerId));
}

function getSelectedWorker() {
    return getWorkerById(assignmentState.selectedWorkerId);
}

function getFilteredWorkers() {
    const term = assignmentState.searchTerm.trim().toLowerCase();
    if (!term) {
        return assignmentState.workers;
    }

    return assignmentState.workers.filter((worker) => (
        worker.full_name.toLowerCase().includes(term) ||
        String(worker.WorkerID).includes(term)
    ));
}

function loadDashboardStats() {
    const staffCountEl = document.getElementById('staffCount');
    const activeSitesEl = document.getElementById('activeSitesCount');

    if (staffCountEl) {
        staffCountEl.textContent = assignmentState.workers.length;
    }
    if (activeSitesEl) {
        activeSitesEl.textContent = assignmentState.sites.filter((site) => String(site.Status).toLowerCase() === 'active').length;
    }
}

function renderStaffList() {
    const container = document.getElementById('staffList');
    if (!container) {
        return;
    }

    const filteredWorkers = getFilteredWorkers();
    if (filteredWorkers.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-title">No workers found</div>
                <div class="empty-state-text">Try another name or worker ID.</div>
            </div>
        `;
        return;
    }

    container.innerHTML = filteredWorkers.map((worker) => {
        const assignedCount = Number(worker.assigned_sites_count || 0);
        const isSelected = worker.WorkerID === assignmentState.selectedWorkerId;

        return `
            <div class="staff-item${isSelected ? ' selected' : ''}"
                 data-staff="${Number(worker.WorkerID) || 0}"
                 data-worker-id="${Number(worker.WorkerID) || 0}"
                 draggable="true">
                <div class="staff-item-header">
                    <div class="staff-name">${escapeHtml(worker.full_name)}</div>
                    <span class="staff-sites-badge${assignedCount > 0 ? '' : ' hidden'}">
                        ${assignedCount} Site${assignedCount === 1 ? '' : 's'}
                    </span>
                </div>
                <div class="staff-email">${escapeHtml(worker.phone || 'No phone')}</div>
            </div>
        `;
    }).join('');
}

function updatePanelCopy() {
    const selectedWorker = getSelectedWorker();
    const panelTitle = document.getElementById('panelTitle');
    const panelSubtitle = document.getElementById('panelSubtitle');
    const selectedWorkerName = document.getElementById('selectedWorkerName');
    const selectedWorkerSites = document.getElementById('selectedWorkerSites');
    const emptyState = document.getElementById('emptyState');
    const sitesContent = document.getElementById('sitesContent');

    if (!selectedWorker) {
        if (emptyState) {
            emptyState.style.display = 'flex';
        }
        if (sitesContent) {
            sitesContent.style.display = 'none';
        }
        if (panelTitle) {
            panelTitle.textContent = 'Assign Sites to Worker';
        }
        if (panelSubtitle) {
            panelSubtitle.textContent = `${assignmentState.sites.length} sites available`;
        }
        if (selectedWorkerName) {
            selectedWorkerName.textContent = 'No worker selected';
        }
        if (selectedWorkerSites) {
            selectedWorkerSites.innerHTML = '';
        }
        return;
    }

    if (emptyState) {
        emptyState.style.display = 'none';
    }
    if (sitesContent) {
        sitesContent.style.display = 'block';
    }

    const assignedSiteIds = getAssignedSiteIds(selectedWorker.WorkerID);
    const assignedCount = assignedSiteIds.size || Number(selectedWorker.assigned_sites_count || 0);
    const assignedSiteNames = assignmentState.sites
        .filter((site) => assignedSiteIds.has(site.SiteID))
        .map((site) => escapeHtml(site.Site_Name));

    if (panelTitle) {
        panelTitle.textContent = `Assign Sites to ${selectedWorker.full_name}`;
    }
    if (panelSubtitle) {
        panelSubtitle.textContent = `${assignedCount} assigned site${assignedCount === 1 ? '' : 's'} | ${assignmentState.sites.length} total site${assignmentState.sites.length === 1 ? '' : 's'}`;
    }
    if (selectedWorkerName) {
        selectedWorkerName.textContent = selectedWorker.full_name;
    }
    if (selectedWorkerSites) {
        selectedWorkerSites.innerHTML = assignedSiteNames.length > 0
            ? assignedSiteNames.map((siteName) => `<span class="assigned-site-pill">${escapeHtml(siteName)}</span>`).join('')
            : '<span class="assigned-site-pill assigned-site-pill--empty">No assigned sites yet</span>';
    }
}

function renderSites(loading = false) {
    const container = document.getElementById('sitesList');
    if (!container) {
        return;
    }

    if (loading) {
        container.innerHTML = `
            <div class="site-empty-state">
                <i class="fas fa-spinner fa-spin"></i>
                <div class="site-empty-state__title">Loading assignments</div>
            </div>
        `;
        return;
    }

    if (assignmentState.sites.length === 0) {
        container.innerHTML = `
            <div class="site-empty-state">
                <i class="fas fa-map-marker-alt"></i>
                <div class="site-empty-state__title">No sites available</div>
                <div class="site-empty-state__text">Create a site first to start assigning workers.</div>
            </div>
        `;
        return;
    }

    const selectedWorker = getSelectedWorker();
    if (!selectedWorker) {
        container.innerHTML = assignmentState.sites.map((site) => `
            <div class="site-item${String(site.Status).toLowerCase() === 'inactive' ? ' inactive' : ''}"
                 data-site-id="${Number(site.SiteID) || 0}">
                <div class="site-item-header">
                    <div>
                        <div class="site-name">${escapeHtml(site.Site_Name)}</div>
                        <div class="site-address">${escapeHtml(site.Location || 'No location')}</div>
                    </div>
                    <button class="site-action add" type="button" data-site-id="${Number(site.SiteID) || 0}" disabled>
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <span class="site-status ${escapeHtml(String(site.Status).toLowerCase())}">${escapeHtml(site.Status)}</span>
                <div class="site-details">
                    <div class="site-detail-item">
                        <span><span class="site-current-count">${Number(site.current_workers || 0)}</span> Current Workers</span>
                    </div>
                    <div class="site-detail-item">
                        <span>Target: <span class="site-target-count">${Number(site.Required_Workers || 0)}</span></span>
                    </div>
                </div>
                <div class="site-assignment-state">
                    <span class="site-assignment-label">Select a worker to manage assignments</span>
                </div>
            </div>
        `).join('');
        return;
    }

    const assignedSiteIds = getAssignedSiteIds(selectedWorker.WorkerID);
    const sortedSites = [...assignmentState.sites].sort((a, b) => {
        const aAssigned = assignedSiteIds.has(a.SiteID) ? 1 : 0;
        const bAssigned = assignedSiteIds.has(b.SiteID) ? 1 : 0;
        if (aAssigned !== bAssigned) {
            return bAssigned - aAssigned;
        }
        return String(a.Site_Name).localeCompare(String(b.Site_Name));
    });

    container.innerHTML = sortedSites.map((site) => {
        const isAssigned = assignedSiteIds.has(site.SiteID);
        const isInactive = String(site.Status).toLowerCase() === 'inactive';
        const target = Number(site.Required_Workers || 0);
        const current = Number(site.current_workers || 0);
        const atCapacity = target > 0 && current >= target;

        return `
            <div class="site-item${isInactive ? ' inactive' : ''}${isAssigned ? ' assigned' : ''}${atCapacity ? ' at-capacity' : ''}"
                 data-site-id="${Number(site.SiteID) || 0}">
                <div class="site-item-header">
                    <div>
                        <div class="site-name">${escapeHtml(site.Site_Name)}</div>
                        <div class="site-address">${escapeHtml(site.Location || 'No location')}</div>
                    </div>
                    <button class="site-action ${isAssigned ? 'remove' : 'add'}"
                            type="button"
                            data-site-id="${Number(site.SiteID) || 0}"
                            aria-label="${isAssigned ? 'Remove assignment' : 'Assign worker'}"
                            ${isInactive ? 'disabled' : ''}>
                        <i class="fas ${isAssigned ? 'fa-minus' : 'fa-plus'}"></i>
                    </button>
                </div>
                <span class="site-status ${escapeHtml(String(site.Status).toLowerCase())}">${escapeHtml(site.Status)}</span>
                <div class="site-details">
                    <div class="site-detail-item">
                        <span><span class="site-current-count">${current}</span> Current Workers</span>
                    </div>
                    <div class="site-detail-item">
                        <span>Target: <span class="site-target-count">${target}</span></span>
                    </div>
                </div>
                <div class="site-assignment-state">
                    <span class="site-assignment-label">${isAssigned ? 'Assigned to selected worker' : 'Available for assignment'}</span>
                    ${atCapacity ? '<span class="site-capacity-warning">Target reached</span>' : ''}
                </div>
                ${isAssigned ? '<div class="site-assignment-note">This worker is currently assigned here.</div>' : ''}
                <div class="site-drop-hint">Drop a worker here to assign</div>
            </div>
        `;
    }).join('');
}

function updateAssignmentsOverview(delta) {
    const assignmentsEl = document.getElementById('assignmentsCount');
    if (!assignmentsEl) {
        return;
    }

    assignmentsEl.textContent = Math.max(0, Number(assignmentsEl.textContent || 0) + delta);
}

function updateWorkerAssignmentCount(workerId, delta) {
    const worker = getWorkerById(workerId);
    if (!worker) {
        return;
    }
    worker.assigned_sites_count = Math.max(0, Number(worker.assigned_sites_count || 0) + delta);
}

function updateSiteWorkerCount(siteId, delta) {
    const site = getSiteById(siteId);
    if (!site) {
        return;
    }
    site.current_workers = Math.max(0, Number(site.current_workers || 0) + delta);
}

async function refreshSiteWorkerCount(siteId) {
    const numericSiteId = Number(siteId);
    if (!numericSiteId) {
        return;
    }

    try {
        const data = await fetchJson(`${API_BASE}get_site_worker_count.php?site_id=${numericSiteId}`);
        if (!data.success) {
            return;
        }

        const site = getSiteById(numericSiteId);
        if (!site) {
            return;
        }

        site.current_workers = Number(data.current_workers || 0);
        if (data.site) {
            site.Required_Workers = Number(data.site.Required_Workers || site.Required_Workers || 0);
            site.Status = data.site.Status || site.Status;
            site.Location = data.site.Location || site.Location;
            site.Site_Name = data.site.Site_Name || site.Site_Name;
        }
    } catch (error) {
        console.error('Failed to refresh site worker count:', error);
    }
}

async function loadWorkerAssignments(workerId, forceRefresh = false) {
    const numericWorkerId = Number(workerId);
    if (!numericWorkerId) {
        return;
    }

    if (assignmentState.loadedWorkers.has(numericWorkerId) && !forceRefresh) {
        return;
    }

    const data = await fetchJson(`${API_BASE}get_worker_sites.php?worker_id=${numericWorkerId}`);
    if (!data.success) {
        throw new Error(data.message || 'Failed to load worker assignments.');
    }

    const siteIds = (data.sites || []).map((site) => Number(site.SiteID));
    if (siteIds.length > 0 || !assignmentState.workerAssignments.has(numericWorkerId)) {
        setAssignedSiteIds(numericWorkerId, siteIds);
    }

    (data.sites || []).forEach((assignedSite) => {
        const localSite = getSiteById(assignedSite.SiteID);
        if (localSite) {
            localSite.current_workers = Number(assignedSite.current_workers || localSite.current_workers || 0);
        }
    });
}

async function selectStaff(workerId) {
    const numericWorkerId = Number(workerId);
    if (!numericWorkerId) {
        return;
    }

    assignmentState.selectedWorkerId = numericWorkerId;
    renderStaffList();
    updatePanelCopy();
    renderSites(true);

    try {
        await loadWorkerAssignments(numericWorkerId, true);
        renderSites();
        updatePanelCopy();
    } catch (error) {
        console.error('Failed to load worker assignments:', error);
        renderSites();
        showAssignmentFeedback(error.message || 'Unable to load assignments.', 'error');
    }
}

async function assignSiteToStaff(siteId, workerId = null) {
    const numericSiteId = Number(siteId);
    const numericWorkerId = Number(workerId || assignmentState.selectedWorkerId);

    if (!numericWorkerId) {
        showAssignmentFeedback('Select a worker first.', 'error');
        return;
    }

    const site = getSiteById(numericSiteId);
    if (!site) {
        showAssignmentFeedback('Site not found.', 'error');
        return;
    }

    if (String(site.Status).toLowerCase() === 'inactive') {
        showAssignmentFeedback('Inactive sites cannot receive assignments.', 'error');
        return;
    }

    await loadWorkerAssignments(numericWorkerId);
    const assignedSiteIds = getAssignedSiteIds(numericWorkerId);
    if (assignedSiteIds.has(numericSiteId)) {
        showAssignmentFeedback('This worker is already assigned to that site.', 'error');
        return;
    }

    try {
        const data = await fetchJson(`${API_BASE}assign_worker.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ workerId: numericWorkerId, siteId: numericSiteId })
        });

        if (!data.success) {
            throw new Error(data.message || 'Assignment failed.');
        }

        assignedSiteIds.add(numericSiteId);
        assignmentState.workerAssignments.set(numericWorkerId, assignedSiteIds);
        assignmentState.selectedWorkerId = numericWorkerId;
        updateWorkerAssignmentCount(numericWorkerId, 1);
        updateAssignmentsOverview(1);
        await refreshSiteWorkerCount(numericSiteId);

        renderStaffList();
        renderSites();
        updatePanelCopy();

        const updatedSite = getSiteById(numericSiteId);
        if (updatedSite && updatedSite.Required_Workers > 0 && updatedSite.current_workers >= updatedSite.Required_Workers) {
            showAssignmentFeedback(`Assigned successfully. ${updatedSite.Site_Name} has reached its target capacity.`, 'warning');
        } else {
            showAssignmentFeedback('Worker assigned successfully.');
        }
    } catch (error) {
        console.error('Assignment failed:', error);
        showAssignmentFeedback(error.message || 'Failed to assign worker.', 'error');
    }
}

async function removeSiteAssignment(siteId, workerId = null) {
    const numericSiteId = Number(siteId);
    const numericWorkerId = Number(workerId || assignmentState.selectedWorkerId);
    if (!numericWorkerId || !numericSiteId) {
        showAssignmentFeedback('Worker and site are required.', 'error');
        return;
    }

    if (!confirm('Remove this worker from the selected site?')) {
        return;
    }

    try {
        const data = await fetchJson(`${API_BASE}remove_worker_assignment.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                worker_id: numericWorkerId,
                site_id: numericSiteId,
                user_id: currentUserId
            })
        });

        if (!data.success) {
            throw new Error(data.message || 'Removal failed.');
        }

        const assignedSiteIds = getAssignedSiteIds(numericWorkerId);
        assignedSiteIds.delete(numericSiteId);
        assignmentState.workerAssignments.set(numericWorkerId, assignedSiteIds);
        updateWorkerAssignmentCount(numericWorkerId, -1);
        updateAssignmentsOverview(-1);
        await refreshSiteWorkerCount(numericSiteId);

        renderStaffList();
        renderSites();
        updatePanelCopy();
        showAssignmentFeedback('Worker removed from site.');
    } catch (error) {
        console.error('Removal failed:', error);
        showAssignmentFeedback(error.message || 'Failed to remove assignment.', 'error');
    }
}

async function showAddEmployeeModal() {
    const modal = document.getElementById('addEmployeeModal');
    if (!modal) {
        return;
    }
    modal.classList.add('active');
    modal.style.display = 'flex';
    document.getElementById('addEmployeeForm')?.reset();
    await loadEmployeePositionCatalog();
}

function closeModal() {
    const modal = document.getElementById('addEmployeeModal');
    if (!modal) {
        return;
    }

    modal.classList.remove('active');
    if (modal.style) {
        modal.style.display = 'none';
    }
}

function mountEmployeeModal() {
    ['addEmployeeModal', 'archiveEmployeeModal', 'viewEmployeeModal'].forEach((modalId) => {
        const modal = document.getElementById(modalId);
        if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
    });
}


function updateEmployeePhotoPreview(file) {
    const preview = document.getElementById('employeePhotoPreview');
    const note = document.getElementById('employeePhotoPreviewNote');
    if (!preview) {
        return;
    }

    if (!file) {
        preview.innerHTML = '<span>No image selected</span>';
        if (note) {
            note.hidden = true;
            note.textContent = '';
        }
        return;
    }

    const reader = new FileReader();
    reader.onload = (event) => {
        const dataUrl = event.target?.result || '';
        const img = new Image();
        img.onload = () => {
            const ratio = img.width / img.height;
            preview.innerHTML = `<img src="${dataUrl}" alt="Employee preview">`;
            if (note) {
                if (ratio < 0.9 || ratio > 1.1) {
                    note.textContent = 'This photo will be center-cropped to a 2×2 inch square when saved.';
                    note.hidden = false;
                } else {
                    note.textContent = '';
                    note.hidden = true;
                }
            }
        };
        img.onerror = () => {
            preview.innerHTML = '<span>Unable to preview this image</span>';
            if (note) {
                note.hidden = true;
                note.textContent = '';
            }
        };
        img.src = dataUrl;
    };
    reader.readAsDataURL(file);
}

async function handleAddEmployeeSubmit(event) {
    event.preventDefault();
    if (!await checkEmployeeEmail(event.target)) {
        event.target.reportValidity();
        return;
    }

    normalizePhoneInputs(event.target);
    syncAddEmployeePhoneFields();
    validateEmployeeDateOfBirth(event.target.querySelector('input[name="date_of_birth"]'));
    if (!event.target.checkValidity()) {
        event.target.reportValidity();
        return;
    }
    if (await checkEmployeeNameDuplicate(event.target)) {
        event.target.reportValidity();
        return;
    }
    const formData = new FormData(event.target);
    formData.append('user_id', currentUserId);

    try {
        const result = await fetchJson(`${API_BASE}create_employee.php`, {
            method: 'POST',
            body: formData
        });

        if (!result.success) {
            window.showCrudResultModal?.(
                false,
                result.message || 'Unable to add employee.',
                'Employee Creation'
            );
            return;
        }

        window.showCrudResultModal?.(
            true,
            result.message || 'Employee added successfully.',
            'Employee Creation'
        );
        closeModal();
        employeeListState.page = 1;
        await loadEmployees();
    } catch (error) {
        console.error('Submit error:', error);
        window.showCrudResultModal?.(
            false,
            friendlyConnectionMessage(error),
            'Employee Creation'
        );
    }
}

function handleStaffListClick(event) {
    const staffItem = event.target.closest('.staff-item');
    if (!staffItem) {
        return;
    }
    selectStaff(staffItem.dataset.workerId || staffItem.dataset.staff);
}

function handleStaffDragStart(event) {
    const staffItem = event.target.closest('.staff-item');
    if (!staffItem) {
        return;
    }
    assignmentState.dragWorkerId = Number(staffItem.dataset.workerId || staffItem.dataset.staff);
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', String(assignmentState.dragWorkerId));
}

function handleSitesClick(event) {
    const button = event.target.closest('.site-action');
    if (!button) {
        return;
    }

    const siteId = Number(button.dataset.siteId);
    if (button.classList.contains('remove')) {
        removeSiteAssignment(siteId);
    } else {
        assignSiteToStaff(siteId);
    }
}

function handleSiteDragOver(event) {
    const siteItem = event.target.closest('.site-item');
    if (!siteItem) {
        return;
    }
    event.preventDefault();
    siteItem.classList.add('drag-over');
}

function handleSiteDragLeave(event) {
    const siteItem = event.target.closest('.site-item');
    if (!siteItem) {
        return;
    }
    siteItem.classList.remove('drag-over');
}

function handleSiteDrop(event) {
    const siteItem = event.target.closest('.site-item');
    if (!siteItem) {
        return;
    }

    event.preventDefault();
    siteItem.classList.remove('drag-over');

    const workerId = Number(event.dataTransfer.getData('text/plain') || assignmentState.dragWorkerId);
    const siteId = Number(siteItem.dataset.siteId);
    if (workerId && siteId) {
        assignSiteToStaff(siteId, workerId);
    }
}

function bindAssignmentListeners() {
    document.getElementById('staffSearch')?.addEventListener('input', (event) => {
        assignmentState.searchTerm = event.target.value || '';
        renderStaffList();
    });

    document.getElementById('staffList')?.addEventListener('click', handleStaffListClick);
    document.getElementById('staffList')?.addEventListener('dragstart', handleStaffDragStart);

    const sitesList = document.getElementById('sitesList');
    sitesList?.addEventListener('click', handleSitesClick);
    sitesList?.addEventListener('dragover', handleSiteDragOver);
    sitesList?.addEventListener('dragleave', handleSiteDragLeave);
    sitesList?.addEventListener('drop', handleSiteDrop);
}

function bindTabListeners() {
    document.querySelectorAll('.tab').forEach((tab) => {
        tab.addEventListener('click', async function () {
            const tabName = this.dataset.tab;

            document.querySelectorAll('.tab').forEach((item) => item.classList.remove('active'));
            this.classList.add('active');
            document.querySelectorAll('.tab-content').forEach((content) => content.classList.remove('active'));

            if (tabName === 'employees') {
                document.getElementById('employeesTab')?.classList.add('active');
                document.getElementById('btnAddEmployee')?.style.setProperty('display', canManageEmployeeRecords ? 'flex' : 'none');
                await loadEmployees();
                return;
            }

            document.getElementById('assignmentsTab')?.classList.add('active');
            document.getElementById('btnAddEmployee')?.style.setProperty('display', 'none');
            loadDashboardStats();
            updatePanelCopy();
            renderSites();
        });
    });
}

function bindModalListeners() {
    document.getElementById('btnAddEmployee')?.addEventListener('click', (event) => {
        event.preventDefault();
        if (!canManageEmployeeRecords) {
            return;
        }
        showAddEmployeeModal();
    });

    // Close Add Employee modal (hard fallback: force-hide overlay)
    document.addEventListener('click', (event) => {
        const modal = document.getElementById('addEmployeeModal');
        if (!modal) return;

        const modalContent = modal.querySelector('.modal-content');

        // Close button click
        const closeBtn = event.target.closest?.('#addEmployeeModal .close-modal');
        if (closeBtn) {
            event.preventDefault();
            event.stopPropagation();
            modal.classList.remove('active');
            if (modal.style) modal.style.display = 'none';
            return;
        }

        // Overlay click outside the modal-content
        if (event.target === modal && modalContent && !modalContent.contains(event.target)) {
            modal.classList.remove('active');
            if (modal.style) modal.style.display = 'none';
        }
    });

    document.getElementById('addEmployeeModal')?.addEventListener('click', (event) => {
        if (event.target === event.currentTarget) {
            closeModal();
        }
    });

    document.addEventListener('click', (event) => {
        if (event.target.closest('#viewEmployeeModal #closeViewEmployeeModal')) {
            event.preventDefault();
            closeViewEmployeeModal();
        }
    });
    document.getElementById('viewEmployeeModal')?.addEventListener('click', (event) => {
        if (event.target === event.currentTarget) {
            closeViewEmployeeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }
        closeViewEmployeeModal();
        closeWorkerIdCardModal();
        closeModal();
    });

    document.getElementById('closeWorkerIdCardModal')?.addEventListener('click', closeWorkerIdCardModal);
    document.getElementById('workerIdCardCloseBtn')?.addEventListener('click', closeWorkerIdCardModal);
    document.getElementById('workerIdCardModal')?.addEventListener('click', (event) => {
        if (event.target === event.currentTarget) {
            closeWorkerIdCardModal();
        }
    });
    document.getElementById('workerIdCardDownloadBtn')?.addEventListener('click', () => {
        downloadWorkerIdCardPdf(currentCardWorkerId);
    });

    const addEmployeeForm = document.getElementById('addEmployeeForm');
    addEmployeeForm?.addEventListener('submit', handleAddEmployeeSubmit);
    bindEmployeeNameDuplicateValidation(addEmployeeForm);
    bindEmployeeDateOfBirthValidation(addEmployeeForm);
    bindEmployeeEmailValidation(addEmployeeForm);
    document.addEventListener('input', (event) => {
        const input = event.target.closest?.('input[name="phone"], input[name="emergency_contact_phone"]');
        if (!input) {
            return;
        }

        input.value = normalizePhoneValue(input.value);
        if (input.closest('#addEmployeeForm') && input.name === 'phone') {
            syncAddEmployeePhoneFields(input);
        }
    });

    document.getElementById('employeePhotoInput')?.addEventListener('change', (event) => {
        const file = event.target.files?.[0] || null;
        updateEmployeePhotoPreview(file);
    });
    document.getElementById('employeePosition')?.addEventListener('change', applySuggestedEmployeeSalary);
    document.querySelector('#addEmployeeForm select[name="rate_type"]')?.addEventListener('change', applySuggestedEmployeeSalary);
}


function bindEmployeeTableActions() {
    document.addEventListener('click', (event) => {
        const button = event.target.closest('.btn-action');
        if (!button) {
            return;
        }

        const row = button.closest('tr');
        const employeeId = Number(row?.dataset?.workerId || 0);
        if (!employeeId) {
            return;
        }
        if (button.classList.contains('view') && !button.classList.contains('qr-link')) {
            viewEmployee(employeeId);
        } else if (button.classList.contains('edit') && canManageEmployeeRecords) {
            viewEmployee(employeeId, true);
        } else if (button.classList.contains('id-card')) {
            openWorkerIdCardModal(employeeId);
        } else if (button.classList.contains('approve') && canApproveEmployeeRecords) {
            approveEmployee(employeeId);
        } else if (button.classList.contains('delete') && canArchiveEmployeeRecords) {
            deleteEmployee(employeeId);
        }
    });
}

document.addEventListener('DOMContentLoaded', async () => {
    currentUserId = Number(document.getElementById('currentUserId')?.value || 1);

    mountEmployeeModal();
    updateDateTime();
    setInterval(updateDateTime, 60000);

    bindTabListeners();
    bindModalListeners();
    bindEmployeeTableActions();

    document.getElementById('roleFilter')?.addEventListener('change', applyEmployeeFilters);
    document.getElementById('siteAssignmentFilter')?.addEventListener('change', applyEmployeeFilters);
    document.getElementById('employeePagination')?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-employee-page]');
        if (!button) return;
        employeeListState.page += button.dataset.employeePage === 'next' ? 1 : -1;
        renderEmployeeList();
    });

    initializeAssignmentState();
    bindAssignmentListeners();
    renderStaffList();
    loadDashboardStats();
    updatePanelCopy();
    renderSites();
    if (assignmentState.selectedWorkerId) {
        await selectStaff(assignmentState.selectedWorkerId);
    }
    await loadEmployees();

    if (new URLSearchParams(window.location.search).get('action') === 'add' && canManageEmployeeRecords) {
        showAddEmployeeModal();
        const cleanUrl = new URL(window.location.href);
        cleanUrl.searchParams.delete('action');
        window.history.replaceState({}, '', cleanUrl.toString());
    }
});


window.searchEmployees = searchEmployees;
window.filterBySite = filterBySite;
window.filterByStatus = filterByStatus;
window.viewEmployee = viewEmployee;
window.closeViewEmployeeModal = closeViewEmployeeModal;
window.openWorkerIdCardModal = openWorkerIdCardModal;
window.closeWorkerIdCardModal = closeWorkerIdCardModal;
window.downloadWorkerIdCardPdf = downloadWorkerIdCardPdf;
window.deleteEmployee = deleteEmployee;
window.selectStaff = selectStaff;
window.assignSiteToStaff = assignSiteToStaff;
window.removeSiteAssignment = removeSiteAssignment;

// Government deduction type checkboxes enable/disable based on radio selection
(function bindGovernmentDeductionTypeToggles() {
    const form = document.getElementById('addEmployeeForm');
    if (!form) return;

    const radioInputs = form.querySelectorAll('input[name="government_deduction_status"]');
    const typeCheckboxes = form.querySelectorAll('input[name="government_deduction_types[]"]');

    const applyState = () => {
        const selected = form.querySelector('input[name="government_deduction_status"]:checked');
        const isWith = selected && selected.value === 'With Deductions';
        const hasSelectedType = Array.from(typeCheckboxes).some((cb) => cb.checked);

        typeCheckboxes.forEach((cb) => {
            cb.disabled = !isWith;
            if (!isWith) cb.checked = false;
            cb.setCustomValidity('');
        });

        if (isWith && typeCheckboxes.length) {
            typeCheckboxes[0].setCustomValidity(hasSelectedType ? '' : 'Select at least one government deduction.');
        }
    };

    radioInputs.forEach((r) => r.addEventListener('change', applyState));
    typeCheckboxes.forEach((cb) => cb.addEventListener('change', applyState));
    applyState();
})();
