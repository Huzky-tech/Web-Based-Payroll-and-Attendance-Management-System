/* ===================== APP CONFIG ===================== */
const API_BASE = '../api';

/* ===================== APP STATE ===================== */
let currentStaffId = null;
let staffList = [];
let allSites = [];
let assignedSites = [];
let auditLogs = [];
let currentUserId = 1;

/* ===================== UTILS ===================== */
function showToast(message, type = 'success') {
    // Remove existing toasts
    document.querySelectorAll('.toast').forEach(toast => toast.remove());
    
    const toast = document.createElement('div');
    toast.className = `toast toast--${type}`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${message}
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => toast.remove(), 4000);
}

function showLoading(element) {
    element.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
}

function getApiUrl(endpoint) {
    return `${API_BASE}/${endpoint}`;
}

/* ===================== API CALLS ===================== */
async function fetchApi(endpoint, options = {}) {
    try {
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
    } catch (error) {
        showToast(error.message || 'Network error', 'error');
        throw error;
    }
}

async function loadStats() {
    const stats = await Promise.all([
        fetchApi('count_payroll_staff.php'),
        fetchApi('count_active_sites.php'),
        fetchApi('count_assignments.php')
    ]);
    
    document.getElementById('staffCount').textContent = stats[0].count;
    document.getElementById('siteCount').textContent = stats[1].count;
    document.getElementById('assignmentCount').textContent = stats[2].count;
}

async function loadStaff() {
    const data = await fetchApi('get_payroll_staff.php');
    staffList = data.staff;
    
    renderStaffList();
}

function renderStaffList(searchTerm = '') {
    const container = document.getElementById('staffList');
    const filteredStaff = staffList.filter(staff => 
        staff.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        staff.email.toLowerCase().includes(searchTerm.toLowerCase())
    );
    
    container.innerHTML = filteredStaff.map(staff => `
        <div class="staff-item" data-staff="${staff.id}" onclick="selectStaff(${staff.id}, '${staff.name.replace(/'/g, "\\'")}')">
            <div class="staff-item-header">
                <div class="staff-name">${staff.name}</div>
                ${staff.assigned_sites_count > 0 ? `<span class="staff-sites-badge">${staff.assigned_sites_count} Site${staff.assigned_sites_count > 1 ? 's' : ''}</span>` : ''}
            </div>
            <div class="staff-email">${staff.email}</div>
            ${staff.user_status === 'Inactive' ? '<span class="status-badge inactive">Inactive</span>' : ''}
        </div>
    `).join('');
}

async function loadAllSites() {
    const data = await fetchApi('get_active_sites.php'); // or get_sites.php with active filter
    allSites = data.sites || data;
}

async function loadAssignedSites(staffId) {
    try {
        const data = await fetchApi(`get_staff_sites.php?staff_id=${staffId}`);
        assignedSites = data.sites;
        renderSites();
    } catch (error) {
        console.error('Failed to load assigned sites');
    }
}

function renderSites() {
    const container = document.getElementById('sitesList');
    container.innerHTML = '';
    
    allSites.forEach(site => {
        const isAssigned = assignedSites.some(as => as.SiteID == site.SiteID);
        
        container.innerHTML += `
            <div class="site-item ${site.Status?.toLowerCase() === 'inactive' ? 'inactive' : ''}" data-site="${site.SiteID}">
                <div class="site-item-header">
                    <div>
                        <div class="site-name">${site.Site_Name}</div>
                        <div class="site-address">${site.Location || ''}</div>
                    </div>
                    <button class="site-action ${isAssigned ? 'remove' : 'add'}" 
                            onclick="${isAssigned ? `removeSite(${currentStaffId}, ${site.SiteID})` : `assignSite(${currentStaffId}, ${site.SiteID})`}">
                        <i class="fas ${isAssigned ? 'fa-trash' : 'fa-plus'}"></i>
                    </button>
                </div>
                <span class="site-status ${site.Status?.toLowerCase()}">${site.Status || 'Unknown'}</span>
                <div class="site-details">
                    <div>${site.current_workers || 0} Current Workers</div>
                    <div>Required: ${site.Required_Workers || 'N/A'}</div>
                </div>
            </div>
        `;
    });
}

async function assignSite(staffId, siteId) {
    showLoading(document.getElementById('sitesList'));
    
    try {
        const data = {
            staff_id: staffId,
            site_id: siteId,
            user_id: currentUserId
        };
        
        await fetchApi('assign_site_to_staff.php', {
            method: 'POST',
            body: JSON.stringify(data)
        });
        
        showToast('Site assigned successfully!');
        currentStaffId = staffId;
        await loadAssignedSites(staffId);
    } catch (error) {
        showToast('Failed to assign site', 'error');
    }
}

async function removeSite(staffId, siteId) {
    if (!confirm('Remove this site assignment?')) return;
    
    showLoading(document.getElementById('sitesList'));
    
    try {
        const data = {
            staff_id: staffId,
            site_id: siteId,
            user_id: currentUserId
        };
        
        await fetchApi('remove_site_assignment.php', {
            method: 'POST',
            body: JSON.stringify(data)
        });
        
        showToast('Site assignment removed!');
        currentStaffId = staffId;
        await loadAssignedSites(staffId);
    } catch (error) {
        showToast('Failed to remove assignment', 'error');
    }
}

async function viewAuditLog() {
    try {
        const data = await fetchApi('get_audit_logs.php?type=assignment');
        renderAuditModal(data);
    } catch (error) {
        showToast('Failed to load audit logs', 'error');
    }
}

function renderAuditModal(logs) {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-clipboard-list"></i> Audit Logs (Assignments)</h3>
                <button class="modal-close" onclick="this.parentElement.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="audit-table">
                    ${logs.map(log => `
                        <div class="audit-row">
                            <div class="audit-user">${log.user}</div>
                            <div class="audit-action">${log.action}</div>
                            <div class="audit-time">${new Date(log.ts).toLocaleString()}</div>
                        </div>
                    `).join('')}
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

/* ===================== EVENT LISTENERS ===================== */
document.addEventListener('DOMContentLoaded', async () => {
    currentUserId = parseInt(document.getElementById('currentUserId')?.value || 1);
    
    try {
        showLoading(document.querySelector('.staff-list'));
        await Promise.all([
            loadStats(),
            loadStaff(),
            loadAllSites()
        ]);
    } catch (error) {
        showToast('Failed to initialize dashboard', 'error');
    }
    
    // Search
    document.getElementById('staffSearch').addEventListener('input', (e) => {
        renderStaffList(e.target.value);
    });
    
// Toast removed as requested

});

/* ===================== GLOBAL FUNCTIONS ===================== */
async function selectStaff(staffId, staffName) {
    currentStaffId = staffId;
    
    document.querySelectorAll('.staff-item').forEach(item => 
        item.classList.remove('selected')
    );
    event.target.closest('.staff-item').classList.add('selected');
    
    document.getElementById('emptyState').style.display = 'none';
    document.getElementById('sitesContent').style.display = 'block';
    
    document.getElementById('panelTitle').textContent = `Assign Sites to ${staffName}`;
    
    showLoading(document.getElementById('sitesList'));
    await loadAssignedSites(staffId);
}

