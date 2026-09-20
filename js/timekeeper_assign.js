const API_BASE = '../api';

let assignments = [];
let timekeepers = [];
let activeSites = [];

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function showToast(message, type = 'success') {
    window.showCrudResultModal?.(
        type !== 'error',
        message,
        'Timekeeper Assignment'
    );
}

async function fetchApi(endpoint, options = {}) {
    const response = await fetch(`${API_BASE}/${endpoint}`, {
        credentials: 'same-origin',
        ...options,
        headers: {
            'Content-Type': 'application/json',
            ...(options.headers || {}),
        },
    });

    const data = await response.json();
    if (!data.success) {
        throw new Error(data.message || 'Request failed');
    }
    return data;
}

async function loadAssignments() {
    const data = await fetchApi('get_timekeeper_assignments.php');
    assignments = data.assignments || [];
    renderAssignments();
}

async function loadTimekeepers() {
    const response = await fetch(`${API_BASE}/get_timekeepers.php`, { credentials: 'same-origin' });
    const data = await response.json();
    timekeepers = data.success ? (data.timekeepers || []) : [];
}

async function loadActiveSites() {
    const response = await fetch(`${API_BASE}/get_sites.php?active_only=1`, { credentials: 'same-origin' });
    const data = await response.json();
    const sites = data.sites || data.data || [];
    activeSites = sites.filter((site) => String(site.Status || site.status || '').toLowerCase() === 'active');
}

function renderAssignments() {
    const tbody = document.getElementById('assignmentTableBody');
    if (!tbody) {
        return;
    }

    if (!assignments.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="loading-cell">No Timekeeper accounts found.</td></tr>';
        return;
    }

    tbody.innerHTML = assignments.map((row) => {
        const status = String(row.status || 'Inactive');
        const statusClass = status.toLowerCase() === 'active' ? 'active' : 'inactive';
        const siteLabel = row.site_name ? escapeHtml(row.site_name) : '<span style="color:#9ca3af">Not assigned</span>';

        return `
            <tr>
                <td>${escapeHtml(row.name || 'Unknown')}</td>
                <td>${escapeHtml(row.email || '')}</td>
                <td>${siteLabel}</td>
                <td><span class="status-badge ${statusClass}">${escapeHtml(status)}</span></td>
                <td>
                    <div class="action-buttons">
                        ${statusClass !== 'active'
                            ? `<button type="button" class="btn-action" data-action="assign" data-user-id="${Number(row.user_id) || 0}">Assign Site</button>`
                            : `<button type="button" class="btn-action" data-action="change" data-user-id="${Number(row.user_id) || 0}" data-assignment-id="${Number(row.assignment_id || '') || 0}" data-site-id="${Number(row.site_id || '') || 0}">Change Site</button>
                               <button type="button" class="btn-action danger" data-action="remove" data-user-id="${Number(row.user_id) || 0}" data-assignment-id="${Number(row.assignment_id || '') || 0}">Remove Assignment</button>`}
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function populateSelectOptions() {
    const tkSelect = document.getElementById('formTimekeeper');
    const siteSelect = document.getElementById('formSite');

    if (tkSelect) {
        tkSelect.innerHTML = '<option value="">Select Timekeeper</option>' + timekeepers.map((tk) => `
            <option value="${tk.id}">${escapeHtml(tk.name || tk.email)}</option>
        `).join('');
    }

    if (siteSelect) {
        siteSelect.innerHTML = '<option value="">Select Active Site</option>' + activeSites.map((site) => {
            const siteId = site.SiteID || site.id;
            const siteName = site.Site_Name || site.name;
            return `<option value="${siteId}">${escapeHtml(siteName)}</option>`;
        }).join('');
    }
}

function openModal(mode, row = null) {
    const modal = document.getElementById('assignmentModal');
    const title = document.getElementById('modalTitle');
    const tkGroup = document.getElementById('timekeeperSelectGroup');
    const tkSelect = document.getElementById('formTimekeeper');
    const siteSelect = document.getElementById('formSite');
    const statusSelect = document.getElementById('formStatus');

    document.getElementById('formUserId').value = row?.user_id || '';
    document.getElementById('formAssignmentId').value = row?.assignment_id || '';

    populateSelectOptions();

    if (mode === 'assign') {
        title.textContent = 'Assign Site';
        tkGroup.style.display = '';
        tkSelect.required = true;
        tkSelect.value = row?.user_id || '';
        siteSelect.value = '';
        statusSelect.value = 'Active';
    } else {
        title.textContent = 'Change Site';
        tkGroup.style.display = 'none';
        tkSelect.required = false;
        tkSelect.value = row?.user_id || '';
        siteSelect.value = row?.site_id || '';
        statusSelect.value = 'Active';
    }

    modal.hidden = false;
}

function closeModal() {
    const modal = document.getElementById('assignmentModal');
    modal.hidden = true;
    document.getElementById('assignmentForm').reset();
}

async function saveAssignment(formData) {
    await fetchApi('save_timekeeper_assignment.php', {
        method: 'POST',
        body: JSON.stringify(formData),
    });
}

async function removeAssignment(userId, assignmentId) {
    await fetchApi('remove_timekeeper_assignment.php', {
        method: 'POST',
        body: JSON.stringify({
            user_id: userId,
            assignment_id: assignmentId || 0,
        }),
    });
}

document.addEventListener('DOMContentLoaded', async () => {
    try {
        await Promise.all([loadAssignments(), loadTimekeepers(), loadActiveSites()]);
    } catch (error) {
        showToast(error.message, 'error');
    }

    document.getElementById('btnAssignSite')?.addEventListener('click', () => openModal('assign'));

    document.getElementById('assignmentTableBody')?.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-action]');
        if (!button) {
            return;
        }

        const action = button.dataset.action;
        const userId = Number(button.dataset.userId || 0);
        const assignmentId = Number(button.dataset.assignmentId || 0);
        const siteId = Number(button.dataset.siteId || 0);
        const row = assignments.find((item) => Number(item.user_id) === userId) || { user_id: userId, assignment_id: assignmentId, site_id: siteId };

        try {
            if (action === 'assign' || action === 'change') {
                openModal(action, row);
                return;
            }

            if (action === 'remove') {
                if (!(await window.showConfirmModal('Remove this Timekeeper site assignment?', {
                    title: 'Confirm Removal',
                    confirmText: 'Remove',
                    type: 'error'
                }))) {
                    return;
                }
                await removeAssignment(userId, assignmentId);
                showToast('Assignment removed successfully');
                await loadAssignments();
            }
        } catch (error) {
            showToast(error.message, 'error');
        }
    });

    document.getElementById('modalCloseBtn')?.addEventListener('click', closeModal);
    document.getElementById('modalCancelBtn')?.addEventListener('click', closeModal);
    document.getElementById('assignmentModal')?.addEventListener('click', (event) => {
        if (event.target.id === 'assignmentModal') {
            closeModal();
        }
    });

    document.getElementById('assignmentForm')?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const userId = Number(document.getElementById('formUserId').value || document.getElementById('formTimekeeper').value || 0);
        const siteId = Number(document.getElementById('formSite').value || 0);
        const status = document.getElementById('formStatus').value;

        if (!userId || !siteId) {
            showToast('Please select a Timekeeper and site', 'error');
            return;
        }

        try {
            await saveAssignment({
                user_id: userId,
                site_id: siteId,
                status,
                assigned_date: new Date().toISOString().slice(0, 10),
            });
            showToast('Assignment saved successfully');
            closeModal();
            await loadAssignments();
        } catch (error) {
            showToast(error.message, 'error');
        }
    });
});
