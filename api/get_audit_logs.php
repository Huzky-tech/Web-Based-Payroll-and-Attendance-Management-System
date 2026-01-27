<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

$sql = "SELECT timestamp, user, action FROM audit_logs ORDER BY timestamp DESC LIMIT 100";
$result = $conn->query($sql);

$logs = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $logs[] = [
            'time' => $row['timestamp'],
            'user' => $row['user'],
            'action' => $row['action']
        ];
    }
}

echo json_encode($logs);
$conn->close();
?>
