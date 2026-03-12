<?php
/**
 * Assign Worker to Site API
 * Insert worker-site assignment into WorkerAssignment table
 */

header('Content-Type: application/json');
include 'connection/db_config.php';

$data = json_decode(file_get_contents('php://input'), true);
$workerId = $data['workerId'] ?? null;
$siteId = $data['siteId'] ?? null;

if (!$workerId || !$siteId) {
    echo json_encode(['success' => false, 'message' => 'Missing worker or site ID']);
    exit;
}

// Optional: check if assignment already exists
$checkSql = "SELECT * FROM WorkerAssignment WHERE WorkerID = ? AND SiteID = ?";
$stmt = $conn->prepare($checkSql);
$stmt->bind_param("ii", $workerId, $siteId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Worker already assigned to this site']);
    exit;
}

// Insert assignment
$insertSql = "INSERT INTO WorkerAssignment (WorkerID, SiteID, Role_On_Site) VALUES (?, ?, '')";
$stmt = $conn->prepare($insertSql);
$stmt->bind_param("ii", $workerId, $siteId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}
$conn->close();
?>

