const API_BASE = '../api';

let currentStaffId = null;
let currentStaffName = '';
let staffList = [];
let allSites = [];
let assignedSites = [];
let currentUserId = 1;
let currentUserRole = '';
let currentPayrollStaffId = 0;
let selfAssignmentMode = false;
let searchTerm = '';
let siteSearchTerm = '';
let assignmentFilter = 'all';

function getApiUrl(endpoint) {
    return `${API_BASE}/${endpoint}`;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function showToast(message, type = 'success') {
    const isSuccess = type !== 'error';
    window.showCrudResultModal?.(
        isSuccess,
        message,
        isSuccess ? 'Site Assignment' : 'Site Assignment'
    );
}

function showLoading(element) {
    if (!element) {
        return;
    }
    element.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
}

async function fetchApi(endpoint, options = {}) {
    const response = await fetch(getApiUrl(endpoint), {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            ...options.headers
        }
    });

    const data = await response.json();
    if (!data.success) {
        throw new Error(data.message || 'API error');
    }

    return data;
}

function getStaffById(staffId) {
    return staffList.find((staff) => Number(staff.id) === Number(staffId)) || null;
}

function updateStatsFromState() {
    const staffCountEl = document.getElementById('staffCount');
    const siteCountEl = document.getElementById('siteCount');
    const assignmentCountEl = document.getElementById('assignmentCount');

    if (staffCountEl) {
        staffCountEl.textContent = selfAssignmentMode ? (currentPayrollStaffId > 0 ? 1 : 0) : staffList.length;
    }
    if (siteCountEl) {
        siteCountEl.textContent = allSites.filter((site) => String(site.Status || '').toLowerCase() === 'active').length;
    }
    if (assignmentCountEl) {
        const totalAssignments = selfAssignmentMode
            ? assignedSites.length
            : staffList.reduce((total, staff) => total + Number(staff.assigned_sites_count || 0), 0);
        assignmentCountEl.textContent = totalAssignments;
    }
}

async function loadStats() {
    await Promise.all([
        loadStaff(),
        loadAllSites()
    ]);
    updateStatsFromState();
}

async function loadStaff() {
    const data = await fetchApi('get_payroll_staff.php');
    staffList = data.staff || [];
    renderStaffList();
}

async function loadAllSites() {
    const data = await fetchApi('get_active_sites.php');
    allSites = data.sites || data || [];
}

async function loadAssignedSites(staffId) {
    const data = await fetchApi(`get_staff_sites.php?staff_id=${staffId}`);
    assignedSites = data.sites || [];
    if (selfAssignmentMode && data.staff?.full_name) {
        currentStaffName = data.staff.full_name;
    }
    renderSites();
    updatePanelHeader();
    updateStatsFromState();
}

function getFilteredStaff() {
    const term = searchTerm.trim().toLowerCase();
    return staffList.filter((staff) => {
        const assignedCount = Number(staff.assigned_sites_count || 0);
        const matchesAssignment = assignmentFilter === 'all'
            || (assignmentFilter === 'assigned' && assignedCount > 0)
            || (assignmentFilter === 'unassigned' && assignedCount === 0);
        const matchesSearch = !term
            || String(staff.name || '').toLowerCase().includes(term)
            || String(staff.email || '').toLowerCase().includes(term)
            || String(staff.assigned_site_names || '').toLowerCase().includes(term);

        return matchesAssignment && matchesSearch;
    });
}

