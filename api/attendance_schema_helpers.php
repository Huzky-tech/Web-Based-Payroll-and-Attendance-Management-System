<?php
/**
 * Runtime schema guard for the mobile attendance endpoints.
 */

if (!function_exists('attendance_schema_ensure_table')) {
    function attendance_schema_ensure_table(mysqli $conn): void
    {
        $created = $conn->query("
            CREATE TABLE IF NOT EXISTS attendance (
                AttendanceID INT AUTO_INCREMENT PRIMARY KEY,
                WorkerID INT NOT NULL,
                SiteID INT NOT NULL,
                Date DATE NOT NULL,
                Time_In TIME NULL,
                Time_Out TIME NULL,
                Hours_Worked DECIMAL(6,2) NOT NULL DEFAULT 0,
                AttendanceStatus VARCHAR(30) NOT NULL DEFAULT 'Present',
                CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        if (!$created) {
            mobile_json_error('Attendance table is missing or cannot be created: ' . $conn->error, 500);
        }

        $columns = attendance_schema_columns($conn);
        if (!isset($columns['AttendanceID'])) {
            mobile_json_error('Attendance table is missing AttendanceID column.', 500);
        }

        $required = [
            'IsLate' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'WorkerID' => 'INT NOT NULL DEFAULT 0',
            'SiteID' => 'INT NOT NULL DEFAULT 0',
            'Date' => 'DATE NULL',
            'Time_In' => 'TIME NULL',
            'Time_Out' => 'TIME NULL',
            'Hours_Worked' => 'DECIMAL(6,2) NOT NULL DEFAULT 0',
            'AttendanceStatus' => "VARCHAR(30) NOT NULL DEFAULT 'Present'",
        ];

        foreach ($required as $column => $definition) {
            if (isset($columns[$column])) {
                continue;
            }
            $added = $conn->query("ALTER TABLE attendance ADD COLUMN {$column} {$definition}");
            if (!$added) {
                mobile_json_error("Attendance table is missing {$column} and could not be updated: " . $conn->error, 500);
            }
        }
        if (!$conn->query("UPDATE attendance SET IsLate = 1, AttendanceStatus = 'Present' WHERE AttendanceStatus = 'Late'")) {
            mobile_json_error('Could not preserve the legacy late indicator.', 500);
        }
    }
}

if (!function_exists('attendance_schema_columns')) {
    function attendance_schema_columns(mysqli $conn): array
    {
        $result = $conn->query('SHOW COLUMNS FROM attendance');
        if (!$result) {
            mobile_json_error('Could not inspect attendance table: ' . $conn->error, 500);
        }

        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $field = (string) ($row['Field'] ?? '');
            if ($field !== '') {
                $columns[$field] = true;
            }
        }
        $result->close();
        return $columns;
    }
}

if (!function_exists('attendance_schema_has_photo_columns')) {
    function attendance_schema_has_photo_columns(mysqli $conn): bool
    {
        $columns = attendance_schema_columns($conn);
        return isset($columns['PhotoPath']);
    }
}

if (!function_exists('attendance_schema_normalize_status')) {
    function attendance_schema_normalize_status(string $status): string
    {
        $normalized = ucfirst(strtolower(trim($status)));
        if ($normalized === 'Late') return 'Present';
        if (in_array($normalized, ['Present', 'Absent'], true)) {
            return $normalized;
        }

        return 'Present';
    }
}

if (!function_exists('attendance_lunch_columns_exist')) {
    function attendance_lunch_columns_exist(mysqli $conn): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $columns = attendance_schema_columns($conn);
        $cached = isset($columns['Lunch_Out'], $columns['Lunch_In']);
        return $cached;
    }
}
