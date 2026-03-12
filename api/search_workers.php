<?php
/**
 * Search Workers API
 * Search workers by name or phone
 */

header('Content-Type: application/json');
include 'connection/db_config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $keyword = $_GET['keyword'] ?? '';
    
    if (empty($keyword)) {
        // If no keyword, return all workers
        $sql = "SELECT 
                    w.WorkerID,
                    CONCAT(w.First_Name, ' ', w.Last_Name) AS full_name,
                    w.Phone,
                    (SELECT COUNT(*) FROM workerassignment wa WHERE wa.WorkerID = w.WorkerID) AS assigned_sites_count
                FROM worker w
                ORDER BY w.Last_Name, w.First_Name";
        
        $result = $conn->query($sql);
    } else {
        // Search by name or phone
        $searchTerm = '%' . $keyword . '%';
        $sql = "SELECT 
                    w.WorkerID,
                    CONCAT(w.First_Name, ' ', w.Last_Name) AS full_name,
                    w.Phone,
                    (SELECT COUNT(*) FROM workerassignment wa WHERE wa.WorkerID = w.WorkerID) AS assigned_sites_count
                FROM worker w
                WHERE w.First_Name LIKE ? OR w.Last_Name LIKE ? OR w.Phone LIKE ?
                ORDER BY w.Last_Name, w.First_Name";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
    }
    
    $workers = [];
    while ($row = $result->fetch_assoc()) {
        $workers[] = [
            'id' => $row['WorkerID'],
            'name' => $row['full_name'] ?? 'Unknown',
            'email' => $row['Phone'],
            'assigned_sites_count' => (int)$row['assigned_sites_count']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'staff' => $workers,
        'count' => count($workers)
    ]);
    
    if (isset($stmt)) {
        $stmt->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

