<?php
include '../api/connection/db_config.php';

// Get all employees from database
$employees = [];
$sql = "SELECT 
            w.WorkerID,
            w.First_Name,
            w.Last_Name,
            CONCAT(w.First_Name, ' ', w.Last_Name) AS full_name,
            w.RateType,
            w.RateAmount AS salary,
            w.Phone,
            w.DateHired AS join_date,
            ws.Status AS worker_status,
            wa.SiteID,
            ps.Site_Name,
            wa.Role_On_Site AS position
        FROM worker w
        LEFT JOIN workerstatus ws ON w.WorkerStatusID = ws.WorkerStatusID
        LEFT JOIN workerassignment wa ON w.WorkerID = wa.WorkerID
        LEFT JOIN projectsite ps ON wa.SiteID = ps.SiteID
        WHERE w.RateType = 'Worker'     -- Add this line to filter only workers
        ORDER BY w.Last_Name, w.First_Name";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}

// Get all sites for filter dropdown
$sites = [];
$siteSql = "SELECT SiteID, Site_Name FROM projectsite WHERE Status = 'active' OR Status = 'Active' ORDER BY Site_Name";
$siteResult = $conn->query($siteSql);
if ($siteResult->num_rows > 0) {
    while ($row = $siteResult->fetch_assoc()) {
        $sites[] = $row;
    }
}

// Get dashboard statistics
$staffCount = 0;
$activeSitesCount = 0;
$assignmentsCount = 0;

$staffSql = "SELECT COUNT(*) as cnt FROM worker WHERE RateType = 'Worker'";
$staffResult = $conn->query($staffSql);
if ($staffResult) $staffCount = $staffResult->fetch_assoc()['cnt'] ?? 0;

$activeSql = "SELECT COUNT(*) as cnt FROM projectsite WHERE LOWER(Status) = 'active'";
$activeResult = $conn->query($activeSql);
if ($activeResult) $activeSitesCount = $activeResult->fetch_assoc()['cnt'] ?? 0;

$assignSql = "SELECT COUNT(*) as cnt FROM workerassignment";
$assignResult = $conn->query($assignSql);
if ($assignResult) $assignmentsCount = $assignResult->fetch_assoc()['cnt'] ?? 0;

// Get only workers for assignment panel (exclude payroll/admin users)
$workers = [];
$psSql = "SELECT 
            WorkerID,
            CONCAT(First_Name, ' ', Last_Name) AS full_name,
            Phone AS email
          FROM worker
          WHERE RateType = 'Worker'  -- only workers
          ORDER BY First_Name";
$psResult = $conn->query($psSql);
if ($psResult->num_rows > 0) {
    while ($row = $psResult->fetch_assoc()) {
        $workers[] = $row;
    }
}

// Get all sites for assignment panel
$allSites = [];
$allSitesSql = "SELECT 
                    ps.SiteID,
                    ps.Site_Name,
                    ps.Location,
                    ps.Status,
                    ps.Required_Workers,
                    (SELECT COUNT(*) FROM WorkerAssignment wa WHERE wa.SiteID = ps.SiteID) AS current_workers
                FROM projectsite ps
                ORDER BY ps.Site_Name";
$allSitesResult = $conn->query($allSitesSql);
if ($allSitesResult->num_rows > 0) {
    while ($row = $allSitesResult->fetch_assoc()) {
        $allSites[] = $row;
    }
}
?>
<!-- Modal -->
<div id="viewModal" class="modal"></div>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/employee.css">
    <script src="../js/employee.js" defer></script>
