/* ===================== Site Assignment Management ===================== 
 * Uses Backend APIs to manage payroll staff site assignments
 */

// ==================== Data Storage ====================
let allStaff = [];
let allSites = [];
let currentStaffId = null;
let currentStaffAssignedSites = [];

// ==================== Initialization ====================
document.addEventListener('DOMContentLoaded', function() {
    loadSummaryData();
    loadPayrollStaff();
    loadAllSites();
});

// ==================== Date & Time ====================
function updateDateTime() {
    const now = new Date();
    const dateEl = document.getElementById('currentDate');
    const timeEl = document.getElementById('currentTime');
    if (dateEl) {
        dateEl.textContent = now.toLocaleDateString('en-US', { 
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
        });
    }
    if (timeEl) {
        timeEl.textContent = now.toLocaleTimeString('en-US', { 
            hour: '2-digit', minute: '2-digit' 
        });
    }
}
updateDateTime();
setInterval(updateDateTime, 60000);

// ==================== Summary Data ====================
async function loadSummaryData() {
    try {
        // Load total payroll staff
        const staffResponse = await fetch('api/count_payroll_staff.php');
        const staffData = await staffResponse.json();
        const staffEl = document.getElementById('totalPayrollStaff');
        if (staffEl) {
            staffEl.textContent = staffData.success ? staffData.count : 0;
        }

        // Load active sites
        const sitesResponse = await fetch('api/get_sites.php');
        const sitesData = await sitesResponse.json();
        const sitesEl = document.getElementById('activeSitesCount');
        if (sitesEl) {
            sitesEl.textContent = sitesData.success ? sitesData.count : 0;
        }

        // Load total assignments
        const assignResponse = await fetch('api/count_assignments.php');
        const assignData = await assignResponse.json();
        const assignEl = document.getElementById('totalAssignments');
        if (assignEl) {
            assignEl.textContent = assignData.success ? assignData.count : 0;
        }
    } catch (error) {
        console.error('Error loading summary data:', error);
    }
}

// ==================== Load Payroll Staff ====================
async function loadPayrollStaff() {
    try {
        const response = await fetch('api/get_payroll_staff.php');
        const data = await response.json();
        
        if (data.success) {
            allStaff = data.staff;
            renderStaffList(allStaff);
        } else {
            showError('Failed to load payroll staff');
        }
    } catch (error) {
        console.error('Error loading payroll staff:', error);
        showError('Error connecting to server');
    }
}

