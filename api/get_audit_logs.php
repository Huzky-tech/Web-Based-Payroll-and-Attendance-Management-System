<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';

require_auth($conn, ['Admin', 'Assistant Admin']);

/* =========================
   Helpers
========================= */
function getSeverity($action) {
    $a = strtolower($action);
    if (stripos($a, 'failed') !== false || stripos($a, 'error') !== false || stripos($a, 'block') !== false) return 'High';
    if (stripos($a, 'update') !== false || stripos($a, 'change') !== false || stripos($a, 'modify') !== false) return 'Medium';
    return 'Info';
}

function getStatus($action) {
    $a = strtolower($action);
    if (stripos($a, 'failed') !== false || stripos($a, 'error') !== false) return 'failure';
    if (stripos($a, 'warning') !== false) return 'warning';
    return 'success';
}

function sanitize_audit_details(string $details): string {
    $explicit = [
        '/\(\s*ID\s*:\s*\d+\s*\)/i',
        '/\bID\s*:\s*\d+\b/i',
        '/\bSiteID\b\s*:\s*\d+/i',
        '/\bSite\s*#\s*\d+\b/i',
        '/\bsite\s*#\s*\d+\b/i',
        '/\(\s*Assignment\s*ID\s*:\s*\d+\s*\)/i',
        '/\bAssignment\s*ID\b\s*:\s*\d+/i',
        '/\(\s*Worker\s*ID\s*:\s*\d+\s*\)/i',
        '/\bWorker\s*ID\b\s*:\s*\d+/i',
        '/\bAttendance\s*ID\b\s*:?\s*\d+\s*:?/i',
        '/\bAttendanceID\b\s*:?\s*\d+\s*:?/i',
        '/\(\s*UserID\s*:?\s*\d+\s*\)/i',
        '/\bUserID\b\s*:?\s*\d+/i',
        '/\bPayroll\s*record\s*#\s*\d+/i',
        '/\bPayroll\s*ID\b\s*:\s*\d+/i',
        '/\bPay\s*record\s*#\s*\d+/i',
        '/\b\#\s*\d+\b/'
    ];

    foreach ($explicit as $p) {
        $details = preg_replace($p, '', $details);
    }

    $details = preg_replace('/\[ID\]/i', '', $details);
    $details = preg_replace('/\(\s*\)/', '', $details);
    $details = preg_replace('/\(\s*\)/', '', $details);
    $details = preg_replace('/\s{2,}/', ' ', $details);
    $details = preg_replace('/\s+,/', ',', $details);
    $details = preg_replace('/\(\s*,/', '(', $details);
    $details = trim($details);

    return $details;
}

/* =========================
   Pagination & Filters
========================= */
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$action_filter = $_GET['action_filter'] ?? '';
$status_filter = $_GET['status_filter'] ?? '';
$severity_filter = $_GET['severity_filter'] ?? '';
$user_filter = $_GET['user'] ?? '';
$start_date = $_GET['start_date'] ?? '';

/* =========================
   Build Base Query
========================= */
$base_sql = "
    SELECT
        a.Date,
        a.Action,
        a.Details,
        u.email,
        CASE
            WHEN ad.UserID IS NOT NULL THEN 'Admin'
            WHEN hr.UserID IS NOT NULL THEN 'HR'
            WHEN tk.UserID IS NOT NULL THEN 'Timekeeper'
            WHEN am.UserID IS NOT NULL THEN 'AssistantManager'
            WHEN ps.UserID IS NOT NULL THEN 'PayrollStaff'
            WHEN w.UserID IS NOT NULL THEN 'Worker'
            ELSE 'User'
        END AS role
    FROM Audit_logs a
    LEFT JOIN users u ON a.UserID = u.id
    LEFT JOIN Admin ad ON ad.UserID = a.UserID
    LEFT JOIN hr ON hr.UserID = a.UserID
    LEFT JOIN Timekeeper tk ON tk.UserID = a.UserID
    LEFT JOIN AssistantManager am ON am.UserID = a.UserID
    LEFT JOIN PayrollStaff ps ON ps.UserID = a.UserID
    LEFT JOIN worker w ON w.UserID = a.UserID
