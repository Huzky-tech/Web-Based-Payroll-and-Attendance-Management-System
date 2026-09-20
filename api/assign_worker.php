<?php
/**
 * Assign Worker to Site API
 * Insert worker-site assignment into WorkerAssignment table
 */

header('Content-Type: application/json');
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

if ($currentRole === 'Payroll Staff') {
    echo json_encode(['success' => false, 'message' => 'Payroll staff can only view assigned site details']);
    exit;
}

if (!isEmployeeApprovedForAssignment($conn, (int) $workerId)) {
    echo json_encode(['success' => false, 'message' => 'This employee is pending approval and cannot be assigned to a site yet.']);
    exit;
}

// Prevent assigning a worker who is already assigned to any site.
$checkSql = "SELECT wa.SiteID, ps.Site_Name
             FROM WorkerAssignment wa
             LEFT JOIN projectsite ps ON wa.SiteID = ps.SiteID
             WHERE wa.WorkerID = ?
             LIMIT 1";
$stmt = $conn->prepare($checkSql);
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
$workerStmt = $conn->prepare("SELECT CONCAT(First_Name, ' ', Last_Name) AS full_name FROM worker WHERE WorkerID = ?");
if ($workerStmt) {
    $workerStmt->bind_param("i", $workerId);
    $workerStmt->execute();
    $workerResult = $workerStmt->get_result();
    if ($workerResult && $workerResult->num_rows > 0) {
        $workerName = $workerResult->fetch_assoc()['full_name'] ?? $workerName;
    }
    $workerStmt->close();
}

// Insert assignment
$insertSql = "INSERT INTO WorkerAssignment (WorkerID, SiteID, Assigned_Date, Role_On_Site) VALUES (?, ?, CURDATE(), '')";
$stmt = $conn->prepare($insertSql);
$stmt->bind_param("ii", $workerId, $siteId);

if ($stmt->execute()) {
    $activation = site_sync_activation_status($conn, (int) $siteId);
    if ($userId > 0) {
        record_audit_log($userId, 'Assign Worker to Site', "{$currentRole} assigned {$workerName} to {$siteName}");
    }
    echo json_encode(['success' => true, 'activation' => $activation]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}
$stmt->close();
$conn->close();
?>
