<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

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
$userId = $data['userId'] ?? 1; // Assume user ID is passed or default to 1

if (!$name || !$position || !$site || !$salary || !$join_date) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
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
