<?php
require_once __DIR__ . '/../includes/security.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "payroll_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'This email is already registered.']);
    $stmt->close();
    $conn->close();
    exit;
}

$_SESSION['pending_email'] = $email;
unset($_SESSION['verification_code'], $_SESSION['verified']);

echo json_encode(['success' => true, 'message' => 'Registration started.']);

$stmt->close();
$conn->close();
?>
