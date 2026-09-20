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
$updates = $input['updates'] ?? []; // e.g. ['Time_In' => '...', 'AttendanceStatus' => 'Present']

try {
    if ($attendance_id <= 0 || empty($updates)) {
        throw new Exception('Attendance ID and updates required');
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
        if (!$scopeStmt) {
            throw new Exception('Failed to validate attendance access');
        }
        $scopeStmt->bind_param("ii", $attendance_id, $payrollStaffId);
        $scopeStmt->execute();
        $scopeResult = $scopeStmt->get_result();
        $hasAccess = $scopeResult && $scopeResult->num_rows > 0;
        $scopeStmt->close();

        if (!$hasAccess) {
            throw new Exception('You can only update attendance for your assigned sites');
        }
    }

    if ($currentRole === 'Timekeeper') {
        $accessError = validate_timekeeper_attendance_record($conn, $currentUserId, $attendance_id);
        if ($accessError !== null) {
            throw new Exception($accessError);
        }
    }
    
    $pdo = getDbConnection();
    $hasLunchColumns = attendance_lunch_columns_exist($conn);
    $allowed_fields = $hasLunchColumns
        ? ['Time_In', 'Lunch_Out', 'Lunch_In', 'Time_Out']
        : ['Time_In', 'Time_Out'];
    $set_parts = [];
    $params = [];
    
    foreach ($updates as $field => $value) {
        if (in_array($field, $allowed_fields)) {
            $set_parts[] = "$field = ?";
            $params[] = $value;
        }
    }
    
    if (empty($set_parts)) {
        throw new Exception('No valid fields to update');
    }

    $recordColumns = $hasLunchColumns
        ? 'SiteID, Time_In, Lunch_Out, Lunch_In, Time_Out'
        : 'SiteID, Time_In, Time_Out';
    $recordStmt = $pdo->prepare("SELECT {$recordColumns} FROM attendance WHERE AttendanceID = ? LIMIT 1");
    $recordStmt->execute([$attendance_id]);
    $record = $recordStmt->fetch(PDO::FETCH_ASSOC);
    if (!$record) {
        throw new Exception('Attendance record not found');
    }

    foreach ($allowed_fields as $field) {
        if (array_key_exists($field, $updates)) {
            $record[$field] = $updates[$field];
        }
    }

    $timeIn = $record['Time_In'] ?? null;
    $timeOut = $record['Time_Out'] ?? null;
    $siteSchedule = get_site_schedule_row($conn, (int) ($record['SiteID'] ?? 0));
    $hasTimeIn = !empty($timeIn) && $timeIn !== '00:00:00';
    $shiftStart = (string) ($siteSchedule['ShiftStart'] ?? '07:00:00');
    $expectedInMinutes = (site_schedule_time_to_minutes($shiftStart) ?? 420) + 30;
    $timeInMinutes = site_schedule_time_to_minutes($timeIn);
    $status = !$hasTimeIn
        ? 'Absent'
        : (($timeInMinutes ?? PHP_INT_MAX) <= $expectedInMinutes ? 'Present' : 'Late');

    $hourTotals = calculate_attendance_hours_worked(
        $timeIn,
        $record['Lunch_Out'] ?? null,
        $record['Lunch_In'] ?? null,
        $timeOut,
        $siteSchedule
    );
    $hoursWorked = ($hasTimeIn && !empty($timeOut) && $timeOut !== '00:00:00')
        ? (float) ($hourTotals['hours_worked'] ?? 0)
        : 0.0;
    $overtimeHours = ($hasTimeIn && !empty($timeOut) && $timeOut !== '00:00:00')
        ? (float) ($hourTotals['overtime_hours'] ?? 0)
        : 0.0;

    $set_parts[] = 'Hours_Worked = ?';
    $params[] = $hoursWorked;
    $set_parts[] = 'Overtime_Hours = ?';
    $params[] = $overtimeHours;
    $set_parts[] = 'AttendanceStatus = ?';
    $params[] = $status;
    $params[] = $attendance_id;

    $sql = "UPDATE attendance SET " . implode(', ', $set_parts) . " WHERE AttendanceID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $user_id = (int) ($_SESSION['user_id'] ?? 0);
    if ($user_id > 0) {
        record_audit_log($user_id, 'Update Attendance', "Updated attendance: " . json_encode(array_merge($updates, [
            'Hours_Worked' => $hoursWorked,
            'Overtime_Hours' => $overtimeHours,
            'AttendanceStatus' => $status,
        ])));
    }
    
    echo json_encode([
        'success' => true,
        'attendance_id' => $attendance_id,
        'status' => $status,
        'message' => 'Attendance updated successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
