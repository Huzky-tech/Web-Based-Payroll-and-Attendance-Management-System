<?php
/**
 * Get Sites Assigned to Staff API
 * Retrieve all sites assigned to a specific payroll staff member
 */

header('Content-Type: application/json');
include 'connection/db_config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Accept both staff_id and payroll_staff_id for compatibility
    $staffId = $_GET['staff_id'] ?? $_GET['payroll_staff_id'] ?? 0;
    
    if (empty($staffId)) {
        echo json_encode(['success' => false, 'message' => 'Staff ID is required']);
        exit;
    }
    
    $sql = "SELECT 
                psa.staffAssignID,
                psa.PayrollStaff_ID,
                psa.SiteID,
                psa.Created_at,
                psa.Updated_at,
                ps.Site_Name,
                ps.Location,
                ps.Project_Type,
                ps.Start_Date,
                ps.End_Date,
                ps.Required_Workers,
                ps.Site_Manager,
                ps.Status AS site_status,
                (SELECT COUNT(*) FROM WorkerAssignment wa WHERE wa.SiteID = ps.SiteID) AS current_workers
            FROM payrollstaffassignment psa
            INNER JOIN projectsite ps ON psa.SiteID = ps.SiteID
            WHERE psa.PayrollStaff_ID = ?
            ORDER BY ps.Site_Name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $staffId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sites = [];
    while ($row = $result->fetch_assoc()) {
        $sites[] = $row;
    }
    
    // Get staff info
    $staffSql = "SELECT ps.PayrollStaff_ID, u.full_name, u.email 
                 FROM payrollstaff ps
                 JOIN users u ON ps.UserID = u.id
                 WHERE ps.PayrollStaff_ID = ?";
    $staffStmt = $conn->prepare($staffSql);
    $staffStmt->bind_param("i", $staffId);
    $staffStmt->execute();
    $staffResult = $staffStmt->get_result();
    
    $staffInfo = null;
    if ($staffResult->num_rows > 0) {
        $staffInfo = $staffResult->fetch_assoc();
    }
    $staffStmt->close();
    
    echo json_encode([
        'success' => true, 
        'sites' => $sites,
        'count' => count($sites),
        'staff' => $staffInfo
    ]);
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

