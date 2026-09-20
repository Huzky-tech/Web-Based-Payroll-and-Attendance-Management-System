<?php
header('Content-Type: application/json');
require_once __DIR__ . '/connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/worker_email_helpers.php';
require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR']);
$email = trim((string) ($_GET['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Enter a valid email address.']);
    exit;
}
try {
    $duplicate = worker_email_in_use($conn, $email, max(0, (int) ($_GET['exclude_worker_id'] ?? 0)));
    echo json_encode(['success' => true, 'duplicate' => $duplicate]);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to verify email availability. Please try again.']);
}
