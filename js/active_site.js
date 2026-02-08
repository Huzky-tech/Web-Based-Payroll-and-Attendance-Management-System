// Update date and time
function updateDateTime() {
    const currentDateEl = document.getElementById('currentDate');
    const currentTimeEl = document.getElementById('currentTime');
    if (!currentDateEl || !currentTimeEl) return;

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

    currentDateEl.textContent = `${day}, ${month} ${date}, ${year}`;
    currentTimeEl.textContent = `${hours}:${minutesStr} ${ampm}`;
}

// Update time every minute
updateDateTime();
setInterval(updateDateTime, 60000);

// Set default date to today (moved inside modal open)

// Modal functionality
const addSiteModal = document.getElementById('addSiteModal');
const btnAddSite = document.getElementById('btnAddSite');
const closeModal = document.getElementById('closeModal');
const cancelBtn = document.getElementById('cancelBtn');
const addSiteForm = document.getElementById('addSiteForm');

// Open modal
if (btnAddSite && addSiteModal) {
    btnAddSite.addEventListener('click', function() {
        addSiteModal.classList.add('active');
        const startDateEl = document.getElementById('startDate');
        if (startDateEl) startDateEl.valueAsDate = new Date();
    });
}

// Close modal
function closeModalFunc() {
    if (addSiteModal) addSiteModal.classList.remove('active');
    if (addSiteForm) addSiteForm.reset();
    const startDateEl = document.getElementById('startDate');
    if (startDateEl) startDateEl.valueAsDate = new Date();
}

if (closeModal) closeModal.addEventListener('click', closeModalFunc);
if (cancelBtn) cancelBtn.addEventListener('click', closeModalFunc);

// Close modal when clicking outside
if (addSiteModal) {
    addSiteModal.addEventListener('click', function(e) {
        if (e.target === addSiteModal) {
            closeModalFunc();
        }
    });
}

// Load sites from DB
async function loadSites() {
    try {
        const response = await fetch("../api/get_sites.php");
        if (!response.ok) throw new Error('Failed to fetch sites');
        const sites = await response.json();
        if (!Array.isArray(sites)) throw new Error('Invalid data format');

        const siteCards = document.getElementById('siteCards');
        if (!siteCards) return; // exit if container not found
        siteCards.innerHTML = "";

    sites.forEach(site => {
        const card = document.createElement('div');
        card.className = 'site-card';

        // Map API fields to JS expectations
        const status = site.Status || 'Active';
        const requiredWorkers = parseInt(site.Required_Workers) || 0;
        const currentWorkers = parseInt(site.Current_Workers ?? site.currentWorkers) || 0;
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
                <span class="site-info-value" data-type="currentWorkers">${currentWorkers}</span>
            </div>

            <div class="site-info-row">
                <span class="site-info-label">Target Capacity:</span>
                <span class="site-info-value" data-type="requiredWorkers">${requiredWorkers}</span>
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
    } catch (err) {
        console.error(err);
        alert('Could not load sites. Please check the server.');
    }
}

// Form submission (SAVE to DB)
if (addSiteForm) {
    addSiteForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Get form elements with checks
        const siteNameEl = document.getElementById('siteName');
        const locationEl = document.getElementById('location');
        const requiredWorkersEl = document.getElementById('requiredWorkers');
        const startDateEl = document.getElementById('startDate');
        const siteManagerEl = document.getElementById('siteManager');
        const statusEl = document.querySelector('input[name="status"]:checked');

        if (!siteNameEl || !locationEl || !requiredWorkersEl || !startDateEl || !siteManagerEl || !statusEl) {
            alert('Form elements not found.');
            return;
        }

        const siteName = siteNameEl.value.trim();
        const location = locationEl.value.trim();
        const requiredWorkersStr = requiredWorkersEl.value.trim();
        const startDate = startDateEl.value;
        const siteManager = siteManagerEl.value.trim();
        const status = statusEl.value;

        // Validation
        if (!siteName || !location || !requiredWorkersStr || !startDate || !siteManager) {
            alert('Please fill in all required fields.');
            return;
        }

        const requiredWorkers = parseInt(requiredWorkersStr);
        if (isNaN(requiredWorkers) || requiredWorkers <= 0) {
            alert('Required workers must be a positive number.');
            return;
        }

        if (new Date(startDate) > new Date()) {
            alert('Start date cannot be in the future.');
            return;
        }

        const formData = {
            siteName,
            location,
            requiredWorkers,
            startDate,
            siteManager,
            status
            // Removed description as it's not saved
        };

        try {
            const response = await fetch("../api/add_site.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (result.status === 'success') {
                closeModalFunc();
                loadSites();
            } else {
                alert(result.message || 'Error saving site.');
            }
        } catch (error) {
            console.error('Error submitting form:', error);
            alert('An error occurred while saving the site.');
        }
    });
}


function updateSummaryCards() {
    const siteCards = document.querySelectorAll('.site-card');

    let totalSites = siteCards.length;
    let activeSites = 0;
    let totalWorkers = 0;
    let totalRequiredWorkers = 0;

    siteCards.forEach(card => {
        const status = card.querySelector('.site-status-badge').textContent.toLowerCase();
        const currentWorkers = parseInt(card.querySelector('.site-info-value[data-type="currentWorkers"]').textContent) || 0;
        const requiredWorkers = parseInt(card.querySelector('.site-info-value[data-type="requiredWorkers"]').textContent) || 0;

        if (status === 'active') activeSites++;
        totalWorkers += currentWorkers;
        totalRequiredWorkers += requiredWorkers;
    });

    const utilization = totalRequiredWorkers === 0 ? 0 : Math.round((totalWorkers / totalRequiredWorkers) * 100);

    const summaryCards = document.querySelectorAll('.summary-card');
    if (summaryCards.length >= 4) {
        summaryCards[0].querySelector('.summary-card-value').textContent = totalSites;
        summaryCards[1].querySelector('.summary-card-value').textContent = activeSites;
        summaryCards[2].querySelector('.summary-card-value').textContent = totalWorkers;
        summaryCards[3].querySelector('.summary-card-value').textContent = utilization + '%';
    }
}


// View Details and Manage button handlers
document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-view-details')) {
        const card = e.target.closest('.site-card');
        const siteName = card.querySelector('.site-card-title').textContent;
        window.location.href = `site_details.php?site=${encodeURIComponent(siteName)}`;
    }

    if (e.target.closest('.btn-manage')) {
        const card = e.target.closest('.site-card');
        const siteName = card.querySelector('.site-card-title').textContent;
        window.location.href = `site_manage.php?site=${encodeURIComponent(siteName)}`;
    }
});

// Initial load
loadSites();
