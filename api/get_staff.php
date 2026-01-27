<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

$sql = "SELECT s.*, COUNT(a.id) as site_count FROM staff s LEFT JOIN assignments a ON s.id = a.staff_id GROUP BY s.id ORDER BY s.name";
$result = $conn->query($sql);

$staff = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $staff[] = [
            'id' => $row['id'],
            'staff_id' => $row['staff_id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'site_count' => (int)$row['site_count']
        ];
    }
}

echo json_encode($staff);
$conn->close();
?>
