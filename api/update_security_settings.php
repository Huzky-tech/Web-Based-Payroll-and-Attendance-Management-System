<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$password_expiry_days = intval($_POST['password_expiry_days'] ?? 90);
$min_password_length = intval($_POST['min_password_length'] ?? 8);
$require_special_char = isset($_POST['require_special_char']) ? 1 : 0;
$require_number = isset($_POST['require_number']) ? 1 : 0;
$require_uppercase = isset($_POST['require_uppercase']) ? 1 : 0;
$max_login_attempts = intval($_POST['max_login_attempts'] ?? 5);
$session_timeout_minutes = intval($_POST['session_timeout_minutes'] ?? 30);
$enable_2fa = isset($_POST['enable_2fa']) ? 1 : 0;
$enable_ip_restriction = isset($_POST['enable_ip_restriction']) ? 1 : 0;

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
