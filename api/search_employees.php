<?php
/**
 * Search Employees API
 * Search employees by name or employee ID
 */

header('Content-Type: application/json');
include 'connection/db_config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $searchTerm = $_GET['search'] ?? '';
    
    if (empty($searchTerm)) {
        echo json_encode(['success' => false, 'message' => 'Search term is required']);
        exit;
    }
    
    // Search by name (first or last) or employee ID
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
                ps.Site_Name
            FROM worker w
            LEFT JOIN workerstatus ws ON w.WorkerStatusID = ws.WorkerStatusID
            LEFT JOIN workerassignment wa ON w.WorkerID = wa.WorkerID
            LEFT JOIN projectsite ps ON wa.SiteID = ps.SiteID
            WHERE w.WorkerID LIKE ? 
               OR w.First_Name LIKE ? 
               OR w.Last_Name LIKE ?
               OR CONCAT(w.First_Name, ' ', w.Last_Name) LIKE ?
            ORDER BY w.Last_Name, w.First_Name";
    
    $searchPattern = "%$searchTerm%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $searchPattern, $searchPattern, $searchPattern, $searchPattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    
    echo json_encode([
        'success' => true, 
        'employees' => $employees,
        'count' => count($employees)
    ]);
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

