<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once 'connection/db_config.php';
require_once 'record_audit_log.php'; // For logging

$input = json_decode(file_get_contents('php://input'), true);
$worker_id = (int)$input['worker_id'] ?? 0;
$site_id = (int)$input['site_id'] ?? 0;
$current_time = date('H:i:s');

try {
    if ($worker_id <= 0 || $site_id <= 0) {
        throw new Exception('Invalid worker or site ID');
    }
    
    $pdo = getDbConnection();
    $pdo->beginTransaction();
    
    $date = date('Y-m-d');
    
    // Check if already clocked in today
    $check_sql = "SELECT AttendanceID FROM attendance WHERE WorkerID = ? AND SiteID = ? AND Date = ? AND Time_In IS NOT NULL AND Time_Out IS NULL";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$worker_id, $site_id, $date]);
    
    if ($check_stmt->rowCount() > 0) {
        throw new Exception('Already clocked in today');
    }
    
    // Upsert attendance record
    $sql = "
        INSERT INTO attendance (WorkerID, SiteID, Date, Time_In, Hours_Worked, AttendanceStatus) 
        VALUES (?, ?, ?, ?, 0.00, 'Present') 
        ON DUPLICATE KEY UPDATE Time_In = VALUES(Time_In), AttendanceStatus = 'Present'
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$worker_id, $site_id, $date, $current_time]);
    
    $attendance_id = $pdo->lastInsertId();
    if (!$attendance_id) {
        $attendance_id = $pdo->query("SELECT AttendanceID FROM attendance WHERE WorkerID = $worker_id AND SiteID = $site_id AND Date = '$date'")->fetchColumn();
    }
    
    $pdo->commit();
    
    // Log audit (assuming logged-in user ID from session)
    $user_id = $_SESSION['user_id'] ?? 1;
    record_audit_log($user_id, 'Clock In', "Worker $worker_id clocked in at site $site_id");
    
    echo json_encode([
        'success' => true,
        'attendance_id' => $attendance_id,
        'time_in' => $current_time,
        'message' => 'Clocked in successfully'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

