<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

// Helper function to get user's role from role tables
function getUserRole($conn, $user_id) {
    // Check admin table
    $stmt = $conn->prepare("SELECT 1 FROM admin WHERE UserID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) return 'Admin';
    
    // Check payrollstaff table
    $stmt = $conn->prepare("SELECT 1 FROM payrollstaff WHERE UserID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) return 'Payroll Staff';
    
    // Check hr table
    $stmt = $conn->prepare("SELECT 1 FROM hr WHERE UserID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) return 'HR';
    
    // Check timekeeper table
    $stmt = $conn->prepare("SELECT 1 FROM timekeeper WHERE UserID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) return 'Timekeeper';
    
    // Check assistantmanager table
    $stmt = $conn->prepare("SELECT 1 FROM assistantmanager WHERE UserID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) return 'Assistant Manager';
    
    return 'Worker';
}

// Helper function to update user's role in role tables
function updateUserRole($conn, $user_id, $old_role, $new_role) {
    // Remove from old role table
    switch ($old_role) {
        case 'Admin':
            $stmt = $conn->prepare("DELETE FROM admin WHERE UserID = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            break;
        case 'Payroll Staff':
            $stmt = $conn->prepare("DELETE FROM payrollstaff WHERE UserID = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            break;
        case 'HR':
            $stmt = $conn->prepare("DELETE FROM hr WHERE UserID = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            break;
        case 'Timekeeper':
            $stmt = $conn->prepare("DELETE FROM timekeeper WHERE UserID = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            break;
        case 'Assistant Manager':
            $stmt = $conn->prepare("DELETE FROM assistantmanager WHERE UserID = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            break;
    }
    
    // Add to new role table
    switch ($new_role) {
        case 'Admin':
            $stmt = $conn->prepare("INSERT INTO admin (UserID) VALUES (?)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            break;
        case 'Payroll Staff':
            $stmt = $conn->prepare("INSERT INTO payrollstaff (UserID) VALUES (?)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            break;
        case 'HR':
            $stmt = $conn->prepare("INSERT INTO hr (UserID) VALUES (?)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            break;
        case 'Timekeeper':
            $stmt = $conn->prepare("INSERT INTO timekeeper (UserID) VALUES (?)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            break;
        case 'Assistant Manager':
            $stmt = $conn->prepare("INSERT INTO assistantmanager (UserID) VALUES (?)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            break;
        // Worker - no table entry needed
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = intval($_POST['user_id'] ?? 0);
$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = $_POST['role'] ?? 'Worker';
$status = $_POST['status'] ?? 'Active';

// Validate inputs
if ($user_id <= 0 || empty($full_name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
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

// Check if email exists for another user
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt->bind_param("si", $email, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    exit;
}

try {
    // Update user without role column
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, status = ? WHERE id = ?");
    $stmt->bind_param("sssi", $full_name, $email, $status, $user_id);

    if ($stmt->execute()) {
        // Update role in role tables if changed
        if ($old_role !== $role) {
            updateUserRole($conn, $user_id, $old_role, $role);
        }
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
