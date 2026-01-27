<?php
include '../api/connection/db_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Assignments - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/site_assign.css">
</head>
<body>
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <div class="top-header">
            <h1 class="page-title">Site Assignments</h1>
            <div class="header-right">
                <div class="date-time">
                    <div class="date" id="currentDate">Wednesday, January 7, 2026</div>
                    <div class="time" id="currentTime">05:23 PM</div>
                </div>
                <div class="user-profile">
                    <div class="user-avatar"><i class="fas fa-user"></i></div>
                    <div class="user-info">
                        <div class="user-name">Admin User</div>
                        <div class="user-role">Admin</div>
                    </div>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Page Header -->
            <div class="page-header">
                <h1>Site Assignments</h1>
                <p class="page-subtitle">Manage payroll staff access to construction sites</p>
            </div>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-icon blue">
                        <i class="fas fa-user-group"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-label">Total Payroll Staff</div>
                        <div class="summary-value">4</div>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon green">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-label">Active Sites</div>
                        <div class="summary-value">3</div>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon purple">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-label">Total Assignments</div>
                        <div class="summary-value">1</div>
                    </div>
                </div>
            </div>

            <!-- Staff Assignment Management -->
            <div class="assignment-section">
                <div class="assignment-header">
                    <div class="assignment-title-section">
                        <h3>Staff Assignment Management</h3>
                        <p class="assignment-subtitle">Assign payroll staff to specific construction sites</p>
                    </div>
                    <button class="btn-audit" onclick="viewAuditLog()">
                        <i class="fas fa-clipboard-list"></i> View Audit Log
                    </button>
                </div>

                <div class="assignment-panels">
                    <!-- Left Panel - Staff List -->
                    <div class="staff-list-panel">
                        <div class="staff-search">
                            <i class="fas fa-search"></i>
                            <input type="text" id="staffSearch" placeholder="Search payroll staff..." onkeyup="filterStaff()">
                        </div>
                        <div class="staff-list" id="staffList">
                            <div class="staff-item" data-staff="staff-a" onclick="selectStaff('staff-a', 'Payroll Staff A')">
                                <div class="staff-item-header">
                                    <div class="staff-name">Payroll Staff A</div>
                                    <span class="staff-sites-badge">1 Sites</span>
                                </div>
                                <div class="staff-email">staff.a@company.com</div>
                            </div>
                            <div class="staff-item" data-staff="staff-b" onclick="selectStaff('staff-b', 'Payroll Staff B')">
                                <div class="staff-item-header">
                                    <div class="staff-name">Payroll Staff B</div>
                                </div>
                                <div class="staff-email">staff.b@company.com</div>
                            </div>
                            <div class="staff-item" data-staff="staff-c" onclick="selectStaff('staff-c', 'Payroll Staff C')">
                                <div class="staff-item-header">
                                    <div class="staff-name">Payroll Staff C</div>
                                </div>
                                <div class="staff-email">staff.c@company.com</div>
                            </div>
                            <div class="staff-item" data-staff="staff-d" onclick="selectStaff('staff-d', 'Payroll Staff D')">
                                <div class="staff-item-header">
                                    <div class="staff-name">Payroll Staff D</div>
                                </div>
                                <div class="staff-email">staff.d@company.com</div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Panel - Site Assignments -->
                    <div class="sites-panel" id="sitesPanel">
                        <!-- Empty State (shown by default) -->
                        <div class="sites-placeholder" id="emptyState">
                            <i class="fas fa-user-group"></i>
                            <div class="sites-placeholder-title">Select a Staff Member</div>
                            <div class="sites-placeholder-text">Select a payroll staff member from the list to view and manage their site assignments.</div>
                        </div>

                        <!-- Site Assignments Content (hidden by default) -->
                        <div id="sitesContent" style="display: none;">
                            <div class="sites-panel-header">
                                <h4 class="sites-panel-title" id="panelTitle">Assign Sites to Payroll Staff A</h4>
                                <p class="sites-panel-subtitle" id="panelSubtitle">1 active assignments</p>
                            </div>
                            <div id="sitesList">
                                <!-- Sites will be dynamically loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sample site data for each staff member
        const staffSites = {
            'staff-a': [
                { name: 'Road Street Site', address: '123 Road St, Cityville', status: 'active', workers: 45, target: 50 }
            ],
            'staff-b': [],
            'staff-c': [],
            'staff-d': []
        };

        // All available sites
        const allSites = [
            { name: 'Road Street Site', address: '123 Road St, Cityville', status: 'active', workers: 45, target: 50 },
            { name: 'Building Construction Site', address: '456 Build Ave, Townsburg', status: 'active', workers: 120, target: 150 },
            { name: 'Bridge Project Alpha', address: '789 River Rd, Bridgeton', status: 'active', workers: 30, target: 40 },
            { name: 'Downtown Renovation', address: '101 Main St, Metropolis', status: 'inactive', workers: 15, target: 20 }
        ];

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

        // Select staff member
        function selectStaff(staffId, staffName) {
            // Remove active class from all staff items
            document.querySelectorAll('.staff-item').forEach(item => {
                item.classList.remove('selected');
            });

            // Add active class to selected staff item
            const selectedItem = document.querySelector(`[data-staff="${staffId}"]`);
            if (selectedItem) {
                selectedItem.classList.add('selected');
            }

            // Get assigned sites for this staff member
            const assignedSites = staffSites[staffId] || [];
            const assignedSiteNames = assignedSites.map(site => site.name);

            // Update panel title
            document.getElementById('panelTitle').textContent = `Assign Sites to ${staffName}`;
            document.getElementById('panelSubtitle').textContent = `${assignedSites.length} active assignments`;

            // Hide empty state and show content
            document.getElementById('emptyState').style.display = 'none';
            document.getElementById('sitesContent').style.display = 'block';

            // Render sites
            renderSites(staffId, assignedSiteNames);
        }

        // Render sites in the right panel
        function renderSites(staffId, assignedSiteNames) {
            const sitesList = document.getElementById('sitesList');
            sitesList.innerHTML = '';

            allSites.forEach(site => {
                const isAssigned = assignedSiteNames.includes(site.name);
                const siteItem = document.createElement('div');
                siteItem.className = `site-item ${site.status === 'inactive' ? 'inactive' : ''}`;
                
                siteItem.innerHTML = `
                    <div class="site-item-header">
                        <div>
                            <div class="site-name">${site.name}</div>
                            <div class="site-address">${site.address}</div>
                        </div>
                        <button class="site-action ${isAssigned ? 'remove' : 'add'}" 
                                onclick="${isAssigned ? `removeSite('${staffId}', '${site.name}')` : `addSite('${staffId}', '${site.name}')`}" 
                                title="${isAssigned ? 'Remove' : 'Add'}">
                            <i class="fas ${isAssigned ? 'fa-trash' : 'fa-plus'}"></i>
                        </button>
                    </div>
                    <span class="site-status ${site.status}">${site.status.charAt(0).toUpperCase() + site.status.slice(1)}</span>
                    <div class="site-details">
                        <div class="site-detail-item">
                            <span>${site.workers} Current Workers</span>
                        </div>
                        <div class="site-detail-item">
                            <span>Target: ${site.target}</span>
                        </div>
                    </div>
                `;
                
                sitesList.appendChild(siteItem);
            });
        }

        // Add site to staff member
        function addSite(staffId, siteName) {
            const site = allSites.find(s => s.name === siteName);
            if (site && !staffSites[staffId]) {
                staffSites[staffId] = [];
            }
            if (site && !staffSites[staffId].find(s => s.name === siteName)) {
                staffSites[staffId].push(site);
                updateStaffBadge(staffId);
                const staffName = document.querySelector(`[data-staff="${staffId}"] .staff-name`).textContent;
                selectStaff(staffId, staffName);
            }
        }

        // Remove site from staff member
        function removeSite(staffId, siteName) {
            if (staffSites[staffId]) {
                staffSites[staffId] = staffSites[staffId].filter(s => s.name !== siteName);
                updateStaffBadge(staffId);
                const staffName = document.querySelector(`[data-staff="${staffId}"] .staff-name`).textContent;
                selectStaff(staffId, staffName);
            }
        }

        // Update staff badge with site count
        function updateStaffBadge(staffId) {
            const staffItem = document.querySelector(`[data-staff="${staffId}"]`);
            const assignedSites = staffSites[staffId] || [];
            const badgeContainer = staffItem.querySelector('.staff-item-header');
            
            // Remove existing badge if any
            const existingBadge = badgeContainer.querySelector('.staff-sites-badge');
            if (existingBadge) {
                existingBadge.remove();
            }

            // Add badge if staff has sites
            if (assignedSites.length > 0) {
                const badge = document.createElement('span');
                badge.className = 'staff-sites-badge';
                badge.textContent = `${assignedSites.length} ${assignedSites.length === 1 ? 'Site' : 'Sites'}`;
                badgeContainer.appendChild(badge);
            }
        }

        // Filter staff list
        function filterStaff() {
            const searchInput = document.getElementById('staffSearch');
            const filter = searchInput.value.toLowerCase();
            const staffItems = document.querySelectorAll('.staff-item');

            staffItems.forEach(item => {
                const name = item.querySelector('.staff-name').textContent.toLowerCase();
                const email = item.querySelector('.staff-email').textContent.toLowerCase();
                
                if (name.includes(filter) || email.includes(filter)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // View audit log
        function viewAuditLog() {
            alert('View Audit Log functionality - This would open the audit log page or modal.');
            // Add your audit log logic here
        }

        // Initialize badges on page load
        document.addEventListener('DOMContentLoaded', function() {
            Object.keys(staffSites).forEach(staffId => {
                updateStaffBadge(staffId);
            });
        });
    </script>
</body>
</html>
