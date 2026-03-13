<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'connection/db_config.php';

try {
    $site_id = isset($_GET['site_id']) ? (int)$_GET['site_id'] : 0;
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    
    if ($site_id === 0) {
        throw new Exception('Site ID required');
    }
    
    // Get workers assigned to site
    $workers_sql = "
        SELECT DISTINCT w.WorkerID, w.First_Name, w.Last_Name, w.WorkerID as ID
        FROM worker w
        INNER JOIN workerassignment wa ON w.WorkerID = wa.WorkerID
        WHERE wa.SiteID = ?
    ";
    $workers_stmt = $conn->prepare($workers_sql);
    $workers_stmt->bind_param("i", $site_id);
    $workers_stmt->execute();
    $workers_result = $workers_stmt->get_result();
    $workers = [];
    while ($row = $workers_result->fetch_assoc()) {
        $workers[] = $row;
    }
    
    $attendance = [];
    foreach ($workers as &$worker) {
        $att_sql = "
            SELECT AttendanceID, Time_In, Time_Out, Hours_Worked, AttendanceStatus
            FROM attendance 
            WHERE WorkerID = ? AND SiteID = ? AND Date = ?
        ";
        $att_stmt = $conn->prepare($att_sql);
        $att_stmt->bind_param("iis", $worker['WorkerID'], $site_id, $date);
        $att_stmt->execute();
        $att_result = $att_stmt->get_result();
        $record = $att_result->fetch_assoc();
        
        $worker['Time_In'] = $record ? $record['Time_In'] : null;
        $worker['Time_Out'] = $record ? $record['Time_Out'] : null;
        $worker['AttendanceStatus'] = $record ? $record['AttendanceStatus'] : 'Absent';
        $worker['AttendanceID'] = $record ? $record['AttendanceID'] : null;
        $worker['Hours_Worked'] = $record ? $record['Hours_Worked'] : 0;
        
        $attendance[] = $worker;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $attendance,
        'date' => $date,
        'site_id' => $site_id
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

