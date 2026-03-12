/* ===================== DATA ===================== */
const auditLogs = [];

const staffSites = {
    'staff-a': [
        { name: 'Road Street Site', address: '123 Road St, Cityville', status: 'active', workers: 45, target: 50 }
    ],
    'staff-b': [],
    'staff-c': [],
    'staff-d': []
};

const allSites = [
    { name: 'Road Street Site', address: '123 Road St, Cityville', status: 'active', workers: 45, target: 50 },
    { name: 'Building Construction Site', address: '456 Build Ave, Townsburg', status: 'active', workers: 120, target: 150 },
    { name: 'Bridge Project Alpha', address: '789 River Rd, Bridgeton', status: 'active', workers: 30, target: 40 },
    { name: 'Downtown Renovation', address: '101 Main St, Metropolis', status: 'inactive', workers: 15, target: 20 }
];

/* ===================== DATE & TIME ===================== */
function updateDateTime() {
    const now = new Date();
    document.getElementById('currentDate').textContent =
        now.toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
    document.getElementById('currentTime').textContent =
        now.toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit' });
}
updateDateTime();
setInterval(updateDateTime, 60000);

/* ===================== STAFF SELECTION ===================== */
function selectStaff(staffId, staffName) {
    document.querySelectorAll('.staff-item').forEach(i => i.classList.remove('selected'));
    document.querySelector(`[data-staff="${staffId}"]`).classList.add('selected');

    document.getElementById('emptyState').style.display = 'none';
    document.getElementById('sitesContent').style.display = 'block';

    document.getElementById('panelTitle').textContent = `Assign Sites to ${staffName}`;
    document.getElementById('panelSubtitle').textContent =
        `${staffSites[staffId].length} active assignments`;

    renderSites(staffId);
}

/* ===================== RENDER SITES ===================== */
function renderSites(staffId) {
    const sitesList = document.getElementById('sitesList');
    sitesList.innerHTML = '';

    allSites.forEach(site => {
        const assigned = staffSites[staffId].some(s => s.name === site.name);

        sitesList.innerHTML += `
            <div class="site-item ${site.status === 'inactive' ? 'inactive' : ''}">
                <div class="site-item-header">
                    <div>
                        <div class="site-name">${site.name}</div>
                        <div class="site-address">${site.address}</div>
                    </div>
                    <button class="site-action ${assigned ? 'remove' : 'add'}"
                        onclick="${assigned
                            ? `removeSite('${staffId}','${site.name}')`
                            : `addSite('${staffId}','${site.name}')`}">
                        <i class="fas ${assigned ? 'fa-trash' : 'fa-plus'}"></i>
                    </button>
                </div>
                <span class="site-status ${site.status}">${site.status}</span>
                <div class="site-details">
                    <div>${site.workers} Current Workers</div>
                    <div>Target: ${site.target}</div>
                </div>
            </div>`;
    });
}

/* ===================== ADD / REMOVE SITE ===================== */
function addSite(staffId, siteName) {
    const site = allSites.find(s => s.name === siteName);
    staffSites[staffId].push(site);

    logAudit(`Assigned ${siteName} to ${staffId}`);
    updateStaffBadge(staffId);
    updateTotals();

    selectStaff(staffId, document.querySelector(`[data-staff="${staffId}"] .staff-name`).textContent);
}

function removeSite(staffId, siteName) {
    staffSites[staffId] = staffSites[staffId].filter(s => s.name !== siteName);

    logAudit(`Removed ${siteName} from ${staffId}`);
    updateStaffBadge(staffId);
    updateTotals();

    selectStaff(staffId, document.querySelector(`[data-staff="${staffId}"] .staff-name`).textContent);
}

/* ===================== BADGES & TOTALS ===================== */
function updateStaffBadge(staffId) {
    const staff = document.querySelector(`[data-staff="${staffId}"] .staff-item-header`);
    staff.querySelector('.staff-sites-badge')?.remove();

    const count = staffSites[staffId].length;
    if (count > 0) {
        staff.innerHTML += `<span class="staff-sites-badge">${count} Site${count > 1 ? 's' : ''}</span>`;
    }
}

function updateTotals() {
    let total = 0;
    Object.values(staffSites).forEach(s => total += s.length);
    document.querySelector('.summary-value:last-child').textContent = total;
}

/* ===================== AUDIT LOG ===================== */
function logAudit(action) {
    auditLogs.push({
        time: new Date().toLocaleString(),
        user: 'Admin User',
        action
    });
}

function viewAuditLog() {
    if (auditLogs.length === 0) {
        alert('No audit records yet.');
        return;
    }

    const logText = auditLogs
        .map(l => `[${l.time}] ${l.user}: ${l.action}`)
        .join('\n');

    alert(logText);
}

/* ===================== SEARCH ===================== */
function filterStaff() {
    const filter = document.getElementById('staffSearch').value.toLowerCase();
    document.querySelectorAll('.staff-item').forEach(item => {
        item.style.display =
            item.innerText.toLowerCase().includes(filter) ? 'block' : 'none';
    });
}

/* ===================== INIT ===================== */
document.addEventListener('DOMContentLoaded', () => {
    Object.keys(staffSites).forEach(updateStaffBadge);
    updateTotals();
});