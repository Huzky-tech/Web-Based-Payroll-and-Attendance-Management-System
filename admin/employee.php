<!-- Modal -->
<div id="viewModal" class="modal"></div>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/employee.css">
</head>
<body>


    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <div class="top-header">
            <h1 class="page-title">Employees</h1>
            <div class="header-right">
                <div class="date-time">
                    <div class="date" id="currentDate">Sunday, January 4, 2026</div>
                    <div class="time" id="currentTime">07:43 AM</div>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">A</div>
                    <div class="user-info">
                        <div class="user-name">Admin User</div>
                        <div class="user-role">Admin</div>
                    </div>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </div>

        <!-- Employee Content -->
        <div class="employee-content">
            <div class="section-header">
                <h2 class="section-title">Employee Management</h2>
                <p class="section-subtitle">Oversee employee data management and system operations</p>
            </div>

            <div class="section-actions">
                <div class="tabs">
                    <button class="tab active" data-tab="employees">
                        <i class="fas fa-user"></i>
                        <span>All Employees</span>
                    </button>
                    <button class="tab" data-tab="assignments">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Site Assignments</span>
                    </button>
                </div>
                <button class="btn-add" id="btnAddEmployee">
                    <i class="fas fa-check"></i>
                    <span>Add New Employee</span>
                </button>
            </div>

            <!-- All Employees Tab Content -->
            <div class="tab-content active" id="employeesTab">
                <!-- Admin Workflow Box -->
                <div class="admin-workflow">
                    <div class="admin-workflow-header">
                        <i class="fas fa-briefcase"></i>
                        <h3 class="admin-workflow-title">Admin Workflow</h3>
                    </div>
                    <ul class="admin-workflow-list">
                        <li>Full access to employee management</li>
                        <li>System stores data in the database</li>
                        <li>Payroll calculations use this employee data</li>
                        <li>Administrators can oversee the entire process</li>
                    </ul>
                </div>

                <!-- Search and Filters -->
                <div class="search-filters">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search employees...">
                    </div>
                    <div class="filter-dropdown">
                        <i class="fas fa-building"></i>
                        <select>
                            <option>All Sites</option>
                        </select>
                    </div>
                    <div class="filter-dropdown">
                        <i class="fas fa-filter"></i>
                        <select>
                            <option>All Status</option>
                        </select>
                    </div>
                </div>

                <!-- Employee Table -->
                <div class="table-container">
                <table class="employee-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Position</th>
                            <th>Site</th>
                            <th>Status</th>
                            <th>Salary</th>
                            <th>Join Date</th>
                            <th>Approval</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="employee-info">
                                    <div class="employee-avatar">J</div>
                                    <div class="employee-details">
                                        <div class="employee-name">John Doe</div>
                                        <div class="employee-id">ID: 1</div>
                                    </div>
                                </div>
                            </td>
                            <td>Construction Worker</td>
                            <td>Main Street Project</td>
                            <td><span class="status-badge active">Active</span></td>
                            <td class="salary">₱15,000</td>
                            <td>2022-05-15</td>
                            <td>
                                <div class="approval-info">
                                    <span class="approval-badge">Approved</span>
                                    <span class="approval-by">By: HR Manager</span>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-action view" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-action delete" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="employee-info">
                                    <div class="employee-avatar">J</div>
                                    <div class="employee-details">
                                        <div class="employee-name">Jane Smith</div>
                                        <div class="employee-id">ID: 2</div>
                                    </div>
                                </div>
                            </td>
                            <td>Electrician</td>
                            <td>Downtown Office Complex</td>
                            <td><span class="status-badge active">Active</span></td>
                            <td class="salary">₱18,000</td>
                            <td>2021-11-03</td>
                            <td>
                                <div class="approval-info">
                                    <span class="approval-badge">Approved</span>
                                    <span class="approval-by">By: HR Manager</span>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-action view" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-action delete" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="employee-info">
                                    <div class="employee-avatar">M</div>
                                    <div class="employee-details">
                                        <div class="employee-name">Michael Johnson</div>
                                        <div class="employee-id">ID: 3</div>
                                    </div>
                                </div>
                            </td>
                            <td>Plumber</td>
                            <td>Riverside Apartments</td>
                            <td><span class="status-badge inactive">Inactive</span></td>
                            <td class="salary">₱16,500</td>
                            <td>2022-02-28</td>
                            <td>
                                <div class="approval-info">
                                    <span class="approval-badge">Approved</span>
                                    <span class="approval-by">By: Admin User</span>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-action view" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-action delete" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="employee-info">
                                    <div class="employee-avatar">S</div>
                                    <div class="employee-details">
                                        <div class="employee-name">Sarah Williams</div>
                                        <div class="employee-id">ID: 4</div>
                                    </div>
                                </div>
                            </td>
                            <td>Carpenter</td>
                            <td>Park Avenue Mall</td>
                            <td><span class="status-badge active">Active</span></td>
                            <td class="salary">₱15,500</td>
                            <td>2023-01-10</td>
                            <td>
                                <div class="approval-info">
                                    <span class="approval-badge">Approved</span>
                                    <span class="approval-by">By: HR Manager</span>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-action view" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-action delete" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="employee-info">
                                    <div class="employee-avatar">R</div>
                                    <div class="employee-details">
                                        <div class="employee-name">Robert Brown</div>
                                        <div class="employee-id">ID: 5</div>
                                    </div>
                                </div>
                            </td>
                            <td>Construction Worker</td>
                            <td>Main Street Project</td>
                            <td><span class="status-badge on-leave">On-leave</span></td>
                            <td class="salary">₱14,000</td>
                            <td>2022-07-22</td>
                            <td>
                                <div class="approval-info">
                                    <span class="approval-badge">Approved</span>
                                    <span class="approval-by">By: HR Manager</span>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-action view" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-action delete" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="employee-info">
                                    <div class="employee-avatar">E</div>
                                    <div class="employee-details">
                                        <div class="employee-name">Emily Davis</div>
                                        <div class="employee-id">ID: 6</div>
                                    </div>
                                </div>
                            </td>
                            <td>Site Manager</td>
                            <td>Downtown Office Complex</td>
                            <td><span class="status-badge active">Active</span></td>
                            <td class="salary">₱25,000</td>
                            <td>2021-09-15</td>
                            <td>
                                <div class="approval-info">
                                    <span class="approval-badge">Approved</span>
                                    <span class="approval-by">By: HR Manager</span>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-action view" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-action delete" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- Site Assignments Tab Content -->
            <div class="tab-content" id="assignmentsTab">
                <!-- Overview Cards -->
                <div class="overview-cards">
                    <div class="overview-card">
                        <div class="overview-card-icon blue">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="overview-card-content">
                            <div class="overview-card-value">4</div>
                            <div class="overview-card-label">Total Payroll Staff</div>
                        </div>
                    </div>
                    <div class="overview-card">
                        <div class="overview-card-icon green">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="overview-card-content">
                            <div class="overview-card-value">3</div>
                            <div class="overview-card-label">Active Sites</div>
                        </div>
                    </div>
                    <div class="overview-card">
                        <div class="overview-card-icon purple">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="overview-card-content">
                            <div class="overview-card-value">1</div>
                            <div class="overview-card-label">Total Assignments</div>
                        </div>
                    </div>
                </div>

                <!-- Staff Assignment Management -->
                <div class="assignment-section">
                    <div class="assignment-header">
                        <div>
                            <h3 class="assignment-title">Staff Assignment Management</h3>
                            <p class="assignment-subtitle">Assign payroll staff to specific construction sites</p>
                        </div>
                        <button class="btn-audit">View Audit Log</button>
                    </div>

                    <div class="assignment-panels">
                        <!-- Left Panel - Staff List -->
                        <div class="staff-list-panel">
                            <div class="staff-search">
                                <i class="fas fa-search"></i>
                                <input type="text" placeholder="Search payroll staff...">
                            </div>
                            <div class="staff-list">
                                <div class="staff-item selected" data-staff="staff-a">
                                    <div class="staff-item-header">
                                        <div class="staff-name">Payroll Staff A</div>
                                        <span class="staff-sites-badge">1 Sites</span>
                                    </div>
                                    <div class="staff-email">staff.a@company.com</div>
                                </div>
                                <div class="staff-item" data-staff="staff-b">
                                    <div class="staff-item-header">
                                        <div class="staff-name">Payroll Staff B</div>
                                    </div>
                                    <div class="staff-email">staff.b@company.com</div>
                                </div>
                                <div class="staff-item" data-staff="staff-c">
                                    <div class="staff-item-header">
                                        <div class="staff-name">Payroll Staff C</div>
                                    </div>
                                    <div class="staff-email">staff.c@company.com</div>
                                </div>
                                <div class="staff-item" data-staff="staff-d">
                                    <div class="staff-item-header">
                                        <div class="staff-name">Payroll Staff D</div>
                                    </div>
                                    <div class="staff-email">staff.d@company.com</div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Panel - Site Assignments -->
                        <div class="sites-panel">
                            <div class="sites-panel-header">
                                <h4 class="sites-panel-title">Assign Sites to Payroll Staff A</h4>
                                <p class="sites-panel-subtitle">1 active assignments</p>
                            </div>

                            <div class="site-item">
                                <div class="site-item-header">
                                    <div>
                                        <div class="site-name">Road Street Site</div>
                                        <div class="site-address">123 Road St, Cityville</div>
                                    </div>
                                    <button class="site-action remove" title="Remove">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <span class="site-status active">Active</span>
                                <div class="site-details">
                                    <div class="site-detail-item">
                                        <span>45 Current Workers</span>
                                    </div>
                                    <div class="site-detail-item">
                                        <span>Target: 50</span>
                                    </div>
                                </div>
                            </div>

                            <div class="site-item">
                                <div class="site-item-header">
                                    <div>
                                        <div class="site-name">Building Construction Site</div>
                                        <div class="site-address">456 Build Ave, Townsburg</div>
                                    </div>
                                    <button class="site-action add" title="Add">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <span class="site-status active">Active</span>
                                <div class="site-details">
                                    <div class="site-detail-item">
                                        <span>120 Current Workers</span>
                                    </div>
                                    <div class="site-detail-item">
                                        <span>Target: 150</span>
                                    </div>
                                </div>
                            </div>

                            <div class="site-item">
                                <div class="site-item-header">
                                    <div>
                                        <div class="site-name">Bridge Project Alpha</div>
                                        <div class="site-address">789 River Rd, Bridgeton</div>
                                    </div>
                                    <button class="site-action add" title="Add">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <span class="site-status active">Active</span>
                                <div class="site-details">
                                    <div class="site-detail-item">
                                        <span>30 Current Workers</span>
                                    </div>
                                    <div class="site-detail-item">
                                        <span>Target: 40</span>
                                    </div>
                                </div>
                            </div>

                            <div class="site-item inactive">
                                <div class="site-item-header">
                                    <div>
                                        <div class="site-name">Downtown Renovation</div>
                                        <div class="site-address">101 Main St, Metropolis</div>
                                    </div>
                                    <button class="site-action add" title="Add">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <span class="site-status inactive">Inactive</span>
                                <div class="site-details">
                                    <div class="site-detail-item">
                                        <span>15 Current Workers</span>
                                    </div>
                                    <div class="site-detail-item">
                                        <span>Target: 20</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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

        // Tab switching
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const tabName = this.getAttribute('data-tab');
                
                // Update tab active state
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Show/hide tab content
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                if (tabName === 'employees') {
                    document.getElementById('employeesTab').classList.add('active');
                    document.getElementById('btnAddEmployee').style.display = 'flex';
                } else if (tabName === 'assignments') {
                    document.getElementById('assignmentsTab').classList.add('active');
                    document.getElementById('btnAddEmployee').style.display = 'none';
                }
            });
        });

        // Add New Employee button
        document.querySelector('.btn-add').addEventListener('click', function() {
            console.log('Add New Employee clicked');
            // Add your modal or navigation logic here
        });

        // Staff selection
        document.querySelectorAll('.staff-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.staff-item').forEach(i => i.classList.remove('selected'));
                this.classList.add('selected');
                
                const staffName = this.querySelector('.staff-name').textContent;
                const staffEmail = this.querySelector('.staff-email').textContent;
                
                // Update right panel header
                document.querySelector('.sites-panel-title').textContent = `Assign Sites to ${staffName}`;
                
                // You can update the sites list here based on selected staff
                console.log(`Selected: ${staffName} (${staffEmail})`);
            });
        });

        // Site action buttons
        document.querySelectorAll('.site-action').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const action = this.classList.contains('remove') ? 'Remove' : 'Add';
                const siteName = this.closest('.site-item').querySelector('.site-name').textContent;
                console.log(`${action} clicked for ${siteName}`);
                // Add your action logic here
            });
        });

        // Action buttons
        document.querySelectorAll('.btn-action').forEach(btn => {
            btn.addEventListener('click', function() {
                const action = this.classList.contains('view') ? 'View' : 'Delete';
                const row = this.closest('tr');
                const employeeName = row.querySelector('.employee-name').textContent;
                console.log(`${action} clicked for ${employeeName}`);
                // Add your action logic here
            });
        });
    </script>
</body>
</html>
