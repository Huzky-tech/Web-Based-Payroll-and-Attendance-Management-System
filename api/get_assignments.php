<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

$staff_id = $_GET['staff_id'] ?? '';

if (empty($staff_id)) {
    echo json_encode([]);
    $conn->close();
    exit;
}

$sql = "SELECT s.*, a.assigned_at FROM sites s
        INNER JOIN assignments a ON s.id = a.site_id
        WHERE a.staff_id = ?
        ORDER BY s.site_name";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();

$assignments = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $assignments[] = [
            'id' => $row['id'],
            'name' => $row['site_name'],
            'address' => $row['location'] ?: 'Address not specified',
            'status' => $row['status'],
            'assigned_at' => $row['assigned_at']
        ];
    }
}

echo json_encode($assignments);
$stmt->close();
$conn->close();
?>
