<?php
/**
 * List active Timekeeper user accounts for site assignment.
 */

header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized', 'timekeepers' => []]);
    exit;
}

$currentRole = $_SESSION['role'] ?? auth_get_user_role($conn, (int) $_SESSION['user_id']);
// HR needs this list to display the site's current Timekeeper inside the
// worker-assignment modal. Mutation remains protected separately by
// assign_site_timekeeper.php (Admin/Assistant Admin only).
$allowedRoles = ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR'];
if (!in_array($currentRole, $allowedRoles, true)) {
    echo json_encode(['success' => false, 'message' => 'Access denied', 'timekeepers' => []]);
    exit;
}

$sql = "SELECT
            u.id AS user_id,
            u.full_name,
            u.email,
            ps.SiteID AS assigned_site_id,
            ps.Site_Name AS assigned_site_name
        FROM users u
        INNER JOIN timekeeper tk ON tk.UserID = u.id
        LEFT JOIN projectsite ps
            ON ps.Timekeeper_UserID = u.id
           AND LOWER(COALESCE(ps.Status, '')) = 'active'
        WHERE LOWER(u.status) = 'active'
        ORDER BY u.full_name ASC, u.email ASC";

$result = $conn->query($sql);
$timekeepers = [];
$seenUserIds = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $userId = (int) ($row['user_id'] ?? 0);
        if ($userId <= 0 || isset($seenUserIds[$userId])) {
            continue;
        }

        $seenUserIds[$userId] = true;
        $timekeepers[] = [
            'id' => (int) $row['user_id'],
            'name' => (string) ($row['full_name'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'assigned_site_id' => !empty($row['assigned_site_id']) ? (int) $row['assigned_site_id'] : null,
            'assigned_site_name' => trim((string) ($row['assigned_site_name'] ?? '')) ?: null,
        ];
    }
    $result->free();
}

echo json_encode([
    'success' => true,
    'timekeepers' => $timekeepers,
]);

$conn->close();
