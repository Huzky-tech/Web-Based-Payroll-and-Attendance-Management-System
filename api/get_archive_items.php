<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';

require_auth($conn, ['Admin', 'Assistant Admin']);

$archiveFile = __DIR__ . '/../data/archive_items.json';
$items = [];

function archive_normalize_type(string $type): string
{
    $normalized = strtolower(trim($type));
    $normalized = str_replace(['-', ' '], '_', $normalized);

    $map = [
        'worker' => 'employee',
        'workers' => 'employee',
        'employee' => 'employee',
        'employees' => 'employee',
        'projectsite' => 'site',
        'project_site' => 'site',
        'site' => 'site',
        'sites' => 'site',
        'payroll' => 'payroll',
        'payroll_records' => 'payroll',
        'payroll_record' => 'payroll',
        'report' => 'report',
        'reports' => 'report',
        'timekeeper_report' => 'report',
        'timekeeper_reports' => 'report',
        'user' => 'user',
        'users' => 'user',
        'admin' => 'user',
        'assistantmanager' => 'user',
        'payrollstaff' => 'user',
        'timekeeper' => 'user',
    ];

    return $map[$normalized] ?? $normalized;
}

function archive_strip_type_suffix(string $value): string
{
    return trim((string) preg_replace('/\s*\[(?:employee|employees|worker|workers|site|sites|projectsite|project_site|payroll|payroll_record|payroll_records|report|reports|user|users|admin|assistantmanager|payrollstaff|timekeeper)\]\s*$/i', '', $value));
}

$employeeQuery = $conn->query("
    SELECT
        w.WorkerID,
        CONCAT(COALESCE(w.First_Name, ''), ' ', COALESCE(w.Last_Name, '')) AS full_name,
        w.DateHired,
        ws.Status AS worker_status
    FROM worker w
    INNER JOIN workerstatus ws ON ws.WorkerStatusID = w.WorkerStatusID
    WHERE ws.Status = 'Inactive'
    ORDER BY w.WorkerID DESC
");

if ($employeeQuery) {
    while ($row = $employeeQuery->fetch_assoc()) {
        $employeeName = trim((string) ($row['full_name'] ?? '')) ?: 'Archived Employee';
        $dateHired = !empty($row['DateHired']) ? date('n/j/Y', strtotime($row['DateHired'])) : 'Unknown';

        $items[] = [
            'id' => 'employee-' . (int) $row['WorkerID'],
            'title' => $employeeName,
            'type' => 'employee',
            'description' => 'Archived employee record',
            'archived_date' => 'Inactive',
            'archived_by' => 'System',
            'original_date' => $dateHired,
            'icon' => 'fa-regular fa-user',
            'color' => 'blue',
            'source' => 'database',
            'can_restore' => true,
            'can_delete' => false
        ];
    }
}

$hasArchivedAt = false;
$columnCheck = $conn->query("SHOW COLUMNS FROM projectsite LIKE 'Archived_At'");
if ($columnCheck && $columnCheck->num_rows > 0) {
    $hasArchivedAt = true;
}

$archivedAtSelect = $hasArchivedAt ? 's.Archived_At' : 'NULL AS Archived_At';

$siteQuery = $conn->query("
    SELECT
        s.SiteID,
        s.Site_Name,
        s.Location,
        s.Site_Manager,
        s.Start_Date,
        s.End_Date,
        {$archivedAtSelect},
        s.Status,
        (SELECT COUNT(*) FROM WorkerAssignment wa WHERE wa.SiteID = s.SiteID) AS worker_count
    FROM projectsite s
    WHERE LOWER(COALESCE(s.Status, '')) IN ('archived', 'inactive')
    ORDER BY s.SiteID DESC
");

if ($siteQuery) {
    while ($row = $siteQuery->fetch_assoc()) {
        $startDate = !empty($row['Start_Date']) ? date('n/j/Y', strtotime($row['Start_Date'])) : 'Unknown';

        $archivedAtRaw = $row['Archived_At'] ?? null;
        if (!empty($archivedAtRaw)) {
            $archivedDate = date('n/j/Y g:i A', strtotime($archivedAtRaw));
        } elseif (!empty($row['End_Date'])) {
            $archivedDate = date('n/j/Y', strtotime($row['End_Date']));
        } else {
            $archivedDate = 'Unknown';
        }

        $siteName = (string) ($row['Site_Name'] ?: 'Archived Site');
        $location = trim((string) ($row['Location'] ?? '')) ?: 'Not specified';
        $projectManager = trim((string) ($row['Site_Manager'] ?? '')) ?: 'Not assigned';
        $workerCount = (int) ($row['worker_count'] ?? 0);

        $items[] = [
            'id' => 'site-' . (int) $row['SiteID'],
            'title' => $siteName,
            'type' => 'site',
            'description' => 'Archived construction site record',
            'location' => $location,
            'project_manager' => $projectManager,
            'date_created' => $startDate,
            'date_archived' => $archivedDate,
            'assigned_workers' => $workerCount,
            'archived_date' => $archivedDate,
            'archived_by' => 'System',
            'original_date' => $startDate,
            'icon' => 'fa-regular fa-building',
            'color' => 'purple',
            'source' => 'database',
            'can_restore' => true,
            'can_delete' => false
        ];
    }
}

$archiveFile = __DIR__ . '/../data/archive_items.json';
if (!file_exists($archiveFile)) {
    $counts = [
        'all' => count($items),
        'employee' => 0,
        'site' => 0,
        'payroll' => 0,
        'report' => 0,
        'user' => 0
    ];

    foreach ($items as $item) {
        $type = strtolower((string) ($item['type'] ?? ''));
        if (array_key_exists($type, $counts)) {
            $counts[$type]++;
        }
    }

    echo json_encode([
        'success' => true,
        'items' => $items,
        'counts' => $counts
    ]);
    exit;
}

$contents = file_get_contents($archiveFile);
$fileItems = json_decode($contents, true);

if (!is_array($fileItems)) {
    echo json_encode(['success' => false, 'message' => 'Archive data is invalid']);
    exit;
}

foreach ($fileItems as $item) {
    $item['source'] = 'file';
    $item['type'] = archive_normalize_type((string) ($item['type'] ?? ''));
    $item['title'] = archive_strip_type_suffix((string) ($item['title'] ?? ''));
    $item['description'] = archive_strip_type_suffix((string) ($item['description'] ?? ''));
    $item['can_restore'] = true;
    // Archive UI should not show delete for archived records.
    $item['can_delete'] = false;
    $items[] = $item;
}

$counts = [
    'all' => count($items),
    'employee' => 0,
    'site' => 0,
    'payroll' => 0,
    'report' => 0,
    'user' => 0
];

foreach ($items as $item) {
    $type = strtolower((string) ($item['type'] ?? ''));
    if (array_key_exists($type, $counts)) {
        $counts[$type]++;
    }
}

echo json_encode([
    'success' => true,
    'items' => $items,
    'counts' => $counts
]);
