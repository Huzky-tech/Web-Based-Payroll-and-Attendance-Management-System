<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$company_name = trim($_POST['company_name'] ?? '');
$tax_id = trim($_POST['tax_id'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');

// Validate inputs
if (empty($company_name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Company name and email are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

$logo_path = null;

// Handle logo upload
if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file_name = basename($_FILES['logo']['name']);
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_exts = ['jpg', 'jpeg', 'png'];

    if (!in_array($file_ext, $allowed_exts)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, JPEG, PNG allowed']);
        exit;
    }

    if ($_FILES['logo']['size'] > 2 * 1024 * 1024) { // 2MB limit
        echo json_encode(['success' => false, 'message' => 'File size too large. Max 2MB']);
        exit;
    }

    $new_file_name = 'company_logo_' . time() . '.' . $file_ext;
    $upload_path = $upload_dir . $new_file_name;

    if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
        $logo_path = 'uploads/' . $new_file_name;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload logo']);
        exit;
    }
}

try {
    if ($logo_path) {
        $stmt = $conn->prepare("UPDATE company_settings SET company_name = ?, tax_id = ?, phone = ?, email = ?, address = ?, logo_path = ? WHERE id = 1");
        $stmt->bind_param("ssssss", $company_name, $tax_id, $phone, $email, $address, $logo_path);
    } else {
        $stmt = $conn->prepare("UPDATE company_settings SET company_name = ?, tax_id = ?, phone = ?, email = ?, address = ? WHERE id = 1");
        $stmt->bind_param("sssss", $company_name, $tax_id, $phone, $email, $address);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Company settings updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update settings']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
