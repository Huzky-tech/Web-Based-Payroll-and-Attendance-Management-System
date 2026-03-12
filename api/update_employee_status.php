<?php
/**
 * Update Employee Status API
 * Change employee status between Active, Inactive, or On Leave
 */

header('Content-Type: application/json');
include 'connection/db_config.php';

// Helper function for audit logging
function logAudit($conn, $userId, $action, $details) {
    $stmt = $conn->prepare("INSERT INTO audit_logs (UserID, Action, Details, Date) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $userId, $action, $details);
    $stmt->execute();
    $stmt->close();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $employeeId = $data['employee_id'] ?? 0;
    $newStatus = $data['status'] ?? '';
    $userId = $data['user_id'] ?? 1;
    
    // Validation
    if (empty($employeeId) || empty($newStatus)) {
        echo json_encode(['success' => false, 'message' => 'Employee ID and status are required']);
        exit;
    }
    
    // Validate status
    $validStatuses = ['Active', 'Inactive', 'OnLeave'];
    if (!in_array($newStatus, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status. Use: Active, Inactive, or OnLeave']);
        exit;
    }
    
    // Get status ID
    $statusSql = "SELECT WorkerStatusID FROM workerstatus WHERE Status = ?";
    $statusStmt = $conn->prepare($statusSql);
    $statusStmt->bind_param("s", $newStatus);
    $statusStmt->execute();
    $statusResult = $statusStmt->get_result();
    
    if ($statusResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Status not found in database']);
        $statusStmt->close();
        exit;
    }
    
    $statusRow = $statusResult->fetch_assoc();
    $statusId = $statusRow['WorkerStatusID'];
    $statusStmt->close();
    
    // Get current employee name
    $nameSql = "SELECT CONCAT(First_Name, ' ', Last_Name) AS full_name FROM worker WHERE WorkerID = ?";
    $nameStmt = $conn->prepare($nameSql);
    $nameStmt->bind_param("i", $employeeId);
    $nameStmt->execute();
    $nameResult = $nameStmt->get_result();
    
    if ($nameResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        $nameStmt->close();
        exit;
    }
    
    $employeeName = $nameResult->fetch_assoc()['full_name'];
    $nameStmt->close();
    
    // Update status
    $updateSql = "UPDATE worker SET WorkerStatusID = ? WHERE WorkerID = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("ii", $statusId, $employeeId);
    
    if ($updateStmt->execute()) {
        // Log the action
        logAudit($conn, $userId, 'Employee Status Updated', "Changed status of $employeeName (ID: $employeeId) to $newStatus");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Employee status updated successfully',
            'employee_id' => $employeeId,
            'new_status' => $newStatus
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update employee status']);
    }
    
    $updateStmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

