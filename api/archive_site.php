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
if (in_array($currentStatus, ['archived', 'inactive'], true)) {
    echo json_encode(['success' => false, 'message' => 'Site is already archived']);
    exit;
}

$siteName = (string) ($siteData['Site_Name'] ?? ('Site #' . $siteId));

$hasArchivedAt = false;
$columnCheck = $conn->query("SHOW COLUMNS FROM projectsite LIKE 'Archived_At'");
if ($columnCheck && $columnCheck->num_rows > 0) {
    $hasArchivedAt = true;
}

if ($hasArchivedAt) {
    $sql = "UPDATE projectsite SET Status = 'Archived', Archived_At = NOW() WHERE SiteID = ?";
} else {
    $sql = "UPDATE projectsite SET Status = 'Archived' WHERE SiteID = ?";
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Unable to archive site']);
    exit;
}

$stmt->bind_param('i', $siteId);
$success = $stmt->execute();
$stmt->close();

if (!$success) {
    echo json_encode(['success' => false, 'message' => 'Failed to archive site']);
    exit;
}

$actorLabel = trim($currentRole) !== '' ? "{$currentRole} User" : 'Admin User';
record_audit_log($userId, 'Site Archived', "{$actorLabel} archived site: {$siteName}");

echo json_encode([
    'success' => true,
    'message' => 'Site archived successfully',
    'site_id' => $siteId,
    'status' => 'Archived'
]);

$conn->close();
