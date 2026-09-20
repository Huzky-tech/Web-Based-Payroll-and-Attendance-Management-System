<?php

/** Keep older installed databases compatible with the System Settings tools. */
function ensure_system_last_backup_column(mysqli $conn): bool
{
    $result = $conn->query("SHOW COLUMNS FROM system_settings LIKE 'last_backup_at'");
    if (!$result) {
        return false;
    }

    if ($result->num_rows === 0) {
        return (bool) $conn->query('ALTER TABLE system_settings ADD COLUMN last_backup_at DATETIME NULL DEFAULT NULL');
    }

    return true;
}

function ensure_password_reset_columns(mysqli $conn): bool
{
    $columns = [
        'force_password_reset' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'password_last_set_at' => 'DATETIME NULL DEFAULT NULL',
    ];

    foreach ($columns as $name => $definition) {
        $result = $conn->query("SHOW COLUMNS FROM users LIKE '{$name}'");
        if (!$result) {
            return false;
        }
        if ($result->num_rows === 0 && !$conn->query("ALTER TABLE users ADD COLUMN {$name} {$definition}")) {
            return false;
        }
    }

    return true;
}
