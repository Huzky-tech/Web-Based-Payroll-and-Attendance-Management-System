
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
    