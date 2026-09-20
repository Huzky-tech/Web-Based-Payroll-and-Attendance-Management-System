const overtimeState = {
    role: document.querySelector('[data-overtime-role]')?.dataset?.overtimeRole
        || document.body?.dataset?.overtimeRole
        || document.body?.dataset?.dashboardRole
        || 'admin',
    items: [],
    formData: { workers: [], sites: [], overtime_types: [] },
    search: '',
    status: '',
    type: '',
    siteId: '',
    date: '',
    selectedItem: null
};

function escapeOvertimeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatTimeLabel(value) {
    if (!value) return '--';
    const [hoursText = '0', minutesText = '00'] = String(value).split(':');
    let hours = Number(hoursText);
    const suffix = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12 || 12;
    return `${hours}:${minutesText} ${suffix}`;
}

function calculatePreviewHours() {
    const start = document.getElementById('overtimeStart')?.value || '';
    const end = document.getElementById('overtimeEnd')?.value || '';
    const startMin = start ? Number(start.split(':')[0]) * 60 + Number(start.split(':')[1]) : null;
    const endMin = end ? Number(end.split(':')[0]) * 60 + Number(end.split(':')[1]) : null;
    const preview = document.getElementById('overtimeTotalHoursPreview');
    if (!preview || startMin === null || endMin === null || endMin <= startMin) {
        if (preview) preview.textContent = '0.00';
        return 0;
    }
    const hours = Math.round(((endMin - startMin) / 60) * 100) / 100;
    preview.textContent = hours.toFixed(2);
    return hours;
}

function getFilteredOvertimeItems() {
    const search = overtimeState.search.toLowerCase();
    return overtimeState.items.filter((item) => {
        const matchesStatus = !overtimeState.status || item.status === overtimeState.status;
        const matchesType = !overtimeState.type || item.overtime_type === overtimeState.type;
        const matchesSite = !overtimeState.siteId || String(item.site_id) === String(overtimeState.siteId);
        const matchesDate = !overtimeState.date || item.request_date === overtimeState.date;
        const haystack = [
            item.worker_name,
            item.site_name,
            item.overtime_type,
            item.status,
            item.submitted_by_name
        ].join(' ').toLowerCase();
        const matchesSearch = !search || haystack.includes(search);
        return matchesStatus && matchesType && matchesSite && matchesDate && matchesSearch;
    });
}

function canReviewOvertime() {
    return ['admin', 'assistant', 'payroll', 'hr', 'reviewer'].includes(String(overtimeState.role).toLowerCase());
}

function populateOvertimeSiteFilter() {
    const select = document.getElementById('overtimeSiteFilter');
    if (!select) return;

    const selectedValue = overtimeState.siteId;
    const sites = new Map();
    overtimeState.items.forEach((item) => {
        if (item.site_id) sites.set(String(item.site_id), item.site_name || 'Unknown Site');
    });
    select.innerHTML = '<option value="">All Sites</option>'
        + [...sites.entries()]
            .sort(([, first], [, second]) => String(first).localeCompare(String(second)))
            .map(([id, name]) => `<option value="${escapeOvertimeHtml(id)}">${escapeOvertimeHtml(name)}</option>`)
            .join('');
    select.value = selectedValue;
}

function renderOvertimeTable() {
    const tbody = document.getElementById('overtimeRequestsBody');
    if (!tbody) return;

    const items = getFilteredOvertimeItems();
    const isAdmin = canReviewOvertime();
    const colSpan = isAdmin ? 10 : 9;

    if (!items.length) {
        tbody.innerHTML = `<tr><td colspan="${colSpan}" class="overtime-empty">No overtime requests found.</td></tr>`;
        return;
    }

    tbody.innerHTML = items.map((item) => {
        const statusClass = String(item.status || '').toLowerCase();
        const actions = [];

        actions.push(`<button type="button" class="overtime-action-btn view" data-overtime-view="${item.id}">View</button>`);

        if (isAdmin && item.can_approve) {
            actions.push(`<button type="button" class="overtime-action-btn approve" data-overtime-approve="${item.id}">Approve</button>`);
            actions.push(`<button type="button" class="overtime-action-btn reject" data-overtime-reject="${item.id}">Reject</button>`);
        }

        const submittedCell = isAdmin
            ? `<td data-label="Submitted by">${escapeOvertimeHtml(item.submitted_by_name || '-')}</td>`
            : '';

        return `
            <tr>
                <td data-label="Worker">${escapeOvertimeHtml(item.worker_name)}</td>
                <td data-label="Site">${escapeOvertimeHtml(item.site_name)}</td>
                <td data-label="Type">${escapeOvertimeHtml(item.overtime_type)}</td>
                <td data-label="Date">${escapeOvertimeHtml(item.request_date_label || item.request_date)}</td>
                <td data-label="Start">${escapeOvertimeHtml(formatTimeLabel(item.overtime_start))}</td>
                <td data-label="End">${escapeOvertimeHtml(formatTimeLabel(item.overtime_end))}</td>
                <td data-label="Hours">${Number(item.total_hours || 0).toFixed(2)}</td>
                ${submittedCell}
                <td data-label="Status"><span class="overtime-status ${statusClass}">${escapeOvertimeHtml(item.status)}</span></td>
                <td data-label="Actions"><div class="overtime-actions">${actions.join('')}</div></td>
            </tr>
        `;
    }).join('');
}

