<?php
/**
 * Count Active Sites API
 * Returns the count of active construction sites
 */

header('Content-Type: application/json');
include 'connection/db_config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Count active sites (case insensitive)
    $sql = "SELECT COUNT(*) as count FROM projectsite WHERE LOWER(Status) = 'active'";
    $result = $conn->query($sql);
    
    if ($result) {
        $count = $result->fetch_assoc()['count'] ?? 0;
        
        echo json_encode([
            'success' => true,
            'count' => $count
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to count active sites'
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

