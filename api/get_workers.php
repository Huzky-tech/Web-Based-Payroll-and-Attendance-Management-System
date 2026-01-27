<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

$sql = "SELECT w.*, s.site_name FROM workers w LEFT JOIN sites s ON w.site_id = s.id ORDER BY w.created_at DESC";
$result = $conn->query($sql);

$workers = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $workers[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'initials' => $row['initials'],
            'role' => $row['role'],
            'status' => $row['status'],
            'site' => $row['site_name'] ?: 'Not Assigned',
            'email' => $row['email'],
            'phone' => $row['phone'],
            'joined' => $row['joined_date'],
            'attendance' => $row['attendance_rate'],
            'color' => $row['color']
        ];
    }
}

echo json_encode($workers);
$conn->close();
?>
