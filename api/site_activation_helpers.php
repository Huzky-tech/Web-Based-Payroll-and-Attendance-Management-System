<?php

/**
 * A project becomes active once it has at least three assigned workers.
 */
if (!function_exists('site_activation_requirements')) {
    function site_activation_requirements(mysqli $conn, int $siteId): array
    {
        $workerCount = 0;
        $workersStmt = $conn->prepare('SELECT COUNT(*) AS worker_count FROM workerassignment WHERE SiteID = ?');
        if ($workersStmt) {
            $workersStmt->bind_param('i', $siteId);
            $workersStmt->execute();
            $workerCount = (int) (($workersStmt->get_result()->fetch_assoc()['worker_count'] ?? 0));
            $workersStmt->close();
        }

        return [
            'worker_count' => $workerCount,
            'minimum_workers' => 3,
            'eligible' => $workerCount >= 3,
        ];
    }
}

if (!function_exists('site_activation_requirement_message')) {
    function site_activation_requirement_message(array $requirements): string
    {
        $missing = [];
        if ((int) ($requirements['worker_count'] ?? 0) < 3) {
            $missing[] = 'at least 3 assigned workers';
        }
        return $missing
            ? 'A site can be Active only with ' . implode(' and ', $missing) . '.'
            : '';
    }
}

if (!function_exists('site_sync_activation_status')) {
    function site_sync_activation_status(mysqli $conn, int $siteId): array
    {
        $requirements = site_activation_requirements($conn, $siteId);
        $status = $requirements['eligible'] ? 'Active' : 'Inactive';
        $updateStmt = $conn->prepare("UPDATE projectsite SET Status = ? WHERE SiteID = ? AND COALESCE(Status, '') <> 'Archived'");
        if ($updateStmt) {
            $updateStmt->bind_param('si', $status, $siteId);
            $updateStmt->execute();
            $updateStmt->close();
        }
        $requirements['status'] = $status;
        return $requirements;
    }
}
