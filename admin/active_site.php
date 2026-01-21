<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Sites - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/active_site.css">
    <script src="../js/active_site.js" defer></script>
</head>
<body>
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <div class="top-header">
            <h1 class="page-title">Active Sites</h1>
            <div class="header-right">
                <div class="date-time">
                    <div class="date" id="currentDate">Thursday, January 8, 2026</div>
                    <div class="time" id="currentTime">02:46 PM</div>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">A</div>
                    <div class="user-info">
                        <div class="user-name">Assistant Manager</div>
                        <div class="user-role">Manager</div>
                    </div>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Breadcrumbs -->
            <div class="breadcrumbs">
                <a href="dashboard.html">Assistant Manager</a>
                <span>></span>
                <span>Active Sites Management</span>
            </div>

            <!-- Page Header -->
            <div class="page-header">
                <h1>Active Sites Management</h1>
                <p>Monitor and manage all construction sites</p>
            </div>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-card-icon blue">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="summary-card-content">
                        <div class="summary-card-value">4</div>
                        <div class="summary-card-label">Total Sites</div>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-card-icon green">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="summary-card-content">
                        <div class="summary-card-value">3</div>
                        <div class="summary-card-label">Active Sites</div>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-card-icon purple">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="summary-card-content">
                        <div class="summary-card-value">210</div>
                        <div class="summary-card-label">Total Workers</div>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-card-icon orange">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="summary-card-content">
                        <div class="summary-card-value">81%</div>
                        <div class="summary-card-label">Utilization</div>
                    </div>
                </div>
            </div>

            <!-- Add Site Button -->
            <div class="add-site-section">
                <button class="btn-add-site" id="btnAddSite">
                    <i class="fas fa-plus"></i>
                    <span>Add New Site</span>
                </button>
            </div>

            <!-- Site Cards -->
            <div class="site-cards" id="siteCards">
                <!-- Site Card 1 -->
                <div class="site-card">
                    <div class="site-card-header">
                        <div>
                            <div class="site-card-title">Road Street Site</div>
                            <span class="site-status-badge active">Active</span>
                        </div>
                    </div>
                    <div class="site-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>123 Road St, Cityville</span>
                    </div>
                    <div class="site-info-row">
                        <span class="site-info-label">Current Workers:</span>
                        <span class="site-info-value">45</span>
                    </div>
                    <div class="site-info-row">
                        <span class="site-info-label">Target Capacity:</span>
                        <span class="site-info-value">50</span>
                    </div>
                    <div class="capacity-bar-container">
                        <div class="capacity-bar-label">
                            <span>Capacity</span>
                            <span>90%</span>
                        </div>
                        <div class="capacity-bar">
                            <div class="capacity-bar-fill orange" style="width: 90%;"></div>
                        </div>
                    </div>
                    <div class="site-manager">
                        <i class="fas fa-briefcase"></i>
                        <span>Site Manager: John Smith</span>
                    </div>
                    <div class="site-start-date">
                        <i class="fas fa-calendar"></i>
                        <span>Started 2023-01-15</span>
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
                </div>

                <!-- Site Card 2 -->
                <div class="site-card">
                    <div class="site-card-header">
                        <div>
                            <div class="site-card-title">Building Construction Site</div>
                            <span class="site-status-badge active">Active</span>
                        </div>
                    </div>
                    <div class="site-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>456 Build Ave, Townsburg</span>
                    </div>
                    <div class="site-info-row">
                        <span class="site-info-label">Current Workers:</span>
                        <span class="site-info-value">120</span>
                    </div>
                    <div class="site-info-row">
                        <span class="site-info-label">Target Capacity:</span>
                        <span class="site-info-value">150</span>
                    </div>
                    <div class="capacity-bar-container">
                        <div class="capacity-bar-label">
                            <span>Capacity</span>
                            <span>80%</span>
                        </div>
                        <div class="capacity-bar">
                            <div class="capacity-bar-fill orange" style="width: 80%;"></div>
                        </div>
                    </div>
                    <div class="site-manager">
                        <i class="fas fa-briefcase"></i>
                        <span>Site Manager: Sarah Johnson</span>
                    </div>
                    <div class="site-start-date">
                        <i class="fas fa-calendar"></i>
                        <span>Started 2023-03-10</span>
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
                </div>

                <!-- Site Card 3 -->
                <div class="site-card">
                    <div class="site-card-header">
                        <div>
                            <div class="site-card-title">Bridge Project Alpha</div>
                            <span class="site-status-badge active">Active</span>
                        </div>
                    </div>
                    <div class="site-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>789 River Rd, Bridgeton</span>
                    </div>
                    <div class="site-info-row">
                        <span class="site-info-label">Current Workers:</span>
                        <span class="site-info-value">30</span>
                    </div>
                    <div class="site-info-row">
                        <span class="site-info-label">Target Capacity:</span>
                        <span class="site-info-value">40</span>
                    </div>
                    <div class="capacity-bar-container">
                        <div class="capacity-bar-label">
                            <span>Capacity</span>
                            <span>75%</span>
                        </div>
                        <div class="capacity-bar">
                            <div class="capacity-bar-fill red" style="width: 75%;"></div>
                        </div>
                        <div class="capacity-warning">Needs 10 more workers</div>
                    </div>
                    <div class="site-manager">
                        <i class="fas fa-briefcase"></i>
                        <span>Site Manager: Mike Brown</span>
                    </div>
                    <div class="site-start-date">
                        <i class="fas fa-calendar"></i>
                        <span>Started 2023-05-20</span>
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
                </div>
            </div>
        </div>
    </div>

    <!-- Add New Site Modal -->
    <div class="modal-overlay" id="addSiteModal">
        <div class="modal-container">
            <div class="modal-header">
                <h2 class="modal-title">
                    <i class="fas fa-building"></i>
                    <span>Add New Construction Site</span>
                </h2>
                <button class="modal-close" id="closeModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="addSiteForm">
                <div class="form-group">
                    <label for="siteName">Site Name <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="siteName" 
                        name="siteName" 
                        placeholder="e.g. Downtown Office Complex"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="location">Location / Address <span class="required">*</span></label>
                    <div class="input-with-icon">
                        <i class="fas fa-map-marker-alt"></i>
                        <input 
                            type="text" 
                            id="location" 
                            name="location" 
                            placeholder="e.g. 123 Main St, Cityville"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="requiredWorkers">Required Workers <span class="required">*</span></label>
                    <div class="input-with-icon">
                        <i class="fas fa-users"></i>
                        <input 
                            type="number" 
                            id="requiredWorkers" 
                            name="requiredWorkers" 
                            placeholder="e.g. 50"
                            min="1"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="startDate">Start Date</label>
                    <div class="input-with-icon right-icon">
                        <input 
                            type="date" 
                            id="startDate" 
                            name="startDate"
                        >
                        <i class="fas fa-calendar"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="siteManager">Site Manager</label>
                    <div class="input-with-icon">
                        <i class="fas fa-briefcase"></i>
                        <input 
                            type="text" 
                            id="siteManager" 
                            name="siteManager" 
                            placeholder="e.g. John Smith"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        placeholder="Brief description of the project scope..."
                    ></textarea>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="statusActive" name="status" value="active" checked>
                            <label for="statusActive">Active</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="statusInactive" name="status" value="inactive">
                            <label for="statusInactive">Inactive</label>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" id="cancelBtn">Cancel</button>
                    <button type="submit" class="btn-create">Create Site</button>
                </div>
            </form>
        </div>
    </div>

    <script>
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

        // Form submission
        addSiteForm.addEventListener('submit', function(e) {
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

            // Create new site card
            createSiteCard(formData);
            
            // Close modal and reset form
            closeModalFunc();
            
            // Update summary cards
            updateSummaryCards();
            
            console.log('New site created:', formData);
        });

        // Create site card dynamically
        function createSiteCard(data) {
            const siteCards = document.getElementById('siteCards');
            const capacity = 0; // New site starts with 0 workers
            const capacityPercent = 0;
            const isLowCapacity = capacityPercent < 75;
            
            const card = document.createElement('div');
            card.className = 'site-card';
            card.innerHTML = `
                <div class="site-card-header">
                    <div>
                        <div class="site-card-title">${data.siteName}</div>
                        <span class="site-status-badge ${data.status}">${data.status.charAt(0).toUpperCase() + data.status.slice(1)}</span>
                    </div>
                </div>
                <div class="site-location">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>${data.location}</span>
                </div>
                <div class="site-info-row">
                    <span class="site-info-label">Current Workers:</span>
                    <span class="site-info-value">0</span>
                </div>
                <div class="site-info-row">
                    <span class="site-info-label">Target Capacity:</span>
                    <span class="site-info-value">${data.requiredWorkers}</span>
                </div>
                <div class="capacity-bar-container">
                    <div class="capacity-bar-label">
                        <span>Capacity</span>
                        <span>0%</span>
                    </div>
                    <div class="capacity-bar">
                        <div class="capacity-bar-fill ${isLowCapacity ? 'red' : 'orange'}" style="width: 0%;"></div>
                    </div>
                    ${isLowCapacity ? '<div class="capacity-warning">Needs ${data.requiredWorkers} more workers</div>' : ''}
                </div>
                <div class="site-manager">
                    <i class="fas fa-briefcase"></i>
                    <span>Site Manager: ${data.siteManager || 'Not assigned'}</span>
                </div>
                <div class="site-start-date">
                    <i class="fas fa-calendar"></i>
                    <span>Started ${data.startDate || 'Not set'}</span>
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
        }

        // Update summary cards
        function updateSummaryCards() {
            const siteCards = document.querySelectorAll('.site-card');
            const totalSites = siteCards.length;
            const activeSites = document.querySelectorAll('.site-status-badge.active').length;
            
            // Update total sites
            document.querySelector('.summary-card:first-child .summary-card-value').textContent = totalSites;
            
            // Update active sites
            document.querySelectorAll('.summary-card')[1].querySelector('.summary-card-value').textContent = activeSites;
        }

        // View Details and Manage button handlers
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-view-details')) {
                const card = e.target.closest('.site-card');
                const siteName = card.querySelector('.site-card-title').textContent;
                console.log('View details for:', siteName);
                // Add your view details logic here
            }
            
            if (e.target.closest('.btn-manage')) {
                const card = e.target.closest('.site-card');
                const siteName = card.querySelector('.site-card-title').textContent;
                console.log('Manage site:', siteName);
                // Add your manage logic here
            }
        });
    </script>
</body>
</html>

