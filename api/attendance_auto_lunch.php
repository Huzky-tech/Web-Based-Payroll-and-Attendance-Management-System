<?php
require_once __DIR__ . '/attendance_schema_helpers.php';

/** Materialize today's noon punch only after noon has actually arrived. */
function attendance_apply_due_lunch_out(mysqli $conn, int $attendanceId = 0): void
{
    $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
    if ($now->format('H:i:s') < '12:00:00' || !attendance_lunch_columns_exist($conn)) {
        return;
    }
    $today = $now->format('Y-m-d');
    $sql = "UPDATE attendance SET Lunch_Out = '12:00:00'
        WHERE Date = ? AND Time_In IS NOT NULL AND Time_In <> '00:00:00'
          AND Time_In <= '12:00:00'
          AND (Time_Out IS NULL OR Time_Out = '00:00:00' OR Time_Out >= '12:00:00')
          AND (Lunch_Out IS NULL OR Lunch_Out = '' OR Lunch_Out = '00:00:00')";
    if ($attendanceId > 0) $sql .= ' AND AttendanceID = ?';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('Automatic AM OUT preparation failed: ' . $conn->error);
        return;
    }
    if ($attendanceId > 0) $stmt->bind_param('si', $today, $attendanceId);
    else $stmt->bind_param('s', $today);
    if (!$stmt->execute()) error_log('Automatic AM OUT failed: ' . $stmt->error);
    $stmt->close();
}
