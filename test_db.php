<?php
include 'api/connection/db_config.php';

echo "Testing database updates...\n";

// Check if new tables exist
$tables = ['company_settings', 'payroll_settings', 'notification_settings', 'security_settings', 'system_settings'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✓ Table '$table' exists\n";
    } else {
        echo "✗ Table '$table' does not exist\n";
    }
}

// Check if users table has new columns
$result = $conn->query("DESCRIBE users");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

$new_columns = ['full_name', 'role', 'status', 'last_login'];
foreach ($new_columns as $col) {
    if (in_array($col, $columns)) {
        echo "✓ Column '$col' exists in users table\n";
    } else {
        echo "✗ Column '$col' does not exist in users table\n";
    }
}

// Check default data
$tables_with_data = [
    'company_settings' => 'company_name',
    'payroll_settings' => 'pay_periods',
    'notification_settings' => 'email_notifications',
    'security_settings' => 'password_expiry_days',
    'system_settings' => 'system_version'
];

foreach ($tables_with_data as $table => $column) {
    $result = $conn->query("SELECT COUNT(*) as count FROM $table");
    $row = $result->fetch_assoc();
    if ($row['count'] > 0) {
        echo "✓ Table '$table' has default data\n";
    } else {
        echo "✗ Table '$table' has no data\n";
    }
}

echo "Database test completed.\n";
?>
