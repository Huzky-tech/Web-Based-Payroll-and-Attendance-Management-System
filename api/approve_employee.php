<?php
/**
 * Approve Employee API
 * Mark an employee as approved by an administrator or HR manager
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
    $approvedBy = $data['approved_by'] ?? 1; // User ID of approver
    $actionType = $data['action_type'] ?? 'Employee Approval';
    $approvalStatus = $data['approval_status'] ?? 'Approved';
    
    // Validation
    if (empty($employeeId)) {
        echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
        exit;
    }
    
    // Check if employee exists
    $checkSql = "SELECT CONCAT(First_Name, ' ', Last_Name) AS full_name FROM worker WHERE WorkerID = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $employeeId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        $checkStmt->close();
        exit;
    }
    
    $employeeName = $checkResult->fetch_assoc()['full_name'];
    $checkStmt->close();
    
    // Insert approval record
    $sql = "INSERT INTO approvals (WorkerID, Action_Type, Approval_By, Approval_Status, Date) 
            VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isis", $employeeId, $actionType, $approvedBy, $approvalStatus);
    
    if ($stmt->execute()) {
        $approvalId = $stmt->insert_id;
        
        // Log the action
        logAudit($conn, $approvedBy, 'Employee Approved', "Approved employee: $employeeName (ID: $employeeId)");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Employee approved successfully',
            'approval_id' => $approvalId,
            'employee_name' => $employeeName,
            'approval_status' => $approvalStatus
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to approve employee']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

