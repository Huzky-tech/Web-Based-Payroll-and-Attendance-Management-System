<?php
require_once __DIR__ . '/../includes/security.php';
header('Content-Type: application/json');
require_once __DIR__ . '/connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR']);
$adminUserId = (int) ($_SESSION['user_id'] ?? 0);
$data = json_decode(file_get_contents('php://input'), true) ?: [];
$id = (int) ($data['notification_id'] ?? 0);
if (!empty($data['mark_all'])) {
    $recipientClause = $currentRole === 'Admin' ? '(RecipientUserID = ? OR RecipientUserID IS NULL)' : 'RecipientUserID = ?';
    $stmt = $conn->prepare("UPDATE admin_notifications SET IsRead = 1 WHERE IsRead = 0 AND {$recipientClause}");
    $stmt->bind_param('i', $adminUserId); $ok = $stmt->execute(); $stmt->close();
} elseif ($id > 0) {
    $recipientClause = $currentRole === 'Admin' ? '(RecipientUserID = ? OR RecipientUserID IS NULL)' : 'RecipientUserID = ?';
    $stmt = $conn->prepare("UPDATE admin_notifications SET IsRead = 1 WHERE NotificationID = ? AND {$recipientClause}");
    $stmt->bind_param('ii', $id, $adminUserId); $ok = $stmt->execute(); $stmt->close();
} else {
    http_response_code(422); echo json_encode(['success' => false]); exit;
}
echo json_encode(['success' => (bool) $ok]);