function renderStaffList() {
    const container = document.getElementById('staffList');
    if (!container) {
        return;
    }

    if (selfAssignmentMode) {
        if (currentPayrollStaffId <= 0) {
            container.innerHTML = '<div class="loading">Your payroll staff profile is not linked yet.</div>';
            return;
        }

        const me = staffList.find((staff) => Number(staff.id) === Number(currentPayrollStaffId));
        const count = Number(me?.assigned_sites_count || assignedSites.length || 0);
        container.innerHTML = `
            <div class="staff-item selected" data-staff-id="${currentPayrollStaffId}" data-staff-name="${escapeHtml(me?.name || currentStaffName || 'My Assignments')}">
                <div class="staff-item-header">
                    <div class="staff-name">${escapeHtml(me?.name || currentStaffName || 'My Assignments')}</div>
                    ${count > 0 ? `<span class="staff-sites-badge">${count} Site${count === 1 ? '' : 's'}</span>` : ''}
                </div>
                <div class="staff-email">${escapeHtml(me?.email || '')}</div>
                <span class="status-badge active">${count > 0 ? 'Assigned to Site' : 'No Site Assigned'}</span>
            </div>
        `;
        return;
    }

    const filteredStaff = getFilteredStaff();
    if (filteredStaff.length === 0) {
        container.innerHTML = '<div class="loading">No payroll staff found.</div>';
        return;
    }

    container.innerHTML = filteredStaff.map((staff) => {
        const count = Number(staff.assigned_sites_count || 0);
        return `
            <div class="staff-item${Number(staff.id) === Number(currentStaffId) ? ' selected' : ''}"
                 data-staff-id="${staff.id}"
                 data-staff-name="${escapeHtml(staff.name)}">
                <div class="staff-item-header">
                    <div class="staff-name">${escapeHtml(staff.name)}</div>
                    ${count > 0 ? `<span class="staff-sites-badge">${count} Site${count === 1 ? '' : 's'}</span>` : ''}
                </div>
                <div class="staff-email">${escapeHtml(staff.email || '')}</div>
                <div class="staff-assignment ${count > 0 ? 'assigned' : 'unassigned'}">
                    <i class="fas ${count > 0 ? 'fa-map-marker-alt' : 'fa-user-clock'}"></i>
                    ${count > 0
                        ? `Assigned to: ${escapeHtml(staff.assigned_site_names || `${count} site${count === 1 ? '' : 's'}`)}`
                        : 'Not assigned to any site'}
                </div>
                ${staff.user_status === 'Inactive' ? '<span class="status-badge inactive">Inactive</span>' : ''}
            </div>
        `;
    }).join('');
}

function updatePanelHeader() {
    const emptyState = document.getElementById('emptyState');
    const sitesContent = document.getElementById('sitesContent');
    const panelTitle = document.getElementById('panelTitle');
    const panelSubtitle = document.getElementById('panelSubtitle');

    if (!currentStaffId) {
        if (emptyState) {
            emptyState.style.display = 'flex';
        }
        if (sitesContent) {
            sitesContent.style.display = 'none';
        }
        return;
    }

    if (emptyState) {
        emptyState.style.display = 'none';
    }
    if (sitesContent) {
        sitesContent.style.display = 'block';
    }
    if (panelTitle) {
        panelTitle.textContent = selfAssignmentMode
            ? 'My Site Assignments'
            : `Assign Sites to ${currentStaffName}`;
    }
    if (panelSubtitle) {
        panelSubtitle.textContent = assignedSites.length > 0
            ? `${assignedSites.length} site${assignedSites.length === 1 ? '' : 's'} assigned`
            : (selfAssignmentMode ? 'You are not assigned to any site yet' : '0 sites assigned');
    }
}

