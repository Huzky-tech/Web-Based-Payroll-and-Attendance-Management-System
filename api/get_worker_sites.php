<?php
/**
 * Get Sites Assigned to Worker API
 * Retrieve all sites assigned to a specific worker
 */

header('Content-Type: application/json');
include 'connection/db_config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Accept both worker_id and staff_id for compatibility
    $workerId = $_GET['worker_id'] ?? $_GET['staff_id'] ?? 0;
    
    if (empty($workerId)) {
        echo json_encode(['success' => false, 'message' => 'Worker ID is required']);
        exit;
    }
    
    $sql = "SELECT 
                wa.AssignmentID,
                wa.WorkerID,
                wa.SiteID,
                wa.Created_at,
                wa.Updated_at,
                ps.Site_Name,
                ps.Location,
                ps.Project_Type,
                ps.Start_Date,
                ps.End_Date,
                ps.Required_Workers,
                ps.Site_Manager,
                ps.Status AS site_status,
                (SELECT COUNT(*) FROM WorkerAssignment w WHERE w.SiteID = ps.SiteID) AS current_workers
            FROM workerassignment wa
            INNER JOIN projectsite ps ON wa.SiteID = ps.SiteID
            WHERE wa.WorkerID = ?
            ORDER BY ps.Site_Name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $workerId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sites = [];
    while ($row = $result->fetch_assoc()) {
        $sites[] = $row;
    }
    
    // Get worker info
    $workerSql = "SELECT w.WorkerID, CONCAT(w.First_Name, ' ', w.Last_Name) AS full_name, w.Phone 
                 FROM worker w
                 WHERE w.WorkerID = ?";
    $workerStmt = $conn->prepare($workerSql);
    $workerStmt->bind_param("i", $workerId);
    $workerStmt->execute();
    $workerResult = $workerStmt->get_result();
    
    $workerInfo = null;
    if ($workerResult->num_rows > 0) {
        $workerInfo = $workerResult->fetch_assoc();
    }
    $workerStmt->close();
    
    echo json_encode([
        'success' => true, 
        'sites' => $sites,
        'count' => count($sites),
        'staff' => $workerInfo
    ]);
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

