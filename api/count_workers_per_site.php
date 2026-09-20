<?php
/**
 * Count Workers Per Site API
 * Returns the number of employees assigned to each site
 */

header('Content-Type: application/json');
include 'connection/db_config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $siteId = $_GET['site_id'] ?? 0;
    
    if (!empty($siteId)) {
        // Get count for specific site
        $sql = "SELECT 
                    ps.SiteID,
                    ps.Site_Name,
                    ps.Location,
                    ps.Required_Workers,
                    COUNT(wa.WorkerID) AS current_workers
                FROM projectsite ps
                LEFT JOIN workerassignment wa ON ps.SiteID = wa.SiteID
                WHERE ps.SiteID = ?
                GROUP BY ps.SiteID";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $siteId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $site = $result->fetch_assoc();
            
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
        // Get count for all sites
        $sql = "SELECT 
                    ps.SiteID,
                    ps.Site_Name,
                    ps.Location,
                    ps.Required_Workers,
                    COUNT(wa.WorkerID) AS current_workers
                FROM projectsite ps
                LEFT JOIN workerassignment wa ON ps.SiteID = wa.SiteID
                GROUP BY ps.SiteID
                ORDER BY ps.Site_Name";
        
        $result = $conn->query($sql);
        
        $sites = [];
        while ($row = $result->fetch_assoc()) {
            $sites[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'sites' => $sites
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

