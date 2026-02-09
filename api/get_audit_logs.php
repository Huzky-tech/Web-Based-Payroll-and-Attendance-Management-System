<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

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

/* =========================
   Query
========================= */
$sql = "
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
            ELSE 'User'
        END AS role
    FROM Audit_logs a
    LEFT JOIN users u ON a.UserID = u.id
    LEFT JOIN Admin ad ON ad.UserID = a.UserID
    LEFT JOIN HR hr ON hr.UserID = a.UserID
    LEFT JOIN Timekeeper tk ON tk.UserID = a.UserID
    LEFT JOIN AssistantManager am ON am.UserID = a.UserID
    LEFT JOIN PayrollStaff ps ON ps.UserID = a.UserID
";

$where = [];
$params = [];
$types = '';

if (!empty($_GET['user'])) {
    $where[] = "u.email LIKE ?";
    $params[] = '%' . $_GET['user'] . '%';
    $types .= 's';
}

if (!empty($_GET['action'])) {
    $where[] = "a.Action LIKE ?";
    $params[] = '%' . $_GET['action'] . '%';
    $types .= 's';
}

if (!empty($_GET['start_date'])) {
    $where[] = "a.Date >= ?";
    $params[] = $_GET['start_date'] . ' 00:00:00';
    $types .= 's';
}

if (!empty($_GET['end_date'])) {
    $where[] = "a.Date <= ?";
    $params[] = $_GET['end_date'] . ' 23:59:59';
    $types .= 's';
}

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= " ORDER BY a.Date DESC LIMIT 100";

$stmt = $conn->prepare($sql);
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
   Output
========================= */
$logs = [];
while ($row = $result->fetch_assoc()) {
    $action = $row['Action'];
    $logs[] = [
        'ts' => $row['Date'],
        'user' => $row['email'] ?: 'System',
        'role' => $row['role'],
        'action' => $action,
        'resource' => 'Audit Log',
        'status' => getStatus($action),
        'severity' => getSeverity($action),
        'details' => $row['Details'] ?? ''
    ];
}

$stmt->close();
$conn->close();

echo json_encode($logs);

?>
