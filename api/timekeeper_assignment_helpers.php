<?php
/**
 * Timekeeper site assignment helpers used by mobile and admin API endpoints.
 * Schema: timekeeper_assignment (UserID, SiteID, AssignedDate, Status).
 */

if (!function_exists('timekeeper_assignment_table_exists')) {
    function timekeeper_assignment_table_exists(mysqli $conn): bool
    {
        $result = $conn->query("SHOW TABLES LIKE 'timekeeper_assignment'");
        return $result !== false && $result->num_rows > 0;
    }
}

if (!function_exists('validate_timekeeper_is_role')) {
    function validate_timekeeper_is_role(mysqli $conn, int $timekeeperId): bool
    {
        if ($timekeeperId <= 0) {
            return false;
        }

        $stmt = $conn->prepare(
            'SELECT id, status FROM users WHERE id = ? LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('i', $timekeeperId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || strcasecmp((string) ($row['status'] ?? ''), 'Active') !== 0) {
            return false;
        }

        if (function_exists('auth_get_user_role')) {
            return auth_get_user_role($conn, $timekeeperId) === 'Timekeeper';
        }

        $roleStmt = $conn->prepare(
            "SELECT r.role_name
             FROM user_roles ur
             INNER JOIN roles r ON r.role_id = ur.role_id
             WHERE ur.user_id = ?
             LIMIT 1"
        );
        if (!$roleStmt) {
            return true;
        }

        $roleStmt->bind_param('i', $timekeeperId);
        $roleStmt->execute();
        $roleRow = $roleStmt->get_result()->fetch_assoc();
        $roleStmt->close();

        return strcasecmp((string) ($roleRow['role_name'] ?? ''), 'Timekeeper') === 0;
    }
}

if (!function_exists('projectsite_timekeeper_column')) {
    function projectsite_timekeeper_column(mysqli $conn): ?string
    {
        foreach (['Timekeeper_UserID', 'TimekeeperUserID', 'TimekeeperID', 'AssignedTimekeeperID'] as $column) {
            $result = $conn->query("SHOW COLUMNS FROM projectsite LIKE '{$column}'");
            if ($result !== false && $result->num_rows > 0) {
                return $column;
            }
        }

        return null;
    }
}

if (!function_exists('get_timekeeper_site_ids')) {
    /**
     * Active site IDs linked to a Timekeeper user account.
     *
     * @return int[]
     */
    function get_timekeeper_site_ids(mysqli $conn, int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $siteIds = [];
        $column = projectsite_timekeeper_column($conn);

        if ($column !== null) {
            $stmt = $conn->prepare("
                SELECT SiteID
                FROM projectsite
                WHERE {$column} = ?
                  AND LOWER(COALESCE(Status, '')) = 'active'
                ORDER BY SiteID ASC
            ");
            if ($stmt) {
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $siteIds[] = (int) ($row['SiteID'] ?? 0);
                }
                $stmt->close();
            }
        }

        if ($siteIds === [] && timekeeper_assignment_table_exists($conn)) {
            $stmt = $conn->prepare("
                SELECT DISTINCT ta.SiteID
                FROM timekeeper_assignment ta
                INNER JOIN projectsite ps ON ps.SiteID = ta.SiteID
                WHERE ta.UserID = ?
                  AND ta.Status = 'Active'
                  AND LOWER(COALESCE(ps.Status, '')) = 'active'
                ORDER BY ta.SiteID ASC
            ");
            if ($stmt) {
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $siteIds[] = (int) ($row['SiteID'] ?? 0);
                }
                $stmt->close();
            }
        }

        $siteIds = array_values(array_filter(array_unique($siteIds), static fn (int $id): bool => $id > 0));

        return $siteIds;
    }
}

if (!function_exists('timekeeper_assigned_to_other_active_site')) {
    function timekeeper_assigned_to_other_active_site(mysqli $conn, int $userId, int $siteId): ?array
    {
        foreach (get_timekeeper_site_ids($conn, $userId) as $assignedSiteId) {
            if ($assignedSiteId !== $siteId) {
                $stmt = $conn->prepare('SELECT Site_Name FROM projectsite WHERE SiteID = ? LIMIT 1');
                if (!$stmt) {
                    return ['site_id' => $assignedSiteId, 'site_name' => "Site {$assignedSiteId}"];
                }

                $stmt->bind_param('i', $assignedSiteId);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                return [
                    'site_id' => $assignedSiteId,
                    'site_name' => trim((string) ($row['Site_Name'] ?? '')) ?: "Site {$assignedSiteId}",
                ];
            }
        }

        return null;
    }
}

