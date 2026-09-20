<?php
require_once __DIR__ . '/../includes/user_identity.php';
header('Content-Type: application/json');

include 'connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR']);

$firstName = trim((string) ($_GET['first_name'] ?? ''));
$lastName = trim((string) ($_GET['last_name'] ?? ''));
$excludeWorkerId = (int) ($_GET['exclude_worker_id'] ?? 0);

if ($firstName === '' || $lastName === '') {
    echo json_encode(['success' => true, 'duplicate' => false, 'message' => '']);
    exit;
}

try {
    $duplicate = user_identity_full_name_exists($conn, $firstName . ' ' . $lastName, 0, $excludeWorkerId);
    echo json_encode(['success' => true, 'duplicate' => $duplicate,
        'message' => $duplicate ? 'This first and last name combination is already registered.' : '']);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to check full name availability.']);
}