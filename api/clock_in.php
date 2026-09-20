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
require_once 'record_audit_log.php'; // For logging
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
$worker_id = (int) ($input['worker_id'] ?? 0);
$site_id = (int) ($input['site_id'] ?? 0);
$current_time = date('H:i:s');

try {
    if ($worker_id <= 0 || $site_id <= 0) {
        throw new Exception('Invalid worker or site ID');
    }

    if ($currentRole === 'Payroll Staff') {
        $payrollStaffId = getCurrentPayrollStaffId($conn, $currentUserId);
        if ($payrollStaffId <= 0) {
            throw new Exception('Your payroll staff account is not linked to any staff profile');
        }

        $scopeStmt = $conn->prepare("
            SELECT 1
            FROM payrollstaffassignment
            WHERE PayrollStaff_ID = ? AND SiteID = ?
            LIMIT 1
        ");
        $scopeStmt->bind_param("ii", $payrollStaffId, $site_id);
        $scopeStmt->execute();
        $scopeResult = $scopeStmt->get_result();
        $hasAccess = $scopeResult && $scopeResult->num_rows > 0;
        $scopeStmt->close();

        if (!$hasAccess) {
            throw new Exception('You can only clock in workers for your assigned sites');
        }
    }

    if ($currentRole === 'Timekeeper') {
        $accessError = validate_timekeeper_worker_access($conn, $currentUserId, $worker_id, $site_id);
        if ($accessError !== null) {
            throw new Exception($accessError);
        }
    }
    
    $pdo = getDbConnection();
    $pdo->beginTransaction();
    
    $date = date('Y-m-d');
    
    // Check if already clocked in today
    $check_sql = "SELECT AttendanceID FROM attendance WHERE WorkerID = ? AND Date = ? AND Time_In IS NOT NULL AND Time_In <> '00:00:00' AND (Time_Out IS NULL OR Time_Out = '00:00:00')";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$worker_id, $date]);
    
    if ($check_stmt->rowCount() > 0) {
        throw new Exception('Already clocked in today');
    }
    
    // Upsert attendance record
    $sql = "
        INSERT INTO attendance (WorkerID, SiteID, Date, Time_In, Time_Out, Hours_Worked, AttendanceStatus)
        VALUES (?, ?, ?, ?, '00:00:00', 0.00, 'Present')
        ON DUPLICATE KEY UPDATE
            SiteID = VALUES(SiteID),
            Time_In = VALUES(Time_In),
            Time_Out = '00:00:00',
            AttendanceStatus = 'Present'
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$worker_id, $site_id, $date, $current_time]);
    
    $attendance_id = $pdo->lastInsertId();
    if (!$attendance_id) {
        $lookupStmt = $pdo->prepare('SELECT AttendanceID FROM attendance WHERE WorkerID = ? AND Date = ? LIMIT 1');
        $lookupStmt->execute([$worker_id, $date]);
        $attendance_id = $lookupStmt->fetchColumn();
    }
    
    $pdo->commit();
    
    // Log audit (assuming logged-in user ID from session)
    $user_id = (int) ($_SESSION['user_id'] ?? 0);
    if ($user_id > 0) {
        record_audit_log($user_id, 'Clock In', "Worker $worker_id clocked in at site $site_id");
    }
    
    echo json_encode([
        'success' => true,
        'attendance_id' => $attendance_id,
        'time_in' => $current_time,
        'message' => 'Clocked in successfully'
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
?>
