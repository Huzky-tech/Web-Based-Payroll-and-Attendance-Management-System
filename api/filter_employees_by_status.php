<?php
/**
 * Filter Employees by Status API
 * Returns employees based on status (Active, Inactive, On Leave)
 */

header('Content-Type: application/json');
include 'connection/db_config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $status = $_GET['status'] ?? '';
    
    if (empty($status)) {
        echo json_encode(['success' => false, 'message' => 'Status is required']);
        exit;
    }
    
    // Validate status
    $validStatuses = ['Active', 'Inactive', 'OnLeave'];
    if (!in_array($status, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status. Use: Active, Inactive, or OnLeave']);
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
                wa.Role_On_Site AS position
            FROM worker w
            INNER JOIN workerstatus ws ON w.WorkerStatusID = ws.WorkerStatusID
            LEFT JOIN workerassignment wa ON w.WorkerID = wa.WorkerID
            LEFT JOIN projectsite ps ON wa.SiteID = ps.SiteID
            WHERE ws.Status = ?
            ORDER BY w.Last_Name, w.First_Name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $status);
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
        'status' => $status
    ]);
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