function renderStaffList(staffList) {
    const staffListEl = document.getElementById('staffList');
    if (!staffListEl) return;

    if (staffList.length === 0) {
        staffListEl.innerHTML = '<div class="no-staff" style="padding: 20px; text-align: center; color: #6b7280;">No payroll staff found</div>';
        return;
    }

    staffListEl.innerHTML = staffList.map(staff => `
        <div class="staff-item" data-staff="${staff.id}" onclick="selectStaff(${staff.id}, '${escapeHtml(staff.name)}')">
            <div class="staff-item-header">
                <div class="staff-info">
                    <div class="staff-name">${escapeHtml(staff.name)}</div>
                    <div class="staff-email">${escapeHtml(staff.email || 'No email')}</div>
                </div>
                <span class="staff-sites-badge">${staff.assigned_sites_count} Site${staff.assigned_sites_count !== 1 ? 's' : ''}</span>
            </div>
        </div>
    `).join('');
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ==================== Load All Sites ====================
async function loadAllSites() {
    try {
        const response = await fetch('api/get_active_sites.php');
        const data = await response.json();
        
        if (data.success && data.sites) {
            allSites = data.sites;
        } else {
            // Try getting all sites if active sites fails
            const allResponse = await fetch('api/get_sites.php');
            const allData = await allResponse.json();
            allSites = Array.isArray(allData) ? allData : [];
        }
    } catch (error) {
        console.error('Error loading sites:', error);
        allSites = [];
    }
}

// ==================== Staff Selection ====================
async function selectStaff(staffId, staffName) {
    currentStaffId = staffId;
    
    // Update UI selection
    document.querySelectorAll('.staff-item').forEach(item => item.classList.remove('selected'));
    const selectedItem = document.querySelector(`[data-staff="${staffId}"]`);
    if (selectedItem) {
        selectedItem.classList.add('selected');
    }

    // Show sites panel
    const emptyState = document.getElementById('emptyState');
    const sitesContent = document.getElementById('sitesContent');
    if (emptyState) emptyState.style.display = 'none';
    if (sitesContent) sitesContent.style.display = 'block';

    // Update panel title
    const panelTitle = document.getElementById('panelTitle');
    if (panelTitle) {
        panelTitle.textContent = `Assign Sites to ${staffName}`;
    }

    // Load assigned sites for this staff
    await loadStaffSites(staffId);
}

async function loadStaffSites(staffId) {
    try {
        const response = await fetch(`api/get_staff_sites.php?staff_id=${staffId}`);
        const data = await response.json();
        
        if (data.success) {
            currentStaffAssignedSites = data.sites.map(s => s.SiteID);
            const panelSubtitle = document.getElementById('panelSubtitle');
            if (panelSubtitle) {
                panelSubtitle.textContent = `${data.count} active assignment${data.count !== 1 ? 's' : ''}`;
            }
            renderSitesList();
        } else {
            currentStaffAssignedSites = [];
            const panelSubtitle = document.getElementById('panelSubtitle');
            if (panelSubtitle) {
                panelSubtitle.textContent = '0 active assignments';
            }
            renderSitesList();
        }
    } catch (error) {
        console.error('Error loading staff sites:', error);
        currentStaffAssignedSites = [];
        renderSitesList();
    }
}

// ==================== Render Sites List ====================
function renderSitesList() {
    const sitesListEl = document.getElementById('sitesList');
    if (!sitesListEl) return;

    if (allSites.length === 0) {
        sitesListEl.innerHTML = '<div style="padding: 20px; text-align: center; color: #6b7280;">No sites available</div>';
        return;
    }

    sitesListEl.innerHTML = allSites.map(site => {
        const isAssigned = currentStaffAssignedSites.includes(site.SiteID);
        const statusClass = site.Status ? site.Status.toLowerCase() : 'inactive';
        
        return `
        <div class="site-item ${statusClass === 'inactive' ? 'inactive' : ''}">
            <div class="site-item-header">
                <div>
                    <div class="site-name">${escapeHtml(site.Site_Name)}</div>
                    <div class="site-address">${escapeHtml(site.Location || 'No address')}</div>
                </div>
                <button class="site-action ${isAssigned ? 'remove' : 'add'}" 
                    onclick="${isAssigned 
                        ? `removeSiteFromStaff(${site.SiteID}, '${escapeHtml(site.Site_Name)}')` 
                        : `assignSiteToStaff(${site.SiteID}, '${escapeHtml(site.Site_Name)}')`}">
                    <i class="fas ${isAssigned ? 'fa-trash' : 'fa-plus'}"></i>
                </button>
            </div>
            <span class="site-status ${statusClass}">${statusClass}</span>
            <div class="site-details">
                <div>${site.Current_Workers || 0} Current Workers</div>
                <div>Target: ${site.Required_Workers || 0}</div>
            </div>
        </div>
    `}).join('');
}

// ==================== Assign Site to Staff ====================
async function assignSiteToStaff(siteId, siteName) {
    if (!currentStaffId) {
        showError('Please select a staff member first');
        return;
    }

    try {
        const response = await fetch('api/assign_site_to_staff.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                staff_id: currentStaffId,
                site_id: siteId,
                user_id: 1 // Admin user ID
            })
        });

        const data = await response.json();

        if (data.success) {
            // Add to local list
            currentStaffAssignedSites.push(siteId);
            
            // Update staff badge in the list
            updateStaffBadgeInList(currentStaffId);
            
            // Re-render sites
            renderSitesList();
            
            // Update summary
            loadSummaryData();
            
            // Update panel subtitle
            const count = currentStaffAssignedSites.length;
            const panelSubtitle = document.getElementById('panelSubtitle');
            if (panelSubtitle) {
                panelSubtitle.textContent = `${count} active assignment${count !== 1 ? 's' : ''}`;
            }
            
            showSuccess(`Site "${siteName}" assigned successfully`);
        } else {
            showError(data.message || 'Failed to assign site');
        }
    } catch (error) {
        console.error('Error assigning site:', error);
        showError('Error connecting to server');
    }
}

