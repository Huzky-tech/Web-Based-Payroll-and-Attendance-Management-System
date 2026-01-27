<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

$sql = "SELECT * FROM payroll_staff ORDER BY created_at DESC";
$result = $conn->query($sql);

$staff = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $staff[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'sites' => json_decode($row['sites'], true)
        ];
    }
}

echo json_encode($staff);
$conn->close();
?>
