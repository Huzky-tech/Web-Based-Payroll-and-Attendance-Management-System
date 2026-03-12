<?php
/**
 * Assign Site to Staff API
 * Assign a payroll staff member to a construction site
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
    
    // Accept both staff_id and payroll_staff_id for compatibility
    $staffId = $data['staff_id'] ?? $data['payroll_staff_id'] ?? 0;
    $siteId = $data['site_id'] ?? 0;
    $userId = $data['user_id'] ?? 1;
    
    // Validation
    if (empty($staffId) || empty($siteId)) {
        echo json_encode(['success' => false, 'message' => 'Staff ID and Site ID are required']);
        exit;
    }
    
    // Check if assignment already exists
    $checkSql = "SELECT staffAssignID FROM payrollstaffassignment 
                 WHERE PayrollStaff_ID = ? AND SiteID = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $staffId, $siteId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This assignment already exists']);
        $checkStmt->close();
        exit;
    }
    $checkStmt->close();
    
    // Get staff name
    $staffSql = "SELECT u.full_name FROM payrollstaff ps 
                 JOIN users u ON ps.UserID = u.id 
                 WHERE ps.PayrollStaff_ID = ?";
    $staffStmt = $conn->prepare($staffSql);
    $staffStmt->bind_param("i", $staffId);
    $staffStmt->execute();
    $staffResult = $staffStmt->get_result();
    
    $staffName = "Staff";
    if ($staffResult->num_rows > 0) {
        $staffName = $staffResult->fetch_assoc()['full_name'] ?? "Staff";
    }
    $staffStmt->close();
    
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
    $sql = "INSERT INTO payrollstaffassignment (PayrollStaff_ID, SiteID, Created_at) 
            VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $staffId, $siteId);
    
    if ($stmt->execute()) {
        $assignmentId = $stmt->insert_id;
        
        // Log the action
        logAudit($conn, $userId, 'Site Assigned to Staff', 
            "Assigned site '$siteName' to staff '$staffName' (Assignment ID: $assignmentId)");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Site assigned to staff successfully',
            'assignment_id' => $assignmentId
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to assign site to staff']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

