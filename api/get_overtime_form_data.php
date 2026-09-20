<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/overtime_helpers.php';

$currentRole = require_auth($conn, ['Timekeeper']);
$userId = (int) ($_SESSION['user_id'] ?? 0);

if (!overtime_table_exists($conn)) {
    echo json_encode(['success' => false, 'message' => 'Overtime requests table is not installed']);
    exit;
}

$siteIds = auth_get_timekeeper_site_ids($conn, $userId);
if (count($siteIds) === 0) {
    echo json_encode([
        'success' => true,
        'sites' => [],
        'workers' => [],
        'overtime_types' => overtime_valid_types(),
        'message' => 'No active site is assigned to your Timekeeper account'
    ]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($siteIds), '?'));
$types = str_repeat('i', count($siteIds));

$siteSql = "
    SELECT SiteID, Site_Name, LunchStart, LunchEnd, ShiftStart, ShiftEnd
    FROM projectsite
    WHERE SiteID IN ({$placeholders})
    ORDER BY Site_Name ASC
";
$siteStmt = $conn->prepare($siteSql);
$siteStmt->bind_param($types, ...$siteIds);
$siteStmt->execute();
$siteResult = $siteStmt->get_result();

$sites = [];
while ($row = $siteResult->fetch_assoc()) {
    $siteId = (int) ($row['SiteID'] ?? 0);
    $sites[] = [
        'site_id' => $siteId,
        'site_name' => (string) ($row['Site_Name'] ?? ''),
        'lunch_schedule_label' => get_overtime_lunch_schedule_label($conn, $siteId),
    ];
}
$siteStmt->close();

$workerSql = "
    SELECT
        w.WorkerID,
        w.First_Name,
        w.Last_Name,
        wa.SiteID,
        ps.Site_Name
    FROM workerassignment wa
    INNER JOIN worker w ON w.WorkerID = wa.WorkerID
    INNER JOIN projectsite ps ON ps.SiteID = wa.SiteID
    WHERE wa.SiteID IN ({$placeholders})
    ORDER BY w.Last_Name, w.First_Name
";
$workerStmt = $conn->prepare($workerSql);
$workerStmt->bind_param($types, ...$siteIds);
$workerStmt->execute();
$workerResult = $workerStmt->get_result();

$workers = [];
while ($row = $workerResult->fetch_assoc()) {
    $name = trim((string) (($row['First_Name'] ?? '') . ' ' . ($row['Last_Name'] ?? '')));
    $workers[] = [
        'worker_id' => (int) ($row['WorkerID'] ?? 0),
        'worker_name' => $name !== '' ? $name : 'Unknown Worker',
        'site_id' => (int) ($row['SiteID'] ?? 0),
        'site_name' => (string) ($row['Site_Name'] ?? ''),
    ];
}
$workerStmt->close();

echo json_encode([
    'success' => true,
    'sites' => $sites,
    'workers' => $workers,
    'overtime_types' => overtime_valid_types(),
]);

$conn->close();
