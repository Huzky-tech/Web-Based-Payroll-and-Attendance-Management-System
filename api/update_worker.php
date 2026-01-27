<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $role = $_POST['role'] ?? '';
    $site_id = $_POST['site_id'] ?? null;
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $joined_date = $_POST['joined_date'] ?? null;
    $status = $_POST['status'] ?? 'active';

    $stmt = $conn->prepare("UPDATE workers SET name=?, role=?, status=?, site_id=?, email=?, phone=?, joined_date=? WHERE id=?");
    $stmt->bind_param("sssssssi", $name, $role, $status, $site_id, $email, $phone, $joined_date, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Worker updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating worker: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
