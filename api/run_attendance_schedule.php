<?php
/**
 * Run this script from a Hostinger cron job once per minute.
 * It writes AM OUT at each site's configured LunchStart; it never writes PM OUT.
 */
$isCli = PHP_SAPI === 'cli';
if (!$isCli) {
    header('Content-Type: application/json');
}

require_once __DIR__ . '/connection/db_config.php';
require_once __DIR__ . '/attendance_auto_lunch.php';

if (!$isCli) {
    require_once __DIR__ . '/../includes/auth.php';
    require_auth($conn, ['Admin', 'Assistant Admin']);
}

$updated = attendance_apply_due_lunch_out($conn);
$result = [
    'success' => true,
    'am_out_records_created' => $updated,
    'message' => 'Attendance schedule completed.',
];

if ($isCli) {
    echo 'Attendance schedule completed. AM OUT records created: ' . $updated . PHP_EOL;
} else {
    echo json_encode($result);
}

$conn->close();
