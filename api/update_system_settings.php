<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
$debug_mode = isset($_POST['debug_mode']) ? 1 : 0;
$data_retention_days = intval($_POST['data_retention_days'] ?? 365);
$backup_schedule = trim($_POST['backup_schedule'] ?? 'Daily');
$timezone = trim($_POST['timezone'] ?? 'Asia/Manila (GMT+8)');
$date_format = trim($_POST['date_format'] ?? 'MM/DD/YYYY');
$time_format = trim($_POST['time_format'] ?? '12-hour (AM/PM)');

// Validate inputs
if ($data_retention_days < 1) {
    echo json_encode(['success' => false, 'message' => 'Data retention days must be positive']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE system_settings SET maintenance_mode = ?, debug_mode = ?, data_retention_days = ?, backup_schedule = ?, timezone = ?, date_format = ?, time_format = ? WHERE id = 1");
    $stmt->bind_param("iiissss", $maintenance_mode, $debug_mode, $data_retention_days, $backup_schedule, $timezone, $date_format, $time_format);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'System settings updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update settings']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
