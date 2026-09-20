<?php

require_once __DIR__ . '/mobile_auth_helpers.php';
require_once __DIR__ . '/record_audit_log.php';
require_once __DIR__ . '/site_schedule_helpers.php';

if (!function_exists('mobile_validate_attendance_record_access')) {
    function mobile_validate_attendance_record_access(mysqli $conn, int $timekeeperId, int $attendanceId): ?array
    {
        if ($attendanceId <= 0) {
            return null;
        }

        $stmt = $conn->prepare('SELECT WorkerID, SiteID FROM attendance WHERE AttendanceID = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $attendanceId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!$row) {
            return null;
        }

        $workerId = (int) ($row['WorkerID'] ?? 0);
        $siteId = (int) ($row['SiteID'] ?? 0);
        $error = validate_timekeeper_worker_access($conn, $timekeeperId, $workerId, $siteId);

        if ($error !== null) {
            mobile_json_error($error);
        }

        return $row;
    }
}
