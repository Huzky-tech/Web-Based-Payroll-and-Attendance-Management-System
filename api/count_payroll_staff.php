<?php
/**
 * Count Payroll Staff API
 * Returns the total count of payroll staff users
 */

header('Content-Type: application/json');
include 'connection/db_config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sql = "SELECT COUNT(*) as count FROM payrollstaff";
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
            'message' => 'Failed to count payroll staff'
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

