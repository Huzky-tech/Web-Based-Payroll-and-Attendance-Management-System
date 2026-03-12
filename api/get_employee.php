<?php
/**
 * Get Employee by ID API
 * Retrieves a single employee's details using their ID
 */

header('Content-Type: application/json');
include 'connection/db_config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $employeeId = $_GET['id'] ?? 0;
    
    if (empty($employeeId)) {
        echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
        exit;
    }
    
    // Get employee with status and current assignment
    $sql = "SELECT 
                w.WorkerID,
                w.First_Name,
                w.Last_Name,
                CONCAT(w.First_Name, ' ', w.Last_Name) AS full_name,
                w.RateType,
                w.RateAmount AS salary,
                w.Phone,
                w.DateHired AS join_date,
                w.UserID,
                ws.Status AS worker_status,
                wa.SiteID,
                ps.Site_Name,
                ps.Location,
                wa.Role_On_Site AS position,
                wa.Assigned_Date
            FROM worker w
            LEFT JOIN workerstatus ws ON w.WorkerStatusID = ws.WorkerStatusID
            LEFT JOIN workerassignment wa ON w.WorkerID = wa.WorkerID
            LEFT JOIN projectsite ps ON wa.SiteID = ps.SiteID
            WHERE w.WorkerID = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $employee = $result->fetch_assoc();
        
        // Get approval details
        $approvalSql = "SELECT 
                            a.ApprovalID,
                            a.Approval_Status,
                            a.Date,
                            u.full_name AS approved_by_name
                        FROM approvals a
                        LEFT JOIN users u ON a.Approval_By = u.id
                        WHERE a.WorkerID = ?
                        ORDER BY a.Date DESC
                        LIMIT 1";
        $approvalStmt = $conn->prepare($approvalSql);
        $approvalStmt->bind_param("i", $employeeId);
        $approvalStmt->execute();
        $approvalResult = $approvalStmt->get_result();
        
        $approvalData = null;
        if ($approvalResult->num_rows > 0) {
            $approvalData = $approvalResult->fetch_assoc();
        }
        $approvalStmt->close();
        
        $employee['approval'] = $approvalData;
        
        echo json_encode([
            'success' => true, 
            'employee' => $employee
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