</head>
<body>


    <!-- Main Content -->
    <div class="main-content">
        <!-- Employee Content -->
        <div class="employee-content">
            <div class="section-header">
                <h2 class="section-title">Employee Management</h2>
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
                    <i class="fas fa-plus"></i>
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
                        <input type="text" id="searchInput" placeholder="Search employees..." onkeyup="searchEmployees(this.value)">
                    </div>
                    <div class="filter-dropdown">
                        <i class="fas fa-building"></i>
                        <select id="siteFilter" onchange="filterBySite(this.value)">
                            <option value="">All Sites</option>
                            <?php foreach ($sites as $site): ?>
                            <option value="<?php echo $site['SiteID']; ?>"><?php echo htmlspecialchars($site['Site_Name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-dropdown">
                        <i class="fas fa-filter"></i>
                        <select id="statusFilter" onchange="filterByStatus(this.value)">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="OnLeave">On Leave</option>
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
                    <tbody id="employeeTableBody">
                        <?php if (count($employees) > 0): ?>
                            <?php foreach ($employees as $employee): ?>
                            <tr>
                                <td>
                                    <div class="employee-info">
                                        <div class="employee-avatar"><?php echo strtoupper(substr($employee['First_Name'], 0, 1)); ?></div>
                                        <div class="employee-details">
                                            <div class="employee-name"><?php echo htmlspecialchars($employee['full_name']); ?></div>
                                            <div class="employee-id">ID: <?php echo $employee['WorkerID']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($employee['position'] ?? 'Not Assigned'); ?></td>
                                <td><?php echo htmlspecialchars($employee['Site_Name'] ?? 'Not Assigned'); ?></td>
                                <td><span class="status-badge <?php echo strtolower($employee['worker_status'] ?? 'active'); ?>"><?php echo $employee['worker_status'] ?? 'Active'; ?></span></td>
                                <td class="salary">₱<?php echo number_format($employee['salary'] ?? 0, 2); ?></td>
                                <td><?php echo $employee['join_date'] ?? 'N/A'; ?></td>
                                <td>
                                    <div class="approval-info">
                                        <span class="approval-badge">Pending</span>
                                        <span class="approval-by">Not approved</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action view" title="View" onclick="viewEmployee(<?php echo $employee['WorkerID']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-action delete" title="Delete" onclick="deleteEmployee(<?php echo $employee['WorkerID']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 20px;">No employees found. Click "Add New Employee" to create one.</td>
                        </tr>
                        <?php endif; ?>
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
                            <div class="overview-card-value" id="staffCount"><?php echo $staffCount; ?></div>
                            <div class="overview-card-label">Total Workers</div>
                        </div>
                    </div>
                    <div class="overview-card">
                        <div class="overview-card-icon green">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="overview-card-content">
                            <div class="overview-card-value" id="activeSitesCount"><?php echo $activeSitesCount; ?></div>
                            <div class="overview-card-label">Active Sites</div>
                        </div>
                    </div>
                    <div class="overview-card">
                        <div class="overview-card-icon purple">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="overview-card-content">
                            <div class="overview-card-value" id="assignmentsCount"><?php echo $assignmentsCount; ?></div>
                            <div class="overview-card-label">Total Assignments</div>
                        </div>
                    </div>
                </div>

                <!-- Staff Assignment Management -->
                <div class="assignment-section">
                    <div class="assignment-header">
                        <div>
                            <h3 class="assignment-title">Staff Assignment Management</h3>
<p class="assignment-subtitle">Assign workers to specific construction sites</p>
                        </div>
                        <button class="btn-audit">View Audit Log</button>
                    </div>

                    <div class="assignment-panels">
                        <!-- Left Panel - Staff List -->
                        <div class="staff-list-panel">
                            <div class="staff-search">
                                <i class="fas fa-search"></i>
                                <input type="text" placeholder="Search workers...">
                            </div>
                       <div class="staff-list" id="staffList">
    <?php if (count($workers) > 0): ?>
        <?php foreach ($workers as $index => $worker): ?>
        <div class="staff-item <?php echo $index === 0 ? 'selected' : ''; ?>"
             data-staff="<?php echo $worker['WorkerID']; ?>"
             onclick="selectStaff(this, <?php echo $worker['WorkerID']; ?>)">
            <div class="staff-item-header">
                <div class="staff-name"><?php echo htmlspecialchars($worker['full_name']); ?></div>
            </div>
            <div class="staff-email"><?php echo htmlspecialchars($worker['email']); ?></div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="staff-item">
            <div class="staff-item-header">
                <div class="staff-name">No Workers</div>
            </div>
            <div class="staff-email">Add workers to assign sites</div>
        </div>
    <?php endif; ?>
</div>
                        </div>

                        <!-- Right Panel - Site Assignments -->
                        <div class="sites-panel">
                            <div class="sites-panel-header">
                                <h4 class="sites-panel-title">Assign Sites to Staff</h4>
                                <p class="sites-panel-subtitle"><?php echo count($allSites); ?> sites available</p>
                            </div>

                            <?php if (count($allSites) > 0): ?>
                                <?php foreach ($allSites as $site): ?>
                                <div class="site-item <?php echo strtolower($site['Status']) === 'inactive' ? 'inactive' : ''; ?>">
                                    <div class="site-item-header">
                                        <div>
                                            <div class="site-name"><?php echo htmlspecialchars($site['Site_Name']); ?></div>
                                            <div class="site-address"><?php echo htmlspecialchars($site['Location'] ?? 'No location'); ?></div>
                                        </div>
                                        <button class="site-action <?php echo strtolower($site['Status']) === 'inactive' ? 'add' : 'add'; ?>" title="Add" onclick="assignSiteToStaff(<?php echo $site['SiteID']; ?>)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <span class="site-status <?php echo strtolower($site['Status']); ?>"><?php echo $site['Status']; ?></span>
                                    <div class="site-details">
                                        <div class="site-detail-item">
                                            <span><?php echo $site['current_workers']; ?> Current Workers</span>
                                        </div>
                                        <div class="site-detail-item">
                                            <span>Target: <?php echo $site['Required_Workers'] ?? 0; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <div class="site-item">
                                <div class="site-item-header">
                                    <div>
                                        <div class="site-name">No Sites Available</div>
                                        <div class="site-address">Create a site to assign staff</div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Employee Modal -->
    <div id="addEmployeeModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Employee</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addEmployeeForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Position</label>
                            <input type="text" name="position">
                        </div>
                        <div class="form-group">
                            <label>Salary</label>
                            <input type="number" name="salary" step="0.01">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone">
                    </div>
                    <div class="form-group">
                        <label>Join Date</label>
                        <input type="date" name="join_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <button type="submit" class="btn-submit">Add Employee</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>