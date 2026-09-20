<?php
header('Content-Type: application/json');
require_once __DIR__ . '/connection/db_config.php';
require_once __DIR__ . '/../includes/user_identity.php';
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['Admin', 'Assistant Admin'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}
$email = trim($_GET['email'] ?? '');
$excludeId = max(0, (int) ($_GET['exclude_id'] ?? 0));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Enter a valid email address.']);
    exit;
}
// Use the same checks as saving, including archived accounts and employees.
try {
    $fullName = trim((string) ($_GET['full_name'] ?? ''));
    $emailAvailable = !user_identity_email_exists($conn, $email, $excludeId);
    $nameAvailable = $fullName === '' || !user_identity_full_name_exists($conn, $fullName, $excludeId);
    echo json_encode(['success' => true, 'available' => $emailAvailable, 'name_available' => $nameAvailable]);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to verify account availability. Please retry.']);
}