// ==================== Remove Site from Staff ====================
async function removeSiteFromStaff(siteId, siteName) {
    if (!currentStaffId) {
        showError('Please select a staff member first');
        return;
    }

    try {
        const response = await fetch('api/remove_site_assignment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                staff_id: currentStaffId,
                site_id: siteId,
                user_id: 1 // Admin user ID
            })
        });

        const data = await response.json();

        if (data.success) {
            // Remove from local list
            currentStaffAssignedSites = currentStaffAssignedSites.filter(id => id !== siteId);
            
            // Update staff badge in the list
            updateStaffBadgeInList(currentStaffId);
            
            // Re-render sites
            renderSitesList();
            
            // Update summary
            loadSummaryData();
            
            // Update panel subtitle
            const count = currentStaffAssignedSites.length;
            const panelSubtitle = document.getElementById('panelSubtitle');
            if (panelSubtitle) {
                panelSubtitle.textContent = `${count} active assignment${count !== 1 ? 's' : ''}`;
            }
            
            showSuccess(`Site "${siteName}" removed successfully`);
        } else {
            showError(data.message || 'Failed to remove site');
        }
    } catch (error) {
        console.error('Error removing site:', error);
        showError('Error connecting to server');
    }
}

function updateStaffBadgeInList(staffId) {
    const staffItem = document.querySelector(`[data-staff="${staffId}"]`);
    if (staffItem) {
        const badge = staffItem.querySelector('.staff-sites-badge');
        if (badge) {
            const count = currentStaffAssignedSites.length;
            badge.textContent = `${count} Site${count !== 1 ? 's' : ''}`;
        }
    }
}

// ==================== Search Staff ====================
async function filterStaff() {
    const searchInput = document.getElementById('staffSearch');
    if (!searchInput) return;
    
    const keyword = searchInput.value.trim();
    
    if (keyword === '') {
        // Load all staff if search is empty
        renderStaffList(allStaff);
        return;
    }

    try {
        const response = await fetch(`api/search_payroll_staff.php?keyword=${encodeURIComponent(keyword)}`);
        const data = await response.json();
        
        if (data.success) {
            renderStaffList(data.staff);
        }
    } catch (error) {
        console.error('Error searching staff:', error);
    }
}

// Debounce search for better performance
let searchTimeout;
if (document.getElementById('staffSearch')) {
    document.getElementById('staffSearch').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(filterStaff, 300);
    });
}

// ==================== Audit Log ====================
async function viewAuditLog() {
    try {
        const response = await fetch('api/get_audit_logs.php');
        const logs = await response.json();
        
        if (!logs || logs.length === 0) {
            alert('No audit records found.');
            return;
        }

        // Build log message
        let logMessage = '=== Site Assignment Audit Log ===\n\n';
        logs.forEach(log => {
            const timestamp = new Date(log.ts).toLocaleString();
            logMessage += `[${timestamp}] ${log.user} (${log.role})\n`;
            logMessage += `Action: ${log.action}\n`;
            if (log.details) {
                logMessage += `Details: ${log.details}\n`;
            }
            logMessage += '\n---\n\n';
        });

        // Show in alert (or could use a modal)
        alert(logMessage);
    } catch (error) {
        console.error('Error loading audit logs:', error);
        showError('Failed to load audit logs');
    }
}

// ==================== Utility Functions ====================
function showError(message) {
    alert('Error: ' + message);
}

function showSuccess(message) {
    // Could use a toast notification here
    console.log('Success:', message);
}

// Make functions globally available for onclick handlers
window.selectStaff = selectStaff;
window.assignSiteToStaff = assignSiteToStaff;
window.removeSiteFromStaff = removeSiteFromStaff;
window.filterStaff = filterStaff;
window.viewAuditLog = viewAuditLog;

