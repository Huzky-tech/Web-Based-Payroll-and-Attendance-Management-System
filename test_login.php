<?php
// Test script for login functionality
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "payroll_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Test login with admin credentials
$email = 'admin@example.com';
$password = 'admin123'; // From the TODO notes

// Prepare and execute query
$stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($id, $hashed_password);
    $stmt->fetch();

    if (password_verify($password, $hashed_password)) {
        echo "Login successful! User ID: $id, Email: $email\n";
        $_SESSION['user_id'] = $id;
        $_SESSION['email'] = $email;
        echo "Session started.\n";
    } else {
        echo "Invalid password.\n";
    }
} else {
    echo "User not found.\n";
}

$stmt->close();
$conn->close();
?>
