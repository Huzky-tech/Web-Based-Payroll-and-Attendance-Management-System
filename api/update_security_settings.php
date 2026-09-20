<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_auth($conn, ['Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$numericInputs = ['password_expiry_days', 'min_password_length', 'max_login_attempts', 'session_timeout_minutes'];
foreach ($numericInputs as $field) {
    if (!preg_match('/^\d+$/', trim((string) ($_POST[$field] ?? '')))) {
        echo json_encode(['success' => false, 'message' => 'Security setting values must contain whole numbers only.']);
        exit;
    }
}
$password_expiry_days = intval($_POST['password_expiry_days']);
$min_password_length = intval($_POST['min_password_length']);
$require_special_char = (int) ($_POST['require_special_char'] ?? 0) === 1 ? 1 : 0;
$require_number = (int) ($_POST['require_number'] ?? 0) === 1 ? 1 : 0;
$require_uppercase = (int) ($_POST['require_uppercase'] ?? 0) === 1 ? 1 : 0;
$max_login_attempts = intval($_POST['max_login_attempts']);
$session_timeout_minutes = intval($_POST['session_timeout_minutes']);
$enable_2fa = (int) ($_POST['enable_2fa'] ?? 0) === 1 ? 1 : 0;
$enable_ip_restriction = (int) ($_POST['enable_ip_restriction'] ?? 0) === 1 ? 1 : 0;

// Validate inputs
if ($password_expiry_days < 1 || $min_password_length < 1 || $max_login_attempts < 1 || $session_timeout_minutes < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid numeric values']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE security_settings SET password_expiry_days = ?, min_password_length = ?, require_special_char = ?, require_number = ?, require_uppercase = ?, max_login_attempts = ?, session_timeout_minutes = ?, enable_2fa = ?, enable_ip_restriction = ? WHERE id = 1");
    $stmt->bind_param("iiiiiiiii", $password_expiry_days, $min_password_length, $require_special_char, $require_number, $require_uppercase, $max_login_attempts, $session_timeout_minutes, $enable_2fa, $enable_ip_restriction);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Security settings updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update settings']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
