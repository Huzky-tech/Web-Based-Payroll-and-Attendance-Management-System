<?php
include '../api/connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/../includes/login_security.php';
$currentRole = require_auth($conn, ['Admin']);
$embeddedDashboard = $embeddedDashboard ?? false;
$canAddDeleteUsers = true;
$canEditUsers = true;
login_security_ensure_columns($conn);
?>
<?php if (!$embeddedDashboard): ?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Employee - Philippians CDO</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../css/employee.css?v=20260910-2">
<script src="../js/action_result_modal.js?v=20260912-1" defer></script>
<script src="../js/employee.js?v=20260920-availability-3" defer></script>
</head><body>
<?php endif; ?>
<div class="main-content"><div class="content-area">
<div class="section-title">Employee</div>
<script>
window.currentUserRole = <?php echo json_encode($currentRole, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.canAddDeleteUsers = true;
window.canEditUsers = true;
window.isUserManagementPage = true;
</script>
            <div id="users" class="panel" data-standalone="true">
                <div class="users-header">
                    <div class="users-header-left">
                        <div class="section-title" style="font-size:18px; margin-bottom:0;">User Management</div>
                        <div class="section-sub" style="margin-bottom:0;">Manage system users and their access permissions</div>
                    </div>
                    <div class="users-header-right">
                        <?php if ($canAddDeleteUsers): ?>
                        <button class="btn-action" onclick="openAddUserModal()"><i class="fas fa-plus"></i>Add User</button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="user-filter-row">
                    <div class="user-search-wrapper">
                        <i class="fas fa-search user-search-icon"></i>
                        <input type="search" id="userSearchInput" name="user_directory_search" class="user-search-input" placeholder="Search by name or email..." autocomplete="off" data-no-live-validation oninput="onUserSearch()">
                    </div>
                    <select id="userRoleFilter" class="user-filter-select" onchange="onUserFilterChange()">
                        <option value="">All Roles</option>
                        <option value="Admin">Admin</option>
                        <option value="Payroll Staff">Payroll Staff</option>
                        <option value="HR">HR</option>
                        <option value="Timekeeper">Timekeeper</option>
                        <option value="Assistant Admin">Assistant Admin</option>
                        <option value="Worker">Worker</option>
                    </select>
                    <select id="userStatusFilter" class="user-filter-select" onchange="onUserFilterChange()">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Account Required">Account Required</option>
                    </select>
                    <select id="userLimitSelect" class="user-filter-select user-limit-select" onchange="onUserLimitChange()">
                        <option value="10">10</option>
                        <option value="20" selected>20</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                        </tbody>
                    </table>
                </div>
                <div class="user-pagination-bar" id="userPaginationBar">
                    <div class="user-pagination-info" id="userPaginationInfo">Showing 0-0 of 0</div>
                    <div class="user-pagination-controls">
                        <button class="user-btn-page" id="userPrevPage" onclick="userChangePage(-1)" disabled><i class="fas fa-chevron-left"></i> Prev</button>
                        <span class="user-page-numbers" id="userPageNumbers"></span>
                        <button class="user-btn-page" id="userNextPage" onclick="userChangePage(1)" disabled>Next <i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>
</div></div>
<?php include __DIR__ . '/../includes/employee_user_modals.php'; ?>
<?php if (!$embeddedDashboard): ?></body></html><?php endif; ?>
