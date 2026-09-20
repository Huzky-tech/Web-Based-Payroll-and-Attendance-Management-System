<?php
require_once __DIR__ . '/../includes/security.php';
header('Content-Type: application/json');

require_once __DIR__ . '/connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/site_priority_helpers.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR']);
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$siteId = filter_var($data['site_id'] ?? null, FILTER_VALIDATE_INT);
$isPriority = filter_var($data['is_priority'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

if (!$siteId || $isPriority === null) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid priority request.']);
    exit;
}

if (!$currentUserId || !site_priority_ensure_user_table($conn)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not prepare site priorities.']);
    exit;
}

$check = $conn->prepare('SELECT SiteID FROM projectsite WHERE SiteID = ? LIMIT 1');
$check->bind_param('i', $siteId);
$check->execute();
if (!$check->get_result()->fetch_assoc()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Site not found.']);
    exit;
}
$check->close();

$priorityValue = $isPriority ? 1 : 0;
if ($isPriority) {
    $stmt = $conn->prepare('INSERT IGNORE INTO user_site_priorities (UserID, SiteID) VALUES (?, ?)');
} else {
    $stmt = $conn->prepare('DELETE FROM user_site_priorities WHERE UserID = ? AND SiteID = ?');
}
$stmt->bind_param('ii', $currentUserId, $siteId);
$stmt->execute();
$stmt->close();

echo json_encode([
    'success' => true,
    'is_priority' => (bool) $priorityValue,
    'message' => $priorityValue ? 'Site pinned to your priorities.' : 'Site removed from your priorities.'
]);
