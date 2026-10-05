<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
require_once __DIR__ . '/../includes/user_identity.php';
require_once __DIR__ . '/../includes/manager_role.php';
try {
    manager_role_ensure_table($conn);
    $columns = user_identity_ensure_columns($conn);
    $lockedAtSql = in_array('account_locked_at', $columns, true) ? 'u.account_locked_at' : 'NULL';
    $firstNameSql = user_identity_first_name_sql();
    // One result query replaces up to six separate role queries per account.
    $stmt = $conn->prepare("SELECT u.id, u.full_name, u.email, u.status, u.last_login, {$lockedAtSql} AS account_locked_at,
        {$firstNameSql} AS first_name,
        CASE WHEN TRIM(CONCAT(u.first_name, ' ', u.last_name)) = TRIM(u.full_name) THEN u.last_name
        ELSE TRIM(SUBSTRING(TRIM(u.full_name), CHAR_LENGTH({$firstNameSql}) + 1)) END AS last_name,
        CASE
            WHEN EXISTS (SELECT 1 FROM admin r WHERE r.UserID = u.id) THEN 'Admin'
            WHEN EXISTS (SELECT 1 FROM hr r WHERE r.UserID = u.id) THEN 'HR'
            WHEN EXISTS (SELECT 1 FROM payrollstaff r WHERE r.UserID = u.id) THEN 'Payroll Staff'
            WHEN EXISTS (SELECT 1 FROM timekeeper r WHERE r.UserID = u.id) THEN 'Timekeeper'
            WHEN EXISTS (SELECT 1 FROM assistantmanager r WHERE r.UserID = u.id) THEN 'Assistant Admin'
            WHEN EXISTS (SELECT 1 FROM managers r WHERE r.UserID = u.id) THEN 'Manager'
            WHEN EXISTS (SELECT 1 FROM worker r WHERE r.UserID = u.id) THEN 'Worker'
            ELSE 'User' END AS role
        FROM users u WHERE LOWER(COALESCE(u.status, '')) <> 'inactive' ORDER BY u.id DESC");
    if (!$stmt || !$stmt->execute()) throw new RuntimeException('Unable to load users.');
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    foreach ($users as &$user) $user['account_pending'] = false;
    unset($user);
    // Older employee records used UserID for the admin who created the record
    // instead of creating a dedicated login. Keep those employees visible in
    // User Management so the missing account is explicit rather than hidden.
    $pendingResult = $conn->query("
        SELECT
            w.WorkerID,
            CONCAT(TRIM(w.First_Name), ' ', TRIM(w.Last_Name)) AS full_name,
            wp.Email AS email
        FROM worker w
        LEFT JOIN worker_profile wp ON wp.WorkerID = w.WorkerID
        LEFT JOIN users linked_user ON linked_user.id = w.UserID
        LEFT JOIN admin a ON a.UserID = w.UserID
        LEFT JOIN assistantmanager am ON am.UserID = w.UserID
        LEFT JOIN managers manager_role ON manager_role.UserID = w.UserID
        LEFT JOIN hr hr_role ON hr_role.UserID = w.UserID
        LEFT JOIN payrollstaff ps ON ps.UserID = w.UserID
        LEFT JOIN timekeeper tk ON tk.UserID = w.UserID
        LEFT JOIN (
            SELECT UserID, COUNT(*) AS worker_count
            FROM worker
            WHERE UserID IS NOT NULL
            GROUP BY UserID
        ) worker_links ON worker_links.UserID = w.UserID
        WHERE linked_user.id IS NULL
           OR a.UserID IS NOT NULL
           OR am.UserID IS NOT NULL
           OR manager_role.UserID IS NOT NULL
           OR hr_role.UserID IS NOT NULL
           OR ps.UserID IS NOT NULL
           OR tk.UserID IS NOT NULL
           OR COALESCE(worker_links.worker_count, 0) > 1
        ORDER BY w.WorkerID DESC
    ");

    if ($pendingResult) {
        while ($worker = $pendingResult->fetch_assoc()) {
            $users[] = [
                'id' => 'worker-' . (int) $worker['WorkerID'],
                'worker_id' => (int) $worker['WorkerID'],
                'full_name' => trim((string) $worker['full_name']),
                'email' => $worker['email'] ?: '',
                'status' => 'Account Required',
                'last_login' => null,
                'account_locked_at' => null,
                'role' => 'Worker',
                'account_pending' => true,
            ];
        }
    }
    echo json_encode(['success' => true, 'data' => $users]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
