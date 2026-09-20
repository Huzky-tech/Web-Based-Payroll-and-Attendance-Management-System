<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

try {
    $stmt = $conn->prepare("SELECT * FROM notification_settings WHERE id = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $settings = $result->fetch_assoc();

    if ($settings) {
        echo json_encode(['success' => true, 'data' => $settings]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Settings not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
