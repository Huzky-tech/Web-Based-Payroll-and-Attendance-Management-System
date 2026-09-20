<?php
/**
 * Returns attendance records for the Timekeeper's assigned site only.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/connection/db_config.php';
require_once __DIR__ . '/mobile_auth_helpers.php';
require_once __DIR__ . '/attendance_schema_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    mobile_json_error('Invalid request method');
}

$timekeeperId = mobile_parse_timekeeper_id([$_GET]);
mobile_validate_timekeeper($conn, $timekeeperId);
$assignedSite = mobile_require_assigned_site($conn, $timekeeperId);
$siteId = (int) $assignedSite['site_id'];
attendance_schema_ensure_table($conn);

$date = trim((string) ($_GET['date'] ?? ''));
if ($date === '') {
    $date = date('Y-m-d');
}

$sql = "
    SELECT
        a.AttendanceID,
        a.WorkerID,
        a.SiteID,
        a.Date,
        a.Time_In,
        a.Time_Out,
        a.AttendanceStatus,
        a.IsLate,
        w.First_Name,
        w.Last_Name,
        wa.Role_On_Site,
        ps.Site_Name
    FROM attendance a
    INNER JOIN worker w ON w.WorkerID = a.WorkerID
    INNER JOIN projectsite ps ON ps.SiteID = a.SiteID
    LEFT JOIN workerassignment wa ON wa.WorkerID = a.WorkerID AND wa.SiteID = a.SiteID
    WHERE a.SiteID = ? AND a.Date = ?
    ORDER BY a.Time_In DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    mobile_json_error('Database error', 500);
}

$stmt->bind_param('is', $siteId, $date);
$stmt->execute();
$result = $stmt->get_result();

$records = [];
while ($row = $result->fetch_assoc()) {
    $workerName = trim(($row['First_Name'] ?? '') . ' ' . ($row['Last_Name'] ?? ''));
    $timeOut = $row['Time_Out'] ?? null;
    if ($timeOut === '00:00:00') {
        $timeOut = null;
    }

    $records[] = [
        'worker_id' => (string) $row['WorkerID'],
        'worker_name' => $workerName,
        'role' => (string) ($row['Role_On_Site'] ?? ''),
        'status' => (string) ($row['AttendanceStatus'] ?? 'Present'),
        'is_late' => (int) ($row['IsLate'] ?? 0),
        'date' => (string) $row['Date'],
        'time_in' => (string) $row['Time_In'],
        'time_out' => $timeOut,
        'site_id' => (string) $row['SiteID'],
        'location' => (string) ($row['Site_Name'] ?? ''),
    ];
}

$stmt->close();

echo json_encode([
    'status' => 'success',
    'site_id' => $siteId,
    'site_name' => $assignedSite['site_name'],
    'date' => $date,
    'records' => $records,
]);

$conn->close();

