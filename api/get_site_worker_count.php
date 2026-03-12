<?php
/**
 * Get Site Worker Count API
 * Return the number of workers assigned to a site
 */

header('Content-Type: application/json');
include 'connection/db_config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $site_id = $_GET['site_id'] ?? 0;
    
    if (empty($site_id)) {
        echo json_encode(['success' => false, 'message' => 'Site ID is required']);
        exit;
    }
    
    $sql = "SELECT COUNT(*) as count 
            FROM workerassignment 
            WHERE SiteID = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $site_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $count = $result->fetch_assoc()['count'] ?? 0;
        
        // Also get site info
        $siteSql = "SELECT SiteID, Site_Name, Location, Required_Workers, Status 
                    FROM projectsite 
                    WHERE SiteID = ?";
        $siteStmt = $conn->prepare($siteSql);
        $siteStmt->bind_param("i", $site_id);
        $siteStmt->execute();
        $siteResult = $siteStmt->get_result();
        
        $siteInfo = null;
        if ($siteResult->num_rows > 0) {
            $siteInfo = $siteResult->fetch_assoc();
        }
        $siteStmt->close();
        
        echo json_encode([
            'success' => true,
            'site_id' => $site_id,
            'current_workers' => (int)$count,
            'site' => $siteInfo
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to get worker count'
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

