<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'];

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
    exit;
}

// Get employee name for audit log
$sql = "SELECT name FROM employees WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

if (!$employee) {
    echo json_encode(['success' => false, 'message' => 'Employee not found']);
    exit;
}

$name = $employee['name'];

// Delete employee
$sql = "DELETE FROM employees WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // Log to audit
    $audit_sql = "INSERT INTO audit_logs (action) VALUES (?)";
    $audit_stmt = $conn->prepare($audit_sql);
    $action = "Deleted employee: $name";
    $audit_stmt->bind_param("s", $action);
    $audit_stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Employee deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete employee']);
}

$stmt->close();
$conn->close();
?>
