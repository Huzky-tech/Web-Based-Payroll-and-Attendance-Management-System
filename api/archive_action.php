<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/record_audit_log.php';
require_once __DIR__ . '/site_activation_helpers.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$action = strtolower(trim((string) ($payload['action'] ?? '')));
$itemId = trim((string) ($payload['id'] ?? ''));
$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($action === '' || $itemId === '') {
    echo json_encode(['success' => false, 'message' => 'Action and item ID are required']);
    exit;
}

$logArchiveAction = static function (mysqli $conn, int $userId, string $auditAction, string $details): void {
    if ($userId <= 0) {
        return;
    }

    $stmt = $conn->prepare("INSERT INTO audit_logs (UserID, Action, Details, Date) VALUES (?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param('iss', $userId, $auditAction, $details);
        $stmt->execute();
        $stmt->close();
    }
};

if (preg_match('/^employee-(\d+)$/', $itemId, $matches) === 1) {
    $workerId = (int) $matches[1];

    if ($action === 'delete') {
        echo json_encode(['success' => false, 'message' => 'Permanent delete is not enabled for archived employees yet']);
        exit;
    }

    $statusStmt = $conn->prepare("SELECT WorkerStatusID FROM workerstatus WHERE Status = 'Active' LIMIT 1");
    if (!$statusStmt) {
        echo json_encode(['success' => false, 'message' => 'Unable to prepare employee restore']);
        exit;
    }
    $statusStmt->execute();
    $statusResult = $statusStmt->get_result();
    $statusRow = $statusResult ? $statusResult->fetch_assoc() : null;
    $statusStmt->close();

    if (!$statusRow) {
        echo json_encode(['success' => false, 'message' => 'Active worker status was not found']);
        exit;
    }

    $nameStmt = $conn->prepare("SELECT CONCAT(COALESCE(First_Name, ''), ' ', COALESCE(Last_Name, '')) AS full_name, UserID FROM worker WHERE WorkerID = ? LIMIT 1");
    if (!$nameStmt) {
        echo json_encode(['success' => false, 'message' => 'Unable to load employee details']);
        exit;
    }
    $nameStmt->bind_param('i', $workerId);
    $nameStmt->execute();
    $nameResult = $nameStmt->get_result();
    $nameRow = $nameResult ? $nameResult->fetch_assoc() : null;
    $nameStmt->close();

    if (!$nameRow) {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit;
    }

    $updateStmt = $conn->prepare("UPDATE worker SET WorkerStatusID = ? WHERE WorkerID = ?");
    if (!$updateStmt) {
        echo json_encode(['success' => false, 'message' => 'Unable to restore employee']);
        exit;
    }
    $activeStatusId = (int) $statusRow['WorkerStatusID'];
    $updateStmt->bind_param('ii', $activeStatusId, $workerId);
    $success = $updateStmt->execute();
    $updateStmt->close();

    if (!$success) {
        echo json_encode(['success' => false, 'message' => 'Failed to restore employee']);
        exit;
    }

    $employeeUserId = (int) ($nameRow['UserID'] ?? 0);
    if ($employeeUserId > 0) {
        $accountStmt = $conn->prepare("UPDATE users SET status = 'Active' WHERE id = ?");
        if ($accountStmt) {
            $accountStmt->bind_param('i', $employeeUserId);
            $accountStmt->execute();
            $accountStmt->close();
        }
    }

    $employeeName = trim((string) ($nameRow['full_name'] ?? '')) ?: ('Employee #' . $workerId);
    $logArchiveAction($conn, $userId, 'Archive Item Restored', "Restored archived employee: {$employeeName}");

    echo json_encode([
        'success' => true,
        'message' => 'Employee restored successfully',
        'item' => [
            'id' => $itemId,
            'title' => $employeeName,
            'type' => 'employee'
        ]
    ]);
    exit;
}

