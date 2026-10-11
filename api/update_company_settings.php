<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_auth($conn, ['Admin', 'Assistant Admin']);

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
if (!preg_match('/^[\p{L}\p{N}\s&.,()\-]+$/u', $company_name)) {
    echo json_encode(['success' => false, 'message' => 'Company name contains unsupported characters.']);
    exit;
}
if ($tax_id !== '' && !preg_match('/^\d+(?:-\d+)*$/', $tax_id)) {
    echo json_encode(['success' => false, 'message' => 'Tax ID must contain numbers and hyphens only.']);
    exit;
}
// Phone numbers may contain conventional formatting, for example
// +63 912-345-6789 or (555) 123-4567.
if ($phone !== '' && !preg_match('/^[\d\s\-()+]+$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Phone number has an invalid format.']);
    exit;
}
if ($address !== '' && !preg_match('/^[\p{L}\p{N}\s,.-]+$/u', $address)) {
    echo json_encode(['success' => false, 'message' => 'Address contains unsupported characters.']);
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

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['logo']['tmp_name']);
    $imageInfo = @getimagesize($_FILES['logo']['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png'], true) || !$imageInfo
        || !in_array($imageInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
        echo json_encode(['success' => false, 'message' => 'Upload a valid JPG or PNG image.']);
        exit;
    }
    $file_ext = $mime === 'image/png' ? 'png' : 'jpg';

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
    error_log('Company settings update failed: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Unable to save company settings. Please try again.']);
}
?>
