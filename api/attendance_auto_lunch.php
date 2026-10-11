<?php
require_once __DIR__ . '/attendance_schema_helpers.php';

/**
 * Materialize AM OUT at each site's configured lunch-start time.
 *
 * A site can have a different lunch schedule, so this must not assume noon.
 */
function attendance_apply_due_lunch_out(mysqli $conn, int $attendanceId = 0): int
{
    $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
    if (!attendance_lunch_columns_exist($conn)) {
        return 0;
    }
    $today = $now->format('Y-m-d');
    $currentTime = $now->format('H:i:s');
    $lunchStart = "COALESCE(NULLIF(ps.LunchStart, ''), '12:00:00')";
    $sql = "UPDATE attendance a
        INNER JOIN projectsite ps ON ps.SiteID = a.SiteID
        SET a.Lunch_Out = $lunchStart
        WHERE a.Date = ? AND a.Time_In IS NOT NULL AND a.Time_In <> '00:00:00'
          AND a.Time_In <= $lunchStart
          AND ? >= $lunchStart
          AND (a.Time_Out IS NULL OR a.Time_Out = '00:00:00' OR a.Time_Out >= $lunchStart)
          AND (a.Lunch_Out IS NULL OR a.Lunch_Out = '' OR a.Lunch_Out = '00:00:00')";
    if ($attendanceId > 0) $sql .= ' AND AttendanceID = ?';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('Automatic AM OUT preparation failed: ' . $conn->error);
        return 0;
    }
    if ($attendanceId > 0) $stmt->bind_param('ssi', $today, $currentTime, $attendanceId);
    else $stmt->bind_param('ss', $today, $currentTime);
    if (!$stmt->execute()) {
        error_log('Automatic AM OUT failed: ' . $stmt->error);
        $stmt->close();
        return 0;
    }
    $updated = $stmt->affected_rows;
    $stmt->close();
    return $updated;
}
