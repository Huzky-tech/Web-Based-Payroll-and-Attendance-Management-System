<?php

function position_catalog_ensure_table(mysqli $conn): bool
{
    $sql = "CREATE TABLE IF NOT EXISTS employee_position_catalog (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        position_name VARCHAR(100) NOT NULL,
        hourly_rate DECIMAL(10,2) NOT NULL DEFAULT 0,
        salary_rate DECIMAL(10,2) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_employee_position_name (position_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (!$conn->query($sql)) return false;
    $defaults = [
        ['Construction Worker', 125, 22000], ['Laborer', 110, 19000],
        ['Carpenter', 150, 26000], ['Mason', 150, 26000],
        ['Electrician', 175, 30000], ['Plumber', 170, 29000],
        ['Welder', 165, 28000], ['Painter', 140, 24000],
        ['Heavy Equipment Operator', 190, 33000], ['Site Foreman', 220, 38000],
    ];
    $stmt = $conn->prepare('INSERT IGNORE INTO employee_position_catalog (position_name, hourly_rate, salary_rate) VALUES (?, ?, ?)');
    if (!$stmt) return false;
    foreach ($defaults as [$name, $hourly, $salary]) {
        $stmt->bind_param('sdd', $name, $hourly, $salary);
        $stmt->execute();
    }
    $stmt->close();
    return true;
}
