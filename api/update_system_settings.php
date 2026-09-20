<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_auth($conn, ['Admin', 'Assistant Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$maintenance_mode = (int) ($_POST['maintenance_mode'] ?? 0) === 1 ? 1 : 0;
$debug_mode = (int) ($_POST['debug_mode'] ?? 0) === 1 ? 1 : 0;
$retentionRaw = trim((string) ($_POST['data_retention_value'] ?? ''));
if ($retentionRaw !== '' && !preg_match('/^\d+$/', $retentionRaw)) {
    echo json_encode(['success' => false, 'message' => 'Data retention period must contain whole numbers only.']);
    exit;
}
$retention_value = (int) $retentionRaw;
$retention_unit = strtolower(trim((string) ($_POST['data_retention_unit'] ?? '')));
$retention_multipliers = ['days' => 1, 'weeks' => 7, 'months' => 30, 'years' => 365];

// Continue accepting the old days field for older cached frontends.
if ($retention_value > 0 && isset($retention_multipliers[$retention_unit])) {
    $data_retention_days = $retention_value * $retention_multipliers[$retention_unit];
} else {
    $data_retention_days = (int) ($_POST['data_retention_days'] ?? 365);
}
$backup_schedule = trim($_POST['backup_schedule'] ?? 'Daily');
$timezone = trim($_POST['timezone'] ?? 'Asia/Manila (GMT+8)');
$date_format = trim($_POST['date_format'] ?? 'MM/DD/YYYY');
$time_format = trim($_POST['time_format'] ?? '12-hour (AM/PM)');

// Validate inputs
if ($data_retention_days < 1) {
    echo json_encode(['success' => false, 'message' => 'Data retention period must be at least one day']);
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
