<?php
/**
 * Get Site Worker Target API
 * Returns the target number of workers for a site
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
                SiteID,
                Site_Name,
                Location,
                Required_Workers AS target_workers,
                Status
            FROM projectsite 
            WHERE SiteID = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $siteId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $site = $result->fetch_assoc();
        
        // Get current workers count
        $countSql = "SELECT COUNT(*) as current_workers FROM workerassignment WHERE SiteID = ?";
        $countStmt = $conn->prepare($countSql);
        $countStmt->bind_param("i", $siteId);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $currentWorkers = $countResult->fetch_assoc()['current_workers'] ?? 0;
        $countStmt->close();
        
        $site['current_workers'] = $currentWorkers;
        
        echo json_encode([
            'success' => true,
            'site' => $site
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Site not found'
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

