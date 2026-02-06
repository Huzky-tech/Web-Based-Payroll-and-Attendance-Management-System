// Update date and time
function updateDateTime() {
    const now = new Date();
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    const day = days[now.getDay()];
    const month = months[now.getMonth()];
    const date = now.getDate();
    const year = now.getFullYear();

    let hours = now.getHours();
    const minutes = now.getMinutes();
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12;
    const minutesStr = minutes < 10 ? '0' + minutes : minutes;

    document.getElementById('currentDate').textContent = `${day}, ${month} ${date}, ${year}`;
    document.getElementById('currentTime').textContent = `${hours}:${minutesStr} ${ampm}`;
}

// Update time every minute
updateDateTime();
setInterval(updateDateTime, 60000);

// Set default date to today
document.getElementById('startDate').valueAsDate = new Date();

// Modal functionality
const addSiteModal = document.getElementById('addSiteModal');
const btnAddSite = document.getElementById('btnAddSite');
const closeModal = document.getElementById('closeModal');
const cancelBtn = document.getElementById('cancelBtn');
const addSiteForm = document.getElementById('addSiteForm');

// Open modal
btnAddSite.addEventListener('click', function() {
    addSiteModal.classList.add('active');
});

// Close modal
function closeModalFunc() {
    addSiteModal.classList.remove('active');
    addSiteForm.reset();
    document.getElementById('startDate').valueAsDate = new Date();
}

closeModal.addEventListener('click', closeModalFunc);
cancelBtn.addEventListener('click', closeModalFunc);

// Close modal when clicking outside
addSiteModal.addEventListener('click', function(e) {
    if (e.target === addSiteModal) {
        closeModalFunc();
    }
});

// Load sites from DB
async function loadSites() {
    const response = await fetch("../api/get_sites.php");
    const sites = await response.json();

    const siteCards = document.getElementById('siteCards');
    siteCards.innerHTML = "";

    sites.forEach(site => {
        const card = document.createElement('div');
        card.className = 'site-card';

        const status = site.Status || 'Active';
        const requiredWorkers = site.requiredWorkers || 0;
        const currentWorkers = site.currentWorkers || 0;
        const capacityPercent = requiredWorkers == 0 ? 0 : Math.round((currentWorkers / requiredWorkers) * 100);
        const needsWorkers = requiredWorkers - currentWorkers;

        const lowCapacity = capacityPercent < 75;

        card.innerHTML = `
            <div class="site-card-header">
                <div>
                    <div class="site-card-title">${site.Site_Name}</div>
                    <span class="site-status-badge ${status.toLowerCase()}">${status}</span>
                </div>
            </div>

            <div class="site-location">
                <i class="fas fa-map-marker-alt"></i>
                <span>${site.Location}</span>
            </div>

            <div class="site-info-row">
                <span class="site-info-label">Current Workers:</span>
                <span class="site-info-value">${currentWorkers}</span>
            </div>

            <div class="site-info-row">
                <span class="site-info-label">Target Capacity:</span>
                <span class="site-info-value">${requiredWorkers}</span>
            </div>

            <div class="capacity-bar-container">
                <div class="capacity-bar-label">
                    <span>Capacity</span>
                    <span>${capacityPercent}%</span>
                </div>
                <div class="capacity-bar">
                    <div class="capacity-bar-fill ${lowCapacity ? 'red' : 'orange'}" style="width: ${capacityPercent}%;"></div>
                </div>
                ${lowCapacity ? `<div class="capacity-warning">Needs ${needsWorkers} more workers</div>` : ''}
            </div>

            <div class="site-manager">
                <i class="fas fa-briefcase"></i>
                <span>Site Manager: ${site.Site_Manager || 'Not assigned'}</span>
            </div>

            <div class="site-start-date">
                <i class="fas fa-calendar"></i>
                <span>Started ${site.Start_Date || 'Not set'}</span>
            </div>

            <div class="site-card-actions">
                <button class="btn-view-details">
                    <i class="fas fa-eye"></i>
                    <span>View Details</span>
                </button>
                <button class="btn-manage">
                    <i class="fas fa-pencil-alt"></i>
                    <span>Manage</span>
                </button>
            </div>
        `;

        siteCards.appendChild(card);
    });

    updateSummaryCards();
}

// Form submission (SAVE to DB)
addSiteForm.addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = {
        siteName: document.getElementById('siteName').value,
        location: document.getElementById('location').value,
        requiredWorkers: document.getElementById('requiredWorkers').value,
        startDate: document.getElementById('startDate').value,
        siteManager: document.getElementById('siteManager').value,
        description: document.getElementById('description').value,
        status: document.querySelector('input[name="status"]:checked').value
    };

    const response = await fetch("../api/get_sites.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(formData)
    });

    const result = await response.json();

    if (result.status === 'success') {
        closeModalFunc();
        loadSites();
    } else {
        alert(result.message);
    }
});

// Update summary cards
function updateSummaryCards() {
    const siteCards = document.querySelectorAll('.site-card');
    const totalSites = siteCards.length;
    const activeSites = document.querySelectorAll('.site-status-badge.active').length;

    document.querySelector('.summary-card:first-child .summary-card-value').textContent = totalSites;
    document.querySelectorAll('.summary-card')[1].querySelector('.summary-card-value').textContent = activeSites;
}

// View Details and Manage button handlers
document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-view-details')) {
        const card = e.target.closest('.site-card');
        const siteName = card.querySelector('.site-card-title').textContent;
        console.log('View details for:', siteName);
    }

    if (e.target.closest('.btn-manage')) {
        const card = e.target.closest('.site-card');
        const siteName = card.querySelector('.site-card-title').textContent;
        console.log('Manage site:', siteName);
    }
});

// Initial load
loadSites();
