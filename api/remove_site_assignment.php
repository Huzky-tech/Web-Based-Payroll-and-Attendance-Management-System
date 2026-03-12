<?php
/**
 * Remove Site Assignment API
 * Remove a site from a payroll staff member
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
    
    // Accept both assignment_id or staff_id + site_id
    $assignmentId = $data['assignment_id'] ?? 0;
    $staffId = $data['staff_id'] ?? $data['payroll_staff_id'] ?? 0;
    $siteId = $data['site_id'] ?? 0;
    $userId = $data['user_id'] ?? 1;
    
    // Validation - need at least assignment ID or both staff ID and site ID
    if (empty($assignmentId) && (empty($staffId) || empty($siteId))) {
        echo json_encode(['success' => false, 'message' => 'Assignment ID or Staff ID and Site ID are required']);
        exit;
    }
    
    // Build the WHERE clause based on what's provided
    if (!empty($assignmentId)) {
        // Get assignment details before deletion
        $getSql = "SELECT psa.PayrollStaff_ID, psa.SiteID, ps.UserID AS staff_user_id, ps.Site_Name
                   FROM payrollstaffassignment psa
                   JOIN projectsite ps ON psa.SiteID = ps.SiteID
                   WHERE psa.staffAssignID = ?";
        $stmt = $conn->prepare($getSql);
        $stmt->bind_param("i", $assignmentId);
    } else {
        $getSql = "SELECT psa.staffAssignID, psa.PayrollStaff_ID, psa.SiteID, ps.Site_Name
                   FROM payrollstaffassignment psa
                   JOIN projectsite ps ON psa.SiteID = ps.SiteID
                   WHERE psa.PayrollStaff_ID = ? AND psa.SiteID = ?";
        $stmt = $conn->prepare($getSql);
        $stmt->bind_param("ii", $staffId, $siteId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Assignment not found']);
        $stmt->close();
        exit;
    }
    
    $assignment = $result->fetch_assoc();
    $assignmentId = $assignment['staffAssignID'];
    $siteName = $assignment['Site_Name'];
    $stmt->close();
    
    // Get staff name
    $staffSql = "SELECT u.full_name FROM payrollstaff ps 
                 JOIN users u ON ps.UserID = u.id 
                 WHERE ps.PayrollStaff_ID = ?";
    $staffStmt = $conn->prepare($staffSql);
    $staffStmt->bind_param("i", $assignment['PayrollStaff_ID']);
    $staffStmt->execute();
    $staffResult = $staffStmt->get_result();
    $staffName = "Staff";
    if ($staffResult->num_rows > 0) {
        $staffName = $staffResult->fetch_assoc()['full_name'] ?? "Staff";
    }
    $staffStmt->close();
    
    // Delete assignment
    $deleteSql = "DELETE FROM payrollstaffassignment WHERE staffAssignID = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bind_param("i", $assignmentId);
    
    if ($deleteStmt->execute()) {
        // Log the action
        logAudit($conn, $userId, 'Site Assignment Removed', 
            "Removed site '$siteName' from staff '$staffName'");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Site assignment removed successfully'
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

