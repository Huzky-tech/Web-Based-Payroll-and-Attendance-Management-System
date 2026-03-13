<?php
/**
 * Count Total Assignments API
 * Returns the total number of staff-site assignments
 */

header('Content-Type: application/json');
include 'connection/db_config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sql = "SELECT COUNT(*) as count FROM payrollstaffassignment";
    $result = $conn->query($sql);
    
    if ($result) {
        $count = $result->fetch_assoc()['count'] ?? 0;
        
        echo json_encode([
            'success' => true,
            'count' => (int)$count
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to count assignments'
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

