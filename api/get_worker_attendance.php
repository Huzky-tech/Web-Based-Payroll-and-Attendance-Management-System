<?php
header('Content-Type: application/json');

include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/timekeeper_assignment_helpers.php';
require_once __DIR__ . '/attendance_photo_helpers.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR', 'Timekeeper']);
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
require_once __DIR__ . '/attendance_schema_helpers.php';
attendance_schema_ensure_table($conn);

$workerId = (int) ($_GET['worker_id'] ?? 0);
$limit = (int) ($_GET['limit'] ?? 30);
$limit = max(1, min($limit, 100));
$days = (int) ($_GET['days'] ?? 30);
$days = max(7, min($days, 365));

if ($workerId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Worker ID is required']);
    exit;
}

if ($currentRole === 'Timekeeper') {
    $assignedSiteId = get_timekeeper_assigned_site_id($conn, $currentUserId);
    if ($assignedSiteId <= 0) {
        echo json_encode(['success' => false, 'message' => 'No site assigned to this Timekeeper.']);
        exit;
    }

    $accessError = validate_timekeeper_worker_access($conn, $currentUserId, $workerId, $assignedSiteId);
    if ($accessError !== null) {
        echo json_encode(['success' => false, 'message' => $accessError]);
        exit;
    }
}

$siteFilterSql = '';
$bindTypes = 'iii';
$bindValues = [$workerId, $days, $limit];

if ($currentRole === 'Timekeeper') {
    $assignedSiteId = get_timekeeper_assigned_site_id($conn, $currentUserId);
    $siteFilterSql = ' AND a.SiteID = ?';
    $bindTypes = 'iiii';
    $bindValues = [$workerId, $assignedSiteId, $days, $limit];
}

$sql = "SELECT 
            a.AttendanceID,
            a.Date,
            a.Time_In,
            a.Time_Out,
            a.Hours_Worked,
            a.AttendanceStatus, a.IsLate,
            ps.Site_Name,
            " . attendance_photo_select_columns($conn) . "
        FROM attendance a
        INNER JOIN projectsite ps ON a.SiteID = ps.SiteID
        WHERE a.WorkerID = ?
          {$siteFilterSql}
          AND a.Date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        ORDER BY a.Date DESC, a.AttendanceID DESC
        LIMIT ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to load attendance records']);
    exit;
}

$stmt->bind_param($bindTypes, ...$bindValues);
$stmt->execute();
$result = $stmt->get_result();

$records = [];
$presentCount = 0;
$lateCount = 0;
$absentCount = 0;
$hoursTotal = 0.0;
$workedDays = 0;

while ($row = $result->fetch_assoc()) {
    $records[] = $row;
    $status = $row['AttendanceStatus'] ?? '';
    if ((int) ($row['IsLate'] ?? 0) === 1) $lateCount++;
    if ($status === 'Present') {
        $presentCount++;
        $workedDays++;
        $hoursTotal += (float) ($row['Hours_Worked'] ?? 0);

    } elseif ($status === 'Absent') {
        $absentCount++;
    }
}
$stmt->close();

$totalDays = count($records);
$attendanceRate = $totalDays > 0 ? round(($workedDays / $totalDays) * 100) : 0;
$avgHours = $workedDays > 0 ? round($hoursTotal / $workedDays, 1) : 0;

$conn->close();

echo json_encode([
    'success' => true,
    'records' => $records,
    'summary' => [
        'days_filter' => $days,
        'total_days' => $totalDays,
        'attendance_rate' => $attendanceRate,
        'avg_hours' => $avgHours,
        'late_arrivals' => $lateCount,
        'absences' => $absentCount,
        'present_days' => $presentCount,
    ],
]);
