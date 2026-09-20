<?php
/**
 * Mobile attendance photo evidence upload (multipart/form-data).
 * Supports delayed/offline sync uploads from the Flutter Timekeeper app.
 */

require_once 'connection/db_config.php';
require_once __DIR__ . '/mobile_auth_helpers.php';
require_once __DIR__ . '/mobile_attendance_helpers.php';
require_once __DIR__ . '/record_audit_log.php';
require_once __DIR__ . '/attendance_photo_helpers.php';
require_once __DIR__ . '/attendance_schema_helpers.php';

mobile_json_headers();
mobile_handle_options();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mobile_json_error('Method not allowed', 405);
}

if (!ensure_attendance_photo_columns($conn)) {
    mobile_json_error('Failed to prepare attendance photo storage.', 500);
}

ensure_attendance_punch_photo_columns($conn);

$payload = array_merge(
    is_array($_POST) ? $_POST : [],
    is_array($_GET) ? $_GET : []
);

$timekeeperId = mobile_resolve_timekeeper_id($payload);
mobile_require_timekeeper_assignment($conn, $timekeeperId);

$attendanceId = (int) ($payload['attendance_id'] ?? $payload['AttendanceID'] ?? 0);
$workerId = (int) ($payload['worker_id'] ?? $payload['WorkerID'] ?? 0);
$siteId = (int) ($payload['site_id'] ?? $payload['SiteID'] ?? 0);
$attendanceTypeRaw = trim((string) ($payload['attendance_type'] ?? $payload['AttendanceType'] ?? 'Time In'));
$attendanceType = attendance_photo_normalize_type($attendanceTypeRaw);

if ($attendanceType === 'Lunch Out') {
    mobile_json_error('Lunch out is recorded automatically at the scheduled lunch time.', 400);
}

if (!attendance_photo_requires_evidence($attendanceType)) {
    mobile_json_error('Photo upload is only supported for AM Time In, PM Time In, and Time Out.');
}

$dateRaw = trim((string) ($payload['date'] ?? $payload['Date'] ?? ''));
$date = $dateRaw !== '' ? mobile_parse_attendance_date($dateRaw) : date('Y-m-d');
if ($date === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
    mobile_json_error('Invalid attendance date.');
}

$eventTimeRaw = trim((string) ($payload['event_time'] ?? $payload['time_in'] ?? ''));
$eventTime = $eventTimeRaw !== '' ? mobile_parse_attendance_time($eventTimeRaw) : date('H:i:s');

$latitudeRaw = $payload['latitude'] ?? $payload['Latitude'] ?? $payload['lat'] ?? null;
$longitudeRaw = $payload['longitude'] ?? $payload['Longitude'] ?? $payload['lng'] ?? null;
$latitude = is_numeric($latitudeRaw) ? (float) $latitudeRaw : null;
$longitude = is_numeric($longitudeRaw) ? (float) $longitudeRaw : null;

$fileField = null;
foreach (['photo', 'Photo', 'attendance_photo', 'file', 'image'] as $candidate) {
    if (isset($_FILES[$candidate]) && is_array($_FILES[$candidate])) {
        $fileField = $candidate;
        break;
    }
}

if ($fileField === null) {
    mobile_json_error('Photo file is required.');
}