function openOvertimeDetails(item) {
    const modal = document.getElementById('overtimeDetailsModal');
    const body = document.getElementById('overtimeDetailsBody');
    if (!modal || !body || !item) return;
    overtimeState.selectedItem = item;

    const lunchNote = item.overtime_type === 'Lunch Overtime'
        ? `<p><strong>Lunch schedule:</strong> ${escapeOvertimeHtml(item.lunch_schedule_label || 'Not available')}. This request covers work rendered during the scheduled lunch period.</p>`
        : '';

    body.innerHTML = `
        <p><strong>Worker:</strong> ${escapeOvertimeHtml(item.worker_name)}</p>
        <p><strong>Site:</strong> ${escapeOvertimeHtml(item.site_name)}</p>
        <p><strong>Overtime Type:</strong> ${escapeOvertimeHtml(item.overtime_type)}</p>
        <p><strong>Date:</strong> ${escapeOvertimeHtml(item.request_date_label || item.request_date)}</p>
        <p><strong>Start Time:</strong> ${escapeOvertimeHtml(formatTimeLabel(item.overtime_start))}</p>
        <p><strong>End Time:</strong> ${escapeOvertimeHtml(formatTimeLabel(item.overtime_end))}</p>
        <p><strong>Total Hours:</strong> ${Number(item.total_hours || 0).toFixed(2)}</p>
        <p><strong>Reason:</strong> ${escapeOvertimeHtml(item.reason)}</p>
        <p><strong>Submitted By:</strong> ${escapeOvertimeHtml(item.submitted_by_name || '-')}</p>
        ${canReviewOvertime() ? `
        <label class="overtime-status-field" for="overtimeDetailsStatus">
            <strong>Status</strong>
            <select id="overtimeDetailsStatus">
                <option value="Approved"${item.status === 'Approved' ? ' selected' : ''}>Approved</option>
                <option value="Rejected"${item.status === 'Rejected' ? ' selected' : ''}>Rejected</option>
            </select>
        </label>` : `<p><strong>Status:</strong> ${escapeOvertimeHtml(item.status)}</p>`}
        ${item.approved_by_name ? `<p><strong>Reviewed By:</strong> ${escapeOvertimeHtml(item.approved_by_name)}</p>` : ''}
        ${lunchNote}
    `;

    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
}

function closeOvertimeDetails() {
    const modal = document.getElementById('overtimeDetailsModal');
    if (!modal) return;
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    overtimeState.selectedItem = null;
}

async function fetchOvertimeRequests() {
    const params = new URLSearchParams();
    if (overtimeState.status) params.set('status', overtimeState.status);
    if (overtimeState.type) params.set('type', overtimeState.type);
    if (overtimeState.siteId) params.set('site_id', overtimeState.siteId);
    if (overtimeState.date) {
        params.set('date_from', overtimeState.date);
        params.set('date_to', overtimeState.date);
    }
    if (overtimeState.search) params.set('search', overtimeState.search);

    const response = await fetch(`../api/get_overtime_requests.php?${params.toString()}`);
    const result = await response.json();
    if (!response.ok || !result.success) {
        throw new Error(result.message || 'Failed to load overtime requests');
    }

    overtimeState.items = Array.isArray(result.items) ? result.items : [];
    populateOvertimeSiteFilter();
    renderOvertimeTable();
}

async function fetchOvertimeFormData() {
    const response = await fetch('../api/get_overtime_form_data.php');
    const result = await response.json();
    if (!response.ok || !result.success) {
        throw new Error(result.message || 'Failed to load overtime form data');
    }
    overtimeState.formData = result;
}

