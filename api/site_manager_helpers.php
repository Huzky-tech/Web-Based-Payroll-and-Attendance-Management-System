<?php
require_once __DIR__ . '/../includes/worker_position_helpers.php';

function site_manager_name_exists(mysqli $conn, string $managerName): bool
{
    worker_position_ensure_column($conn);
    $normalized = preg_replace('/\s+/', ' ', trim($managerName));
    if ($normalized === '') return false;

    $stmt = $conn->prepare("
        SELECT 1
        FROM worker w
        LEFT JOIN workerstatus ws ON ws.WorkerStatusID = w.WorkerStatusID
        LEFT JOIN workerassignment wa ON wa.WorkerID = w.WorkerID
        WHERE LOWER(TRIM(CONCAT(COALESCE(w.First_Name, ''), ' ', COALESCE(w.Last_Name, '')))) = LOWER(?)
          AND LOWER(COALESCE(ws.Status, '')) NOT IN ('inactive', 'archived')
          AND (
              LOWER(COALESCE(w.Position, '')) LIKE '%manager%'
              OR LOWER(COALESCE(wa.Role_On_Site, '')) LIKE '%manager%'
          )
        LIMIT 1
    ");
    if (!$stmt) return false;
    $stmt->bind_param('s', $normalized);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $exists;
}
