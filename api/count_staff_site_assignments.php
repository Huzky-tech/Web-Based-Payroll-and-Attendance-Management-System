<?php
/**
 * Count Staff Site Assignments API
 * Return the number of sites assigned to a specific staff member
 */

header('Content-Type: application/json');
include 'connection/db_config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $staff_id = $_GET['staff_id'] ?? 0;
    
    if (empty($staff_id)) {
        echo json_encode(['success' => false, 'message' => 'Staff ID is required']);
        exit;
    }
    
    $sql = "SELECT COUNT(*) as count 
            FROM payrollstaffassignment 
            WHERE PayrollStaff_ID = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $count = $result->fetch_assoc()['count'] ?? 0;
        
        // Also get staff info
        $staffSql = "SELECT ps.PayrollStaff_ID, u.full_name, u.email 
                     FROM payrollstaff ps
                     INNER JOIN users u ON ps.UserID = u.id
                     WHERE ps.PayrollStaff_ID = ?";
        $staffStmt = $conn->prepare($staffSql);
        $staffStmt->bind_param("i", $staff_id);
        $staffStmt->execute();
        $staffResult = $staffStmt->get_result();
        
        $staffInfo = null;
        if ($staffResult->num_rows > 0) {
            $staffInfo = $staffResult->fetch_assoc();
        }
        $staffStmt->close();
        
        echo json_encode([
            'success' => true,
            'staff_id' => $staff_id,
            'count' => (int)$count,
            'staff' => $staffInfo
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to count assignments'
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

