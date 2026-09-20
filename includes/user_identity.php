<?php
// Preserve separate names; full_name alone cannot represent compound surnames.
function user_identity_ensure_columns(mysqli $conn): array
{
    $columns = $conn->query('SHOW COLUMNS FROM users');
    if (!$columns) throw new RuntimeException('Unable to check user name fields.');
    $existing = array_column($columns->fetch_all(MYSQLI_ASSOC), 'Field');
    $add = [];
    foreach (['first_name', 'last_name'] as $column) {
        if (!in_array($column, $existing, true)) $add[] = "ADD COLUMN {$column} VARCHAR(50) NULL";
    }
    if ($add) {
        $lock = $conn->query("SELECT GET_LOCK(CONCAT(DATABASE(), ':user_identity_schema'), 5) AS acquired");
        if (!$lock || (int) $lock->fetch_assoc()['acquired'] !== 1) {
            throw new RuntimeException('User management is initializing. Please try again.');
        }
        try {
            // A concurrent request may have added the columns while we waited.
            $columns = $conn->query('SHOW COLUMNS FROM users');
            if (!$columns) throw new RuntimeException('Unable to check user name fields.');
            $existing = array_column($columns->fetch_all(MYSQLI_ASSOC), 'Field');
            $add = [];
            foreach (['first_name', 'last_name'] as $column) {
                if (!in_array($column, $existing, true)) $add[] = "ADD COLUMN {$column} VARCHAR(50) NULL";
            }
            if ($add && !$conn->query('ALTER TABLE users ' . implode(', ', $add))) {
                throw new RuntimeException('Unable to initialize user name fields.');
            }
        } finally {
            $conn->query("SELECT RELEASE_LOCK(CONCAT(DATABASE(), ':user_identity_schema'))");
        }
    }
    return array_unique(array_merge($existing, ['first_name', 'last_name']));
}

function user_identity_first_name_sql(): string
{
    // Use the employee's original name when available. Older staff accounts
    // follow the same last-word surname convention as the existing edit form.
    return "COALESCE(CASE WHEN TRIM(CONCAT(u.first_name, ' ', u.last_name)) = TRIM(u.full_name)
        THEN NULLIF(u.first_name, '') END,
        (SELECT NULLIF(TRIM(w.First_Name), '') FROM worker w
         WHERE w.UserID = u.id AND LOWER(TRIM(CONCAT(w.First_Name, ' ', w.Last_Name))) = LOWER(TRIM(u.full_name))
         ORDER BY w.WorkerID LIMIT 1),
        CASE WHEN LOCATE(' ', TRIM(u.full_name)) = 0 THEN TRIM(u.full_name)
        ELSE TRIM(LEFT(TRIM(u.full_name), CHAR_LENGTH(TRIM(u.full_name)) - CHAR_LENGTH(SUBSTRING_INDEX(TRIM(u.full_name), ' ', -1)) - 1)) END)";
}

// Only a dedicated worker login is the same identity. Legacy creator links
// and shared accounts must never be excluded or renamed as the employee.
function user_identity_linked_user(mysqli $conn, int $workerId): int
{
    $sql = "SELECT w.UserID FROM worker w WHERE w.WorkerID = ?
        AND NOT EXISTS (SELECT 1 FROM worker other WHERE other.UserID = w.UserID AND other.WorkerID <> w.WorkerID)";
    foreach (['admin', 'hr', 'payrollstaff', 'timekeeper', 'assistantmanager'] as $table) {
        $sql .= " AND NOT EXISTS (SELECT 1 FROM {$table} r WHERE r.UserID = w.UserID)";
    }
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new RuntimeException('Unable to verify linked account.');
    $stmt->bind_param('i', $workerId);
    if (!$stmt->execute()) throw new RuntimeException('Unable to verify linked account.');
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int) ($row['UserID'] ?? 0);
}

