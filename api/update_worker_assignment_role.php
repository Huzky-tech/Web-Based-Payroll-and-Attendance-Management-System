<?php
/**
 * Update a worker's site role (Role_On_Site) for an existing assignment.
 */

header('Content-Type: application/json');
ini_set('display_errors', '0');
ob_start();

function update_assignment_role_response(array $payload, int $status = 200): void
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
    error_log('update_worker_assignment_role failed: ' . $error->getMessage());
    update_assignment_role_response([
        'success' => false,
        'message' => 'Unable to update the worker assignment. Please try again.'
    ], 500);
});

register_shutdown_function(static function (): void {
    $error = error_get_last();
    $fatalErrors = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if ($error === null || !in_array($error['type'] ?? 0, $fatalErrors, true)) {
        return;
    }

    error_log('update_worker_assignment_role fatal error: ' . ($error['message'] ?? 'Unknown error'));
    update_assignment_role_response([
        'success' => false,
        'message' => 'Unable to update the worker assignment. Please try again.'
    ], 500);
});

include 'connection/db_config.php';
include '../includes/auth.php';
require_once 'record_audit_log.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR', 'Timekeeper']);

$data = json_decode(file_get_contents('php://input'), true);
$workerId = (int) ($data['worker_id'] ?? $data['workerId'] ?? 0);
$siteId = (int) ($data['site_id'] ?? $data['siteId'] ?? 0);
$role = trim((string) ($data['role'] ?? $data['position'] ?? ''));
$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($workerId <= 0 || $siteId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Worker ID and site ID are required']);
    exit;
}

if ($role === '') {
    $role = 'Construction Worker';
}

$hasRoleColumn = false;
$columnsResult = $conn->query("SHOW COLUMNS FROM workerassignment LIKE 'Role_On_Site'");
if ($columnsResult) {
    $hasRoleColumn = $columnsResult->num_rows > 0;
}

// Older databases do not keep a separate site role.  The worker's position is
// already shown and remains the effective role for that assignment.
if (!$hasRoleColumn) {
    echo json_encode(['success' => true, 'message' => 'Worker assignment saved successfully']);
    $conn->close();
    exit;
}

$stmt = $conn->prepare(
    'UPDATE workerassignment SET Role_On_Site = ? WHERE WorkerID = ? AND SiteID = ?'
);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare update']);
    exit;
}

$stmt->bind_param('sii', $role, $workerId, $siteId);
$stmt->execute();
$affectedRows = $stmt->affected_rows;
$stmt->close();

$roleAlreadySet = false;
if ($affectedRows === 0) {
    $checkStmt = $conn->prepare(
        'SELECT Role_On_Site FROM workerassignment WHERE WorkerID = ? AND SiteID = ? LIMIT 1'
    );
    if (!$checkStmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to verify assignment']);
        exit;
    }

    $checkStmt->bind_param('ii', $workerId, $siteId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $existing = $checkResult->fetch_assoc();
    $checkStmt->close();

    if (!$existing) {
        echo json_encode(['success' => false, 'message' => 'Assignment not found for this worker and site']);
        exit;
    }

    $existingRole = trim((string) ($existing['Role_On_Site'] ?? ''));
    if ($existingRole === $role) {
        $roleAlreadySet = true;
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not update role for this assignment']);
        exit;
    }
}

if ($userId > 0 && !$roleAlreadySet) {
    $workerName = 'employee';
    $siteName = 'site';

    $nameStmt = $conn->prepare(
        'SELECT CONCAT(w.First_Name, \' \', w.Last_Name) AS full_name, ps.Site_Name
         FROM worker w
         INNER JOIN projectsite ps ON ps.SiteID = ?
         WHERE w.WorkerID = ?
         LIMIT 1'
    );
    if ($nameStmt) {
        $nameStmt->bind_param('ii', $siteId, $workerId);
        $nameStmt->execute();
        $nameRow = $nameStmt->get_result()->fetch_assoc();
        if ($nameRow) {
            $workerName = trim($nameRow['full_name'] ?? '') ?: $workerName;
            $siteName = trim($nameRow['Site_Name'] ?? '') ?: $siteName;
        }
        $nameStmt->close();
    }

    record_audit_log(
        $userId,
        'Update Worker Site Role',
        "{$currentRole} updated role to {$role} for {$workerName} at {$siteName}"
    );
}

echo json_encode(['success' => true, 'message' => 'Role updated successfully']);
$conn->close();
