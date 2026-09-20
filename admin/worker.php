<?php
include '../api/connection/db_config.php';
include '../includes/auth.php';
require_once '../includes/worker_position_helpers.php';
worker_position_ensure_column($conn);

$currentRole = require_auth($conn, ['Admin','Assistant Admin','Payroll Staff','HR']);
$embeddedDashboard = $embeddedDashboard ?? false;
$isDirectAdminWorkerPage = !$embeddedDashboard && $currentRole === 'Admin';
if ($isDirectAdminWorkerPage) {
    header('Location: dashboard.php?page=worker');
    exit;
}
$canManageEmployees = in_array($currentRole, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR'], true);

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
            w.photo_path,
            w.qr_code_path,
            ws.Status AS worker_status,
            COALESCE(NULLIF(wa.Role_On_Site, ''), NULLIF(w.Position, '')) AS position,
            COALESCE(latest_approval.Approval_Status, CASE WHEN LOWER(ws.Status) = 'active' THEN 'Approved' ELSE 'Pending' END) AS approval_status,
            latest_approval.Approval_By AS approval_by,
            latest_approval.Date AS approval_date,
            approver.full_name AS approved_by_name
        FROM worker w
        LEFT JOIN workerstatus ws ON w.WorkerStatusID = ws.WorkerStatusID
        LEFT JOIN workerassignment wa ON w.WorkerID = wa.WorkerID
        LEFT JOIN (
            SELECT a.*
            FROM approvals a
            INNER JOIN (
                SELECT WorkerID, MAX(ApprovalID) AS ApprovalID
                FROM approvals
                GROUP BY WorkerID
            ) latest ON latest.ApprovalID = a.ApprovalID
        ) latest_approval ON latest_approval.WorkerID = w.WorkerID
        LEFT JOIN users approver ON approver.id = latest_approval.Approval_By
        WHERE LOWER(COALESCE(ws.Status, '')) NOT IN ('inactive', 'archived')
        ORDER BY w.WorkerID DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}
?>
<?php if ($embeddedDashboard): ?>
<script>window.employeePageRole = <?php echo json_encode($_SESSION['role'] ?? 'Admin', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;</script>
<?php endif; ?>
<?php if (!$embeddedDashboard): ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/worker.css?v=20260915-profile-validation-1">
    <script>window.employeePageRole = <?php echo json_encode($_SESSION['role'] ?? 'Admin', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;</script>
    <script src="../js/action_result_modal.js?v=20260920-access-1" defer></script>
    <script src="../js/worker.js?v=20260915-profile-validation-1" defer></script>
</head>
<body data-employee-page="admin">
<?php endif; ?>


    <!-- Main Content -->
    <?php if (!$embeddedDashboard): ?>
    <div class="main-content">
    <?php endif; ?>
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
                </div>
                <button class="btn-add" id="btnAddEmployee">
    <i class="fas fa-plus"></i> Add New Worker
</button>
            </div>

            <!-- All Employees Tab Content -->
            <div class="tab-content active" id="employeesTab">
                <!-- Role-specific workflow box -->
                <div class="admin-workflow">
                    <div class="admin-workflow-header">
                        <i class="fas fa-briefcase"></i>
                        <h3 class="admin-workflow-title"><?php echo htmlspecialchars(($currentRole ?? ($_SESSION['role'] ?? 'User')) . ' Workflow'); ?></h3>
                    </div>
                    <ul class="admin-workflow-list">
                        <li>Access is based on your <?php echo htmlspecialchars($currentRole ?? ($_SESSION['role'] ?? 'user')); ?> permissions</li>
                        <li>System stores data in the database</li>
                        <li>Payroll calculations use this employee data</li>
                        <li>Employee actions are recorded under your account</li>
                    </ul>
                </div>

                <!-- Search and Filters -->
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

                <!-- Employee Table -->
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
                            <?php
                                $approvalStatus = $employee['approval_status'] ?? 'Pending';
                                $approvalDetail = $approvalStatus === 'Approved'
                                    ? 'Approved' . (!empty($employee['approved_by_name']) ? ' by ' . $employee['approved_by_name'] : '')
                                    : 'Not approved yet';
                            ?>
                            <tr data-worker-id="<?php echo (int) $employee['WorkerID']; ?>">
                                <td>
                                    <div class="employee-info">
                                        <div class="employee-avatar">
                                            <?php if (!empty($employee['photo_path'])): ?>
                                            <img src="../<?php echo htmlspecialchars($employee['photo_path']); ?>" alt="<?php echo htmlspecialchars($employee['full_name']); ?>">
                                            <?php else: ?>
                                            <?php echo strtoupper(substr($employee['First_Name'], 0, 1)); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="employee-details">
                                            <div class="employee-name"><?php echo htmlspecialchars($employee['full_name']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($employee['position'] ?? 'Not Assigned'); ?></td>
                                <td><span class="status-badge <?php echo htmlspecialchars(strtolower($employee['worker_status'] ?? 'active'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($employee['worker_status'] ?? 'Active', ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td class="salary">P<?php echo number_format($employee['salary'] ?? 0, 2); ?></td>
                                <td><?php echo htmlspecialchars($employee['join_date'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <div class="approval-info">
                                        <span class="approval-badge"><?php echo htmlspecialchars($approvalStatus); ?></span>
                                        <span class="approval-by"><?php echo htmlspecialchars($approvalDetail); ?></span>
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
                                        <?php if ($approvalStatus !== 'Approved'): ?>
                                        <button class="btn-action approve" title="Approve Employee">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <?php endif; ?>
                                        <button class="btn-action delete" title="Archive">
                                            <i class="fas fa-box-archive"></i>
                                        </button>
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
                <div class="employee-pagination" id="employeePagination" aria-label="Employee table pagination"></div>
            </div>
        </div>
    <?php if (!$embeddedDashboard): ?>
    </div>
    <?php endif; ?>

  <?php include __DIR__ . '/../includes/add_employee_modal.php'; ?>

<!-- View Employee Modal -->
<div id="viewEmployeeModal" class="modal">
    <div class="modal-content view-employee-modal">
        <div class="modal-header">
            <h2>Employee Details</h2>
            <button type="button" class="close-modal" id="closeViewEmployeeModal" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body" id="viewEmployeeBody"></div>
    </div>
</div>

<?php include __DIR__ . '/../includes/worker_id_card_modal.php'; ?>
<?php if (!$embeddedDashboard): ?>
</body>
</html>
<?php endif; ?>

<?php
$conn->close();
?>
