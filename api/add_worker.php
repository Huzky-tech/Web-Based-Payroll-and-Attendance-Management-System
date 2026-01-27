<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $role = $_POST['role'] ?? '';
    $site_name = $_POST['site'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $joined_date = $_POST['joined_date'] ?? null;
    $status = $_POST['status'] ?? 'active';

    // Get site_id from site name
    $site_stmt = $conn->prepare("SELECT id FROM sites WHERE site_name = ?");
    $site_stmt->bind_param("s", $site_name);
    $site_stmt->execute();
    $site_result = $site_stmt->get_result();
    $site_id = null;
    if ($site_result->num_rows > 0) {
        $site_row = $site_result->fetch_assoc();
        $site_id = $site_row['id'];
    }
    $site_stmt->close();

    // Generate initials
    $name_parts = explode(' ', trim($name));
    $initials = strtoupper(substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : ''));

    $stmt = $conn->prepare("INSERT INTO workers (name, initials, role, status, site_id, email, phone, joined_date, attendance_rate, color) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 90, 'blue')");
    $stmt->bind_param("ssssssss", $name, $initials, $role, $status, $site_id, $email, $phone, $joined_date);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Worker added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding worker: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
