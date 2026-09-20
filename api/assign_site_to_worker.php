<?php
/**
 * Assign Site to Worker API
 * Assign a worker to a construction site
 */

header('Content-Type: application/json');
include 'connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR', 'Timekeeper']);
require_once 'employee_helpers.php';
require_once 'site_activation_helpers.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

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
    
    // Accept both worker_id and staff_id for compatibility
    $workerId = $data['worker_id'] ?? $data['staff_id'] ?? 0;
    $siteId = $data['site_id'] ?? 0;
    $userId = (int) ($_SESSION['user_id'] ?? ($data['user_id'] ?? 0));
    
    // Validation
    if (empty($workerId) || empty($siteId)) {
        echo json_encode(['success' => false, 'message' => 'Worker ID and Site ID are required']);
        exit;
    }

    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized audit user']);
        exit;
    }

    if (!isEmployeeApprovedForAssignment($conn, (int) $workerId)) {
        echo json_encode(['success' => false, 'message' => 'This employee is pending approval and cannot be assigned to a site yet.']);
        exit;
    }
    
    // Prevent duplicate worker assignments. A worker can only have one active site assignment.
    $checkSql = "SELECT wa.AssignmentID, wa.SiteID, ps.Site_Name
                 FROM workerassignment wa
                 LEFT JOIN projectsite ps ON ps.SiteID = wa.SiteID
                 WHERE wa.WorkerID = ?
                 LIMIT 1";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $workerId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $existingAssignment = $checkResult->fetch_assoc();
        $existingSiteId = (int) ($existingAssignment['SiteID'] ?? 0);
        $existingSiteName = trim((string) ($existingAssignment['Site_Name'] ?? 'another site'));

        if ($existingSiteId === (int) $siteId) {
            echo json_encode(['success' => false, 'message' => 'This worker is already assigned to this site.']);
        } else {
            echo json_encode(['success' => false, 'message' => "This worker is already assigned to {$existingSiteName}."]);
        }
        $checkStmt->close();
        exit;
    }
    $checkStmt->close();
    
    // Get worker name
    $workerSql = "SELECT CONCAT(First_Name, ' ', Last_Name) AS full_name FROM worker 
                 WHERE WorkerID = ?";
    $workerStmt = $conn->prepare($workerSql);
    $workerStmt->bind_param("i", $workerId);
    $workerStmt->execute();
    $workerResult = $workerStmt->get_result();
    
    $workerName = "Worker";
    if ($workerResult->num_rows > 0) {
        $workerName = $workerResult->fetch_assoc()['full_name'] ?? "Worker";
    }
    $workerStmt->close();
    
    // Get site name
    $siteSql = "SELECT Site_Name FROM projectsite WHERE SiteID = ?";
    $siteStmt = $conn->prepare($siteSql);
    $siteStmt->bind_param("i", $siteId);
    $siteStmt->execute();
    $siteResult = $siteStmt->get_result();
    
    if ($siteResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Site not found']);
        $siteStmt->close();
        exit;
    }
    
    $siteName = $siteResult->fetch_assoc()['Site_Name'];
    $siteStmt->close();
    
    // Create assignment
    $sql = "INSERT INTO workerassignment (WorkerID, SiteID, Created_at) 
            VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $workerId, $siteId);
    
    if ($stmt->execute()) {
        $assignmentId = $stmt->insert_id;
        $activation = site_sync_activation_status($conn, (int) $siteId);
        
        // Log the action
        logAudit($conn, $userId, 'Site Assigned to Worker', 
            "Assigned site '$siteName' to worker '$workerName'");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Site assigned to worker successfully',
            'assignment_id' => $assignmentId,
            'activation' => $activation
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to assign site to worker']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
