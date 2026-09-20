<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_auth($conn, ['Admin']);
include 'system_settings_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    if (!ensure_password_reset_columns($conn)) {
        throw new RuntimeException('Unable to prepare password reset fields.');
    }
    $stmt = $conn->prepare("UPDATE users SET force_password_reset = 1, password_last_set_at = NULL WHERE status = 'Active'");
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Password reset enforcement enabled for all active users']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to initiate password reset']);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
