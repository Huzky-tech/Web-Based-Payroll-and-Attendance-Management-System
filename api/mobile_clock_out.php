<?php
require_once 'connection/db_config.php';
require_once __DIR__ . '/mobile_attendance_helpers.php';

mobile_json_headers();
mobile_handle_options();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mobile_json_error('Method not allowed', 405);
}

$payload = mobile_get_request_payload();
$timekeeperId = mobile_resolve_timekeeper_id($payload);
mobile_require_timekeeper_assignment($conn, $timekeeperId);

$attendanceId = (int) ($payload['attendance_id'] ?? 0);
mobile_validate_attendance_record_access($conn, $timekeeperId, $attendanceId);

$currentTime = date('H:i:s');

try {
    $pdo = getDbConnection();
    $pdo->beginTransaction();

    $lunchSelect = attendance_lunch_columns_exist($conn)
        ? 'Time_In, Lunch_Out, Lunch_In'
        : 'Time_In';

    $checkSql = "SELECT WorkerID, SiteID, Date, {$lunchSelect} FROM attendance WHERE AttendanceID = ? AND (Time_Out IS NULL OR Time_Out = '00:00:00')";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$attendanceId]);
    $record = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        throw new Exception('No open clock-in found for this record');
    }

    $siteId = (int) ($record['SiteID'] ?? 0);
    $siteSchedule = get_site_schedule_row($conn, $siteId);
    $lunchOut = $record['Lunch_Out'] ?? null;
    $lunchIn = $record['Lunch_In'] ?? null;

    if (attendance_lunch_columns_exist($conn)) {
        if (empty($lunchOut) && !empty($siteSchedule['LunchStart'])) {
            $lunchOut = $siteSchedule['LunchStart'];
        }
        if (empty($lunchIn) && !empty($siteSchedule['LunchEnd'])) {
            $lunchIn = $siteSchedule['LunchEnd'];
        }
    }

    $hourTotals = calculate_attendance_hours_worked(
        $record['Time_In'] ?? null,
        $lunchOut,
        $lunchIn,
        $currentTime,
        $siteSchedule
    );

    $hoursWorked = (float) ($hourTotals['hours_worked'] ?? 0);
    $overtimeHours = (float) ($hourTotals['overtime_hours'] ?? 0);
    $shiftStart = (string) ($siteSchedule['ShiftStart'] ?? '07:00:00');
    $expectedInMin = (site_schedule_time_to_minutes($shiftStart) ?? 420) + 30;
    $timeInMin = site_schedule_time_to_minutes($record['Time_In'] ?? null) ?? 0;
    $status = $timeInMin <= $expectedInMin ? 'Present' : 'Late';

    if (attendance_lunch_columns_exist($conn)) {
        $updateSql = "
            UPDATE attendance
            SET Time_Out = ?, Lunch_Out = COALESCE(Lunch_Out, ?), Lunch_In = COALESCE(Lunch_In, ?),
                Hours_Worked = ?, Overtime_Hours = ?, AttendanceStatus = ?
            WHERE AttendanceID = ?
        ";
        $stmt = $pdo->prepare($updateSql);
        $stmt->execute([$currentTime, $lunchOut, $lunchIn, $hoursWorked, $overtimeHours, $status, $attendanceId]);
    } else {
        $updateSql = "UPDATE attendance SET Time_Out = ?, Hours_Worked = ?, Overtime_Hours = ?, AttendanceStatus = ? WHERE AttendanceID = ?";
        $stmt = $pdo->prepare($updateSql);
        $stmt->execute([$currentTime, $hoursWorked, $overtimeHours, $status, $attendanceId]);
    }

    $pdo->commit();
    record_audit_log($timekeeperId, 'Mobile Clock Out', "Worker {$record['WorkerID']} clocked out at site {$record['SiteID']}");

    echo json_encode([
        'status' => 'success',
        'success' => true,
        'attendance_id' => $attendanceId,
        'time_out' => $currentTime,
        'hours_worked' => number_format($hoursWorked, 2),
        'overtime_hours' => number_format($overtimeHours, 2),
        'attendance_status' => $status,
        'message' => 'Clocked out successfully',
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    mobile_json_error($e->getMessage());
}

$conn->close();
