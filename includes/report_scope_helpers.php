<?php

if (!function_exists('report_scope_site_ids')) {
    /**
     * Returns null for unrestricted roles, or the list of SiteID values the current
     * user may view.
     *
     * @return int[]|null
     */
    function report_scope_site_ids(mysqli $conn): ?array
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $role = $_SESSION['role'] ?? null;
        if (!$role && $userId > 0) {
            $role = auth_get_user_role($conn, $userId);
            $_SESSION['role'] = $role;
        }

        if ($role === 'Timekeeper') {
            return array_values(array_unique(auth_get_timekeeper_site_ids($conn, $userId)));
        }

        return null;
    }
}

if (!function_exists('report_scope_condition')) {
    function report_scope_condition(mysqli $conn, string $siteColumn, string &$types, array &$params): string
    {
        $siteIds = report_scope_site_ids($conn);
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

if (!function_exists('report_query')) {
    function report_query(mysqli $conn, string $sql, string $types = '', array $params = [])
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

if (!function_exists('report_site_options')) {
    /**
     * @return string[]
     */
    function report_site_options(mysqli $conn): array
    {
        $types = '';
        $params = [];
        $scope = report_scope_condition($conn, 'ps.SiteID', $types, $params);
        $sql = 'SELECT ps.Site_Name FROM projectsite ps'
            . ($scope !== '' ? " WHERE {$scope}" : '')
            . ' ORDER BY ps.Site_Name ASC';

        $result = report_query($conn, $sql, $types, $params);
        if (!$result) {
            return [];
        }

        $sites = [];
        while ($row = $result->fetch_assoc()) {
            $siteName = trim((string) ($row['Site_Name'] ?? ''));
            if ($siteName !== '') {
                $sites[] = $siteName;
            }
        }

        return $sites;
    }
}
?>
