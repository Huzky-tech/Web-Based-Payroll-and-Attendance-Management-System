<?php
include 'api/connection/db_config.php';

// Check if role column exists in users table
$check_column = "SELECT COUNT(*) as count FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'payroll_db' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'";
$result = $conn->query($check_column);
$row = $result->fetch_assoc();

if ($row['count'] > 0) {
    // Remove the role column (we now use role tables instead)
    $alter_table = "ALTER TABLE users DROP COLUMN role";
    if ($conn->query($alter_table)) {
        echo "Successfully removed 'role' column from users table.<br>";
    } else {
        echo "Error removing column: " . $conn->error . "<br>";
    }
} else {
    echo "No 'role' column in users table - using role tables instead.<br>";
}

echo "Database fix complete.";
?>
