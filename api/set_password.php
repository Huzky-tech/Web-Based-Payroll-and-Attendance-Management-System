<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['verified']) || !$_SESSION['verified']) {
    echo json_encode(['success' => false, 'message' => 'Not verified']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$password = $data['password'] ?? '';

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password too short']);
    exit;
}

$email = $_SESSION['pending_email'];
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Database connection
$servername = "localhost";
$username = "root";
$password_db = "";
$dbname = "payroll_db";

$conn = new mysqli($servername, $username, $password_db, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
$stmt->bind_param("ss", $email, $hashed_password);

if ($stmt->execute()) {
    $_SESSION['user_id'] = $conn->insert_id;
    $_SESSION['email'] = $email;
    unset($_SESSION['pending_email'], $_SESSION['verification_code'], $_SESSION['verified']);
    echo json_encode(['success' => true, 'message' => 'Account created']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to create account']);
}

$stmt->close();
$conn->close();
?>
