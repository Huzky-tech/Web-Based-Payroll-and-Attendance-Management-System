<?php
/**
 * Delete Employee API
 * Removes an employee from the database
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

if ($method === 'POST' || $method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $employeeId = $data['id'] ?? $_GET['id'] ?? 0;
    $userId = $data['user_id'] ?? 1;
    
    if (empty($employeeId)) {
        echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
        exit;
    }
    
    // Get employee name before deletion
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
    
    // Delete worker assignments first
    $assignSql = "DELETE FROM workerassignment WHERE WorkerID = ?";
    $assignStmt = $conn->prepare($assignSql);
    $assignStmt->bind_param("i", $employeeId);
    $assignStmt->execute();
    $assignStmt->close();
    
    // Delete positions
    $posSql = "DELETE FROM positions WHERE WorkerID = ?";
    $posStmt = $conn->prepare($posSql);
    $posStmt->bind_param("i", $employeeId);
    $posStmt->execute();
    $posStmt->close();
    
    // Delete approvals
    $appSql = "DELETE FROM approvals WHERE WorkerID = ?";
    $appStmt = $conn->prepare($appSql);
    $appStmt->bind_param("i", $employeeId);
    $appStmt->execute();
    $appStmt->close();
    
    // Delete the worker
    $sql = "DELETE FROM worker WHERE WorkerID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employeeId);
    
    if ($stmt->execute()) {
        // Log the action
        logAudit($conn, $userId, 'Employee Deleted', "Deleted employee: $employeeName (ID: $employeeId)");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Employee deleted successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete employee']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

