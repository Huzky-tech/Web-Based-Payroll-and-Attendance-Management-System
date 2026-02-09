<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

function logAudit($conn, $userId, $action, $details) {
    $sql = "INSERT INTO Audit_logs (UserID, Action, Details, Date) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $userId, $action, $details);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $site = $_POST['site'] ?? '';
    $userId = $_POST['userId'] ?? 1; // Assume user ID is passed or default to 1

    if (empty($id) || empty($site)) {
        echo json_encode(['success' => false, 'message' => 'Worker ID and site are required']);
        exit;
    }

    // First, find the site_id based on site name
    $siteQuery = "SELECT SiteID FROM ProjectSite WHERE Site_Name = ?";
    $siteStmt = $conn->prepare($siteQuery);
    $siteStmt->bind_param("s", $site);
    $siteStmt->execute();
    $siteResult = $siteStmt->get_result();

    if ($siteResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Site not found']);
        exit;
    }

    $siteRow = $siteResult->fetch_assoc();
    $siteId = $siteRow['SiteID'];

    // Update the worker
    $updateQuery = "UPDATE Worker SET WorkerStatusID = 1 WHERE WorkerID = ?"; // Assuming update to active status or similar
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("i", $id);

    if ($updateStmt->execute()) {
        logAudit($conn, $userId, 'Worker Updated', "Updated worker: ID $id");
        echo json_encode(['success' => true, 'message' => 'Worker updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update worker']);
    }

    $siteStmt->close();
    $updateStmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