if (preg_match('/^site-(\d+)$/', $itemId, $matches) === 1) {
    $siteId = (int) $matches[1];

    if ($action === 'delete') {
        echo json_encode(['success' => false, 'message' => 'Permanent delete is not enabled for archived sites yet']);
        exit;
    }

    $siteStmt = $conn->prepare("SELECT Site_Name, Status FROM projectsite WHERE SiteID = ? LIMIT 1");
    if (!$siteStmt) {
        echo json_encode(['success' => false, 'message' => 'Unable to load site details']);
        exit;
    }
    $siteStmt->bind_param('i', $siteId);
    $siteStmt->execute();
    $siteResult = $siteStmt->get_result();
    $siteRow = $siteResult ? $siteResult->fetch_assoc() : null;
    $siteStmt->close();

    if (!$siteRow) {
        echo json_encode(['success' => false, 'message' => 'Site not found']);
        exit;
    }

    $siteStatus = strtolower(trim((string) ($siteRow['Status'] ?? '')));
    if (!in_array($siteStatus, ['archived', 'inactive'], true)) {
        echo json_encode(['success' => false, 'message' => 'Site is not archived']);
        exit;
    }

    $hasArchivedAt = false;
    $columnCheck = $conn->query("SHOW COLUMNS FROM projectsite LIKE 'Archived_At'");
    if ($columnCheck && $columnCheck->num_rows > 0) {
        $hasArchivedAt = true;
    }

    $activation = site_activation_requirements($conn, $siteId);
    $restoredStatus = !empty($activation['eligible']) ? 'Active' : 'Inactive';
    $restoreSql = $hasArchivedAt
        ? "UPDATE projectsite SET Status = ?, Archived_At = NULL WHERE SiteID = ?"
        : "UPDATE projectsite SET Status = ? WHERE SiteID = ?";

    $updateStmt = $conn->prepare($restoreSql);
    if (!$updateStmt) {
        echo json_encode(['success' => false, 'message' => 'Unable to restore site']);
        exit;
    }
    $updateStmt->bind_param('si', $restoredStatus, $siteId);
    $success = $updateStmt->execute();
    $updateStmt->close();

    if (!$success) {
        echo json_encode(['success' => false, 'message' => 'Failed to restore site']);
        exit;
    }

    $siteName = (string) ($siteRow['Site_Name'] ?? ('Site #' . $siteId));
    $actorLabel = trim($currentRole) !== '' ? "{$currentRole} User" : 'Admin User';
    record_audit_log($userId, 'Site Restored', "{$actorLabel} restored site: {$siteName}");

    echo json_encode([
        'success' => true,
        'message' => $restoredStatus === 'Active'
            ? 'Site restored and activated successfully'
            : 'Site restored as Inactive. ' . site_activation_requirement_message($activation),
        'item' => [
            'id' => $itemId,
            'title' => $siteName,
            'type' => 'site'
        ]
    ]);
    exit;
}

$archiveFile = __DIR__ . '/../data/archive_items.json';
if (!file_exists($archiveFile)) {
    echo json_encode(['success' => false, 'message' => 'Archive data file was not found']);
    exit;
}

$contents = file_get_contents($archiveFile);
$items = json_decode($contents, true);
if (!is_array($items)) {
    echo json_encode(['success' => false, 'message' => 'Archive data is invalid']);
    exit;
}

$foundIndex = null;
$foundItem = null;
foreach ($items as $index => $item) {
    if ((string) ($item['id'] ?? '') === $itemId) {
        $foundIndex = $index;
        $foundItem = $item;
        break;
    }
}

if ($foundItem === null) {
    echo json_encode(['success' => false, 'message' => 'Archived item not found']);
    exit;
}

if (!in_array($action, ['restore', 'delete'], true)) {
    echo json_encode(['success' => false, 'message' => 'Unsupported archive action']);
    exit;
}

unset($items[$foundIndex]);
$items = array_values($items);

$encoded = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($encoded === false || file_put_contents($archiveFile, $encoded . PHP_EOL, LOCK_EX) === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to update archive data']);
    exit;
}

$auditAction = $action === 'restore' ? 'Archive Item Restored' : 'Archive Item Deleted';
$details = sprintf(
    '%s archive item: %s',
    ucfirst($action),
    (string) ($foundItem['title'] ?? 'Unknown Item')
);
$logArchiveAction($conn, $userId, $auditAction, $details);

echo json_encode([
    'success' => true,
    'message' => $action === 'restore' ? 'Archive item restored successfully' : 'Archive item deleted successfully',
    'item' => $foundItem
]);
