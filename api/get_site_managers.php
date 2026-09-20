<?php
header('Content-Type: application/json');

include 'connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/worker_position_helpers.php';
require_once __DIR__ . '/../includes/manager_role.php';

require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR']);
worker_position_ensure_column($conn);
manager_role_ensure_table($conn);

$sql = "
    SELECT
        0 AS WorkerID,
        TRIM(COALESCE(u.full_name, CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')))) AS full_name,
        'Manager' AS position
    FROM managers manager_role
    INNER JOIN users u ON u.id = manager_role.UserID
    WHERE LOWER(COALESCE(u.status, 'active')) = 'active'

    UNION ALL

    SELECT
        w.WorkerID,
        TRIM(CONCAT(COALESCE(w.First_Name, ''), ' ', COALESCE(w.Last_Name, ''))) AS full_name,
        COALESCE(NULLIF(TRIM(w.Position), ''), NULLIF(TRIM(wa.Role_On_Site), '')) AS position
    FROM worker w
    LEFT JOIN workerstatus ws ON ws.WorkerStatusID = w.WorkerStatusID
    LEFT JOIN workerassignment wa ON wa.WorkerID = w.WorkerID
    WHERE LOWER(COALESCE(ws.Status, '')) NOT IN ('inactive', 'archived')
      AND (
          LOWER(COALESCE(w.Position, '')) LIKE '%manager%'
          OR LOWER(COALESCE(wa.Role_On_Site, '')) LIKE '%manager%'
      )
    GROUP BY w.WorkerID, w.First_Name, w.Last_Name, w.Position, wa.Role_On_Site
    ORDER BY full_name
";

$result = $conn->query($sql);
$managers = [];
$seen = [];
while ($result && $row = $result->fetch_assoc()) {
    $name = trim((string) ($row['full_name'] ?? ''));
    if ($name === '' || isset($seen[strtolower($name)])) {
        continue;
    }
    $seen[strtolower($name)] = true;
    $managers[] = [
        'worker_id' => (int) ($row['WorkerID'] ?? 0),
        'full_name' => $name,
        'position' => trim((string) ($row['position'] ?? 'Manager')) ?: 'Manager',
    ];
}

echo json_encode(['success' => true, 'managers' => $managers]);
