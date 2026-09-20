<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_auth($conn, ['Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email_notifications = (int) ($_POST['email_notifications'] ?? 0) === 1 ? 1 : 0;
$in_system_notifications = (int) ($_POST['in_system_notifications'] ?? 0) === 1 ? 1 : 0;
$leave_request_updates = (int) ($_POST['leave_request_updates'] ?? 0) === 1 ? 1 : 0;
$payroll_processing = (int) ($_POST['payroll_processing'] ?? 0) === 1 ? 1 : 0;
$attendance_issues = (int) ($_POST['attendance_issues'] ?? 0) === 1 ? 1 : 0;
$system_updates = (int) ($_POST['system_updates'] ?? 0) === 1 ? 1 : 0;
$daily_reports = (int) ($_POST['daily_reports'] ?? 0) === 1 ? 1 : 0;
$email_digest_frequency = trim($_POST['email_digest_frequency'] ?? 'Daily');

try {
    $stmt = $conn->prepare("UPDATE notification_settings SET email_notifications = ?, in_system_notifications = ?, leave_request_updates = ?, payroll_processing = ?, attendance_issues = ?, system_updates = ?, daily_reports = ?, email_digest_frequency = ? WHERE id = 1");
    $stmt->bind_param("iiiiiiis", $email_notifications, $in_system_notifications, $leave_request_updates, $payroll_processing, $attendance_issues, $system_updates, $daily_reports, $email_digest_frequency);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Notification settings updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update settings']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
