<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

try {
    $stmt = $conn->prepare("SELECT id, full_name, email, role, status, last_login FROM users ORDER BY id");
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['success' => true, 'data' => $users]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
