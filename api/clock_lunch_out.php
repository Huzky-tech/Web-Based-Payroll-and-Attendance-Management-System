<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once 'connection/db_config.php';
require_once 'record_audit_log.php';
require_once 'site_schedule_helpers.php';
require_once '../includes/auth.php';
require_once __DIR__ . '/timekeeper_assignment_helpers.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'Timekeeper']);

if (!attendance_lunch_columns_exist($conn)) {
    echo json_encode(['success' => false, 'error' => 'Lunch attendance is not enabled yet']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$attendance_id = (int) ($input['attendance_id'] ?? 0);
$current_time = date('H:i:s');

try {
    if ($attendance_id <= 0) {
        throw new Exception('Attendance ID required');
    }

    $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
    if ($currentRole === 'Timekeeper') {
        $accessError = validate_timekeeper_attendance_record($conn, $currentUserId, $attendance_id);
        if ($accessError !== null) {
            throw new Exception($accessError);
        }
    }

    $pdo = getDbConnection();

    $check_sql = "SELECT WorkerID, SiteID, Time_In, Lunch_Out, Time_Out FROM attendance WHERE AttendanceID = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$attendance_id]);
    $record = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record || empty($record['Time_In'])) {
        throw new Exception('Worker must be clocked in before lunch out');
    }

    if (!empty($record['Lunch_Out']) && $record['Lunch_Out'] !== '00:00:00') {
        throw new Exception('Lunch out has already been recorded');
    }

    $lunchOutTime = $current_time;

    $update_sql = "UPDATE attendance SET Lunch_Out = ? WHERE AttendanceID = ?";
    $stmt = $pdo->prepare($update_sql);
    $stmt->execute([$lunchOutTime, $attendance_id]);

    $user_id = (int) ($_SESSION['user_id'] ?? 0);
    if ($user_id > 0) {
        record_audit_log($user_id, 'Lunch Out', "Worker {$record['WorkerID']} lunch out at site {$record['SiteID']}");
    }

    echo json_encode([
        'success' => true,
        'attendance_id' => $attendance_id,
        'lunch_out' => $lunchOutTime,
        'message' => 'Lunch out recorded successfully'
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
