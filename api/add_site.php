<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_auth($conn, ['Admin', 'Assistant Admin']);
require_once __DIR__ . '/site_schedule_helpers.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function logAudit($conn, $userId, $action, $details) {
    $sql = "INSERT INTO Audit_logs (UserID, Action, Details, Date) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $userId, $action, $details);
    $stmt->execute();
    $stmt->close();
}

function is_valid_site_date($date): bool {
    if (!is_string($date) || $date === '') {
        return false;
    }

    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    return $parsed && $parsed->format('Y-m-d') === $date;
}

$data = json_decode(file_get_contents("php://input"), true);

$siteName = trim($data['siteName'] ?? '');
$location = trim($data['location'] ?? '');
$coordinates = trim($data['coordinates'] ?? '');
// Geofence (Location Lock)
$geofenceRadiusRaw = trim((string) ($data['geofenceRadiusM'] ?? ''));
$geofenceRadiusM = $geofenceRadiusRaw !== '' ? (float) $geofenceRadiusRaw : null;
$geofenceLatitude = array_key_exists('geofenceLatitude', $data) ? trim((string)$data['geofenceLatitude']) : null;
$geofenceLongitude = array_key_exists('geofenceLongitude', $data) ? trim((string)$data['geofenceLongitude']) : null;

$requiredWorkers = (int) ($data['requiredWorkers'] ?? 0);
$startDate = $data['startDate'] ?? '';

$shiftStart = normalize_site_time_input($data['shiftStart'] ?? ($data['startTime'] ?? '07:00'));
$lunchStart = normalize_site_time_input($data['lunchStart'] ?? '12:00');
$lunchEnd = normalize_site_time_input($data['lunchEnd'] ?? '13:00');
$shiftEnd = normalize_site_time_input($data['shiftEnd'] ?? ($data['endTime'] ?? '17:00'));
$siteManager = trim($data['siteManager'] ?? '');
// A site becomes active only after at least one worker is assigned.
$status = 'Inactive';
$locationID = 1;
$userId = (int) ($_SESSION['user_id'] ?? ($data['userId'] ?? 0));

if ($userId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized audit user']);
    exit;
}

if ($siteName === '' || $location === '' || $requiredWorkers <= 0 || $startDate === '') {
    echo json_encode(['status' => 'error', 'message' => 'Missing required site fields']);
    exit;
}

if (mb_strlen($location) > 255) {
    echo json_encode(['status' => 'error', 'message' => 'Site location cannot exceed 255 characters.']);
    exit;
}

if (!preg_match('/^[\p{L}]+(?: [\p{L}]+)*$/u', $siteName)) {
    echo json_encode(['status' => 'error', 'message' => 'Site name may contain letters and single spaces only.']);
    exit;
}

if ($siteManager !== '' && !preg_match('/^[\p{L}]+(?: [\p{L}]+)*$/u', $siteManager)) {
    echo json_encode(['status' => 'error', 'message' => 'Manager name may contain letters and single spaces only.']);
    exit;
}

if ($coordinates !== '') {
    if (!preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/', $coordinates, $coordinateMatches)) {
        echo json_encode(['status' => 'error', 'message' => 'Coordinates must contain numbers in latitude, longitude format.']);
        exit;
    }
    $coordinateLat = (float) $coordinateMatches[1];
    $coordinateLng = (float) $coordinateMatches[2];
    if ($coordinateLat < -90 || $coordinateLat > 90 || $coordinateLng < -180 || $coordinateLng > 180) {
        echo json_encode(['status' => 'error', 'message' => 'Coordinates are outside the valid latitude or longitude range.']);
        exit;
    }
}

if (!preg_match('/^\d+$/', $geofenceRadiusRaw)) {
    echo json_encode(['status' => 'error', 'message' => 'Location Lock Radius must contain whole numbers only.']);
    exit;
}

$rawRequiredWorkers = trim((string) ($data['requiredWorkers'] ?? ''));
if (!preg_match('/^\d+$/', $rawRequiredWorkers) || $requiredWorkers < 1 || $requiredWorkers > 10000) {
    echo json_encode(['status' => 'error', 'message' => 'Target capacity must be a whole number between 1 and 10,000 workers.']);
    exit;
}

// Geofence validation (must have selected center + valid radius)
// Flutter enforcement depends on these values.
$geofenceRadiusMNum = $geofenceRadiusM;
$geofenceLatParsed = $geofenceLatitude !== null && $geofenceLatitude !== '' ? (float)$geofenceLatitude : null;
$geofenceLngParsed = $geofenceLongitude !== null && $geofenceLongitude !== '' ? (float)$geofenceLongitude : null;

