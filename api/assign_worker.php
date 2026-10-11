<?php
/**
 * Assign Worker to Site API
 * Insert worker-site assignment into workerassignment table
 */

header('Content-Type: application/json');
ini_set('display_errors', '0');
ob_start();

function assign_worker_response(array $payload, int $status = 200): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

set_exception_handler(static function (Throwable $error): void {
    error_log('assign_worker failed: ' . $error->getMessage());
    assign_worker_response([
        'success' => false,
        'message' => 'Unable to save the worker assignment. Please try again.'
    ], 500);
});

register_shutdown_function(static function (): void {
    $error = error_get_last();
    $fatalErrors = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if ($error === null || !in_array($error['type'] ?? 0, $fatalErrors, true)) {
        return;
    }

    error_log('assign_worker fatal error: ' . ($error['message'] ?? 'Unknown error'));
    assign_worker_response([
        'success' => false,
        'message' => 'Unable to save the worker assignment. Please try again.'
    ], 500);
});

include 'connection/db_config.php';
include '../includes/auth.php';
require_once 'record_audit_log.php';
require_once 'employee_helpers.php';
require_once 'site_activation_helpers.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR', 'Timekeeper']);

$data = json_decode(file_get_contents('php://input'), true);
$workerId = $data['workerId'] ?? null;
$siteId = $data['siteId'] ?? null;
$userId = (int) ($_SESSION['user_id'] ?? 0);

if (!$workerId || !$siteId) {
    echo json_encode(['success' => false, 'message' => 'Missing worker or site ID']);
    exit;
}

if (!isEmployeeApprovedForAssignment($conn, (int) $workerId)) {
    echo json_encode(['success' => false, 'message' => 'This employee is pending approval and cannot be assigned to a site yet.']);
    exit;
}

// Prevent assigning a worker who is already assigned to any site.
$checkSql = "SELECT wa.SiteID, ps.Site_Name
             FROM workerassignment wa
             LEFT JOIN projectsite ps ON wa.SiteID = ps.SiteID
             WHERE wa.WorkerID = ?
             LIMIT 1";
$stmt = $conn->prepare($checkSql);
if (!$stmt) {
    error_log('assign_worker lookup failed: ' . $conn->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to check worker assignments. Please contact the administrator.']);
    exit;
}
$stmt->bind_param("i", $workerId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $existingAssignment = $result->fetch_assoc();
    $existingSiteId = (int) ($existingAssignment['SiteID'] ?? 0);
    $existingSiteName = trim((string) ($existingAssignment['Site_Name'] ?? 'another site'));

    if ($existingSiteId === (int) $siteId) {
        echo json_encode(['success' => false, 'message' => 'Worker already assigned to this site']);
    } else {
        echo json_encode(['success' => false, 'message' => "Worker is already assigned to {$existingSiteName}"]);
    }
    $stmt->close();
    exit;
}
$stmt->close();

$siteName = "Site {$siteId}";
$siteStmt = $conn->prepare("SELECT Site_Name FROM projectsite WHERE SiteID = ?");
if ($siteStmt) {
    $siteStmt->bind_param("i", $siteId);
    $siteStmt->execute();
    $siteResult = $siteStmt->get_result();
    if ($siteResult && $siteResult->num_rows > 0) {
        $siteName = $siteResult->fetch_assoc()['Site_Name'] ?? $siteName;
    }
    $siteStmt->close();
}

$workerName = "Worker {$workerId}";
$workerPosition = '';
$workerStmt = $conn->prepare("SELECT CONCAT(First_Name, ' ', Last_Name) AS full_name, Position FROM worker WHERE WorkerID = ?");
if ($workerStmt) {
    $workerStmt->bind_param("i", $workerId);
    $workerStmt->execute();
    $workerResult = $workerStmt->get_result();
    if ($workerResult && $workerResult->num_rows > 0) {
        $workerRow = $workerResult->fetch_assoc();
        $workerName = $workerRow['full_name'] ?? $workerName;
        $workerPosition = trim((string) ($workerRow['Position'] ?? ''));
    }
    $workerStmt->close();
}

// Keep the worker's position as the initial site role.  The role column was
// introduced after early deployments, so use the base assignment fields when
// it is not present on an older hosted database.
$assignmentColumns = [];
$columnsResult = $conn->query('SHOW COLUMNS FROM workerassignment');
if ($columnsResult) {
    while ($column = $columnsResult->fetch_assoc()) {
        $assignmentColumns[strtolower((string) $column['Field'])] = (string) $column['Field'];
    }
}
$assignedDateColumn = $assignmentColumns['assigned_date'] ?? null;
$roleColumn = $assignmentColumns['role_on_site'] ?? null;

if ($assignedDateColumn !== null && $roleColumn !== null) {
    $insertSql = "INSERT INTO workerassignment (WorkerID, SiteID, `{$assignedDateColumn}`, `{$roleColumn}`) VALUES (?, ?, CURDATE(), ?)";
} elseif ($assignedDateColumn !== null) {
    $insertSql = "INSERT INTO workerassignment (WorkerID, SiteID, `{$assignedDateColumn}`) VALUES (?, ?, CURDATE())";
} else {
    $insertSql = 'INSERT INTO workerassignment (WorkerID, SiteID) VALUES (?, ?)';
}
$stmt = $conn->prepare($insertSql);
if (!$stmt) {
    error_log('assign_worker insert preparation failed: ' . $conn->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to save worker assignment. Please contact the administrator.']);
    exit;
}
if ($roleColumn !== null && $assignedDateColumn !== null) {
    $stmt->bind_param('iis', $workerId, $siteId, $workerPosition);
} else {
    $stmt->bind_param('ii', $workerId, $siteId);
}

if ($stmt->execute()) {
    $activation = site_sync_activation_status($conn, (int) $siteId);
    if ($userId > 0) {
        try {
            record_audit_log($userId, 'Assign Worker to Site', "{$currentRole} assigned {$workerName} to {$siteName}");
        } catch (Throwable $auditError) {
            error_log('assign_worker audit log failed: ' . $auditError->getMessage());
        }
    }
    echo json_encode(['success' => true, 'activation' => $activation]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}
$stmt->close();
$conn->close();
?>
