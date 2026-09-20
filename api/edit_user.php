<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/user_identity.php';
include 'connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';

// Session is already started in db_config.php via api_block_for_maintenance_if_needed()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentRole = require_auth($conn, ['Admin', 'Assistant Admin']);

// Only Admin and Assistant Admin can edit users
if (!in_array($currentRole, ['Admin', 'Assistant Admin'], true)) {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

// Helper function to get user's role from role tables
function getUserRole($conn, $user_id) {
    // Check admin table
    $stmt = $conn->prepare("SELECT 1 FROM admin WHERE UserID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) return 'Admin';

    $stmt = $conn->prepare("SELECT 1 FROM hr WHERE UserID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) return 'HR';
    
    // Check payrollstaff table
    $stmt = $conn->prepare("SELECT 1 FROM payrollstaff WHERE UserID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) return 'Payroll Staff';
    
    // Check timekeeper table
    $stmt = $conn->prepare("SELECT 1 FROM timekeeper WHERE UserID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) return 'Timekeeper';
    
    // Check assistantmanager table
    $stmt = $conn->prepare("SELECT 1 FROM assistantmanager WHERE UserID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) return 'Assistant Admin';

    $stmt = $conn->prepare("SELECT 1 FROM worker WHERE UserID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) return 'Worker';
    
    return 'User';
}

function normalizeIncomingRole($role) {
    if ($role === 'Assistant Admin') {
        return 'Assistant Admin';
    }

    return $role;
}

function isSupportedRole($role) {
    return in_array($role, ['Admin', 'Assistant Admin', 'HR', 'Payroll Staff', 'Timekeeper', 'Worker', 'User'], true);
}

function executeRoleStatement(mysqli $conn, string $sql, int $userId): void {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Unable to prepare role update: ' . $conn->error);
    }

    $stmt->bind_param('i', $userId);
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Unable to update the user role: ' . $error);
    }
    $stmt->close();
}

// A user must only be present in one role table. Payroll Staff assignments are
// validated before this function is called and must be removed by an admin first.
function updateUserRole(mysqli $conn, int $userId, string $newRole): void {
    executeRoleStatement($conn, 'DELETE FROM admin WHERE UserID = ?', $userId);
    executeRoleStatement($conn, 'DELETE FROM assistantmanager WHERE UserID = ?', $userId);
    executeRoleStatement($conn, 'DELETE FROM hr WHERE UserID = ?', $userId);
    executeRoleStatement($conn, 'DELETE FROM payrollstaff WHERE UserID = ?', $userId);
    executeRoleStatement($conn, 'DELETE FROM timekeeper WHERE UserID = ?', $userId);

    // Add the selected role record.
    switch ($newRole) {
        case 'Admin':
            executeRoleStatement($conn, 'INSERT INTO admin (UserID) VALUES (?)', $userId);
            break;
        case 'Payroll Staff':
            executeRoleStatement($conn, 'INSERT INTO payrollstaff (UserID) VALUES (?)', $userId);
            break;
        case 'HR':
            executeRoleStatement($conn, 'INSERT INTO hr (UserID) VALUES (?)', $userId);
            break;
        case 'Timekeeper':
            executeRoleStatement($conn, 'INSERT INTO timekeeper (UserID) VALUES (?)', $userId);
            break;
        case 'Assistant Admin':
            executeRoleStatement($conn, 'INSERT INTO assistantmanager (UserID) VALUES (?)', $userId);
            break;
        // User/Worker - no dashboard-role table entry is needed.
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = intval($_POST['user_id'] ?? 0);
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$full_name = trim($first_name . ' ' . $last_name);
$email = trim($_POST['email'] ?? '');
$role = $_POST['role'] ?? 'Worker';
$status = $_POST['status'] ?? 'Active';
$role = normalizeIncomingRole($role);

if (!in_array($status, ['Active', 'Inactive'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid account status.']);
    exit;
}

// Validate inputs
if ($user_id <= 0 || empty($first_name) || empty($last_name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

if (!preg_match('/^[\\p{L}]+(?: [\\p{L}]+)*$/u', $first_name)
    || !preg_match('/^[\\p{L}]+(?: [\\p{L}]+)*$/u', $last_name)
    || mb_strlen($first_name) > 50 || mb_strlen($last_name) > 50) {
    echo json_encode(['success' => false, 'message' => 'First name and last name may contain letters and single spaces only. Numbers and special characters are not allowed.']);
    exit;
}

if (!isSupportedRole($role)) {
    echo json_encode(['success' => false, 'message' => 'Selected role is no longer available.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

try {
    user_identity_ensure_columns($conn);
    user_identity_lock($conn);
    if (user_identity_full_name_exists($conn, $full_name, $user_id)) {
        echo json_encode(['success' => false, 'message' => 'This first and last name combination is already registered.']);
        exit;
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

// Check if user exists
$stmt = $conn->prepare("SELECT id, status FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$existing_user = $result->fetch_assoc();

if (!$existing_user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// Get current role from role tables
$old_role = getUserRole($conn, $user_id);

// Assistant Admin restrictions: cannot change role, cannot deactivate admin, cannot deactivate last admin
if ($currentRole === 'Assistant Admin') {
    // Cannot change roles at all
    if ($old_role !== $role) {
        echo json_encode(['success' => false, 'message' => 'Assistant Admin cannot change user roles.']);
        exit;
    }
    
    // Cannot deactivate an Admin
    if ($old_role === 'Admin' && $status === 'Inactive') {
        echo json_encode(['success' => false, 'message' => 'Assistant Admin cannot deactivate an Admin account.']);
        exit;
    }
    
    // Prevent deactivating the last admin (safety check)
    if ($old_role === 'Admin' && $status === 'Inactive') {
        $admin_count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin");
        $admin_count_stmt->execute();
        $admin_count_result = $admin_count_stmt->get_result();
        $admin_count = $admin_count_result->fetch_assoc()['count'];
        
        if ($admin_count <= 1) {
            echo json_encode(['success' => false, 'message' => 'Cannot deactivate the last admin account']);
            exit;
        }
    }
}

// Admin restrictions
if ($currentRole === 'Admin') {
    // Prevent editing the last admin's role to something else
    if ($old_role === 'Admin' && $role !== 'Admin') {
        $admin_count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin");
        $admin_count_stmt->execute();
        $admin_count_result = $admin_count_stmt->get_result();
        $admin_count = $admin_count_result->fetch_assoc()['count'];
        
        if ($admin_count <= 1) {
            echo json_encode(['success' => false, 'message' => 'Cannot change the last admin account role']);
            exit;
        }
    }

    // Prevent deactivating the last admin
    if ($old_role === 'Admin' && $status === 'Inactive') {
        $admin_count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin");
        $admin_count_stmt->execute();
        $admin_count_result = $admin_count_stmt->get_result();
        $admin_count = $admin_count_result->fetch_assoc()['count'];
        
        if ($admin_count <= 1) {
            echo json_encode(['success' => false, 'message' => 'Cannot deactivate the last admin account']);
            exit;
        }
    }
}

// Check if email exists for another user
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt->bind_param("si", $email, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    exit;
}

// Check if deactivating a Payroll Staff user with active site assignments
if ($old_role === 'Payroll Staff' && ($status === 'Inactive' || $old_role !== $role)) {
    $staff_id_stmt = $conn->prepare("SELECT PayrollStaff_ID FROM payrollstaff WHERE UserID = ?");
    $staff_id_stmt->bind_param("i", $user_id);
    $staff_id_stmt->execute();
    $staff_id_result = $staff_id_stmt->get_result();
    
    if ($staff_id_result->num_rows > 0) {
        $staff_row = $staff_id_result->fetch_assoc();
        $payrollStaffId = (int) $staff_row['PayrollStaff_ID'];
        
        $assignment_check = $conn->prepare("
            SELECT COUNT(*) as assignment_count, GROUP_CONCAT(ps.Site_Name SEPARATOR ', ') as site_names
            FROM payrollstaffassignment psa
            INNER JOIN projectsite ps ON psa.SiteID = ps.SiteID
            WHERE psa.PayrollStaff_ID = ?
        ");
        $assignment_check->bind_param("i", $payrollStaffId);
        $assignment_check->execute();
        $assignment_result = $assignment_check->get_result();
        $assignment_data = $assignment_result->fetch_assoc();
        $assignment_count = (int) ($assignment_data['assignment_count'] ?? 0);
        $site_names = $assignment_data['site_names'] ?? '';
        
        if ($assignment_count > 0) {
            $action = $status === 'Inactive'
                ? 'deactivate this Payroll Staff user'
                : 'change the role of this Payroll Staff user';
            echo json_encode([
                'success' => false, 
                'message' => "Cannot {$action}. They are currently assigned to {$assignment_count} site(s): {$site_names}. Please unassign them from those sites first."
            ]);
            exit;
        }
    }
    $staff_id_stmt->close();
}

try {
    $conn->begin_transaction();
    // Update user without role column
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, first_name = ?, last_name = ?, email = ?, status = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $full_name, $first_name, $last_name, $email, $status, $user_id);

    if ($stmt->execute()) {
        $workerNameStmt = $conn->prepare("UPDATE worker SET First_Name = ?, Last_Name = ? WHERE UserID = ?");
        if ($workerNameStmt) {
            $workerNameStmt->bind_param('ssi', $first_name, $last_name, $user_id);
            if (!$workerNameStmt->execute()) {
                throw new RuntimeException('Unable to synchronize the worker name: ' . $workerNameStmt->error);
            }
            $workerNameStmt->close();
        }

        // Update role records atomically so a previous role cannot remain active.
        if ($old_role !== $role) {
            updateUserRole($conn, $user_id, $role);
        }
        $conn->commit();
        $redirect = null;
        if ($user_id === (int) ($_SESSION['user_id'] ?? 0) && $old_role !== $role) {
            $_SESSION['role'] = $role;
            $redirect = auth_get_redirect_path($role);
        }

        echo json_encode([
            'success' => true,
            'message' => 'User updated successfully',
            'role' => $role,
            'redirect' => $redirect,
        ]);
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to update user']);
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