if (
    $geofenceRadiusMNum === null ||
    $geofenceLatParsed === null ||
    $geofenceLngParsed === null ||
    !is_numeric($geofenceLatitude) ||
    !is_numeric($geofenceLongitude) ||
    $geofenceRadiusMNum <= 0 ||
    $geofenceRadiusMNum > 20000 ||
    $geofenceLatParsed < -90 || $geofenceLatParsed > 90 ||
    $geofenceLngParsed < -180 || $geofenceLngParsed > 180
) {
    echo json_encode(['status' => 'error', 'message' => 'Geofence radius and center are required (radius in meters, > 0).']);
    exit;
}



if (!is_valid_site_date($startDate)) {
    echo json_encode(['status' => 'error', 'message' => 'Start date must be a valid date.']);
    exit;
}

if ($startDate < date('Y-m-d')) {
    echo json_encode(['status' => 'error', 'message' => 'Start date cannot be in the past.']);
    exit;
}

$scheduleError = validate_site_schedule_times($shiftStart, $lunchStart, $lunchEnd, $shiftEnd);
if ($scheduleError !== null) {
    echo json_encode(['status' => 'error', 'message' => $scheduleError]);
    exit;
}

if (!ensure_geofence_columns($conn)) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare geofence storage. Please contact an administrator.']);
    exit;
}

$duplicateSiteStmt = $conn->prepare("
    SELECT SiteID
    FROM projectsite
    WHERE LOWER(TRIM(Site_Name)) = LOWER(TRIM(?))
      AND COALESCE(Status, '') <> 'Archived'
    LIMIT 1
");
if ($duplicateSiteStmt) {
    $duplicateSiteStmt->bind_param('s', $siteName);
    $duplicateSiteStmt->execute();
    $duplicateSite = $duplicateSiteStmt->get_result()->fetch_assoc();
    $duplicateSiteStmt->close();

    if ($duplicateSite) {
        echo json_encode(['status' => 'error', 'message' => 'A site with this name already exists.']);
        exit;
    }
}

$duplicateLocationStmt = $conn->prepare("SELECT SiteID FROM projectsite WHERE LOWER(TRIM(Location)) = LOWER(TRIM(?)) AND COALESCE(Status, '') <> 'Archived' LIMIT 1");
if ($duplicateLocationStmt) {
    $duplicateLocationStmt->bind_param('s', $location);
    $duplicateLocationStmt->execute();
    $duplicateLocation = $duplicateLocationStmt->get_result()->fetch_assoc();
    $duplicateLocationStmt->close();
    if ($duplicateLocation) {
        echo json_encode(['status' => 'error', 'message' => 'A site with this exact location already exists.']);
        exit;
    }
}

$scheduleDay = 'Monday';
$conn->begin_transaction();

try {
    if (site_schedule_columns_exist($conn)) {
        // Persist geofence fields (DB must have these columns)
        $sql = "INSERT INTO ProjectSite (Site_Name, Location, Coordinates, Required_Workers, Start_Date, Site_Manager, Status, LocationID, ShiftStart, LunchStart, LunchEnd, ShiftEnd, Geofence_Radius_M, Geofence_Lat, Geofence_Lng)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        $stmt->bind_param(
            "sssisssissssddd",
            $siteName,
            $location,
            $coordinates,
            $requiredWorkers,
            $startDate,
            $siteManager,
            $status,
            $locationID,
            $shiftStart,
            $lunchStart,
            $lunchEnd,
            $shiftEnd,
            $geofenceRadiusMNum,
            $geofenceLatParsed,
            $geofenceLngParsed
        );

    } else {
        $sql = "INSERT INTO ProjectSite (Site_Name, Location, Coordinates, Required_Workers, Start_Date, Site_Manager, Status, LocationID, Geofence_Radius_M, Geofence_Lat, Geofence_Lng)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param(
            "sssisssiddd",
            $siteName,
            $location,
            $coordinates,
            $requiredWorkers,
            $startDate,
            $siteManager,
            $status,
            $locationID,
            $geofenceRadiusMNum,
            $geofenceLatParsed,
            $geofenceLngParsed
        );
    }


    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $siteId = (int) $conn->insert_id;
    $stmt->close();

    sync_site_schedule_legacy_table($conn, $siteId, $shiftStart, $shiftEnd);

    logAudit($conn, $userId, 'Site Created', "Created site: $siteName");
    $conn->commit();

    echo json_encode(['status' => 'success', 'siteId' => $siteId]);
} catch (Exception $exception) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $exception->getMessage()]);
}

$conn->close();

?>
