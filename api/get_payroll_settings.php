<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

function ensure_payroll_deduction_columns(mysqli $conn): void {
    $columns = [
        'late_worker_deduction' => "ALTER TABLE payroll_settings ADD COLUMN late_worker_deduction DECIMAL(10,2) NOT NULL DEFAULT 0.00",
        'position_default_deductions' => "ALTER TABLE payroll_settings ADD COLUMN position_default_deductions TEXT DEFAULT NULL",
    ];

    foreach ($columns as $column => $alterSql) {
        $check = $conn->query("SHOW COLUMNS FROM payroll_settings LIKE '{$column}'");
        if ($check && $check->num_rows === 0) {
            $conn->query($alterSql);
        }
    }
}

try {
    ensure_payroll_deduction_columns($conn);

    $stmt = $conn->prepare("SELECT * FROM payroll_settings WHERE id = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $settings = $result->fetch_assoc();

    if ($settings) {
        echo json_encode(['success' => true, 'data' => $settings]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Settings not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
