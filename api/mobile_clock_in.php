<?php
/**
 * Mobile clock-in for Timekeepers (stateless, scoped to assigned site).
 */

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

$workerId = (int) ($payload['worker_id'] ?? $payload['WorkerID'] ?? 0);
$siteId = (int) ($payload['site_id'] ?? $payload['SiteID'] ?? 0);

$accessError = validate_timekeeper_worker_access($conn, $timekeeperId, $workerId, $siteId);
if ($accessError !== null) {
    mobile_json_error($accessError);
}

$currentTime = date('H:i:s');
$date = date('Y-m-d');

try {
    $pdo = getDbConnection();
    $pdo->beginTransaction();

    $checkSql = "SELECT AttendanceID FROM attendance WHERE WorkerID = ? AND SiteID = ? AND Date = ? AND Time_In IS NOT NULL AND Time_Out IS NULL";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$workerId, $siteId, $date]);

    if ($checkStmt->rowCount() > 0) {
        throw new Exception('Already clocked in today');
    }

    $sql = "
        INSERT INTO attendance (WorkerID, SiteID, Date, Time_In, Hours_Worked, AttendanceStatus)
        VALUES (?, ?, ?, ?, 0.00, 'Present')
        ON DUPLICATE KEY UPDATE Time_In = VALUES(Time_In), AttendanceStatus = 'Present'
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$workerId, $siteId, $date, $currentTime]);

    $attendanceId = (int) $pdo->lastInsertId();
    if ($attendanceId <= 0) {
        $lookup = $pdo->prepare('SELECT AttendanceID FROM attendance WHERE WorkerID = ? AND SiteID = ? AND Date = ? LIMIT 1');
        $lookup->execute([$workerId, $siteId, $date]);
        $attendanceId = (int) ($lookup->fetchColumn() ?: 0);
    }

    $pdo->commit();

    record_audit_log($timekeeperId, 'Mobile Clock In', "Worker {$workerId} clocked in at site {$siteId}");

    echo json_encode([
        'status' => 'success',
        'success' => true,
        'attendance_id' => $attendanceId,
        'time_in' => $currentTime,
        'message' => 'Clocked in successfully',
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    mobile_json_error($e->getMessage());
}

$conn->close();
