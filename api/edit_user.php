<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = intval($_POST['user_id'] ?? 0);
$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = $_POST['role'] ?? 'Worker';
$status = $_POST['status'] ?? 'Active';

// Validate inputs
if ($user_id <= 0 || empty($full_name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Check if email exists for another user
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt->bind_param("si", $email, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $full_name, $email, $role, $status, $user_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
