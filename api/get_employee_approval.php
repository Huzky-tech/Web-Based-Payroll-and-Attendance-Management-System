<?php
/**
 * Get Employee Approval Details API
 * Return approval status and who approved the employee
 */

header('Content-Type: application/json');
include 'connection/db_config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $employeeId = $_GET['employee_id'] ?? 0;
    
    if (empty($employeeId)) {
        echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
        exit;
    }
    
    // Get approval details
    $sql = "SELECT 
                a.ApprovalID,
                a.WorkerID,
                a.Action_Type,
                a.Approval_By,
                a.Approval_Status,
                a.Date,
                u.full_name AS approved_by_name,
                u.email AS approved_by_email
            FROM approvals a
            LEFT JOIN users u ON a.Approval_By = u.id
            WHERE a.WorkerID = ?
            ORDER BY a.Date DESC
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $approval = $result->fetch_assoc();
        
        // Determine if approved
        $isApproved = ($approval['Approval_Status'] === 'Approved');
        
        echo json_encode([
            'success' => true, 
            'approval' => $approval,
            'is_approved' => $isApproved
        ]);
    } else {
        echo json_encode([
            'success' => true, 
            'approval' => null,
            'is_approved' => false,
            'message' => 'No approval record found'
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

