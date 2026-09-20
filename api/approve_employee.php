<?php

header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
include 'employee_helpers.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'HR']);


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
    $approvedBy = (int) ($_SESSION['user_id'] ?? 0);
    $actionType = $data['action_type'] ?? 'Employee Approval';
    $approvalStatus = 'Approved';
    
    // Validation
    if (empty($employeeId)) {
        echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
        exit;
    }

    if ($approvedBy <= 0) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized approver']);
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
    
    $latestSql = "SELECT ApprovalID, Approval_Status FROM approvals WHERE WorkerID = ? ORDER BY Date DESC, ApprovalID DESC LIMIT 1";
    $latestStmt = $conn->prepare($latestSql);
    $latestStmt->bind_param("i", $employeeId);
    $latestStmt->execute();
    $latestResult = $latestStmt->get_result();
    $latestApproval = $latestResult ? $latestResult->fetch_assoc() : null;
    $latestStmt->close();

    if (($latestApproval['Approval_Status'] ?? '') === 'Approved') {
        echo json_encode([
            'success' => true,
            'message' => 'Employee is already approved.',
            'approval_id' => (int) ($latestApproval['ApprovalID'] ?? 0),
            'employee_name' => $employeeName,
            'approval_status' => $approvalStatus
        ]);
        exit;
    }

    if (!empty($latestApproval['ApprovalID'])) {
        $sql = "UPDATE approvals
                SET Action_Type = ?, Approval_By = ?, Approval_Status = ?, Date = NOW()
                WHERE ApprovalID = ?";
        $stmt = $conn->prepare($sql);
        $approvalId = (int) $latestApproval['ApprovalID'];
        $stmt->bind_param("sisi", $actionType, $approvedBy, $approvalStatus, $approvalId);
    } else {
        $sql = "INSERT INTO approvals (WorkerID, Action_Type, Approval_By, Approval_Status, Date)
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isis", $employeeId, $actionType, $approvedBy, $approvalStatus);
        $approvalId = 0;
    }

    if ($stmt && $stmt->execute()) {
        if ($approvalId <= 0) {
            $approvalId = (int) $stmt->insert_id;
        }
        
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
        echo json_encode(['success' => false, 'message' => 'Failed to approve employee: ' . ($stmt ? $stmt->error : 'Unable to prepare approval update')]);
    }
    
    if ($stmt) {
        $stmt->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