";

$count_sql = "
    SELECT COUNT(*) as total
    FROM Audit_logs a
    LEFT JOIN users u ON a.UserID = u.id
";

$where = [];
$params = [];
$types = '';

// Search across multiple fields
if (!empty($search)) {
    $where[] = "(u.email LIKE ? OR a.Action LIKE ? OR a.Details LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'sss';
}

// Filter by user (email)
if (!empty($user_filter)) {
    $where[] = "u.email LIKE ?";
    $params[] = '%' . $user_filter . '%';
    $types .= 's';
}

// Filter by action (exact match or LIKE)
if (!empty($action_filter)) {
    $where[] = "a.Action LIKE ?";
    $params[] = '%' . $action_filter . '%';
    $types .= 's';
}

// Filter by the selected calendar date.
if (!empty($start_date)) {
    $validDate = DateTime::createFromFormat('Y-m-d', $start_date);
    if ($validDate && $validDate->format('Y-m-d') === $start_date) {
        $where[] = "a.Date >= ? AND a.Date < DATE_ADD(?, INTERVAL 1 DAY)";
        $params[] = $start_date . ' 00:00:00';
        $params[] = $start_date . ' 00:00:00';
        $types .= 'ss';
    }
}



// Status and Severity filters are applied in PHP after fetching
// We'll use a broader query and filter in PHP loop
$whereClause = $where ? ' WHERE ' . implode(' AND ', $where) : '';

/* =========================
   Get Total Count First
========================= */
$count_query = $count_sql . $whereClause;
$count_stmt = $conn->prepare($count_query);
if (!$count_stmt) {
    echo json_encode(['error' => $conn->error]);
    exit;
}
if ($params) {
    $count_stmt->bind_param($types, ...$params);
}
if (!$count_stmt->execute()) {
    echo json_encode(['error' => $count_stmt->error]);
    exit;
}
$count_result = $count_stmt->get_result();
$total_all = (int)$count_result->fetch_assoc()['total'];
$count_stmt->close();

/* =========================
   Fetch Data (unlimited for now, filter in PHP)
========================= */
// For proper server-side filtering by status/severity, we'd need DB columns.
// Since these are derived, we fetch a generous limit and filter in PHP.
$data_sql = $base_sql . $whereClause . " ORDER BY a.Date DESC";

// For performance, limit the raw query to a reasonable number
$data_sql .= " LIMIT 1000";

$stmt = $conn->prepare($data_sql);
if (!$stmt) {
    echo json_encode(['error' => $conn->error]);
    exit;
}
if ($params) {
    $stmt->bind_param($types, ...$params);
}
if (!$stmt->execute()) {
    echo json_encode(['error' => $stmt->error]);
    exit;
}
$result = $stmt->get_result();

/* =========================
   Process & Filter
========================= */
$allLogs = [];
while ($row = $result->fetch_assoc()) {
    $action = $row['Action'];
    $status = getStatus($action);
    $severity = getSeverity($action);

    // Apply status filter
    if (!empty($status_filter) && $status !== $status_filter) {
        continue;
    }
    // Apply severity filter
    if (!empty($severity_filter) && $severity !== $severity_filter) {
        continue;
    }

    $rawDetails = (string) ($row['Details'] ?? '');
    $allLogs[] = [
        'ts' => $row['Date'],
        'user' => $row['email'] ?: 'System',
        'role' => $row['role'],
        'action' => $action,
        'resource' => 'Audit Log',
        'status' => $status,
        'severity' => $severity,
        'details' => sanitize_audit_details($rawDetails),
    ];
}

/* =========================
   Apply Pagination
========================= */
$total_filtered = count($allLogs);
$paginatedLogs = array_slice($allLogs, $offset, $limit);

$stmt->close();
$conn->close();

echo json_encode([
    'logs' => $paginatedLogs,
    'total' => $total_filtered,
    'page' => $page,
    'limit' => $limit,
    'totalPages' => max(1, ceil($total_filtered / $limit))
]);
?>
