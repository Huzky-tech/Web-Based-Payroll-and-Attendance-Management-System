<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

$data = json_decode(file_get_contents('php://input'), true);

$name = $data['name'];
$position = $data['position'];
$site = $data['site'];
$salary = $data['salary'];
$join_date = $data['join_date'];

if (!$name || !$position || !$site || !$salary || !$join_date) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

$sql = "INSERT INTO employees (name, position, site, salary, join_date) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $name, $position, $site, $salary, $join_date);

if ($stmt->execute()) {
    // Log to audit
    $audit_sql = "INSERT INTO audit_logs (action) VALUES (?)";
    $audit_stmt = $conn->prepare($audit_sql);
    $action = "Added employee: $name";
    $audit_stmt->bind_param("s", $action);
    $audit_stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Employee added successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add employee']);
}

$stmt->close();
$conn->close();
?>
