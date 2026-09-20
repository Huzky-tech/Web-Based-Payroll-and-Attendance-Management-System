<?php
/**
 * Update Site API
 * Edit site information such as name or address
 */

header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/site_schedule_helpers.php';
require_once __DIR__ . '/site_activation_helpers.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff']);

// Helper function for audit logging
function logAudit($conn, $userId, $action, $details) {
    $stmt = $conn->prepare("INSERT INTO audit_logs (UserID, Action, Details, Date) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $userId, $action, $details);
    $stmt->execute();
    $stmt->close();
}

function isValidSiteDate($date): bool {
    if (!is_string($date) || $date === '') {
        return false;
    }

    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    return $parsed && $parsed->format('Y-m-d') === $date;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $siteId = $data['site_id'] ?? 0;
    $siteName = $data['site_name'] ?? '';
    $location = $data['location'] ?? '';
    $projectType = $data['project_type'] ?? null;
    $coordinates = array_key_exists('coordinates', $data) ? trim((string) $data['coordinates']) : null;
    $startDate = $data['start_date'] ?? null;
    $endDate = $data['end_date'] ?? null;
    $requiredWorkers = $data['required_workers'] ?? null;
    $siteManager = $data['site_manager'] ?? null;
    $status = $data['status'] ?? null;
    $startTime = $data['start_time'] ?? null;
    $endTime = $data['end_time'] ?? null;
    $shiftStart = normalize_site_time_input($data['shift_start'] ?? $startTime);
    $lunchStart = normalize_site_time_input($data['lunch_start'] ?? null);
    $lunchEnd = normalize_site_time_input($data['lunch_end'] ?? null);
    $shiftEnd = normalize_site_time_input($data['shift_end'] ?? $endTime);
    $userId = (int) ($_SESSION['user_id'] ?? ($data['user_id'] ?? 0));

    // Geofence (Location Lock)
    $geofenceRadiusRaw = array_key_exists('geofenceRadiusM', $data) ? trim((string) $data['geofenceRadiusM']) : '';
    $geofenceRadiusM = $geofenceRadiusRaw !== '' ? (float) $geofenceRadiusRaw : null;
    $geofenceLatitude = array_key_exists('geofenceLatitude', $data) ? trim((string) $data['geofenceLatitude']) : null;
    $geofenceLongitude = array_key_exists('geofenceLongitude', $data) ? trim((string) $data['geofenceLongitude']) : null;

    if (
        $geofenceRadiusM === null ||
        $geofenceLatitude === null ||
        $geofenceLatitude === '' ||
        $geofenceLongitude === null ||
        $geofenceLongitude === ''
    ) {
        // Allow empty when DB schema doesn't support geofence yet.
        // Frontend sends it; this fallback keeps backward compatibility.
        $geofenceRadiusM = null;
        $geofenceLatitude = null;
        $geofenceLongitude = null;
    } else {
        if (!preg_match('/^\d+$/', $geofenceRadiusRaw)) {
            echo json_encode(['success' => false, 'message' => 'Location Lock Radius must contain whole numbers only.']);
            exit;
        }
        $geofenceLatParsed = (float) $geofenceLatitude;
        $geofenceLngParsed = (float) $geofenceLongitude;

        if (
            !is_numeric($geofenceLatitude) ||
            !is_numeric($geofenceLongitude) ||
            $geofenceRadiusM <= 0 ||
            $geofenceRadiusM > 20000 ||
            $geofenceLatParsed < -90 || $geofenceLatParsed > 90 ||
            $geofenceLngParsed < -180 || $geofenceLngParsed > 180
        ) {
            echo json_encode(['success' => false, 'message' => 'Geofence radius and center are invalid (radius in meters, > 0).']);
            exit;
        }
        // overwrite with parsed floats
        $geofenceLatitude = $geofenceLatParsed;
        $geofenceLongitude = $geofenceLngParsed;
    }
    
    
    // Validation
    if (empty($siteId) || empty($siteName)) {
        echo json_encode(['success' => false, 'message' => 'Site ID and name are required']);
        exit;
    }

    $location = trim((string) $location);
    if ($location === '' || mb_strlen($location) > 255) {
        echo json_encode(['success' => false, 'message' => 'Site location is required and cannot exceed 255 characters.']);
        exit;
    }
    if (!preg_match('/^[\p{L}\p{N}\s,.-]+$/u', $location)) {
        echo json_encode(['success' => false, 'message' => 'Site location cannot contain special characters.']);
        exit;
    }


    if ($coordinates !== null && $coordinates !== '') {
        if (!preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/', $coordinates, $coordinateMatches)) {
            echo json_encode(['success' => false, 'message' => 'Coordinates must contain numbers in latitude, longitude format.']);
            exit;
        }
        $coordinateLat = (float) $coordinateMatches[1];
        $coordinateLng = (float) $coordinateMatches[2];
        if ($coordinateLat < -90 || $coordinateLat > 90 || $coordinateLng < -180 || $coordinateLng > 180) {
            echo json_encode(['success' => false, 'message' => 'Coordinates are outside the valid latitude or longitude range.']);
            exit;
        }
    }

    $rawRequiredWorkers = trim((string) $requiredWorkers);
    if (!preg_match('/^\d+$/', $rawRequiredWorkers) || (int) $requiredWorkers < 1 || (int) $requiredWorkers > 10000) {
        echo json_encode(['success' => false, 'message' => 'Target capacity must be a whole number between 1 and 10,000 workers.']);
        exit;
    }
    $requiredWorkers = (int) $requiredWorkers;

    $siteName = trim((string) $siteName);
    $siteManager = $siteManager === null ? null : trim((string) $siteManager);
    if (!preg_match('/^[\p{L}]+(?: [\p{L}]+)*$/u', $siteName)) {
        echo json_encode(['success' => false, 'message' => 'Site name may contain letters and single spaces only.']);
        exit;
    }
    if ($siteManager !== null && $siteManager !== '' && !preg_match('/^[\p{L}]+(?: [\p{L}]+)*$/u', $siteManager)) {
        echo json_encode(['success' => false, 'message' => 'Manager name may contain letters and single spaces only.']);
        exit;
    }

    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized audit user']);
        exit;
    }

    if ($currentRole === 'Payroll Staff') {
        echo json_encode(['success' => false, 'message' => 'Payroll staff can only view assigned site details']);
        exit;
    }

    if (!in_array($currentRole, ['Admin', 'Assistant Admin'], true)) {
        echo json_encode(['success' => false, 'message' => 'You are not allowed to update sites']);
        exit;
    }

    if (strcasecmp((string) $status, 'Active') === 0) {
        $activation = site_activation_requirements($conn, (int) $siteId);
        if (empty($activation['eligible'])) {
            echo json_encode(['success' => false, 'message' => site_activation_requirement_message($activation)]);
            exit;
        }
    }

    $today = date('Y-m-d');

    if ($startDate !== null && $startDate !== '') {
        if (!isValidSiteDate($startDate)) {
            echo json_encode(['success' => false, 'message' => 'Start date must be a valid date.']);
            exit;
        }

        if ($startDate < $today) {
            echo json_encode(['success' => false, 'message' => 'Start date cannot be in the past.']);
            exit;
        }
    }

    if ($endDate !== null && $endDate !== '') {
        if (!isValidSiteDate($endDate)) {
            echo json_encode(['success' => false, 'message' => 'End date must be a valid date.']);
            exit;
        }

        if ($endDate < $today) {
            echo json_encode(['success' => false, 'message' => 'End date cannot be in the past.']);
            exit;
        }
    }

    if ($startDate !== null && $startDate !== '' && $endDate !== null && $endDate !== '' && $endDate < $startDate) {
        echo json_encode(['success' => false, 'message' => 'End date cannot be earlier than the start date.']);
        exit;
    }
    
    // Check if site exists
    $checkSql = "SELECT Site_Name FROM projectsite WHERE SiteID = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $siteId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Site not found']);
        $checkStmt->close();
        exit;
    }
    
    $oldSiteName = $checkResult->fetch_assoc()['Site_Name'];
    $checkStmt->close();

    if ($siteName !== '') {
        $duplicateSiteStmt = $conn->prepare("
            SELECT SiteID
            FROM projectsite
            WHERE SiteID <> ?
              AND LOWER(TRIM(Site_Name)) = LOWER(TRIM(?))
              AND COALESCE(Status, '') <> 'Archived'
            LIMIT 1
        ");
        if ($duplicateSiteStmt) {
            $duplicateSiteStmt->bind_param('is', $siteId, $siteName);
            $duplicateSiteStmt->execute();
            $duplicateSite = $duplicateSiteStmt->get_result()->fetch_assoc();
            $duplicateSiteStmt->close();

            if ($duplicateSite) {
                echo json_encode(['success' => false, 'message' => 'A site with this name already exists.']);
                exit;
            }
        }
    }


    $duplicateLocationStmt = $conn->prepare("SELECT SiteID FROM projectsite WHERE SiteID <> ? AND LOWER(TRIM(Location)) = LOWER(TRIM(?)) AND COALESCE(Status, '') <> 'Archived' LIMIT 1");
    if ($duplicateLocationStmt) {
        $duplicateLocationStmt->bind_param('is', $siteId, $location);
        $duplicateLocationStmt->execute();
        $duplicateLocation = $duplicateLocationStmt->get_result()->fetch_assoc();
        $duplicateLocationStmt->close();
        if ($duplicateLocation) {
            echo json_encode(['success' => false, 'message' => 'A site with this exact location already exists.']);
            exit;
        }
    }
    
    // Build update query dynamically
    $updates = [];
    $params = [];
    $types = "";
    
    if (!empty($siteName)) {
        $updates[] = "Site_Name = ?";
        $params[] = $siteName;
        $types .= "s";
    }
    if (!empty($location)) {
        $updates[] = "Location = ?";
        $params[] = $location;
        $types .= "s";
    }
    if ($projectType !== null) {
        $updates[] = "Project_Type = ?";
        $params[] = $projectType;
        $types .= "s";
    }
    if ($coordinates !== null) {
        $updates[] = "Coordinates = ?";
        $params[] = $coordinates;
        $types .= "s";
    }
    if ($startDate !== null) {
        $updates[] = "Start_Date = ?";
        $params[] = $startDate;
        $types .= "s";
    }
    if ($endDate !== null) {
        $updates[] = "End_Date = ?";
        $params[] = $endDate;
        $types .= "s";
    }
    if ($requiredWorkers !== null) {
        $updates[] = "Required_Workers = ?";
        $params[] = $requiredWorkers;
        $types .= "i";
    }
    if ($siteManager !== null) {
        $updates[] = "Site_Manager = ?";
        $params[] = $siteManager;
        $types .= "s";
    }
    if ($status !== null) {
        $updates[] = "Status = ?";
        $params[] = $status;
        $types .= "s";
    }

    // Persist geofence fields when available.
    // We only update if all three values are present (radius + center).
    if ($geofenceRadiusM !== null && $geofenceLatitude !== null && $geofenceLongitude !== null) {
        if (!ensure_geofence_columns($conn)) {
            echo json_encode(['success' => false, 'message' => 'Failed to prepare geofence storage. Please contact an administrator.']);
            exit;
        }

        $updates[] = "Geofence_Radius_M = ?";
        $params[] = $geofenceRadiusM;
        $types .= "d";

        $updates[] = "Geofence_Lat = ?";
        $params[] = $geofenceLatitude;
        $types .= "d";

        $updates[] = "Geofence_Lng = ?";
        $params[] = $geofenceLongitude;
        $types .= "d";
    }

    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        exit;
    }
    
    $params[] = $siteId;
    $types .= "i";
    
    $sql = "UPDATE projectsite SET " . implode(", ", $updates) . " WHERE SiteID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        $scheduleTouched = $shiftStart !== null || $lunchStart !== null || $lunchEnd !== null || $shiftEnd !== null
            || $startTime !== null || $endTime !== null;

        if ($scheduleTouched) {
            $existingSchedule = get_site_schedule_row($conn, (int) $siteId) ?? [
                'ShiftStart' => '07:00:00',
                'LunchStart' => '12:00:00',
                'LunchEnd' => '13:00:00',
                'ShiftEnd' => '17:00:00',
            ];

            $resolvedShiftStart = $shiftStart ?? normalize_site_time_input($startTime) ?? $existingSchedule['ShiftStart'];
            $resolvedLunchStart = $lunchStart ?? $existingSchedule['LunchStart'];
            $resolvedLunchEnd = $lunchEnd ?? $existingSchedule['LunchEnd'];
            $resolvedShiftEnd = $shiftEnd ?? normalize_site_time_input($endTime) ?? $existingSchedule['ShiftEnd'];

            $scheduleError = validate_site_schedule_times(
                $resolvedShiftStart,
                $resolvedLunchStart,
                $resolvedLunchEnd,
                $resolvedShiftEnd
            );
            if ($scheduleError !== null) {
                echo json_encode(['success' => false, 'message' => $scheduleError]);
                $stmt->close();
                exit;
            }

            if (site_schedule_columns_exist($conn)) {
                $scheduleUpdateStmt = $conn->prepare("
                    UPDATE projectsite
                    SET ShiftStart = ?, LunchStart = ?, LunchEnd = ?, ShiftEnd = ?
                    WHERE SiteID = ?
                ");
                if ($scheduleUpdateStmt) {
                    $scheduleUpdateStmt->bind_param(
                        'ssssi',
                        $resolvedShiftStart,
                        $resolvedLunchStart,
                        $resolvedLunchEnd,
                        $resolvedShiftEnd,
                        $siteId
                    );
                    if (!$scheduleUpdateStmt->execute()) {
                        echo json_encode(['success' => false, 'message' => 'Site updated but schedule could not be saved']);
                        $scheduleUpdateStmt->close();
                        $stmt->close();
                        exit;
                    }
                    $scheduleUpdateStmt->close();
                }
            }

            sync_site_schedule_legacy_table($conn, (int) $siteId, $resolvedShiftStart, $resolvedShiftEnd);
        }

        // Log the action
        logAudit($conn, $userId, 'Site Updated', "{$currentRole} updated site: $oldSiteName (ID: $siteId)");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Site updated successfully',
            'site_id' => $siteId
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update site']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
