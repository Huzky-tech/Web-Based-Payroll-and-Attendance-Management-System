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
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);

function getCurrentPayrollStaffId(mysqli $conn, int $userId): int
{
    $stmt = $conn->prepare("SELECT PayrollStaff_ID FROM payrollstaff WHERE UserID = ? LIMIT 1");
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return (int) ($row['PayrollStaff_ID'] ?? 0);
}

$input = json_decode(file_get_contents('php://input'), true);
$attendance_id = (int) ($input['attendance_id'] ?? 0);
$current_time = date('H:i:s');

try {
    if ($attendance_id <= 0) {
        throw new Exception('Attendance ID required');
    }

    if ($currentRole === 'Payroll Staff') {
        $payrollStaffId = getCurrentPayrollStaffId($conn, $currentUserId);
        if ($payrollStaffId <= 0) {
            throw new Exception('Your payroll staff account is not linked to any staff profile');
        }

        $scopeStmt = $conn->prepare("
            SELECT 1
            FROM attendance a
            INNER JOIN payrollstaffassignment psa ON psa.SiteID = a.SiteID
            WHERE a.AttendanceID = ? AND psa.PayrollStaff_ID = ?
            LIMIT 1
        ");
        $scopeStmt->bind_param("ii", $attendance_id, $payrollStaffId);
        $scopeStmt->execute();
        $scopeResult = $scopeStmt->get_result();
        $hasAccess = $scopeResult && $scopeResult->num_rows > 0;
        $scopeStmt->close();

        if (!$hasAccess) {
            throw new Exception('You can only clock out workers for your assigned sites');
        }
    }

    if ($currentRole === 'Timekeeper') {
        $accessError = validate_timekeeper_attendance_record($conn, $currentUserId, $attendance_id);
        if ($accessError !== null) {
            throw new Exception($accessError);
        }
    }

    $pdo = getDbConnection();
    $pdo->beginTransaction();

    $lunchSelect = attendance_lunch_columns_exist($conn)
        ? 'Time_In, Lunch_Out, Lunch_In'
        : 'Time_In';

    $check_sql = "SELECT WorkerID, SiteID, Date, {$lunchSelect} FROM attendance WHERE AttendanceID = ? AND (Time_Out IS NULL OR Time_Out = '00:00:00')";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$attendance_id]);
    $record = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        throw new Exception('No open clock-in found for this record');
    }

    $lunchOut = $record['Lunch_Out'] ?? null;
    $lunchIn = $record['Lunch_In'] ?? null;
    if (attendance_lunch_columns_exist($conn)
        && (empty($lunchOut) || $lunchOut === '00:00:00' || empty($lunchIn) || $lunchIn === '00:00:00')) {
        throw new Exception('Record Lunch Out and PM Time In before clocking out.');
    }

    $siteId = (int) ($record['SiteID'] ?? 0);
    $siteSchedule = get_site_schedule_row($conn, $siteId);

    $hourTotals = calculate_attendance_hours_worked(
        $record['Time_In'] ?? null,
        $lunchOut,
        $lunchIn,
        $current_time,
        $siteSchedule
    );

    $hours_worked = (float) ($hourTotals['hours_worked'] ?? 0);
    $overtime_hours = (float) ($hourTotals['overtime_hours'] ?? 0);

    $shiftStart = (string) ($siteSchedule['ShiftStart'] ?? '07:00:00');
    $graceMinutes = 30;
    $expectedInMin = (site_schedule_time_to_minutes($shiftStart) ?? 420) + $graceMinutes;
    $timeInMin = site_schedule_time_to_minutes($record['Time_In'] ?? null) ?? 0;
    $status = $timeInMin <= $expectedInMin ? 'Present' : 'Late';

    if (attendance_lunch_columns_exist($conn)) {
        $update_sql = "
            UPDATE attendance
            SET Time_Out = ?, Hours_Worked = ?, Overtime_Hours = ?, AttendanceStatus = ?
            WHERE AttendanceID = ?
        ";
        $stmt = $pdo->prepare($update_sql);
        $stmt->execute([
            $current_time,
            $hours_worked,
            $overtime_hours,
            $status,
            $attendance_id
        ]);
    } else {
        $update_sql = "
            UPDATE attendance
            SET Time_Out = ?, Hours_Worked = ?, Overtime_Hours = ?, AttendanceStatus = ?
            WHERE AttendanceID = ?
        ";
        $stmt = $pdo->prepare($update_sql);
        $stmt->execute([$current_time, $hours_worked, $overtime_hours, $status, $attendance_id]);
    }

    $pdo->commit();

    $user_id = (int) ($_SESSION['user_id'] ?? 0);
    if ($user_id > 0) {
        record_audit_log($user_id, 'Clock Out', "Worker {$record['WorkerID']} clocked out at site {$record['SiteID']}, {$hours_worked}hrs");
    }

    echo json_encode([
        'success' => true,
        'attendance_id' => $attendance_id,
        'time_out' => $current_time,
        'lunch_out' => $lunchOut,
        'lunch_in' => $lunchIn,
        'hours_worked' => number_format($hours_worked, 2),
        'overtime_hours' => number_format($overtime_hours, 2),
        'status' => $status,
        'message' => 'Clocked out successfully'
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
