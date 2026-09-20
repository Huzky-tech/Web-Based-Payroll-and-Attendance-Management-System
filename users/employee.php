<?php
include '../api/connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/../includes/employee_scope_helpers.php';
require_once __DIR__ . '/../includes/worker_position_helpers.php';
worker_position_ensure_column($conn);

$currentRole = require_auth($conn, ['Assistant Admin', 'Payroll Staff', 'HR', 'Timekeeper']);
$embeddedDashboard = $embeddedDashboard ?? false;
$canManageEmployees = in_array($currentRole, ['Assistant Admin', 'Payroll Staff', 'HR'], true);
$canArchiveEmployees = false;


$employees = [];
$employeeTypes = '';
$employeeParams = [];
$employeeScope = employee_scope_condition($conn, $currentRole, (int) ($_SESSION['user_id'] ?? 0), 'wa.SiteID', $employeeTypes, $employeeParams);
$activeEmployeeCondition = "LOWER(COALESCE(ws.Status, '')) NOT IN ('inactive', 'archived')";
$employeeWhere = $employeeScope !== ''
    ? "WHERE ({$employeeScope}) AND {$activeEmployeeCondition}"
    : "WHERE {$activeEmployeeCondition}";
$sql = "SELECT 
            w.WorkerID,
            w.First_Name,
            w.Last_Name,
            CONCAT(w.First_Name, ' ', w.Last_Name) AS full_name,
            w.RateType,
            w.RateAmount AS salary,
            w.Phone,
            w.DateHired AS join_date,
            w.photo_path,
            w.qr_code_path,
            ws.Status AS worker_status,
            COALESCE(NULLIF(wa.Role_On_Site, ''), NULLIF(w.Position, '')) AS position
        FROM worker w
        LEFT JOIN workerstatus ws ON w.WorkerStatusID = ws.WorkerStatusID
        LEFT JOIN workerassignment wa ON w.WorkerID = wa.WorkerID
        {$employeeWhere}
        ORDER BY w.Last_Name, w.First_Name";
$result = employee_query($conn, $sql, $employeeTypes, $employeeParams);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}

$conn->close();
?>

<?php if (!$embeddedDashboard): ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/employee.css?v=20260906-2">
    <script>window.employeePageRole = <?php echo json_encode($currentRole, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;</script>
    <script src="../js/employee.js?v=20260920-full-name-2" defer></script>
    <script src="../js/action_result_modal.js?v=20260912-1" defer></script>
</head>
<body data-employee-page="users">
<?php endif; ?>

<?php if ($embeddedDashboard): ?>
<script>window.employeePageRole = <?php echo json_encode($currentRole, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;</script>
<?php endif; ?>

<div class="main-content">
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
            </div>
            <button class="btn-add" id="btnAddEmployee" <?php echo $canManageEmployees ? '' : 'hidden'; ?>>
                <i class="fas fa-plus"></i> Add New Employee
            </button>
        </div>

        <div class="tab-content active" id="employeesTab">
            <div class="admin-workflow">
                <div class="admin-workflow-header">
                    <i class="fas fa-briefcase"></i>
                    <h3 class="admin-workflow-title"><?php echo htmlspecialchars($currentRole . ' Workflow'); ?></h3>
                </div>
                <ul class="admin-workflow-list">
                    <li>Access is based on your <?php echo htmlspecialchars($currentRole); ?> permissions</li>
                    <li>System stores data in the database</li>
                    <li>Payroll calculations use this employee data</li>
                    <li>Employee actions are recorded under your account</li>
                </ul>
            </div>

            <div class="search-filters">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search employees..." onkeyup="searchEmployees(this.value)">
                </div>
                <div class="filter-dropdown status-filter-dropdown">
                    <i class="fas fa-filter"></i>
                    <select id="statusFilter" onchange="filterByStatus(this.value)">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                    </select>
                </div>
                <div class="filter-dropdown">
                    <select id="roleFilter" aria-label="Filter by employee position">
                        <option value="">All Positions</option>
                    </select>
                </div>
                <div class="filter-dropdown">
                    <select id="siteAssignmentFilter" aria-label="Filter by site assignment">
                        <option value="">All Site Assignments</option>
                        <option value="assigned">Assigned to Site</option>
                        <option value="unassigned">Not Assigned to Site</option>
                    </select>
                </div>
            </div>

            <div class="table-container">
                <table class="employee-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Position</th>
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
                                <tr data-worker-id="<?php echo (int) $employee['WorkerID']; ?>">
                                    <td>
                                        <div class="employee-info">
                                            <div class="employee-avatar">
                                                <?php if (!empty($employee['photo_path'])): ?>
                                                    <img src="../<?php echo htmlspecialchars($employee['photo_path']); ?>" alt="<?php echo htmlspecialchars($employee['full_name']); ?>">
                                                <?php else: ?>
                                                    <?php echo strtoupper(substr($employee['First_Name'] ?? '', 0, 1)); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="employee-details">
                                                <div class="employee-name"><?php echo htmlspecialchars($employee['full_name']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($employee['position'] ?? 'Not Assigned'); ?></td>
                                    <td><span class="status-badge <?php echo strtolower($employee['worker_status'] ?? 'active'); ?>"><?php echo htmlspecialchars($employee['worker_status'] ?? 'Active'); ?></span></td>
                                    <td class="salary">â‚±<?php echo number_format((float) ($employee['salary'] ?? 0), 2); ?></td>
                                    <td><?php echo htmlspecialchars($employee['join_date'] ?? 'N/A'); ?></td>
                                    <td>
                                        <div class="approval-info">
                                            <?php $legacyApproved = strtolower((string) ($employee['worker_status'] ?? '')) === 'active'; ?>
                                            <span class="approval-badge <?php echo $legacyApproved ? 'approved' : 'pending'; ?>"><?php echo $legacyApproved ? 'Approved' : 'Pending'; ?></span>
                                            <span class="approval-by"><?php echo $legacyApproved ? 'Approved legacy employee' : 'Not approved'; ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action view" title="View">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($canManageEmployees): ?>
                                            <button class="btn-action edit" title="Edit Employee" aria-label="Edit <?php echo htmlspecialchars($employee['full_name']); ?>">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <?php endif; ?>
                                            <button class="btn-action id-card" title="Worker ID Card">
                                                <i class="fas fa-id-card"></i>
                                            </button>
                                            <?php if ($canArchiveEmployees): ?>
                                            <button class="btn-action delete" title="Archive">
                                                <i class="fas fa-box-archive"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 20px;">No employees found. Click "Add New Employee" to create one.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($canManageEmployees): ?>
<?php include __DIR__ . '/../includes/add_employee_modal.php'; ?>
<?php endif; ?>

<!-- View Employee Modal -->
<div id="viewEmployeeModal" class="modal">
    <div class="modal-content view-employee-modal">
        <div class="modal-header">
            <h2>Employee Details</h2>
            <button type="button" class="close-modal" id="closeViewEmployeeModal" aria-label="Close" onclick="closeViewEmployeeModal()">&times;</button>
        </div>
        <div class="modal-body" id="viewEmployeeBody"></div>
    </div>
</div>
<?php include __DIR__ . '/../includes/worker_id_card_modal.php'; ?>

<?php if (!$embeddedDashboard): ?>
</body>
</html>
<?php endif; ?>
