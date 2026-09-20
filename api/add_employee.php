<?php
require_once __DIR__ . '/../includes/user_identity.php';
header('Content-Type: application/json');
include 'connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_auth($conn, ['Admin', 'Assistant Admin']);
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function logAudit($conn, $userId, $action, $details) {
    $sql = "INSERT INTO Audit_logs (UserID, Action, Details, Date) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $userId, $action, $details);
    $stmt->execute();
    $stmt->close();
}

$data = json_decode(file_get_contents('php://input'), true);

$name = $data['name'];
$position = $data['position'];
$site = $data['site'];
$salary = $data['salary'];
$join_date = $data['join_date'];
$userId = (int) ($_SESSION['user_id'] ?? ($data['userId'] ?? 0));

if (!$name || !$position || !$site || !$salary || !$join_date) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized audit user']);
    exit;
}

// Assuming 'employees' table exists or update to Worker table if needed
$sql = "INSERT INTO employees (name, position, site, salary, join_date) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $name, $position, $site, $salary, $join_date);

if ($stmt->execute()) {
    logAudit($conn, $userId, 'Employee Added', "Added employee: $name");
    echo json_encode(['success' => true, 'message' => 'Employee added successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add employee']);
}

$stmt->close();
$conn->close();
?>
