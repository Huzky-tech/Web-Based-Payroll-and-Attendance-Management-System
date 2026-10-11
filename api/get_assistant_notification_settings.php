<?php
header('Content-Type: application/json');
require_once __DIR__ . '/connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';

require_auth($conn, ['Assistant Admin']);
$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Unable to identify the current user.']);
    exit;
}

$schemaSql = "CREATE TABLE IF NOT EXISTS assistant_notification_settings (
    UserID INT NOT NULL,
    in_system_notifications TINYINT(1) NOT NULL DEFAULT 1,
    payroll_processing TINYINT(1) NOT NULL DEFAULT 1,
    attendance_issues TINYINT(1) NOT NULL DEFAULT 1,
    site_assignments TINYINT(1) NOT NULL DEFAULT 1,
    overtime_requests TINYINT(1) NOT NULL DEFAULT 1,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (UserID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($schemaSql)) {
    echo json_encode(['success' => false, 'message' => 'Unable to prepare notification settings.']);
    exit;
}

$insertStmt = $conn->prepare('INSERT IGNORE INTO assistant_notification_settings (UserID) VALUES (?)');
if ($insertStmt) {
    $insertStmt->bind_param('i', $userId);
    $insertStmt->execute();
    $insertStmt->close();
}

$stmt = $conn->prepare('SELECT in_system_notifications, payroll_processing, attendance_issues, site_assignments, overtime_requests FROM assistant_notification_settings WHERE UserID = ? LIMIT 1');
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Unable to load notification settings.']);
    exit;
}
$stmt->bind_param('i', $userId);
$stmt->execute();
$settings = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

echo json_encode(['success' => true, 'data' => $settings]);
?>
