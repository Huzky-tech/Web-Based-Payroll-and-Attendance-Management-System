<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['pending_email'])) {
    echo json_encode(['success' => false, 'message' => 'No pending email']);
    exit;
}

$email = $_SESSION['pending_email'];

// Generate a 6-digit code
$code = rand(100000, 999999);
$_SESSION['verification_code'] = $code;

// For demo purposes, just return success. In production, send email.
echo json_encode(['success' => true, 'message' => 'Verification code sent (demo: ' . $code . ')']);
?>