function renderSites() {
    const container = document.getElementById('sitesList');
    if (!container) {
        return;
    }

    if (!currentStaffId) {
        container.innerHTML = '';
        return;
    }

    if (selfAssignmentMode && assignedSites.length === 0) {
        container.innerHTML = `
            <div class="site-item inactive">
                <div class="site-item-header">
                    <div>
                        <div class="site-name">No assignment available</div>
                        <div class="site-address">You do not have any assigned sites yet.</div>
                    </div>
                </div>
                <span class="site-status inactive">Unassigned</span>
                <div class="site-details">
                    <div>Please contact your administrator or Assistant Admin.</div>
                </div>
            </div>
        `;
        return;
    }

    const normalizedSiteSearch = siteSearchTerm.trim().toLowerCase();
    const matchesSiteSearch = (site) => !normalizedSiteSearch
        || String(site.Site_Name || '').toLowerCase().includes(normalizedSiteSearch);

    if (selfAssignmentMode) {
        const filteredAssignedSites = assignedSites.filter(matchesSiteSearch);
        if (filteredAssignedSites.length === 0) {
            container.innerHTML = '<div class="loading">No sites match your search.</div>';
            return;
        }
        container.innerHTML = filteredAssignedSites.map((site) => {
            const statusClass = String(site.site_status || site.Status || '').toLowerCase();
            return `
                <div class="site-item ${statusClass === 'inactive' ? 'inactive' : ''}" data-site="${Number(site.SiteID) || 0}">
                    <div class="site-item-header">
                        <div>
                            <div class="site-name">${escapeHtml(site.Site_Name)}</div>
                            <div class="site-address">${escapeHtml(site.Location || '')}</div>
                        </div>
                    </div>
                    <span class="site-status ${statusClass}">${escapeHtml(site.site_status || site.Status || 'Assigned')}</span>
                    <div class="site-details">
                        <div><strong>Assigned Site:</strong> ${escapeHtml(site.Site_Name)}</div>
                        <div>${escapeHtml(site.current_workers || 0)} Current Workers</div>
                        <div>Required: ${escapeHtml(site.Required_Workers || 'N/A')}</div>
                    </div>
                </div>
            `;
        }).join('');
        return;
    }

    const assignedSiteIds = new Set(assignedSites.map((site) => Number(site.SiteID)));
    const sortedSites = [...allSites].filter(matchesSiteSearch).sort((firstSite, secondSite) => {
        const firstAssigned = assignedSiteIds.has(Number(firstSite.SiteID)) ? 1 : 0;
        const secondAssigned = assignedSiteIds.has(Number(secondSite.SiteID)) ? 1 : 0;

        if (firstAssigned !== secondAssigned) {
            return secondAssigned - firstAssigned;
        }

        return String(firstSite.Site_Name || '').localeCompare(String(secondSite.Site_Name || ''));
    });

    if (sortedSites.length === 0) {
        container.innerHTML = '<div class="loading">No sites match your search.</div>';
        return;
    }

    container.innerHTML = sortedSites.map((site) => {
        const isAssigned = assignedSiteIds.has(Number(site.SiteID));
        const assignedPayrollStaffId = Number(site.PayrollStaff_ID || 0);
        const assignedToOtherStaff = assignedPayrollStaffId > 0 && assignedPayrollStaffId !== Number(currentStaffId);
        const statusClass = String(site.Status || '').toLowerCase();

        return `
            <div class="site-item ${statusClass === 'inactive' ? 'inactive' : ''}" data-site="${Number(site.SiteID) || 0}">
                <div class="site-item-header">
                    <div>
                        <div class="site-name">${escapeHtml(site.Site_Name)}</div>
                        <div class="site-address">${escapeHtml(site.Location || '')}</div>
                    </div>
                    <button class="site-action ${isAssigned ? 'remove' : 'add'}${assignedToOtherStaff ? ' unavailable' : ''}"
                            type="button"
                            data-site-id="${Number(site.SiteID) || 0}"
                            ${assignedToOtherStaff ? 'disabled' : ''}
                            title="${escapeHtml(isAssigned ? 'Remove assignment' : (assignedToOtherStaff ? `Assigned to ${site.PayrollStaff_Name || 'another Payroll Staff member'}` : 'Assign this site'))}">
                        <i class="fas ${isAssigned ? 'fa-user-minus' : (assignedToOtherStaff ? 'fa-user-lock' : 'fa-user-check')}"></i>
                    </button>
                </div>
                <span class="site-status ${statusClass}">${escapeHtml(site.Status || 'Unknown')}</span>
                <div class="site-details">
                    <div>${escapeHtml(site.current_workers || 0)} Current Workers</div>
                    <div>Required: ${escapeHtml(site.Required_Workers || 'N/A')}</div>
                    ${assignedToOtherStaff ? `<div><strong>Payroll Staff:</strong> ${escapeHtml(site.PayrollStaff_Name || 'Already assigned')}</div>` : ''}
                </div>
            </div>
        `;
    }).join('');
}

async function selectStaff(staffId, staffName = '') {
    currentStaffId = Number(staffId);
    currentStaffName = staffName || getStaffById(staffId)?.name || 'Staff';

    renderStaffList();
    updatePanelHeader();
    showLoading(document.getElementById('sitesList'));

    try {
        await loadAssignedSites(currentStaffId);
    } catch (error) {
        console.error('Failed to load assigned sites:', error);
        showToast(error.message || 'Failed to load assigned sites', 'error');
    }
}

function updateLocalAssignmentCounts(staffId, delta) {
    const staff = getStaffById(staffId);
    if (!staff) {
        return;
    }

    staff.assigned_sites_count = Math.max(0, Number(staff.assigned_sites_count || 0) + delta);
}

async function assignSite(staffId, siteId) {
    const numericStaffId = Number(staffId);
    const numericSiteId = Number(siteId);
    showLoading(document.getElementById('sitesList'));

    try {
        await fetchApi('assign_site_to_staff.php', {
            method: 'POST',
            body: JSON.stringify({
                staff_id: numericStaffId,
                site_id: numericSiteId,
                user_id: currentUserId
            })
        });

        const assignedSite = allSites.find((site) => Number(site.SiteID) === numericSiteId);
        if (assignedSite && !assignedSites.some((site) => Number(site.SiteID) === numericSiteId)) {
            assignedSite.PayrollStaff_ID = numericStaffId;
            assignedSite.PayrollStaff_Name = getStaffById(numericStaffId)?.name || currentStaffName;
            assignedSites.push({
                ...assignedSite,
                SiteID: numericSiteId
            });
        }

        updateLocalAssignmentCounts(numericStaffId, 1);
        updateStatsFromState();
        renderStaffList();
        renderSites();
        updatePanelHeader();
        showToast('Site assigned successfully!');
    } catch (error) {
        console.error('Assign site failed:', error);
        await loadAssignedSites(numericStaffId).catch(() => {});
        showToast(error.message || 'Failed to assign site', 'error');
    }
}