if (!function_exists('deactivate_timekeeper_assignments')) {
    function deactivate_timekeeper_assignments(mysqli $conn, int $userId): void
    {
        if ($userId <= 0 || !timekeeper_assignment_table_exists($conn)) {
            return;
        }

        $stmt = $conn->prepare("
            UPDATE timekeeper_assignment
            SET Status = 'Inactive'
            WHERE UserID = ? AND Status = 'Active'
        ");
        if (!$stmt) {
            return;
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('sync_projectsite_timekeeper_user')) {
    function sync_projectsite_timekeeper_user(
        mysqli $conn,
        int $userId,
        int $siteId,
        bool $active
    ): void {
        $column = projectsite_timekeeper_column($conn);
        if ($column === null || $userId <= 0) {
            return;
        }

        if ($active && $siteId > 0) {
            $clearStmt = $conn->prepare("UPDATE projectsite SET {$column} = NULL WHERE {$column} = ?");
            if ($clearStmt) {
                $clearStmt->bind_param('i', $userId);
                $clearStmt->execute();
                $clearStmt->close();
            }

            $assignStmt = $conn->prepare("UPDATE projectsite SET {$column} = ? WHERE SiteID = ?");
            if ($assignStmt) {
                $assignStmt->bind_param('ii', $userId, $siteId);
                $assignStmt->execute();
                $assignStmt->close();
            }

            return;
        }

        $clearStmt = $conn->prepare("UPDATE projectsite SET {$column} = NULL WHERE {$column} = ?");
        if ($clearStmt) {
            $clearStmt->bind_param('i', $userId);
            $clearStmt->execute();
            $clearStmt->close();
        }
    }
}

if (!function_exists('get_active_timekeeper_assignment')) {
    function get_active_timekeeper_assignment(mysqli $conn, int $timekeeperId): ?array
    {
        if ($timekeeperId <= 0) {
            return null;
        }

        if (function_exists('get_timekeeper_site_ids')) {
            $siteIds = get_timekeeper_site_ids($conn, $timekeeperId);
            if (count($siteIds) !== 1) {
                return null;
            }

            $siteId = (int) $siteIds[0];
            $stmt = $conn->prepare("
                SELECT
                    ps.SiteID,
                    ps.Site_Name,
                    ps.Location,
                    ps.ShiftStart,
                    ps.LunchStart,
                    ps.LunchEnd,
                    ps.ShiftEnd
                FROM projectsite ps
                WHERE ps.SiteID = ?
                LIMIT 1
            ");
            if (!$stmt) {
                return null;
            }

            $stmt->bind_param('i', $siteId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return $row ?: null;
        }

        if (timekeeper_assignment_table_exists($conn)) {
            $stmt = $conn->prepare("
                SELECT
                    ps.SiteID,
                    ps.Site_Name,
                    ps.Location,
                    ps.ShiftStart,
                    ps.LunchStart,
                    ps.LunchEnd,
                    ps.ShiftEnd
                FROM timekeeper_assignment ta
                INNER JOIN projectsite ps ON ps.SiteID = ta.SiteID
                WHERE ta.UserID = ?
                  AND ta.Status = 'Active'
                ORDER BY ta.AssignedDate DESC, ta.SiteID ASC
                LIMIT 1
            ");
            if ($stmt) {
                $stmt->bind_param('i', $timekeeperId);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($row) {
                    return $row;
                }
            }
        }

        $legacy = $conn->query("SHOW TABLES LIKE 'timekeeperassignment'");
        if ($legacy !== false && $legacy->num_rows > 0) {
            $stmt = $conn->prepare("
                SELECT
                    ps.SiteID,
                    ps.Site_Name,
                    ps.Location,
                    ps.ShiftStart,
                    ps.LunchStart,
                    ps.LunchEnd,
                    ps.ShiftEnd
                FROM timekeeperassignment ta
                INNER JOIN projectsite ps ON ps.SiteID = ta.SiteID
                WHERE ta.TimekeeperID = ?
                ORDER BY ta.SiteID ASC
                LIMIT 1
            ");
            if ($stmt) {
                $stmt->bind_param('i', $timekeeperId);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                return $row ?: null;
            }
        }

        return null;
    }
}
