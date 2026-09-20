<?php
/**
 * Remove Site Assignment API
 * Remove a site from a worker
 */

header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once 'site_activation_helpers.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR', 'Timekeeper']);

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
    
    // Accept both assignment_id or worker_id + site_id
    $assignmentId = $data['assignment_id'] ?? 0;
    $workerId = $data['worker_id'] ?? $data['staff_id'] ?? 0;
    $siteId = $data['site_id'] ?? 0;
    $userId = (int) ($_SESSION['user_id'] ?? ($data['user_id'] ?? 0));
    
    // Validation - need at least assignment ID or both worker ID and site ID
    if (empty($assignmentId) && (empty($workerId) || empty($siteId))) {
        echo json_encode(['success' => false, 'message' => 'Assignment ID or Worker ID and Site ID are required']);
        exit;
    }

    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized audit user']);
        exit;
    }
    
    // Build the WHERE clause based on what's provided
    if (!empty($assignmentId)) {
        // Get assignment details before deletion
        $getSql = "SELECT wa.AssignmentID, wa.WorkerID, wa.SiteID, ps.Site_Name
                   FROM workerassignment wa
                   JOIN projectsite ps ON wa.SiteID = ps.SiteID
                   WHERE wa.AssignmentID = ?";
        $stmt = $conn->prepare($getSql);
        $stmt->bind_param("i", $assignmentId);
    } else {
        $getSql = "SELECT wa.AssignmentID, wa.WorkerID, wa.SiteID, ps.Site_Name
                   FROM workerassignment wa
                   JOIN projectsite ps ON wa.SiteID = ps.SiteID
                   WHERE wa.WorkerID = ? AND wa.SiteID = ?";
        $stmt = $conn->prepare($getSql);
        $stmt->bind_param("ii", $workerId, $siteId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Assignment not found']);
        $stmt->close();
        exit;
    }
    
    $assignment = $result->fetch_assoc();
    $assignmentId = $assignment['AssignmentID'];
    $siteName = $assignment['Site_Name'];
    $stmt->close();

    if ($currentRole === 'Payroll Staff') {
        echo json_encode(['success' => false, 'message' => 'Payroll staff can only view assigned site details']);
        exit;
    }
    
    // Get worker name
    $workerSql = "SELECT CONCAT(First_Name, ' ', Last_Name) AS full_name FROM worker 
                 WHERE WorkerID = ?";
    $workerStmt = $conn->prepare($workerSql);
    $workerStmt->bind_param("i", $assignment['WorkerID']);
    $workerStmt->execute();
    $workerResult = $workerStmt->get_result();
    $workerName = "Worker";
    if ($workerResult->num_rows > 0) {
        $workerName = $workerResult->fetch_assoc()['full_name'] ?? "Worker";
    }
    $workerStmt->close();
    
    // Delete assignment
    $deleteSql = "DELETE FROM workerassignment WHERE AssignmentID = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bind_param("i", $assignmentId);
    
    if ($deleteStmt->execute()) {
        $activation = site_sync_activation_status($conn, (int) $assignment['SiteID']);
        // Log the action
        logAudit($conn, $userId, 'Site Assignment Removed', 
            "{$currentRole} removed site '$siteName' from worker '$workerName'");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Site assignment removed successfully',
            'activation' => $activation
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove site assignment']);
    }
    
    $deleteStmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
