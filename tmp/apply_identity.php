<?php
$p='includes/user_identity.php';$s=file_get_contents($p);$a=strpos($s,'function user_identity_full_name_exists(');$b=strpos($s,'function user_identity_lock(', $a);
$s=substr_replace($s, <<<'CODE'
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
    return $exists;
}

CODE
,$a,$b-$a);file_put_contents($p,$s);
foreach(['api/create_employee.php','api/update_employee.php','api/update_my_profile.php','api/check_duplicate_employee.php','api/add_employee.php','api/sample_data_seeder.php'] as $p){$s=file_get_contents($p);$s=preg_replace('/<\?php/',"<?php\nrequire_once __DIR__ . '/../includes/user_identity.php';",$s,1);file_put_contents($p,$s);}
$p='api/create_employee.php';$s=file_get_contents($p);$a=strpos($s,'$duplicateWorkerStmt =');$b=strpos($s,'$conn->begin_transaction();',$a);$s=substr_replace($s, <<<'CODE'
try {
    user_identity_ensure_columns($conn);
    user_identity_lock($conn);
    if (user_identity_full_name_exists($conn, $firstName . ' ' . $lastName)) {
        throw new RuntimeException('This first and last name combination is already registered.');
    }
    login_security_ensure_columns($conn);
} catch (Throwable $error) {
    echo json_encode(['success' => false, 'message' => $error->getMessage()]);
    exit;
}

CODE
,$a,$b-$a);$s=str_replace('    login_security_ensure_columns($conn);' . "\r\n",'', $s);
$s=str_replace('INSERT INTO users (email, password, full_name, status, password_last_set_at, must_change_password) VALUES (?, ?, ?,', 'INSERT INTO users (email, password, full_name, first_name, last_name, status, password_last_set_at, must_change_password) VALUES (?, ?, ?, ?, ?,', $s);
$s=str_replace("\$accountStmt->bind_param('sss', \$email, \$accountHash, \$workerFullName);", "\$accountStmt->bind_param('sssss', \$email, \$accountHash, \$workerFullName, \$firstName, \$lastName);",$s);file_put_contents($p,$s);
$p='api/check_duplicate_employee.php';$s=file_get_contents($p);$a=strpos($s,'$sql =');$s=substr($s,0,$a). <<<'CODE'
try {
    $duplicate = user_identity_full_name_exists($conn, $firstName . ' ' . $lastName, 0, $excludeWorkerId);
    echo json_encode(['success' => true, 'duplicate' => $duplicate,
        'message' => $duplicate ? 'This first and last name combination is already registered.' : '']);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to check full name availability.']);
}
CODE;
file_put_contents($p,$s);
