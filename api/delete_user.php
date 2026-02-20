<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

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

// Check if user is an admin
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// Prevent deleting the last admin
if ($user['role'] === 'Admin') {
    // Count total admins
    $admin_count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'Admin' AND status = 'Active'");
    $admin_count_stmt->execute();
    $admin_count_result = $admin_count_stmt->get_result();
    $admin_count = $admin_count_result->fetch_assoc()['count'];
    
    if ($admin_count <= 1) {
        echo json_encode(['success' => false, 'message' => 'Cannot deactivate the last admin account']);
        exit;
    }
}

try {
    // Soft delete - mark as inactive instead of deleting
    $stmt = $conn->prepare("UPDATE users SET status = 'Inactive' WHERE id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User deactivated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to deactivate user']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