async function removeSite(staffId, siteId) {
    const numericStaffId = Number(staffId);
    const numericSiteId = Number(siteId);

    if (!(await window.showConfirmModal('Remove this site assignment?', {
        title: 'Confirm Removal',
        confirmText: 'Remove',
        type: 'error'
    }))) {
        return;
    }

    showLoading(document.getElementById('sitesList'));

    try {
        await fetchApi('remove_site_assignment.php', {
            method: 'POST',
            body: JSON.stringify({
                staff_id: numericStaffId,
                site_id: numericSiteId,
                user_id: currentUserId
            })
        });

        assignedSites = assignedSites.filter((site) => Number(site.SiteID) !== numericSiteId);
        const removedSite = allSites.find((site) => Number(site.SiteID) === numericSiteId);
        if (removedSite && Number(removedSite.PayrollStaff_ID || 0) === numericStaffId) {
            removedSite.PayrollStaff_ID = null;
            removedSite.PayrollStaff_Name = null;
        }
        updateLocalAssignmentCounts(numericStaffId, -1);
        updateStatsFromState();
        renderStaffList();
        renderSites();
        updatePanelHeader();
        showToast('Site assignment removed!');
    } catch (error) {
        console.error('Remove site failed:', error);
        await loadAssignedSites(numericStaffId).catch(() => {});
        showToast(error.message || 'Failed to remove assignment', 'error');
    }
}

function handleStaffClick(event) {
    const staffItem = event.target.closest('.staff-item');
    if (!staffItem) {
        return;
    }

    selectStaff(staffItem.dataset.staffId, staffItem.dataset.staffName);
}

function handleSiteAction(event) {
    if (selfAssignmentMode) {
        return;
    }

    const button = event.target.closest('.site-action');
    if (!button || !currentStaffId) {
        return;
    }

    const siteId = Number(button.dataset.siteId);
    const isAssigned = assignedSites.some((site) => Number(site.SiteID) === siteId);

    if (isAssigned) {
        removeSite(currentStaffId, siteId);
    } else {
        assignSite(currentStaffId, siteId);
    }
}

function filterStaff() {
    searchTerm = document.getElementById('staffSearch')?.value || '';
    renderStaffList();
}

function filterSites() {
    siteSearchTerm = document.getElementById('siteAssignmentSearch')?.value || '';
    renderSites();
}

function setAssignmentFilter(filter) {
    assignmentFilter = ['all', 'assigned', 'unassigned'].includes(filter) ? filter : 'all';
    document.querySelectorAll('[data-assignment-filter]').forEach((button) => {
        button.classList.toggle('active', button.dataset.assignmentFilter === assignmentFilter);
    });
    renderStaffList();
}

document.addEventListener('DOMContentLoaded', async () => {
    currentUserId = Number(document.getElementById('currentUserId')?.value || 1);
    currentUserRole = String(document.getElementById('currentUserRole')?.value || '');
    currentPayrollStaffId = Number(document.getElementById('currentPayrollStaffId')?.value || 0);
    selfAssignmentMode = currentUserRole === 'Payroll Staff';

    try {
        showLoading(document.querySelector('.staff-list'));
        await loadStats();

        if (selfAssignmentMode && currentPayrollStaffId > 0) {
            const selfStaff = getStaffById(currentPayrollStaffId);
            currentStaffId = currentPayrollStaffId;
            currentStaffName = selfStaff?.name || 'My Assignments';
            renderStaffList();
            updatePanelHeader();
            showLoading(document.getElementById('sitesList'));
            await loadAssignedSites(currentPayrollStaffId);
        } else {
            updatePanelHeader();
        }
    } catch (error) {
        console.error('Failed to initialize dashboard:', error);
        showToast(error.message || 'Failed to initialize dashboard', 'error');
    }

    if (!selfAssignmentMode) {
        document.getElementById('staffSearch')?.addEventListener('input', filterStaff);
        document.getElementById('staffList')?.addEventListener('click', handleStaffClick);
        document.querySelectorAll('[data-assignment-filter]').forEach((button) => {
            button.addEventListener('click', () => setAssignmentFilter(button.dataset.assignmentFilter));
        });
    }
    document.getElementById('sitesList')?.addEventListener('click', handleSiteAction);
    document.getElementById('siteAssignmentSearch')?.addEventListener('input', filterSites);
});

window.selectStaff = selectStaff;
window.assignSite = assignSite;
window.removeSite = removeSite;
window.filterStaff = filterStaff;
window.filterSites = filterSites;
