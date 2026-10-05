<?php
/**
 * Get Worker by ID for mobile QR scanner.
 * Requires timekeeper_id and validates worker belongs to the timekeeper's site.
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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    mobile_json_error('Invalid request method');
}

$timekeeperId = mobile_parse_timekeeper_id([$_GET]);

$idParam = trim((string) ($_GET['id'] ?? $_GET['worker_id'] ?? ''));
if ($idParam === '') {
    mobile_json_error('Invalid worker ID');
}

if (ctype_digit($idParam)) {
    $workerId = (int) $idParam;
} else {
    $digitsOnly = preg_replace('/\D+/', '', $idParam);
    $workerId = $digitsOnly !== '' ? (int) $digitsOnly : 0;
}

if ($workerId <= 0) {
    mobile_json_error('Invalid worker ID');
}

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
    WHERE w.WorkerID = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    mobile_json_error('Database error', 500);
}

$stmt->bind_param('ii', $siteId, $workerId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    $existsStmt = $conn->prepare('SELECT WorkerID FROM worker WHERE WorkerID = ? LIMIT 1');
    if ($existsStmt) {
        $existsStmt->bind_param('i', $workerId);
        $existsStmt->execute();
        $exists = $existsStmt->get_result()->fetch_assoc();
        $existsStmt->close();
        if ($exists) {
            mobile_json_error('Worker is not assigned to your site.', 403);
        }
    }
    mobile_json_error('Worker not found', 404);
}

$name = trim(($row['First_Name'] ?? '') . ' ' . ($row['Last_Name'] ?? ''));

echo json_encode([
    'status' => 'success',
    'data' => [
        'WorkerID' => (int) $row['WorkerID'],
        'worker_id' => (string) $row['WorkerID'],
        'name' => $name,
        'phone' => $row['Phone'] ?? '',
        'role' => (string) ($row['Role_On_Site'] ?? ''),
        'Role_On_Site' => (string) ($row['Role_On_Site'] ?? ''),
        'site_id' => (int) $row['SiteID'],
        'SiteID' => (int) $row['SiteID'],
        'site_name' => (string) ($row['Site_Name'] ?? ''),
        'Site_Name' => (string) ($row['Site_Name'] ?? ''),
        'assigned_site' => (string) ($row['Site_Name'] ?? ''),
        'shift_start' => substr((string) ($row['ShiftStart'] ?? ''), 0, 5),
        'shift_end' => substr((string) ($row['ShiftEnd'] ?? ''), 0, 5),
    ],
]);

$conn->close();

