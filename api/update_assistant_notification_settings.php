<?php
header('Content-Type: application/json');
require_once __DIR__ . '/connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';

require_auth($conn, ['Assistant Admin']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

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

$inSystem = (int) ($_POST['in_system_notifications'] ?? 0) === 1 ? 1 : 0;
$payroll = (int) ($_POST['payroll_processing'] ?? 0) === 1 ? 1 : 0;
$attendance = (int) ($_POST['attendance_issues'] ?? 0) === 1 ? 1 : 0;
$siteAssignments = (int) ($_POST['site_assignments'] ?? 0) === 1 ? 1 : 0;
$overtime = (int) ($_POST['overtime_requests'] ?? 0) === 1 ? 1 : 0;

$stmt = $conn->prepare("INSERT INTO assistant_notification_settings
    (UserID, in_system_notifications, payroll_processing, attendance_issues, site_assignments, overtime_requests)
    VALUES (?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        in_system_notifications = VALUES(in_system_notifications),
        payroll_processing = VALUES(payroll_processing),
        attendance_issues = VALUES(attendance_issues),
        site_assignments = VALUES(site_assignments),
        overtime_requests = VALUES(overtime_requests)");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Unable to save notification settings.']);
    exit;
}
$stmt->bind_param('iiiiii', $userId, $inSystem, $payroll, $attendance, $siteAssignments, $overtime);
$success = $stmt->execute();
$stmt->close();

echo json_encode([
    'success' => $success,
    'message' => $success ? 'Notification settings saved successfully.' : 'Unable to save notification settings.'
]);
?>
