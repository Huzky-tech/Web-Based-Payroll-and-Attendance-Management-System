<?php
include 'api/connection/db_config.php';

echo "=== payrollstaff table ===\n";
$result = $conn->query('SELECT * FROM payrollstaff');
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) { print_r($row); }
} else { echo "No records found\n"; }

echo "\n=== payrollstaffassignment table ===\n";
$result2 = $conn->query('SELECT * FROM payrollstaffassignment');
if ($result2->num_rows > 0) {
    while($row = $result2->fetch_assoc()) { print_r($row); }
} else { echo "No records found\n"; }

echo "\n=== users table ===\n";
$result3 = $conn->query('SELECT id, email, full_name FROM users');
while($row = $result3->fetch_assoc()) { print_r($row); }

$conn->close();
?>

