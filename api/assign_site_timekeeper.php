<?php
/**
 * Assign or clear the Timekeeper user for a construction site.
 */

header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once 'record_audit_log.php';
require_once __DIR__ . '/timekeeper_assignment_helpers.php';
require_once __DIR__ . '/site_activation_helpers.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    $data = $_POST;
}

$siteId = (int) ($data['site_id'] ?? 0);
$userId = isset($data['user_id']) ? (int) $data['user_id'] : 0;
$sessionUserId = (int) ($_SESSION['user_id'] ?? 0);

if ($siteId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Site ID is required']);
    exit;
}

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please select a timekeeper before saving assignments.']);
    exit;
}

$siteStmt = $conn->prepare('SELECT Site_Name FROM projectsite WHERE SiteID = ? LIMIT 1');
if (!$siteStmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare site lookup']);
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

$siteName = (string) ($siteRow['Site_Name'] ?? "Site {$siteId}");

if ($userId > 0) {
    $checkStmt = $conn->prepare(
        "SELECT u.full_name, u.email
         FROM users u
         INNER JOIN timekeeper tk ON tk.UserID = u.id
         WHERE u.id = ? AND LOWER(u.status) = 'active'
         LIMIT 1"
    );
    if (!$checkStmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to validate timekeeper']);
        exit;
    }

    $checkStmt->bind_param('i', $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $timekeeperUser = $checkResult ? $checkResult->fetch_assoc() : null;
    $checkStmt->close();

    if (!$timekeeperUser) {
        echo json_encode(['success' => false, 'message' => 'Selected user is not an active Timekeeper account']);
        exit;
    }

    $otherSite = timekeeper_assigned_to_other_active_site($conn, $userId, $siteId);
    if ($otherSite !== null) {
        echo json_encode([
            'success' => false,
            'message' => 'This Timekeeper is already assigned to '
                . $otherSite['site_name']
                . '. Remove that assignment first or change it from Timekeeper Site Assignment.',
        ]);
        exit;
    }
}

$columnCheck = $conn->query("SHOW COLUMNS FROM projectsite LIKE 'Timekeeper_UserID'");
if (!$columnCheck || $columnCheck->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Database is missing Timekeeper_UserID on projectsite. Run database/migrations/add_site_timekeeper_user.sql',
    ]);
    exit;
}

$previousTimekeeperUserId = 0;
$previousStmt = $conn->prepare('SELECT Timekeeper_UserID FROM projectsite WHERE SiteID = ? LIMIT 1');
if ($previousStmt) {
    $previousStmt->bind_param('i', $siteId);
    $previousStmt->execute();
    $previousResult = $previousStmt->get_result();
    $previousRow = $previousResult ? $previousResult->fetch_assoc() : null;
    $previousTimekeeperUserId = (int) ($previousRow['Timekeeper_UserID'] ?? 0);
    $previousStmt->close();
}

if ($userId > 0) {
    $updateStmt = $conn->prepare('UPDATE projectsite SET Timekeeper_UserID = ? WHERE SiteID = ?');
    if (!$updateStmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare timekeeper assignment']);
        exit;
    }
    $updateStmt->bind_param('ii', $userId, $siteId);
} else {
    $updateStmt = $conn->prepare('UPDATE projectsite SET Timekeeper_UserID = NULL WHERE SiteID = ?');
    if (!$updateStmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare timekeeper assignment']);
        exit;
    }
    $updateStmt->bind_param('i', $siteId);
}

if (!$updateStmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to assign timekeeper: ' . $updateStmt->error]);
    $updateStmt->close();
    exit;
}
$updateStmt->close();

if ($userId > 0) {
    sync_projectsite_timekeeper_user($conn, $userId, $siteId, true);
} elseif ($previousTimekeeperUserId > 0) {
    sync_projectsite_timekeeper_user($conn, $previousTimekeeperUserId, 0, false);
}

if (timekeeper_assignment_table_exists($conn)) {
    if ($userId > 0) {
        if ($previousTimekeeperUserId > 0 && $previousTimekeeperUserId !== $userId) {
            deactivate_timekeeper_assignments($conn, $previousTimekeeperUserId);
        }
        deactivate_timekeeper_assignments($conn, $userId);
        $assignDate = date('Y-m-d');
        $insertStmt = $conn->prepare("
            INSERT INTO timekeeper_assignment (UserID, SiteID, AssignedDate, Status)
            VALUES (?, ?, ?, 'Active')
        ");
        if ($insertStmt) {
            $insertStmt->bind_param('iis', $userId, $siteId, $assignDate);
            $insertStmt->execute();
            $insertStmt->close();
        }
    } elseif ($previousTimekeeperUserId > 0) {
        deactivate_timekeeper_assignments($conn, $previousTimekeeperUserId);
    }
}

$activation = site_sync_activation_status($conn, $siteId);

if ($sessionUserId > 0) {
    if ($userId > 0) {
        $tkName = trim((string) ($timekeeperUser['full_name'] ?? ''));
        $tkEmail = trim((string) ($timekeeperUser['email'] ?? ''));
        $label = $tkName !== '' ? $tkName : $tkEmail;
        record_audit_log(
            $sessionUserId,
            'Site Timekeeper Assigned',
            "{$currentRole} assigned Timekeeper {$label} to {$siteName}"
        );
    } else {
        record_audit_log(
            $sessionUserId,
            'Site Timekeeper Cleared',
            "{$currentRole} removed Timekeeper from {$siteName}"
        );
    }
}

echo json_encode([
    'success' => true,
    'message' => $userId > 0 ? 'Timekeeper assigned successfully' : 'Timekeeper removed from site',
    'site_id' => $siteId,
    'user_id' => $userId > 0 ? $userId : null,
    'activation' => $activation,
]);

$conn->close();
