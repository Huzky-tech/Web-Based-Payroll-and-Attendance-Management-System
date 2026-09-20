<?php
/**
 * Mobile sync API — returns site and workers for a Timekeeper's assigned site.
 */

require_once 'connection/db_config.php';
require_once __DIR__ . '/mobile_auth_helpers.php';

mobile_json_headers();
mobile_handle_options();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    mobile_json_error('Invalid request method.', 405);
}

$payload = mobile_get_request_payload();
$timekeeperId = mobile_resolve_timekeeper_id($payload);
$assignment = mobile_require_timekeeper_assignment($conn, $timekeeperId);
$siteId = (int) ($assignment['SiteID'] ?? 0);

$workers = [];
$workerStmt = $conn->prepare("
    SELECT
        w.WorkerID,
        w.First_Name,
        w.Last_Name,
        COALESCE(NULLIF(TRIM(wa.Role_On_Site), ''), 'Construction Worker') AS Position
    FROM workerassignment wa
    INNER JOIN worker w ON w.WorkerID = wa.WorkerID
    WHERE wa.SiteID = ?
    ORDER BY w.Last_Name ASC, w.First_Name ASC
");

if ($workerStmt) {
    $workerStmt->bind_param('i', $siteId);
    $workerStmt->execute();
    $result = $workerStmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $workers[] = [
            'WorkerID' => (int) ($row['WorkerID'] ?? 0),
            'First_Name' => (string) ($row['First_Name'] ?? ''),
            'Last_Name' => (string) ($row['Last_Name'] ?? ''),
            'Position' => (string) ($row['Position'] ?? ''),
        ];
    }

    $workerStmt->close();
}

echo json_encode([
    'status' => 'success',
    'site' => [
        'SiteID' => $siteId,
        'Site_Name' => (string) ($assignment['Site_Name'] ?? ''),
        'Location' => (string) ($assignment['Location'] ?? ''),
    ],
    'workers' => $workers,
]);

$conn->close();
