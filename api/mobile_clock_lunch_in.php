<?php
require_once 'connection/db_config.php';
require_once __DIR__ . '/mobile_attendance_helpers.php';

mobile_json_headers();
mobile_handle_options();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mobile_json_error('Method not allowed', 405);
}

if (!attendance_lunch_columns_exist($conn)) {
    mobile_json_error('Lunch attendance is not enabled yet');
}

$payload = mobile_get_request_payload();
$timekeeperId = mobile_resolve_timekeeper_id($payload);
mobile_require_timekeeper_assignment($conn, $timekeeperId);

$attendanceId = (int) ($payload['attendance_id'] ?? 0);
mobile_validate_attendance_record_access($conn, $timekeeperId, $attendanceId);

$currentTime = date('H:i:s');

try {
    $pdo = getDbConnection();

    $checkSql = "SELECT WorkerID, SiteID, Time_In, Lunch_Out, Lunch_In, Time_Out FROM attendance WHERE AttendanceID = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$attendanceId]);
    $record = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$record || empty($record['Time_In'])) {
        throw new Exception('Worker must be clocked in before PM time in');
    }

    if (empty($record['Lunch_Out']) || $record['Lunch_Out'] === '00:00:00') {
        throw new Exception('Lunch out must be recorded first');
    }

    if (!empty($record['Lunch_In']) && $record['Lunch_In'] !== '00:00:00') {
        throw new Exception('PM time in has already been recorded');
    }

    $lunchInTime = $currentTime;

    $updateSql = 'UPDATE attendance SET Lunch_In = ? WHERE AttendanceID = ?';
    $stmt = $pdo->prepare($updateSql);
    $stmt->execute([$lunchInTime, $attendanceId]);

    record_audit_log($timekeeperId, 'Mobile Lunch In', "Worker {$record['WorkerID']} PM time in at site {$record['SiteID']}");

    echo json_encode([
        'status' => 'success',
        'success' => true,
        'attendance_id' => $attendanceId,
        'lunch_in' => $lunchInTime,
        'message' => 'PM time in recorded successfully',
    ]);
} catch (Exception $e) {
    mobile_json_error($e->getMessage());
}

$conn->close();
