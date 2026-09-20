<?php
/**
 * Returns workers assigned to the Timekeeper's site only.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

include 'connection/db_config.php';
require_once __DIR__ . '/mobile_auth_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    mobile_json_error('Invalid request method');
}

$timekeeperId = mobile_parse_timekeeper_id([$_GET]);
mobile_validate_timekeeper($conn, $timekeeperId);
$assignedSite = mobile_require_assigned_site($conn, $timekeeperId);
$siteId = (int) $assignedSite['site_id'];

$sql = "
    SELECT
        w.WorkerID,
        w.First_Name,
        w.Last_Name,
        w.Phone,
        wa.Role_On_Site,
        ps.SiteID,
        ps.Site_Name,
        ps.ShiftStart,
        ps.ShiftEnd
    FROM worker w
    INNER JOIN workerassignment wa ON wa.WorkerID = w.WorkerID AND wa.SiteID = ?
    INNER JOIN projectsite ps ON ps.SiteID = wa.SiteID
    ORDER BY w.Last_Name ASC, w.First_Name ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    mobile_json_error('Database error', 500);
}

$stmt->bind_param('i', $siteId);
$stmt->execute();
$result = $stmt->get_result();

$workers = [];
while ($row = $result->fetch_assoc()) {
    $name = trim(($row['First_Name'] ?? '') . ' ' . ($row['Last_Name'] ?? ''));
    $workers[] = [
        'worker_id' => (string) $row['WorkerID'],
        'WorkerID' => (int) $row['WorkerID'],
        'name' => $name,
        'phone' => (string) ($row['Phone'] ?? ''),
        'role' => (string) ($row['Role_On_Site'] ?? ''),
        'site_id' => (int) $row['SiteID'],
        'site_name' => (string) ($row['Site_Name'] ?? ''),
        'assigned_site' => (string) ($row['Site_Name'] ?? ''),
        'shift_start' => substr((string) ($row['ShiftStart'] ?? ''), 0, 5),
        'shift_end' => substr((string) ($row['ShiftEnd'] ?? ''), 0, 5),
    ];
}

$stmt->close();

echo json_encode([
    'status' => 'success',
    'site_id' => $siteId,
    'site_name' => $assignedSite['site_name'],
    'location' => $assignedSite['location'],
    'shift_start' => $assignedSite['shift_start'],
    'lunch_start' => $assignedSite['lunch_start'],
    'lunch_end' => $assignedSite['lunch_end'],
    'shift_end' => $assignedSite['shift_end'],
    'assigned_workers' => $assignedSite['assigned_workers'],
    'workers' => $workers,
]);

$conn->close();
