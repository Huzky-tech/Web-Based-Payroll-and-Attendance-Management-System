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
require_once 'record_audit_log.php';

$input = json_decode(file_get_contents('php://input'), true);
$attendance_id = (int)$input['attendance_id'] ?? 0;
$current_time = date('H:i:s');

try {
    if ($attendance_id <= 0) {
        throw new Exception('Attendance ID required');
    }
    
    $pdo = getDbConnection();
    $pdo->beginTransaction();
    
    // Get current record
    $check_sql = "SELECT WorkerID, SiteID, Date, Time_In FROM attendance WHERE AttendanceID = ? AND Time_Out IS NULL";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$attendance_id]);
    $record = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        throw new Exception('No open clock-in found for this record');
    }
    
    $time_in = new DateTime($record['Time_In']);
    $time_out = new DateTime($current_time);
    $hours_worked = $time_out->diff($time_in)->h + ($time_out->diff($time_in)->i / 60);
    $overtime_hours = max(0, $hours_worked - 8);
    $hours_worked = min($hours_worked, 8) + $overtime_hours; // Cap regular at 8hrs
    
    // Determine status: Late if in > 8:30 AM (assuming 8AM start)
    $expected_in = new DateTime('08:30:00');
    $status = ($time_in < $expected_in) ? 'Present' : 'Late';
    
    $update_sql = "
        UPDATE attendance 
        SET Time_Out = ?, Hours_Worked = ?, Overtime_Hours = ?, AttendanceStatus = ?
        WHERE AttendanceID = ?
    ";
    $stmt = $pdo->prepare($update_sql);
    $stmt->execute([$current_time, $hours_worked, $overtime_hours, $status, $attendance_id]);
    
    $pdo->commit();
    
    // Audit log
    $user_id = $_SESSION['user_id'] ?? 1;
    record_audit_log($user_id, 'Clock Out', "Worker {$record['WorkerID']} clocked out at site {$record['SiteID']}, {$hours_worked}hrs");
    
    echo json_encode([
        'success' => true,
        'attendance_id' => $attendance_id,
        'time_out' => $current_time,
        'hours_worked' => number_format($hours_worked, 2),
        'overtime_hours' => number_format($overtime_hours, 2),
        'status' => $status,
        'message' => 'Clocked out successfully'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

