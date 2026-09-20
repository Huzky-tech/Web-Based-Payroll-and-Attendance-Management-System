<?php
header('Content-Type: application/json');

include 'connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff']);

$siteName = trim((string) ($_GET['site_name'] ?? ''));
$location = trim((string) ($_GET['location'] ?? ''));
$excludeSiteId = (int) ($_GET['exclude_site_id'] ?? 0);

if ($siteName === '' && $location === '') {
    echo json_encode([
        'success' => true,
        'duplicate' => false
    ]);
    exit;
}

$checkingLocation = $location !== '';
$value = $checkingLocation ? $location : $siteName;
$column = $checkingLocation ? 'Location' : 'Site_Name';
$sql = "SELECT SiteID, Site_Name FROM projectsite
    WHERE LOWER(TRIM({$column})) = LOWER(TRIM(?))
      AND COALESCE(Status, '') <> 'Archived'";
$types = 's';
$params = [$value];

if ($excludeSiteId > 0) {
    $sql .= " AND SiteID <> ?";
    $types .= 'i';
    $params[] = $excludeSiteId;
}

$sql .= " LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => $checkingLocation ? 'Could not check site location.' : 'Could not check site name.'
    ]);
    exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo json_encode([
    'success' => true,
    'duplicate' => (bool) $row,
    'message' => $row ? ($checkingLocation ? 'A site with this exact location already exists.' : 'A site with this name already exists.') : ''
]);
