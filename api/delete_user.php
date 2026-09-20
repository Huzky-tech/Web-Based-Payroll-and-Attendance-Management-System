<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

// Session may already be started in db_config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentRole = $_SESSION['role'] ?? '';

// Only Admin can delete users
if ($currentRole !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied. Only Admin can delete users.']);
    exit;
}

function getUserRole($conn, $user_id) {
    $tables = [
        'admin' => 'Admin',
        'payrollstaff' => 'Payroll Staff',
        'timekeeper' => 'Timekeeper',
        'assistantmanager' => 'Assistant Admin',
        'worker' => 'Worker'
    ];

    foreach ($tables as $table => $role) {
        $stmt = $conn->prepare("SELECT 1 FROM {$table} WHERE UserID = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt->close();
            return $role;
        }
        $stmt->close();
    }

    return 'User';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = intval($_POST['user_id'] ?? 0);
$current_user_id = intval($_POST['current_user_id'] ?? 0);

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// Prevent deleting yourself
if ($user_id == $current_user_id) {
    echo json_encode(['success' => false, 'message' => 'You cannot deactivate your own account']);
    exit;
}

// Check if user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// Prevent deleting the last admin
$userRole = getUserRole($conn, $user_id);
if ($userRole === 'Admin') {
    // Count total admins
    $admin_count_stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM admin a
        INNER JOIN users u ON a.UserID = u.id
        WHERE u.status = 'Active'
    ");
    $admin_count_stmt->execute();
    $admin_count_result = $admin_count_stmt->get_result();
    $admin_count = $admin_count_result->fetch_assoc()['count'];
    
    if ($admin_count <= 1) {
        echo json_encode(['success' => false, 'message' => 'Cannot deactivate the last admin account']);
        exit;
    }
}

// Check if user is Payroll Staff with active site assignments
if ($userRole === 'Payroll Staff') {
    // Get the PayrollStaff_ID for this user
    $staff_id_stmt = $conn->prepare("SELECT PayrollStaff_ID FROM payrollstaff WHERE UserID = ?");
    $staff_id_stmt->bind_param("i", $user_id);
    $staff_id_stmt->execute();
    $staff_id_result = $staff_id_stmt->get_result();
    
    if ($staff_id_result->num_rows > 0) {
        $staff_row = $staff_id_result->fetch_assoc();
        $payrollStaffId = (int) $staff_row['PayrollStaff_ID'];
        
        // Check if this payroll staff has active site assignments
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
            echo json_encode([
                'success' => false, 
                'message' => "Cannot deactivate this Payroll Staff user. They are currently assigned to {$assignment_count} site(s): {$site_names}. Please remove their site assignments first."
            ]);
            exit;
        }
    }
    $staff_id_stmt->close();
}

try {
    // Soft delete - mark as inactive instead of deleting.
    $stmt = $conn->prepare("UPDATE users SET status = 'Inactive' WHERE id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User archived successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to archive user']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
