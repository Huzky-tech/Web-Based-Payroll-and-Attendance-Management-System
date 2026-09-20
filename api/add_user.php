<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/user_identity.php';
include 'connection/db_config.php';
include '../includes/password_policy.php';
include '../includes/email_delivery.php';
require_once __DIR__ . '/../includes/login_security.php';
require_once __DIR__ . '/record_audit_log.php';

// Session may already be started in db_config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentRole = $_SESSION['role'] ?? '';

// Only Admin can add users
if ($currentRole !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied. Only Admin can add users.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$full_name = trim($first_name . ' ' . $last_name);
$email = trim($_POST['email'] ?? '');
$role = $_POST['role'] ?? 'Worker';

function generate_temporary_password(mysqli $conn): string {
    $settings = password_policy_settings($conn);
    $minimumLength = max(12, (int) ($settings['min_password_length'] ?? 8));
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
    $password = 'A' . 'a' . '7' . '!';
    while (strlen($password) < $minimumLength) {
        $password .= $characters[random_int(0, strlen($characters) - 1)];
    }
    $charactersArray = str_split($password);
    for ($index = count($charactersArray) - 1; $index > 0; $index--) {
        $swapIndex = random_int(0, $index);
        [$charactersArray[$index], $charactersArray[$swapIndex]] = [$charactersArray[$swapIndex], $charactersArray[$index]];
    }
    return implode('', $charactersArray);
}

if ($role === 'Assistant Admin') {
    $role = 'Assistant Admin';
}

$allowedRoles = ['Admin', 'Assistant Admin', 'HR', 'Payroll Staff', 'Timekeeper', 'Worker'];
if (!in_array($role, $allowedRoles, true)) {
    echo json_encode(['success' => false, 'message' => 'Selected role is no longer available.']);
    exit;
}


if (empty($first_name) || empty($last_name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if (!preg_match('/^[\\p{L}]+(?: [\\p{L}]+)*$/u', $first_name)
    || !preg_match('/^[\\p{L}]+(?: [\\p{L}]+)*$/u', $last_name)
    || mb_strlen($first_name) > 50 || mb_strlen($last_name) > 50) {
    echo json_encode(['success' => false, 'message' => 'First name and last name may contain letters and single spaces only. Numbers and special characters are not allowed.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

try {
    user_identity_ensure_columns($conn);
    user_identity_lock($conn);
    if (user_identity_email_exists($conn, $email, 0)) {
        throw new RuntimeException('This email address is already registered.');
    }
    if (user_identity_full_name_exists($conn, $full_name, 0)) {
        echo json_encode(['success' => false, 'message' => 'This first and last name combination is already registered.']);
        exit;
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
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
    echo json_encode(['success' => false, 'message' => 'This email is already registered.']);
    exit;
}

try {
    $password = generate_temporary_password($conn);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    login_security_ensure_columns($conn);
    $conn->begin_transaction();
    $stmt = $conn->prepare("INSERT INTO users (full_name, first_name, last_name, email, password, status, password_last_set_at, must_change_password) VALUES (?, ?, ?, ?, ?, 'Active', NOW(), 1)");
    $stmt->bind_param("sssss", $full_name, $first_name, $last_name, $email, $hashed_password);

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
            case 'Assistant Admin':
                $role_stmt = $conn->prepare("INSERT INTO assistantmanager (UserID) VALUES (?)");
                $role_stmt->bind_param("i", $user_id);
                $role_stmt->execute();
                break;
            case 'Timekeeper':
                $role_stmt = $conn->prepare("INSERT INTO timekeeper (UserID) VALUES (?)");
                $role_stmt->bind_param("i", $user_id);
                $role_stmt->execute();
                break;
            case 'Worker':
                // Also create a worker record
                $rate_type = 'Hourly';
                $rate_amount = 0.00;
                $phone = null;
                $date_hired = date('Y-m-d');
                $worker_status_id = 1; // Active status
                
                $worker_stmt = $conn->prepare("INSERT INTO worker (First_Name, Last_Name, RateType, RateAmount, Phone, DateHired, WorkerStatusID, UserID) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $worker_stmt->bind_param("sssdssii", $first_name, $last_name, $rate_type, $rate_amount, $phone, $date_hired, $worker_status_id, $user_id);
                if (!$worker_stmt->execute()) {
                    throw new RuntimeException('Failed to create the Worker profile.');
                }
                $worker_stmt->close();
                break;
        }

        if (isset($role_stmt)) {
            if ($role_stmt->errno) {
                throw new RuntimeException('Failed to create the selected user role.');
            }
            $role_stmt->close();
        }

        $applicationUrl = rtrim((string) (getenv('APP_URL') ?: 'http://localhost/capstone'), '/');
        // SMTP may take several seconds; do not block other requests from this session.
        session_write_close();
        $emailMessage = "Hello {$full_name},\n\n"
            . "Your {$role} account for Philippians CDO Construction Company has been created.\n\n"
            . "Login email: {$email}\n"
            . "Temporary password: {$password}\n"
            . "Login page: {$applicationUrl}/index.php\n\n"
            . "For your security, you will be required to verify your email and create a new password during your first login.\n"
            . "Do not share this temporary password with anyone.";

        if (!app_send_email($email, 'Your temporary system password', $emailMessage)) {
            throw new RuntimeException('The account was not created because the temporary-password email could not be delivered. Please check the SMTP settings and try again.');
        }

        $actorUserId = (int) ($_SESSION['user_id'] ?? 0);
        if (!record_audit_log(
            $actorUserId,
            'User Account Created',
            "Created {$role} account for {$full_name} ({$email})."
        )) {
            throw new RuntimeException('The account was not created because its audit log could not be recorded.');
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'User added successfully. A temporary password was sent to their email.']);
    } else {
        throw new RuntimeException('Failed to add user');
    }
} catch (Throwable $e) {
    if ($conn->errno === 0 || $conn->connect_errno === 0) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
    }
    $message = ((int) $e->getCode() === 1062)
        ? 'This email is already registered.'
        : $e->getMessage();
    echo json_encode(['success' => false, 'message' => $message]);
}
?>
