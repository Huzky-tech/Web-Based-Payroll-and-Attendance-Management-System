<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = $_POST['role'] ?? 'Worker';
$password = $_POST['password'] ?? '';

// Validate inputs
if (empty($full_name) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
    exit;
}

// Prevent creating more than one admin if already exists
if ($role === 'Admin') {
    $admin_count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin");
    $admin_count_stmt->execute();
    $admin_count_result = $admin_count_stmt->get_result();
    $admin_count = $admin_count_result->fetch_assoc()['count'];
    
    if ($admin_count >= 1) {
        echo json_encode(['success' => false, 'message' => 'Only one admin account is allowed. Please assign a different role.']);
        exit;
    }
}

// Check if email exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    exit;
}

try {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, status) VALUES (?, ?, ?, 'Active')");
    $stmt->bind_param("sss", $full_name, $email, $hashed_password);

    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        
        // Add user to role-specific table based on role
        switch ($role) {
            case 'Admin':
                $role_stmt = $conn->prepare("INSERT INTO admin (UserID) VALUES (?)");
                $role_stmt->bind_param("i", $user_id);
                $role_stmt->execute();
                break;
            case 'Payroll Staff':
                $role_stmt = $conn->prepare("INSERT INTO payrollstaff (UserID) VALUES (?)");
                $role_stmt->bind_param("i", $user_id);
                $role_stmt->execute();
                break;
            case 'HR':
                $role_stmt = $conn->prepare("INSERT INTO hr (UserID) VALUES (?)");
                $role_stmt->bind_param("i", $user_id);
                $role_stmt->execute();
                break;
            case 'Timekeeper':
                $role_stmt = $conn->prepare("INSERT INTO timekeeper (UserID) VALUES (?)");
                $role_stmt->bind_param("i", $user_id);
                $role_stmt->execute();
                break;
            case 'Assistant Manager':
                $role_stmt = $conn->prepare("INSERT INTO assistantmanager (UserID) VALUES (?)");
                $role_stmt->bind_param("i", $user_id);
                $role_stmt->execute();
                break;
            case 'Worker':
                // Also create a worker record
                // Parse full name into first and last name
                $name_parts = explode(' ', $full_name, 2);
                $first_name = $name_parts[0];
                $last_name = $name_parts[1] ?? '';
                $rate_type = 'Hourly';
                $rate_amount = 0.00;
                $phone = '';
                $date_hired = date('Y-m-d');
                $worker_status_id = 1; // Active status
                
                $worker_stmt = $conn->prepare("INSERT INTO worker (First_Name, Last_Name, RateType, RateAmount, Phone, DateHired, WorkerStatusID, UserID) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $worker_stmt->bind_param("sssdssii", $first_name, $last_name, $rate_type, $rate_amount, $phone, $date_hired, $worker_status_id, $user_id);
                $worker_stmt->execute();
                break;
        }
        
        echo json_encode(['success' => true, 'message' => 'User added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add user']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