function populateOvertimeForm() {
    const workerSelect = document.getElementById('overtimeWorker');
    const typeSelect = document.getElementById('overtimeType');
    const siteInput = document.getElementById('overtimeSite');
    const siteIdInput = document.getElementById('overtimeSiteId');
    const dateInput = document.getElementById('overtimeDate');

    if (!workerSelect || !typeSelect) return;

    const workers = overtimeState.formData.workers || [];
    const sites = overtimeState.formData.sites || [];

    workerSelect.innerHTML = workers.length
        ? workers.map((worker) => `<option value="${worker.worker_id}" data-site-id="${worker.site_id}">${escapeOvertimeHtml(worker.worker_name)} (${escapeOvertimeHtml(worker.site_name)})</option>`).join('')
        : '<option value="">No workers available</option>';

    typeSelect.innerHTML = (overtimeState.formData.overtime_types || []).map((type) => `<option value="${escapeOvertimeHtml(type)}">${escapeOvertimeHtml(type)}</option>`).join('');

    if (dateInput && !dateInput.value) {
        dateInput.value = new Date().toISOString().split('T')[0];
    }

    const selectedWorker = workers[0];
    if (selectedWorker && siteInput && siteIdInput) {
        siteIdInput.value = String(selectedWorker.site_id);
        siteInput.value = selectedWorker.site_name;
        updateLunchNote(selectedWorker.site_id);
    } else if (sites[0] && siteInput && siteIdInput) {
        siteIdInput.value = String(sites[0].site_id);
        siteInput.value = sites[0].site_name;
        updateLunchNote(sites[0].site_id);
    }

    calculatePreviewHours();
}

function updateLunchNote(siteId) {
    const note = document.getElementById('overtimeLunchNote');
    const schedule = document.getElementById('overtimeLunchSchedule');
    const typeSelect = document.getElementById('overtimeType');
    const site = (overtimeState.formData.sites || []).find((entry) => Number(entry.site_id) === Number(siteId));

    if (schedule && site) {
        schedule.textContent = site.lunch_schedule_label || 'Not set';
    }

    if (note && typeSelect) {
        const show = typeSelect.value === 'Lunch Overtime';
        note.hidden = !show;
    }
}

function openOvertimeFormModal() {
    const modal = document.getElementById('overtimeFormModal');
    if (!modal) return;
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    populateOvertimeForm();
}

function closeOvertimeFormModal() {
    const modal = document.getElementById('overtimeFormModal');
    const form = document.getElementById('overtimeRequestForm');
    if (!modal) return;
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    form?.reset();
}

async function submitOvertimeRequest(event) {
    event.preventDefault();

    const workerId = Number(document.getElementById('overtimeWorker')?.value || 0);
    const siteId = Number(document.getElementById('overtimeSiteId')?.value || 0);
    const requestDate = document.getElementById('overtimeDate')?.value || '';
    const overtimeType = document.getElementById('overtimeType')?.value || '';
    const overtimeStart = document.getElementById('overtimeStart')?.value || '';
    const overtimeEnd = document.getElementById('overtimeEnd')?.value || '';
    const reason = document.getElementById('overtimeReason')?.value.trim() || '';
    const totalHours = calculatePreviewHours();

    if (!workerId || !siteId || !requestDate || !overtimeType || !overtimeStart || !overtimeEnd || !reason || totalHours <= 0) {
        alert('Please complete all required fields with a valid overtime duration.');
        return;
    }

    const response = await fetch('../api/create_overtime_request.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            worker_id: workerId,
            site_id: siteId,
            request_date: requestDate,
            overtime_type: overtimeType,
            overtime_start: overtimeStart,
            overtime_end: overtimeEnd,
            reason
        })
    });

    const result = await response.json();
    if (!response.ok || !result.success) {
        window.showCrudResultModal?.(
            false,
            result.message || 'Failed to submit overtime request',
            'Overtime Request'
        );
        return;
    }

    closeOvertimeFormModal();
    await fetchOvertimeRequests();
    window.showCrudResultModal?.(
        true,
        result.message || 'Overtime request submitted.',
        'Overtime Request'
    );
}

async function runOvertimeAction(action, overtimeId) {
    const response = await fetch('../api/overtime_request_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action, overtime_id: overtimeId })
    });
    const result = await response.json();
    if (!response.ok || !result.success) {
        window.showCrudResultModal?.(
            false,
            result.message || 'Unable to update overtime request',
            'Overtime Request Update'
        );
        return;
    }
    await fetchOvertimeRequests();
    window.showCrudResultModal?.(
        true,
        result.message || 'Overtime request updated.',
        'Overtime Request Update'
    );
}

