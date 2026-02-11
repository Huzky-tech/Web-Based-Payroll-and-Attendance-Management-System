<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
$in_system_notifications = isset($_POST['in_system_notifications']) ? 1 : 0;
$leave_request_updates = isset($_POST['leave_request_updates']) ? 1 : 0;
$payroll_processing = isset($_POST['payroll_processing']) ? 1 : 0;
$attendance_issues = isset($_POST['attendance_issues']) ? 1 : 0;
$system_updates = isset($_POST['system_updates']) ? 1 : 0;
$daily_reports = isset($_POST['daily_reports']) ? 1 : 0;
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
