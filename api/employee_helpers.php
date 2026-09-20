<?php

function ensureDirectoryExists(string $path): void
{
    if (!is_dir($path)) {
        mkdir($path, 0775, true);
    }
}

function employeeUploadsBasePath(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
}

function employeePhotoUploadDir(): string
{
    return employeeUploadsBasePath() . DIRECTORY_SEPARATOR . 'employees';
}

function employeeQrUploadDir(): string
{
    return employeeUploadsBasePath() . DIRECTORY_SEPARATOR . 'qrcodes';
}

function employeePublicRelativePath(string $subdir, string $filename): string
{
    return 'uploads/' . trim($subdir, '/\\') . '/' . $filename;
}

function saveEmployeePhoto(array $file, string $employeeReference): ?string
{
    if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Photo upload failed.');
    }

    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    if (!isset($allowedMimeTypes[$mimeType])) {
        throw new RuntimeException('Only JPG and PNG images are allowed.');
    }

    ensureDirectoryExists(employeePhotoUploadDir());

    $extension = $allowedMimeTypes[$mimeType];
    $safeReference = preg_replace('/[^A-Za-z0-9_-]/', '_', $employeeReference);
    $filename = strtolower($safeReference . '_' . bin2hex(random_bytes(6)) . '.' . $extension);
    $destination = employeePhotoUploadDir() . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Failed to store employee photo.');
    }

    return employeePublicRelativePath('employees', $filename);
}

function buildEmployeeProfileUrl(string $baseUrl, int $workerId): string
{
    $baseUrl = rtrim($baseUrl, '/');
    return $baseUrl . '/employee_profile.php?id=' . $workerId;
}

function buildApplicationBaseUrlFromRequest(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    $basePath = preg_replace('#/api$#', '', $basePath);

    return $scheme . '://' . $host . $basePath;
}

function generateEmployeeQrCode(string $profileUrl, string $employeeReference): string
{
    ensureDirectoryExists(employeeQrUploadDir());

    $safeReference = preg_replace('/[^A-Za-z0-9_-]/', '_', $employeeReference);
    $qrLib = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'phpqrcode' . DIRECTORY_SEPARATOR . 'qrlib.php';
    if (!is_readable($qrLib)) {
        throw new RuntimeException('Failed to generate QR code image. QR library is missing.');
    }

    if (!class_exists('QRcode', false)) {
        require_once $qrLib;
    }

    $extension = extension_loaded('gd') ? 'png' : 'svg';
    $filename = strtolower($safeReference . '_qr_' . bin2hex(random_bytes(4)) . '.' . $extension);
    $destination = employeeQrUploadDir() . DIRECTORY_SEPARATOR . $filename;

    if ($extension === 'png') {
        QRcode::png($profileUrl, $destination, 'M', 8, 2);
    } else {
        QRcode::svg($profileUrl, $destination, 'M', 8, 2);
    }

    if (!is_file($destination) || filesize($destination) === 0) {
        throw new RuntimeException('Failed to save QR code image.');
    }

    return employeePublicRelativePath('qrcodes', $filename);
}

function ensureEmployeeQrCode(mysqli $conn, int $workerId, ?string $existingQrCodePath = null): ?string
{
    if (trim((string) $existingQrCodePath) !== '') {
        return $existingQrCodePath;
    }

    $employeeReference = 'employee_' . $workerId;
    $profileUrl = buildEmployeeProfileUrl(buildApplicationBaseUrlFromRequest(), $workerId);
    $qrCodePath = generateEmployeeQrCode($profileUrl, $employeeReference);

    $stmt = $conn->prepare('UPDATE worker SET qr_code_path = ? WHERE WorkerID = ?');
    if (!$stmt) {
        throw new RuntimeException('Failed to prepare employee QR update.');
    }

    $stmt->bind_param('si', $qrCodePath, $workerId);
    if (!$stmt->execute()) {
        throw new RuntimeException('Failed to save employee QR code path: ' . $stmt->error);
    }
    $stmt->close();

    return $qrCodePath;
}

function getEmployeeLatestApprovalStatus(mysqli $conn, int $workerId): ?string
{
    $stmt = $conn->prepare("
        SELECT Approval_Status
        FROM approvals
        WHERE WorkerID = ?
        ORDER BY Date DESC, ApprovalID DESC
        LIMIT 1
    ");

    if (!$stmt) {
        throw new RuntimeException('Failed to prepare employee approval lookup.');
    }

    $stmt->bind_param('i', $workerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row['Approval_Status'] ?? null;
}

function isEmployeeApprovedForAssignment(mysqli $conn, int $workerId): bool
{
    return getEmployeeLatestApprovalStatus($conn, $workerId) === 'Approved';
}
