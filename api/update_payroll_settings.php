<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$pay_periods = trim($_POST['pay_periods'] ?? '');
$sss_rate = floatval($_POST['sss_rate'] ?? 0);
$philhealth_rate = floatval($_POST['philhealth_rate'] ?? 0);
$pagibig_rate = floatval($_POST['pagibig_rate'] ?? 0);
$tax_table = trim($_POST['tax_table'] ?? '');
$allow_overtime = isset($_POST['allow_overtime']) ? 1 : 0;
$allow_night_diff = isset($_POST['allow_night_diff']) ? 1 : 0;
$overtime_rate = floatval($_POST['overtime_rate'] ?? 0);
$night_diff_rate = floatval($_POST['night_diff_rate'] ?? 0);

// Validate rates
if ($philhealth_rate < 0 || $pagibig_rate < 0 || $overtime_rate < 0 || $night_diff_rate < 0) {
    echo json_encode(['success' => false, 'message' => 'Rates must be non-negative']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE payroll_settings SET pay_periods = ?, sss_rate = ?, philhealth_rate = ?, pagibig_rate = ?, tax_table = ?, allow_overtime = ?, allow_night_diff = ?, overtime_rate = ?, night_diff_rate = ? WHERE id = 1");
    $stmt->bind_param("sddddsddd", $pay_periods, $sss_rate, $philhealth_rate, $pagibig_rate, $tax_table, $allow_overtime, $allow_night_diff, $overtime_rate, $night_diff_rate);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Payroll settings updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update settings']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
