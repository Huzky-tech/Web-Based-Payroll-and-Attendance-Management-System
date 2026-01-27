<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $site = $_POST['site'] ?? '';

    if (empty($id) || empty($site)) {
        echo json_encode(['success' => false, 'message' => 'Employee ID and site are required']);
        exit;
    }

    // First, find the site_id based on site name
    $siteQuery = "SELECT id FROM sites WHERE site_name = ?";
    $siteStmt = $conn->prepare($siteQuery);
    $siteStmt->bind_param("s", $site);
    $siteStmt->execute();
    $siteResult = $siteStmt->get_result();

    if ($siteResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Site not found']);
        exit;
    }

    $siteRow = $siteResult->fetch_assoc();
    $siteId = $siteRow['id'];

    // Update the employee
    $updateQuery = "UPDATE employees SET site_id = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("ii", $siteId, $id);

    if ($updateStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Employee site updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update employee site']);
    }

    $siteStmt->close();
    $updateStmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
