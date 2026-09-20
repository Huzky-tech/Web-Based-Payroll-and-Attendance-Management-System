<?php

/**
 * A project is operational only when it has enough workers and a Timekeeper.
 */
if (!function_exists('site_activation_requirements')) {
    function site_activation_requirements(mysqli $conn, int $siteId): array
    {
        $workerCount = 0;
        $timekeeperUserId = 0;

        $workersStmt = $conn->prepare('SELECT COUNT(*) AS worker_count FROM workerassignment WHERE SiteID = ?');
        if ($workersStmt) {
            $workersStmt->bind_param('i', $siteId);
            $workersStmt->execute();
            $workerCount = (int) (($workersStmt->get_result()->fetch_assoc()['worker_count'] ?? 0));
            $workersStmt->close();
        }

        $timekeeperColumn = $conn->query("SHOW COLUMNS FROM projectsite LIKE 'Timekeeper_UserID'");
        if ($timekeeperColumn && $timekeeperColumn->num_rows > 0) {
            $timekeeperStmt = $conn->prepare('SELECT Timekeeper_UserID FROM projectsite WHERE SiteID = ? LIMIT 1');
            if ($timekeeperStmt) {
                $timekeeperStmt->bind_param('i', $siteId);
                $timekeeperStmt->execute();
                $timekeeperUserId = (int) (($timekeeperStmt->get_result()->fetch_assoc()['Timekeeper_UserID'] ?? 0));
                $timekeeperStmt->close();
            }
        }

        return [
            'worker_count' => $workerCount,
            'minimum_workers' => 3,
            'has_timekeeper' => $timekeeperUserId > 0,
            'timekeeper_user_id' => $timekeeperUserId > 0 ? $timekeeperUserId : null,
            'eligible' => $workerCount >= 3 && $timekeeperUserId > 0,
        ];
    }
}

if (!function_exists('site_activation_requirement_message')) {
    function site_activation_requirement_message(array $requirements): string
    {
        $missing = [];
        if (empty($requirements['has_timekeeper'])) {
            $missing[] = 'an assigned Timekeeper';
        }
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
