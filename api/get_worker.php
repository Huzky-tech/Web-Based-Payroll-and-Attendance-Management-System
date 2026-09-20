<?php
/**
 * Get Worker by ID API
 * Returns worker details for Flutter QR scanner integration.
 */

header('Content-Type: application/json');
include 'connection/db_config.php';

// Public read for Flutter QR scanner (no PHP session on mobile).

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$idParam = $_GET['id'] ?? '';
if ($idParam === '' || !ctype_digit((string) $idParam)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid worker ID']);
    exit;
}

$workerId = (int) $idParam;
if ($workerId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid worker ID']);
    exit;
}

$sql = 'SELECT WorkerID, First_Name, Last_Name, Phone FROM worker WHERE WorkerID = ? LIMIT 1';
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
    $conn->close();
    exit;
}

$stmt->bind_param('i', $workerId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['status' => 'error', 'message' => 'Worker not found']);
    $conn->close();
    exit;
}

$name = trim(($row['First_Name'] ?? '') . ' ' . ($row['Last_Name'] ?? ''));

echo json_encode([
    'status' => 'success',
    'data' => [
        'WorkerID' => (int) $row['WorkerID'],
        'name' => $name,
        'phone' => $row['Phone'] ?? '',
    ],
]);

$conn->close();