try {
    $attendanceRow = null;

    if ($attendanceId > 0) {
        $attendanceRow = mobile_validate_attendance_record_access($conn, $timekeeperId, $attendanceId);
        if ($attendanceRow === null) {
            mobile_json_error('Attendance record not found.');
        }
    } else {
        if ($workerId <= 0) {
            mobile_json_error('Worker ID or Attendance ID is required.');
        }

        $assignment = get_active_timekeeper_assignment($conn, $timekeeperId);
        $assignedSiteId = (int) ($assignment['SiteID'] ?? 0);
        if ($siteId <= 0) {
            $siteId = $assignedSiteId;
        }

        $accessError = validate_timekeeper_worker_access($conn, $timekeeperId, $workerId, $siteId);
        if ($accessError !== null) {
            mobile_json_error($accessError, 403);
        }

        $lookup = $conn->prepare('
            SELECT AttendanceID, WorkerID, SiteID, Date
            FROM attendance
            WHERE WorkerID = ? AND Date = ?
            LIMIT 1
        ');
        if (!$lookup) {
            mobile_json_error('Database error', 500);
        }

        $lookup->bind_param('is', $workerId, $date);
        $lookup->execute();
        $attendanceRow = $lookup->get_result()->fetch_assoc();
        $lookup->close();

        if (!$attendanceRow) {
            mobile_json_error(
                'Attendance record not found for this worker and date. Sync attendance first, then upload the photo.'
            );
        }
    }

    $attendanceId = (int) ($attendanceRow['AttendanceID'] ?? 0);
    $workerId = (int) ($attendanceRow['WorkerID'] ?? $workerId);
    $siteId = (int) ($attendanceRow['SiteID'] ?? $siteId);
    $date = (string) ($attendanceRow['Date'] ?? $date);

    $photoPath = attendance_photo_save_uploaded_file($_FILES[$fileField], $workerId, $attendanceType);
    if ($photoPath === null || $photoPath === '') {
        mobile_json_error('Failed to save attendance photo.');
    }

    $distanceFromSite = attendance_calculate_distance_from_site($conn, $siteId, $latitude, $longitude);

    attendance_photo_log_punch(
        $conn,
        $workerId,
        $siteId,
        $timekeeperId,
        $attendanceType,
        $date,
        $eventTime,
        $latitude,
        $longitude,
        $distanceFromSite,
        $photoPath,
        $attendanceId
    );

    if (function_exists('save_attendance_apply_photo_evidence')) {
        save_attendance_apply_photo_evidence(
            $conn,
            $attendanceId,
            $photoPath,
            $latitude,
            $longitude,
            $distanceFromSite
        );
    } else {
        if ($latitude === null && $longitude === null && $distanceFromSite === null) {
            $updateSql = '
                UPDATE attendance
                SET PhotoPath = ?
                WHERE AttendanceID = ?
                LIMIT 1
            ';
            $updateStmt = $conn->prepare($updateSql);
            if (!$updateStmt) {
                @unlink(__DIR__ . '/../' . ltrim($photoPath, '/'));
                mobile_json_error('Database error', 500);
            }
            $updateStmt->bind_param('si', $photoPath, $attendanceId);
        } else {
            $updateSql = '
                UPDATE attendance
                SET PhotoPath = ?,
                    Latitude = ?,
                    Longitude = ?,
                    DistanceFromSite = ?
                WHERE AttendanceID = ?
                LIMIT 1
            ';
            $updateStmt = $conn->prepare($updateSql);
            if (!$updateStmt) {
                @unlink(__DIR__ . '/../' . ltrim($photoPath, '/'));
                mobile_json_error('Database error', 500);
            }

            $latitudeValue = $latitude;
            $longitudeValue = $longitude;
            $distanceValue = $distanceFromSite ?? 0.0;
            $updateStmt->bind_param(
                'sdddi',
                $photoPath,
                $latitudeValue,
                $longitudeValue,
                $distanceValue,
                $attendanceId
            );
        }

        $updateStmt->execute();
        $updateStmt->close();
    }

    $auditDetails = sprintf(
        'Attendance photo uploaded for worker %d on %s (AttendanceID %d)',
        $workerId,
        $date,
        $attendanceId
    );
    if ($attendanceType !== '') {
        $auditDetails .= ' [' . $attendanceType . ']';
    }
    record_audit_log($timekeeperId, 'Mobile Attendance Photo Upload', $auditDetails);

    mobile_json_success([
        'success' => true,
        'message' => 'Attendance photo uploaded successfully.',
        'attendance_id' => $attendanceId,
        'worker_id' => $workerId,
        'site_id' => $siteId,
        'date' => $date,
        'photo_path' => $photoPath,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'distance_from_site' => $distanceFromSite,
        'attendance_type' => $attendanceType,
    ]);
} catch (InvalidArgumentException $e) {
    mobile_json_error($e->getMessage());
} catch (RuntimeException $e) {
    mobile_json_error($e->getMessage(), 500);
}

$conn->close();
