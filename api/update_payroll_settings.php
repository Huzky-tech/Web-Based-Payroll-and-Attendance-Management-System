<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_auth($conn, ['Admin', 'Assistant Admin']);

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

function normalize_pay_period(string $value): string {
    return 'Weekly';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$pay_periods = normalize_pay_period((string) ($_POST['pay_periods'] ?? ''));
$overtimeRaw = trim((string) ($_POST['overtime_rate'] ?? ''));
$lateDeductionRaw = trim((string) ($_POST['late_worker_deduction'] ?? ''));
foreach (['Overtime Rate' => $overtimeRaw, 'Late Worker Deduction' => $lateDeductionRaw] as $label => $value) {
    if (!preg_match('/^\d+(?:\.\d+)?$/', $value)) {
        echo json_encode(['success' => false, 'message' => "{$label} must contain numbers only. Special characters and letters are not allowed."]);
        exit;
    }
}
// Government deductions are fixed by business policy and cannot be changed
// through this endpoint, including by a manually crafted request.
$sss_rate = 950.00;
$philhealth_rate = 262.50;
$pagibig_rate = 200.00;
$allow_overtime = (int) ($_POST['allow_overtime'] ?? 0) === 1 ? 1 : 0;
$overtime_rate = (float) $overtimeRaw;
$late_worker_deduction = (float) $lateDeductionRaw;
if ($overtime_rate < 0 || $late_worker_deduction < 0) {
    echo json_encode(['success' => false, 'message' => 'Rates must be non-negative']);
    exit;
}

try {
    ensure_payroll_deduction_columns($conn);

    // Position default deductions are deprecated; only late_worker_deduction is used.
    $stmt = $conn->prepare("UPDATE payroll_settings SET pay_periods = ?, sss_rate = ?, philhealth_rate = ?, pagibig_rate = ?, allow_overtime = ?, overtime_rate = ?, late_worker_deduction = ? WHERE id = 1");
    $stmt->bind_param("sdddidd", $pay_periods, $sss_rate, $philhealth_rate, $pagibig_rate, $allow_overtime, $overtime_rate, $late_worker_deduction);


    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Payroll settings updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update settings']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
