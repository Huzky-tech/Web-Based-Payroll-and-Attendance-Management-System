<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'connection/db_config.php';

try {
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    
    $pdo = getDbConnection();
    
    // Get stats for active sites
    $stats_sql = "
        SELECT 
            ps.SiteID,
            ps.Site_Name,
            COUNT(DISTINCT a.WorkerID) as total_workers,
            SUM(CASE WHEN a.AttendanceStatus = 'Present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN a.AttendanceStatus = 'Late' THEN 1 ELSE 0 END) as late_count,
            SUM(CASE WHEN a.AttendanceStatus = 'Absent' OR a.AttendanceID IS NULL THEN 1 ELSE 0 END) as absent_count
        FROM projectsite ps
        LEFT JOIN workerassignment wa ON ps.SiteID = wa.SiteID
        LEFT JOIN worker w ON wa.WorkerID = w.WorkerID
        LEFT JOIN attendance a ON w.WorkerID = a.WorkerID AND a.SiteID = ps.SiteID AND a.Date = ?
        WHERE ps.Status = 'Active'
        GROUP BY ps.SiteID, ps.Site_Name
        ORDER BY present_count DESC
    ";
    
    $stmt = $pdo->prepare($stats_sql);
    $stmt->execute([$date]);
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ensure absent_count reflects workers without attendance
    foreach ($stats as &$stat) {
        $stat['absent_count'] = max(0, $stat['total_workers'] - $stat['present_count'] - $stat['late_count']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $stats,
        'date' => $date
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

