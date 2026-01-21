<?php
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$code = $data['code'] ?? '';

if (!isset($_SESSION['verification_code']) || $_SESSION['verification_code'] != $code) {
    echo json_encode(['success' => false, 'message' => 'Invalid code']);
    exit;
}

$_SESSION['verified'] = true;
echo json_encode(['success' => true, 'message' => 'Verified']);
?>
