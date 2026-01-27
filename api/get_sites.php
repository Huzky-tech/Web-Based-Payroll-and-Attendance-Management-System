<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

$sql = "SELECT id, site_name, status FROM sites ORDER BY site_name";
$result = $conn->query($sql);

$sites = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $sites[] = [
            'id' => $row['id'],
            'site_name' => $row['site_name'],
            'status' => $row['status']
        ];
    }
}

echo json_encode($sites);
$conn->close();
?>
