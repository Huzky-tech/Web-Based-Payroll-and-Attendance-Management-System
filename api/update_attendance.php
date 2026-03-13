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
$updates = $input['updates'] ?? []; // e.g. ['Time_In' => '...', 'AttendanceStatus' => 'Present']

try {
    if ($attendance_id <= 0 || empty($updates)) {
        throw new Exception('Attendance ID and updates required');
    }
    
    $pdo = getDbConnection();
    
    $allowed_fields = ['Time_In', 'Time_Out', 'Hours_Worked', 'Overtime_Hours', 'AttendanceStatus'];
    $set_parts = [];
    $params = [$attendance_id];
    
    foreach ($updates as $field => $value) {
        if (in_array($field, $allowed_fields)) {
            $set_parts[] = "$field = ?";
            $params[] = $value;
        }
    }
    
    if (empty($set_parts)) {
        throw new Exception('No valid fields to update');
    }
    
    $sql = "UPDATE attendance SET " . implode(', ', $set_parts) . " WHERE AttendanceID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('No record updated');
    }
    
    $user_id = $_SESSION['user_id'] ?? 1;
    record_audit_log($user_id, 'Update Attendance', "Updated AttendanceID $attendance_id: " . json_encode($updates));
    
    echo json_encode([
        'success' => true,
        'attendance_id' => $attendance_id,
        'message' => 'Attendance updated successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

