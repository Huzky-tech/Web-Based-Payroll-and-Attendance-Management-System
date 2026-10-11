<?php
/**
 * Archive Site API
 * Soft-archive a project site (Status = Archived).
 */

header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/record_audit_log.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$siteId = (int) ($data['site_id'] ?? 0);
$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($siteId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Site ID is required']);
    exit;
}

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized audit user']);
    exit;
}

$checkSql = "SELECT Site_Name, Status FROM projectsite WHERE SiteID = ? LIMIT 1";
$checkStmt = $conn->prepare($checkSql);
if (!$checkStmt) {
    echo json_encode(['success' => false, 'message' => 'Unable to load site']);
    exit;
}

$checkStmt->bind_param('i', $siteId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$siteData = $checkResult ? $checkResult->fetch_assoc() : null;
$checkStmt->close();

if (!$siteData) {
    echo json_encode(['success' => false, 'message' => 'Site not found']);
    exit;
}

$currentStatus = strtolower(trim((string) ($siteData['Status'] ?? '')));
$alreadyArchived = $currentStatus === 'archived';

$siteName = (string) ($siteData['Site_Name'] ?? ('Site #' . $siteId));

$hasArchivedAt = false;
$columnCheck = $conn->query("SHOW COLUMNS FROM projectsite LIKE 'Archived_At'");
if ($columnCheck && $columnCheck->num_rows > 0) {
    $hasArchivedAt = true;
}

$stmt = null;

$conn->begin_transaction();

try {
    if (!$alreadyArchived) {
        $sql = $hasArchivedAt
            ? "UPDATE projectsite SET Status = 'Archived', Archived_At = NOW() WHERE SiteID = ?"
            : "UPDATE projectsite SET Status = 'Archived' WHERE SiteID = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Unable to archive site');
        }
        $stmt->bind_param('i', $siteId);
        if (!$stmt->execute()) {
            throw new RuntimeException('Failed to archive site');
        }
        $stmt->close();
        $stmt = null;
    }

    // Archiving ends the current site assignment. Keep attendance records for
    // history, but make every worker available for another active site.
    $unassignStmt = $conn->prepare('DELETE FROM workerassignment WHERE SiteID = ?');
    if (!$unassignStmt) {
        throw new RuntimeException('Unable to remove worker assignments');
    }
    $unassignStmt->bind_param('i', $siteId);
    if (!$unassignStmt->execute()) {
        $unassignStmt->close();
        throw new RuntimeException('Unable to remove worker assignments');
    }
    $unassignedWorkers = $unassignStmt->affected_rows;
    $unassignStmt->close();

    $conn->commit();
} catch (Throwable $error) {
    if ($stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    $conn->rollback();
    error_log('Site archive failed: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to archive site and remove worker assignments']);
    exit;
}

$actorLabel = trim($currentRole) !== '' ? "{$currentRole} User" : 'Admin User';
record_audit_log(
    $userId,
    $alreadyArchived ? 'Archived Site Assignments Cleared' : 'Site Archived',
    $alreadyArchived
        ? "{$actorLabel} cleared {$unassignedWorkers} worker assignment(s) from archived site: {$siteName}"
        : "{$actorLabel} archived site: {$siteName}; unassigned {$unassignedWorkers} worker(s)"
);

echo json_encode([
    'success' => true,
    'message' => $alreadyArchived
        ? "The site was already archived. {$unassignedWorkers} worker(s) were unassigned."
        : "Site archived successfully. {$unassignedWorkers} worker(s) were unassigned.",
    'site_id' => $siteId,
    'status' => 'Archived',
    'already_archived' => $alreadyArchived,
    'unassigned_workers' => $unassignedWorkers
]);

$conn->close();
