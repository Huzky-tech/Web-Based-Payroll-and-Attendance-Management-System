<?php
/**
 * List Timekeeper site assignments for the admin panel.
 */

header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/timekeeper_assignment_helpers.php';

require_auth($conn, ['Admin']);

$sql = "SELECT
            u.id AS user_id,
            u.full_name,
            u.email,
            ta.AssignmentID,
            ta.SiteID,
            ta.AssignedDate,
            ta.Status AS assignment_status,
            ps.Site_Name
        FROM users u
        INNER JOIN timekeeper tk ON tk.UserID = u.id
        LEFT JOIN timekeeper_assignment ta ON ta.UserID = u.id AND ta.Status = 'Active'
        LEFT JOIN projectsite ps ON ps.SiteID = ta.SiteID
        ORDER BY u.full_name ASC, u.email ASC";

$assignments = [];

if (timekeeper_assignment_table_exists($conn)) {
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $userId = (int) ($row['user_id'] ?? 0);
            $siteName = trim((string) ($row['Site_Name'] ?? ''));

            if ($siteName === '' && $userId > 0) {
                $legacy = get_active_timekeeper_assignment($conn, $userId);
                if ($legacy) {
                    $siteName = (string) ($legacy['Site_Name'] ?? '');
                    $row['SiteID'] = $legacy['SiteID'] ?? null;
                    $row['assignment_status'] = 'Active';
                }
            }

            $assignments[] = [
                'user_id' => $userId,
                'name' => (string) ($row['full_name'] ?? ''),
                'email' => (string) ($row['email'] ?? ''),
                'assignment_id' => !empty($row['AssignmentID']) ? (int) $row['AssignmentID'] : null,
                'site_id' => !empty($row['SiteID']) ? (int) $row['SiteID'] : null,
                'site_name' => $siteName !== '' ? $siteName : null,
                'assigned_date' => (string) ($row['AssignedDate'] ?? ''),
                'status' => (string) ($row['assignment_status'] ?? 'Inactive'),
            ];
        }
        $result->free();
    }
} else {
    $fallbackSql = "SELECT u.id AS user_id, u.full_name, u.email
                    FROM users u
                    INNER JOIN timekeeper tk ON tk.UserID = u.id
                    ORDER BY u.full_name ASC, u.email ASC";
    $result = $conn->query($fallbackSql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $userId = (int) ($row['user_id'] ?? 0);
            $legacy = get_active_timekeeper_assignment($conn, $userId);
            $assignments[] = [
                'user_id' => $userId,
                'name' => (string) ($row['full_name'] ?? ''),
                'email' => (string) ($row['email'] ?? ''),
                'assignment_id' => null,
                'site_id' => $legacy ? (int) ($legacy['SiteID'] ?? 0) : null,
                'site_name' => $legacy ? (string) ($legacy['Site_Name'] ?? '') : null,
                'assigned_date' => $legacy ? (string) ($legacy['AssignedDate'] ?? '') : '',
                'status' => $legacy ? 'Active' : 'Inactive',
            ];
        }
        $result->free();
    }
}

echo json_encode([
    'success' => true,
    'assignments' => $assignments,
    'count' => count($assignments),
]);

$conn->close();
