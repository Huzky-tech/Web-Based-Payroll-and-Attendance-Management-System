<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

function logAudit($conn, $userId, $action, $details) {
    $sql = "INSERT INTO audit_logs (UserID, Action, Details, Date) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("iss", $userId, $action, $details);
        $stmt->execute();
        $stmt->close();
    }
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$position = trim($_POST['position'] ?? '');
$rate_amount = floatval($_POST['salary'] ?? 0);
$phone = trim($_POST['phone'] ?? '');
$join_date = $_POST['join_date'] ?? date('Y-m-d');
$user_id = intval($_POST['user_id'] ?? 1);

if (empty($first_name) || empty($last_name) || $rate_amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'First name, last name, and salary are required']);
    exit;
}

$sql = "INSERT INTO worker (First_Name, Last_Name, RateType, RateAmount, Phone, DateHired, WorkerStatusID, UserID) VALUES (?, ?, 'Worker', ?, ?, ?, 1, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssdssi", $first_name, $last_name, $rate_amount, $phone, $join_date, $user_id);

if ($stmt->execute()) {
    $worker_id = $conn->insert_id;
    logAudit($conn, $user_id, 'Worker Added', "Added worker: {$first_name} {$last_name} (ID: {$worker_id})");
    echo json_encode(['success' => true, 'message' => 'Employee added successfully', 'worker_id' => $worker_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add employee: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>

