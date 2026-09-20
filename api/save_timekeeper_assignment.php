<?php
/**
 * Assign or change a Timekeeper's site assignment.
 * Only one Active assignment per Timekeeper is allowed.
 */

header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/record_audit_log.php';
require_once __DIR__ . '/timekeeper_assignment_helpers.php';

$currentRole = require_auth($conn, ['Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!timekeeper_assignment_table_exists($conn)) {
    echo json_encode([
        'success' => false,
        'message' => 'Database is missing timekeeper_assignment table. Run database/migrations/add_timekeeper_assignment.sql',
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$userId = (int) ($data['user_id'] ?? 0);
$siteId = (int) ($data['site_id'] ?? 0);
$status = trim((string) ($data['status'] ?? 'Active'));
$assignedDate = trim((string) ($data['assigned_date'] ?? date('Y-m-d')));
$sessionUserId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0 || $siteId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Timekeeper and site are required']);
    exit;
}

if (!in_array($status, ['Active', 'Inactive'], true)) {
    echo json_encode(['success' => false, 'message' => 'Status must be Active or Inactive']);
    exit;
}

if (!validate_timekeeper_is_role($conn, $userId)) {
    echo json_encode(['success' => false, 'message' => 'Selected user is not an active Timekeeper account']);
    exit;
}

$siteStmt = $conn->prepare("
    SELECT Site_Name FROM projectsite
    WHERE SiteID = ? AND LOWER(COALESCE(Status, '')) = 'active'
    LIMIT 1
");
if (!$siteStmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to validate site']);
    exit;
}

$siteStmt->bind_param('i', $siteId);
$siteStmt->execute();
$siteResult = $siteStmt->get_result();
$siteRow = $siteResult ? $siteResult->fetch_assoc() : null;
$siteStmt->close();

if (!$siteRow) {
    echo json_encode(['success' => false, 'message' => 'Selected site is not an active site']);
    exit;
}

$siteName = (string) ($siteRow['Site_Name'] ?? "Site {$siteId}");

$nameStmt = $conn->prepare('SELECT full_name, email FROM users WHERE id = ? LIMIT 1');
$nameStmt->bind_param('i', $userId);
$nameStmt->execute();
$nameResult = $nameStmt->get_result();
$userRow = $nameResult ? $nameResult->fetch_assoc() : null;
$nameStmt->close();

$tkLabel = trim((string) (($userRow['full_name'] ?? '') ?: ($userRow['email'] ?? "User #{$userId}")));

if ($status === 'Active') {
    deactivate_timekeeper_assignments($conn, $userId);
    sync_projectsite_timekeeper_user($conn, $userId, $siteId, true);
} else {
    sync_projectsite_timekeeper_user($conn, $userId, 0, false);
}

$stmt = $conn->prepare("
    INSERT INTO timekeeper_assignment (UserID, SiteID, AssignedDate, Status)
    VALUES (?, ?, ?, ?)
");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to save assignment']);
    exit;
}

$stmt->bind_param('iiss', $userId, $siteId, $assignedDate, $status);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to save assignment: ' . $stmt->error]);
    $stmt->close();
    exit;
}

$assignmentId = (int) $stmt->insert_id;
$stmt->close();

if ($sessionUserId > 0) {
    $action = $status === 'Active' ? 'Timekeeper Site Assigned' : 'Timekeeper Site Assignment Inactive';
    record_audit_log(
        $sessionUserId,
        $action,
        "{$currentRole} set Timekeeper {$tkLabel} to {$siteName} ({$status})"
    );
}

echo json_encode([
    'success' => true,
    'message' => $status === 'Active' ? 'Timekeeper assigned to site successfully' : 'Assignment saved as inactive',
    'assignment_id' => $assignmentId,
    'user_id' => $userId,
    'site_id' => $siteId,
    'status' => $status,
]);

$conn->close();
