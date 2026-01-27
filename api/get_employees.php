<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

$sql = "SELECT * FROM employees ORDER BY created_at DESC";
$result = $conn->query($sql);

$employees = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $employees[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'position' => $row['position'],
            'site' => $row['site'],
            'status' => $row['status'],
            'salary' => $row['salary'],
            'join' => $row['join_date']
        ];
    }
}

echo json_encode($employees);
$conn->close();
?>
