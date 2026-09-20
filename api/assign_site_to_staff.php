<?php
/**
 * Assign Site to Staff API
 * Assign a payroll staff member to a construction site
 */

header('Content-Type: application/json');
include 'connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/timekeeper_report_helpers.php';
require_auth($conn, ['Admin']);
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
    
    // Accept both staff_id and payroll_staff_id for compatibility
    $staffId = $data['staff_id'] ?? $data['payroll_staff_id'] ?? 0;
    $siteId = $data['site_id'] ?? 0;
    $userId = (int) ($_SESSION['user_id'] ?? ($data['user_id'] ?? 0));
    
    // Validation
    if (empty($staffId) || empty($siteId)) {
        echo json_encode(['success' => false, 'message' => 'Staff ID and Site ID are required']);
        exit;
    }

    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized audit user']);
        exit;
    }
    
    // A site can have only one Payroll Staff member. A Payroll Staff member
    // can still be responsible for multiple different sites.
    $checkSql = "SELECT psa.staffAssignID, psa.PayrollStaff_ID, u.full_name
                 FROM payrollstaffassignment psa
                 LEFT JOIN payrollstaff ps ON ps.PayrollStaff_ID = psa.PayrollStaff_ID
                 LEFT JOIN users u ON u.id = ps.UserID
                 WHERE psa.SiteID = ? LIMIT 1";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $siteId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $existingAssignment = $checkResult->fetch_assoc();
        $existingName = trim((string) ($existingAssignment['full_name'] ?? 'another Payroll Staff member'));
        $message = (int) $existingAssignment['PayrollStaff_ID'] === (int) $staffId
            ? 'This Payroll Staff member is already assigned to the site.'
            : "This site is already assigned to {$existingName}. Remove the existing assignment first.";
        echo json_encode(['success' => false, 'message' => $message]);
        $checkStmt->close();
        exit;
    }
    $checkStmt->close();
    
    // Get staff name
    $staffSql = "SELECT ps.UserID, u.full_name FROM payrollstaff ps 
                 JOIN users u ON ps.UserID = u.id 
                 WHERE ps.PayrollStaff_ID = ?";
    $staffStmt = $conn->prepare($staffSql);
    $staffStmt->bind_param("i", $staffId);
    $staffStmt->execute();
    $staffResult = $staffStmt->get_result();
    
    $staffName = "Staff";
    $staffUserId = 0;
    if ($staffResult->num_rows > 0) {
        $staff = $staffResult->fetch_assoc();
        $staffName = $staff['full_name'] ?? "Staff";
        $staffUserId = (int) ($staff['UserID'] ?? 0);
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
            "Assigned site '$siteName' to staff '$staffName'");

        // The assignment recipient needs their own notification.  A global
        // audit notification only reaches administrators and was why Payroll
        // Staff could see an assigned site but no alert for it.
        tk_report_ensure_schema($conn);
        if ($staffUserId > 0) {
            $notificationType = 'Site Assignment';
            $notificationTitle = 'New site assignment';
            $notificationMessage = "You have been assigned to {$siteName}.";
            $notificationStmt = $conn->prepare(
                "INSERT INTO admin_notifications
                    (RecipientUserID, NotificationType, ReferenceID, Title, Message, IsRead, CreatedAt)
                 VALUES (?, ?, ?, ?, ?, 0, NOW())"
            );
            if ($notificationStmt) {
                $notificationStmt->bind_param(
                    'isiss',
                    $staffUserId,
                    $notificationType,
                    $assignmentId,
                    $notificationTitle,
                    $notificationMessage
                );
                $notificationStmt->execute();
                $notificationStmt->close();
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Site assigned to staff successfully',
            'assignment_id' => $assignmentId
        ]);
    } else {
        $message = (int) $stmt->errno === 1062
            ? 'This site already has a Payroll Staff assignment. Remove it before assigning another staff member.'
            : 'Failed to assign site to staff';
        echo json_encode(['success' => false, 'message' => $message]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
