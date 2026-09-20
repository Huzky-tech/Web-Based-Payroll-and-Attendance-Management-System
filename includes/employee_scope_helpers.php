<?php

if (!function_exists('employee_scope_site_ids')) {
    /**
     * Returns null for roles that can view all employees, or the assigned SiteID
     * list for scoped roles.
     *
     * @return int[]|null
     */
    function employee_scope_site_ids(mysqli $conn, string $role, int $userId): ?array
    {
        // Admin, Assistant Admin, HR, and Payroll Staff use the All Employees
        // directory and must be able to see every active worker. Site scoping is
        // only required for timekeepers.

        if ($role === 'Timekeeper') {
            return array_values(array_unique(auth_get_timekeeper_site_ids($conn, $userId)));
        }

        return null;
    }
}

if (!function_exists('employee_scope_condition')) {
    function employee_scope_condition(mysqli $conn, string $role, int $userId, string $siteColumn, string &$types, array &$params): string
    {
        $siteIds = employee_scope_site_ids($conn, $role, $userId);
        if ($siteIds === null) {
            return '';
        }

        if (!$siteIds) {
            return '1 = 0';
        }

        $placeholders = implode(',', array_fill(0, count($siteIds), '?'));
        $types .= str_repeat('i', count($siteIds));
        foreach ($siteIds as $siteId) {
            $params[] = $siteId;
        }

        return "{$siteColumn} IN ({$placeholders})";
    }
}

if (!function_exists('employee_query')) {
    function employee_query(mysqli $conn, string $sql, string $types = '', array $params = [])
    {
        if ($types === '') {
            return $conn->query($sql);
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result();
    }
}

?>
