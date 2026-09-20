<?php
/**
 * Returns overtime requests for the Timekeeper's assigned site only.
 * Standalone endpoint used by the Flutter app (no require of mobile_get_overtime.php).
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
require_once __DIR__ . '/overtime_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    mobile_json_error('Invalid request method');
}

$timekeeperId = mobile_parse_timekeeper_id([$_GET]);
mobile_validate_timekeeper($conn, $timekeeperId);
$assignedSite = mobile_require_assigned_site($conn, $timekeeperId);
$siteId = (int) $assignedSite['site_id'];

if (!overtime_table_exists($conn)) {
    echo json_encode([
        'status' => 'success',
        'site_id' => $siteId,
        'site_name' => $assignedSite['site_name'],
        'requests' => [],
    ]);
    $conn->close();
    exit;
}

$sql = "
    SELECT
        ot.OvertimeID,
        ot.WorkerID,
        ot.SiteID,
        ot.RequestDate,
        ot.OvertimeType,
        ot.TotalHours,
        ot.Status,
        w.First_Name,
        w.Last_Name
    FROM overtime_requests ot
    INNER JOIN worker w ON w.WorkerID = ot.WorkerID
    WHERE ot.SiteID = ?
    ORDER BY ot.OvertimeID DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    mobile_json_error('Database error', 500);
}

$stmt->bind_param('i', $siteId);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $workerName = trim(($row['First_Name'] ?? '') . ' ' . ($row['Last_Name'] ?? ''));
    $requests[] = [
        'id' => (int) ($row['OvertimeID'] ?? 0),
        'worker_id' => (int) ($row['WorkerID'] ?? 0),
        'worker_name' => $workerName,
        'site_id' => (int) ($row['SiteID'] ?? 0),
        'date' => !empty($row['RequestDate'])
            ? date('m/d/Y', strtotime((string) $row['RequestDate']))
            : '',
        'type' => (string) ($row['OvertimeType'] ?? ''),
        'hours' => (float) ($row['TotalHours'] ?? 0),
        'status' => (string) ($row['Status'] ?? 'Pending'),
    ];
}

$stmt->close();

echo json_encode([
    'status' => 'success',
    'site_id' => $siteId,
    'site_name' => $assignedSite['site_name'],
    'requests' => $requests,
]);

$conn->close();