function user_identity_linked_worker(mysqli $conn, int $userId): int
{
    if ($userId <= 0) return 0;
    $stmt = $conn->prepare('SELECT WorkerID FROM worker WHERE UserID = ?');
    if (!$stmt) throw new RuntimeException('Unable to verify linked employee.');
    $stmt->bind_param('i', $userId);
    if (!$stmt->execute()) throw new RuntimeException('Unable to verify linked employee.');
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    if (count($rows) !== 1) return 0;
    $workerId = (int) $rows[0]['WorkerID'];
    return user_identity_linked_user($conn, $workerId) === $userId ? $workerId : 0;
}

function user_identity_full_name_exists(mysqli $conn, string $fullName, int $excludeId = 0, int $excludeWorkerId = 0): bool
{
    $fullName = trim(preg_replace('/\s+/u', ' ', $fullName));
    if ($excludeWorkerId > 0) $excludeId = user_identity_linked_user($conn, $excludeWorkerId);
    elseif ($excludeId > 0) $excludeWorkerId = user_identity_linked_worker($conn, $excludeId);
    $stmt = $conn->prepare("SELECT id FROM users
        WHERE LOWER(TRIM(REGEXP_REPLACE(full_name, '[[:space:]]+', ' '))) = LOWER(?) AND id <> ?
        UNION ALL SELECT WorkerID FROM worker
        WHERE LOWER(TRIM(REGEXP_REPLACE(CONCAT(First_Name, ' ', Last_Name), '[[:space:]]+', ' '))) = LOWER(?)
        AND WorkerID <> ? LIMIT 1");
    if (!$stmt) throw new RuntimeException('Unable to check full name availability.');
    $stmt->bind_param('sisi', $fullName, $excludeId, $fullName, $excludeWorkerId);
    if (!$stmt->execute()) throw new RuntimeException('Unable to check full name availability.');
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    // Some older installations still expose the legacy employee/worker forms.
    foreach (['employees', 'workers'] as $table) {
        if ($exists) break;
        $tables = $conn->query("SHOW TABLES LIKE '{$table}'");
        if (!$tables) throw new RuntimeException('Unable to check employee names.');
        if ($tables->num_rows === 0) continue;
        $legacy = $conn->prepare("SELECT 1 FROM {$table} WHERE LOWER(TRIM(REGEXP_REPLACE(name, '[[:space:]]+', ' '))) = LOWER(?) LIMIT 1");
        if (!$legacy) throw new RuntimeException('Unable to check employee names.');
        $legacy->bind_param('s', $fullName);
        if (!$legacy->execute()) throw new RuntimeException('Unable to check employee names.');
        $exists = $legacy->get_result()->num_rows > 0;
        $legacy->close();
    }
    return $exists;
}
function user_identity_lock(mysqli $conn): void
{
    // Serialize user-management writes so simultaneous submissions cannot pass
    // the duplicate check before either insert is committed.
    $result = $conn->query("SELECT GET_LOCK(CONCAT(DATABASE(), ':user_identity'), 5) AS acquired");
    if (!$result || (int) $result->fetch_assoc()['acquired'] !== 1) {
        throw new RuntimeException('Another account is being saved. Please try again shortly.');
    }
    register_shutdown_function(static function () use ($conn): void {
        try { $conn->query("SELECT RELEASE_LOCK(CONCAT(DATABASE(), ':user_identity'))"); } catch (Throwable $ignored) {}
    });
}

function user_identity_email_exists(mysqli $conn, string $email, int $excludeUserId = 0): bool
{
    $workerId = user_identity_linked_worker($conn, $excludeUserId);
    $stmt = $conn->prepare("SELECT id FROM users WHERE LOWER(TRIM(email)) = LOWER(?) AND id <> ?
        UNION ALL SELECT WorkerID FROM worker_profile WHERE LOWER(TRIM(Email)) = LOWER(?) AND WorkerID <> ? LIMIT 1");
    if (!$stmt) throw new RuntimeException('Unable to check email availability.');
    $email = trim($email);
    $stmt->bind_param('sisi', $email, $excludeUserId, $email, $workerId);
    if (!$stmt->execute()) throw new RuntimeException('Unable to check email availability.');
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $exists;
}
