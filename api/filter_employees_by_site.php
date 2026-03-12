<?php
/**
 * Filter Employees by Site API
 * Returns employees assigned to a specific construction site
 */

header('Content-Type: application/json');
include 'connection/db_config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $siteId = $_GET['site_id'] ?? 0;
    
    if (empty($siteId)) {
        echo json_encode(['success' => false, 'message' => 'Site ID is required']);
        exit;
    }
    
    $sql = "SELECT 
                w.WorkerID,
                w.First_Name,
                w.Last_Name,
                CONCAT(w.First_Name, ' ', w.Last_Name) AS full_name,
                w.RateType,
                w.RateAmount AS salary,
                w.DateHired AS join_date,
                ws.Status AS worker_status,
                wa.SiteID,
                ps.Site_Name,
                wa.Role_On_Site AS position,
                wa.Assigned_Date
            FROM worker w
            INNER JOIN workerassignment wa ON w.WorkerID = wa.WorkerID
            LEFT JOIN workerstatus ws ON w.WorkerStatusID = ws.WorkerStatusID
            LEFT JOIN projectsite ps ON wa.SiteID = ps.SiteID
            WHERE wa.SiteID = ?
            ORDER BY w.Last_Name, w.First_Name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $siteId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    
    echo json_encode([
        'success' => true, 
        'employees' => $employees,
        'count' => count($employees),
        'site_id' => $siteId
    ]);
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

