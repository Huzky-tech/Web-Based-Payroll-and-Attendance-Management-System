<?php
/**
 * Timekeeper mobile dashboard statistics scoped to assigned site.
 */

require_once 'connection/db_config.php';
require_once __DIR__ . '/mobile_auth_helpers.php';
require_once __DIR__ . '/overtime_helpers.php';

mobile_json_headers();
mobile_handle_options();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    mobile_json_error('Invalid request method.', 405);
}

$payload = mobile_get_request_payload();
$timekeeperId = mobile_resolve_timekeeper_id($payload);
$assignment = mobile_require_timekeeper_assignment($conn, $timekeeperId);
$siteId = (int) ($assignment['SiteID'] ?? 0);
$today = date('Y-m-d');

$totalWorkers = 0;
$presentToday = 0;
$absentToday = 0;
$pendingOvertime = 0;

$workerCountStmt = $conn->prepare('SELECT COUNT(*) AS total FROM workerassignment WHERE SiteID = ?');
if ($workerCountStmt) {
    $workerCountStmt->bind_param('i', $siteId);
    $workerCountStmt->execute();
    $countResult = $workerCountStmt->get_result();
    $countRow = $countResult ? $countResult->fetch_assoc() : null;
    $totalWorkers = (int) ($countRow['total'] ?? 0);
    $workerCountStmt->close();
}

$attendanceStmt = $conn->prepare("
    SELECT
        SUM(CASE WHEN COALESCE(a.AttendanceStatus, 'Absent') IN ('Present', 'Late') THEN 1 ELSE 0 END) AS present_count,
        SUM(CASE WHEN COALESCE(a.AttendanceStatus, 'Absent') = 'Absent' THEN 1 ELSE 0 END) AS absent_count
    FROM workerassignment wa
    LEFT JOIN attendance a ON a.WorkerID = wa.WorkerID AND a.SiteID = wa.SiteID AND a.Date = ?
    WHERE wa.SiteID = ?
");

if ($attendanceStmt) {
    $attendanceStmt->bind_param('si', $today, $siteId);
    $attendanceStmt->execute();
    $attendanceResult = $attendanceStmt->get_result();
    $attendanceRow = $attendanceResult ? $attendanceResult->fetch_assoc() : null;
    $presentToday = (int) ($attendanceRow['present_count'] ?? 0);
    $absentToday = (int) ($attendanceRow['absent_count'] ?? 0);
    $attendanceStmt->close();
}

if (overtime_table_exists($conn)) {
    $otStmt = $conn->prepare("
        SELECT COUNT(*) AS pending_count
        FROM overtime_requests
        WHERE SiteID = ? AND SubmittedBy = ? AND Status = 'Pending'
    ");
    if ($otStmt) {
        $otStmt->bind_param('ii', $siteId, $timekeeperId);
        $otStmt->execute();
        $otResult = $otStmt->get_result();
        $otRow = $otResult ? $otResult->fetch_assoc() : null;
        $pendingOvertime = (int) ($otRow['pending_count'] ?? 0);
        $otStmt->close();
    }
}

echo json_encode([
    'status' => 'success',
    'site' => [
        'SiteID' => $siteId,
        'Site_Name' => (string) ($assignment['Site_Name'] ?? ''),
        'Location' => (string) ($assignment['Location'] ?? ''),
    ],
    'total_workers' => $totalWorkers,
    'present_today' => $presentToday,
    'absent_today' => $absentToday,
    'pending_overtime_requests' => $pendingOvertime,
]);

$conn->close();
