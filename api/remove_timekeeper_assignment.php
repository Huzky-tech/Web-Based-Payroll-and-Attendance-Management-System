<?php
/**
 * Remove (deactivate) a Timekeeper's site assignment.
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

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$userId = (int) ($data['user_id'] ?? 0);
$assignmentId = (int) ($data['assignment_id'] ?? 0);
$sessionUserId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0 && $assignmentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'User ID or assignment ID is required']);
    exit;
}

if ($assignmentId > 0 && $userId <= 0) {
    $lookup = $conn->prepare('SELECT UserID FROM timekeeper_assignment WHERE AssignmentID = ? LIMIT 1');
    if ($lookup) {
        $lookup->bind_param('i', $assignmentId);
        $lookup->execute();
        $lookupResult = $lookup->get_result();
        $lookupRow = $lookupResult ? $lookupResult->fetch_assoc() : null;
        $lookup->close();
        $userId = (int) ($lookupRow['UserID'] ?? 0);
    }
}

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Assignment not found']);
    exit;
}

if (!validate_timekeeper_is_role($conn, $userId)) {
    echo json_encode(['success' => false, 'message' => 'Invalid Timekeeper account']);
    exit;
}

$assignment = get_active_timekeeper_assignment($conn, $userId);
$siteName = $assignment ? (string) ($assignment['Site_Name'] ?? 'site') : 'site';

if (timekeeper_assignment_table_exists($conn)) {
    deactivate_timekeeper_assignments($conn, $userId);
}

sync_projectsite_timekeeper_user($conn, $userId, 0, false);

$nameStmt = $conn->prepare('SELECT full_name, email FROM users WHERE id = ? LIMIT 1');
$nameStmt->bind_param('i', $userId);
$nameStmt->execute();
$nameResult = $nameStmt->get_result();
$userRow = $nameResult ? $nameResult->fetch_assoc() : null;
$nameStmt->close();

$tkLabel = trim((string) (($userRow['full_name'] ?? '') ?: ($userRow['email'] ?? "User #{$userId}")));

if ($sessionUserId > 0) {
    record_audit_log(
        $sessionUserId,
        'Timekeeper Site Assignment Removed',
        "{$currentRole} removed site assignment for Timekeeper {$tkLabel} ({$siteName})"
    );
}

echo json_encode([
    'success' => true,
    'message' => 'Timekeeper assignment removed successfully',
    'user_id' => $userId,
]);

$conn->close();
