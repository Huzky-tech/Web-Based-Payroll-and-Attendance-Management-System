<?php
require_once __DIR__ . '/../includes/user_identity.php';
header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/../includes/user_profile_photo.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$profilePhotoPath = null;

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized user']);
    exit;
}

if ($fullName === '' || $email === '') {
    echo json_encode(['success' => false, 'message' => 'Full name and email are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

$checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$checkStmt->bind_param('si', $email, $userId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
if ($checkResult && $checkResult->num_rows > 0) {
    $checkStmt->close();
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    exit;
}
$checkStmt->close();

ensure_user_profile_photo_column($conn);
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    $photo = $_FILES['profile_photo'];
    if ($photo['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Profile photo upload failed']);
        exit;
    }
    if ((int) $photo['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Profile photo must be 5 MB or smaller']);
        exit;
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($photo['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, and WebP images are allowed']);
        exit;
    }

    $uploadDirectory = __DIR__ . '/../uploads/profile_photos';
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Unable to prepare profile photo storage']);
        exit;
    }
    $filename = 'user_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($photo['tmp_name'], $uploadDirectory . '/' . $filename)) {
        echo json_encode(['success' => false, 'message' => 'Unable to save profile photo']);
        exit;
    }
    $profilePhotoPath = 'uploads/profile_photos/' . $filename;
}

if ($profilePhotoPath !== null) {
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, profile_photo = ? WHERE id = ?");
    $stmt->bind_param('sssi', $fullName, $email, $profilePhotoPath, $userId);
} else {
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
    $stmt->bind_param('ssi', $fullName, $email, $userId);
}

if ($stmt->execute()) {
    $_SESSION['full_name'] = $fullName;
    $_SESSION['email'] = $email;

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'data' => [
            'full_name' => $fullName,
            'email' => $email,
            'profile_photo_url' => user_profile_photo_url($profilePhotoPath),
            'role' => $currentRole
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
}

$stmt->close();
$conn->close();
?>