async function updateOvertimeStatusFromModal() {
    const item = overtimeState.selectedItem;
    const status = document.getElementById('overtimeDetailsStatus')?.value;
    if (!item || !['Approved', 'Rejected'].includes(status)) return;

    const action = status === 'Approved' ? 'approve' : 'reject';
    const confirmed = await window.showConfirmModal(`${status === 'Approved' ? 'Approve' : 'Reject'} this overtime request?`, {
        title: 'Confirm Overtime Status',
        confirmText: status,
        type: status === 'Approved' ? 'success' : 'error'
    });
    if (!confirmed) return;

    const overtimeId = Number(item.id);
    closeOvertimeDetails();
    await runOvertimeAction(action, overtimeId);
}

function bindOvertimeEvents() {
    document.getElementById('overtimeSearchInput')?.addEventListener('input', (event) => {
        overtimeState.search = event.target.value.trim();
        renderOvertimeTable();
    });

    document.getElementById('overtimeStatusFilter')?.addEventListener('change', async (event) => {
        overtimeState.status = event.target.value;
        await fetchOvertimeRequests();
    });

    document.getElementById('overtimeTypeFilter')?.addEventListener('change', async (event) => {
        overtimeState.type = event.target.value;
        await fetchOvertimeRequests();
    });

    document.getElementById('overtimeSiteFilter')?.addEventListener('change', async (event) => {
        overtimeState.siteId = event.target.value;
        await fetchOvertimeRequests();
    });

    document.getElementById('overtimeDateFilter')?.addEventListener('change', async (event) => {
        overtimeState.date = event.target.value || '';
        await fetchOvertimeRequests();
    });

    document.getElementById('openOvertimeRequestBtn')?.addEventListener('click', async () => {
        try {
            await fetchOvertimeFormData();
            openOvertimeFormModal();
        } catch (error) {
            alert(error.message);
        }
    });

    document.getElementById('cancelOvertimeFormBtn')?.addEventListener('click', closeOvertimeFormModal);
    document.getElementById('closeOvertimeDetailsBtn')?.addEventListener('click', closeOvertimeDetails);
    document.getElementById('updateOvertimeStatusBtn')?.addEventListener('click', updateOvertimeStatusFromModal);
    document.getElementById('overtimeRequestForm')?.addEventListener('submit', submitOvertimeRequest);

    document.getElementById('overtimeStart')?.addEventListener('change', calculatePreviewHours);
    document.getElementById('overtimeEnd')?.addEventListener('change', calculatePreviewHours);

    document.getElementById('overtimeWorker')?.addEventListener('change', (event) => {
        const option = event.target.selectedOptions[0];
        const siteId = Number(option?.dataset?.siteId || 0);
        const worker = (overtimeState.formData.workers || []).find((entry) => Number(entry.worker_id) === Number(event.target.value));
        const siteInput = document.getElementById('overtimeSite');
        const siteIdInput = document.getElementById('overtimeSiteId');
        if (worker && siteInput && siteIdInput) {
            siteInput.value = worker.site_name;
            siteIdInput.value = String(worker.site_id);
            updateLunchNote(worker.site_id);
        } else if (siteId) {
            updateLunchNote(siteId);
        }
    });

    document.getElementById('overtimeType')?.addEventListener('change', () => {
        const siteId = Number(document.getElementById('overtimeSiteId')?.value || 0);
        updateLunchNote(siteId);
    });

    document.getElementById('overtimeRequestsBody')?.addEventListener('click', async (event) => {
        const viewBtn = event.target.closest('[data-overtime-view]');
        const approveBtn = event.target.closest('[data-overtime-approve]');
        const rejectBtn = event.target.closest('[data-overtime-reject]');

        if (viewBtn) {
            const item = overtimeState.items.find((entry) => String(entry.id) === String(viewBtn.dataset.overtimeView));
            openOvertimeDetails(item);
            return;
        }

        if (approveBtn) {
            if (!(await window.showConfirmModal('Approve this overtime request?', {
                title: 'Confirm Overtime Approval',
                confirmText: 'Approve',
                type: 'success'
            }))) return;
            await runOvertimeAction('approve', Number(approveBtn.dataset.overtimeApprove));
            return;
        }

        if (rejectBtn) {
            if (!(await window.showConfirmModal('Reject this overtime request?', {
                title: 'Confirm Overtime Rejection',
                confirmText: 'Reject',
                type: 'error'
            }))) return;
            await runOvertimeAction('reject', Number(rejectBtn.dataset.overtimeReject));
        }
    });
}

document.addEventListener('DOMContentLoaded', async () => {
    bindOvertimeEvents();
    try {
        if (overtimeState.role === 'timekeeper') {
            await fetchOvertimeFormData();
        }
        await fetchOvertimeRequests();
    } catch (error) {
        const tbody = document.getElementById('overtimeRequestsBody');
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="10" class="overtime-empty">${escapeOvertimeHtml(error.message)}</td></tr>`;
        }
    }
});
