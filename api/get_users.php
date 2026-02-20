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

try {
    $stmt = $conn->prepare("SELECT id, full_name, email, status, last_login FROM users ORDER BY id");
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    
    // Add role to each user from role tables
    foreach ($users as &$user) {
        $user['role'] = getUserRole($conn, $user['id']);
    }

    echo json_encode(['success' => true, 'data' => $users]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
