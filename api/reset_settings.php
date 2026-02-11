<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Reset all settings to defaults
    $conn->query("UPDATE company_settings SET company_name = 'Philippians CDO Construction Company', tax_id = '123-45-6789', phone = '(555) 123-4567', email = 'info@philippianscdo.com', address = '123 Main Street, CDO City', logo_path = NULL WHERE id = 1");
    $conn->query("UPDATE payroll_settings SET pay_periods = 'Semi-monthly (1-15, 16-end)', sss_rate = 0.00, philhealth_rate = 3.00, pagibig_rate = 2.00, tax_table = 'Latest BIR Tax Table', allow_overtime = TRUE, allow_night_diff = TRUE, overtime_rate = 1.25, night_diff_rate = 1.10 WHERE id = 1");
    $conn->query("UPDATE notification_settings SET email_notifications = TRUE, in_system_notifications = TRUE, leave_request_updates = TRUE, payroll_processing = TRUE, attendance_issues = FALSE, system_updates = FALSE, daily_reports = FALSE, email_digest_frequency = 'Daily' WHERE id = 1");
    $conn->query("UPDATE security_settings SET password_expiry_days = 90, min_password_length = 8, require_special_char = TRUE, require_number = TRUE, require_uppercase = TRUE, max_login_attempts = 5, session_timeout_minutes = 30, enable_2fa = FALSE, enable_ip_restriction = FALSE WHERE id = 1");
    $conn->query("UPDATE system_settings SET maintenance_mode = FALSE, debug_mode = FALSE, data_retention_days = 365, backup_schedule = 'Daily', timezone = 'Asia/Manila (GMT+8)', date_format = 'MM/DD/YYYY', time_format = '12-hour (AM/PM)', system_version = '1.0.5', last_update = '2023-07-01', server_environment = 'Production', database_size = '125 MB' WHERE id = 1");

    echo json_encode(['success' => true, 'message' => 'All settings reset to defaults']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
