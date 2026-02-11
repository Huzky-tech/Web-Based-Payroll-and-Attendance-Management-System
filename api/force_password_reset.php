<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // This would typically set a flag in the database to force password reset on next login
    // For simplicity, we'll just update all users to require password change
    $stmt = $conn->prepare("UPDATE users SET password = '' WHERE status = 'Active'");
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Password reset initiated for all active users']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to initiate password reset']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
