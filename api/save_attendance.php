<?php
/**
 * Mobile attendance sync endpoint.
 * Accepts batched records from Flutter (JSON or multipart with photo).
 */

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('html_errors', '0');

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

function save_attendance_json_response(array $payload, int $httpCode = 200): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code($httpCode);
    }
    echo json_encode($payload);
}

set_exception_handler(static function (Throwable $e): void {
    error_log('[AttendanceSync] ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
    save_attendance_json_response(['status' => 'error', 'message' => 'Server error during attendance sync.'], 500);
    exit;
});

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if ($error === null) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array((int) $error['type'], $fatalTypes, true)) {
        return;
    }
    error_log('[AttendanceSync] fatal: ' . $error['message']);

    save_attendance_json_response(['status' => 'error', 'message' => 'Server error during attendance sync.'], 500);
});

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/connection/db_config.php';
require_once __DIR__ . '/mobile_auth_helpers.php';
require_once __DIR__ . '/attendance_photo_helpers.php';
require_once __DIR__ . '/attendance_schema_helpers.php';

if (!function_exists('save_attendance_apply_photo_evidence')) {
    function save_attendance_apply_photo_evidence(
        mysqli $conn,
        int $attendanceId,
        ?string $photoPath,
        ?float $latitude,
        ?float $longitude,
        ?float $distanceFromSite
    ): void {
        if ($photoPath === null || $photoPath === '' || !attendance_schema_has_photo_columns($conn)) {
            return;
        }

        $checkStmt = $conn->prepare('SELECT PhotoPath FROM attendance WHERE AttendanceID = ? LIMIT 1');
        if (!$checkStmt) {
            mobile_json_error('Database error', 500);
        }
        $checkStmt->bind_param('i', $attendanceId);
        $checkStmt->execute();
        $existingRow = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        $existingPhoto = trim((string) ($existingRow['PhotoPath'] ?? ''));
        if ($existingPhoto !== '') {
            return;
        }

        if ($latitude === null && $longitude === null && $distanceFromSite === null) {
            $stmt = $conn->prepare('UPDATE attendance SET PhotoPath = ? WHERE AttendanceID = ?');
            if (!$stmt) {
                mobile_json_error('Database error', 500);
            }
            $stmt->bind_param('si', $photoPath, $attendanceId);
        } else {
            $stmt = $conn->prepare('
                UPDATE attendance
                SET PhotoPath = ?, Latitude = ?, Longitude = ?, DistanceFromSite = ?
                WHERE AttendanceID = ?
            ');
            if (!$stmt) {
                mobile_json_error('Database error', 500);
            }
            $lat = $latitude ?? 0.0;
            $lng = $longitude ?? 0.0;
            $distance = $distanceFromSite ?? 0.0;
            $stmt->bind_param('sdddi', $photoPath, $lat, $lng, $distance, $attendanceId);
        }

        if (!$stmt->execute()) {
            $stmt->close();
            mobile_json_error('Failed to save attendance photo evidence.', 500);
        }
        $stmt->close();
    }
}

if (!function_exists('save_attendance_site_lunch_start')) {
    function save_attendance_site_lunch_start(mysqli $conn, int $siteId): string
    {
        $stmt = $conn->prepare('SELECT LunchStart FROM projectsite WHERE SiteID = ? LIMIT 1');
        if (!$stmt) {
            return '12:00:00';
        }
        $stmt->bind_param('i', $siteId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $lunchStart = trim((string) ($row['LunchStart'] ?? ''));
        if ($lunchStart === '') {
            return '12:00:00';
        }

        return mobile_parse_attendance_time($lunchStart);
    }
}

if (!function_exists('save_attendance_apply_auto_lunch_out')) {
    function save_attendance_apply_auto_lunch_out(mysqli $conn, int $attendanceId, int $siteId): void
    {
        if (!attendance_lunch_columns_exist($conn)) {
            return;
        }

        $lunchOutTime = save_attendance_site_lunch_start($conn, $siteId);
        $stmt = $conn->prepare("
            UPDATE attendance
            SET Lunch_Out = ?
            WHERE AttendanceID = ?
              AND (Lunch_Out IS NULL OR Lunch_Out = '' OR Lunch_Out = '00:00:00')
        ");
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('si', $lunchOutTime, $attendanceId);
        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('save_attendance_require_execute')) {
    function save_attendance_require_execute(mysqli_stmt $stmt, string $action): void
    {
        if ($stmt->execute()) {
            return;
        }

        $error = $stmt->error;
        $stmt->close();
        mobile_json_error($action . ' failed: ' . ($error !== '' ? $error : 'database error'), 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mobile_json_error('Invalid request method');
}

$contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
$isMultipart = strpos($contentType, 'multipart/form-data') !== false;

if ($isMultipart) {
    $payload = $_POST;
    $recordsRaw = (string) ($payload['records'] ?? '[]');
    $records = json_decode($recordsRaw, true);
    if (!is_array($records)) {
        mobile_json_error('Invalid attendance records format');
    }
} else {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        mobile_json_error('Invalid request body');
    }
    $records = $payload['records'] ?? $payload;
}

$timekeeperId = mobile_parse_timekeeper_id([$payload]);
mobile_validate_timekeeper($conn, $timekeeperId);
$assignedSite = mobile_require_assigned_site($conn, $timekeeperId);
$assignedSiteId = (int) $assignedSite['site_id'];
attendance_schema_ensure_table($conn);

if (!is_array($records) || $records === []) {
    mobile_json_error('No attendance records provided');
}

if (!isset($records[0]) || !is_array($records[0])) {
    mobile_json_error('Invalid attendance records format');
}

$uploadedPhotoPath = null;
if ($isMultipart) {
    $photoFile = $_FILES['photo'] ?? null;
    if (is_array($photoFile) && ($photoFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        // Photo file is optional at request level; validated per punch type below.
    }
}

$saved = 0;
error_log('[AttendanceSync] request local_record_id=' . (string) ($payload['local_record_id'] ?? '') . ' records=' . count($records));
$savedRecords = [];
$responsePhotoUrl = null;

foreach ($records as $index => $record) {
    if (!is_array($record)) {
        mobile_json_error('Invalid attendance record');
    }

    $workerIdRaw = $record['worker_id'] ?? 0;
    if (is_numeric($workerIdRaw)) {
        $workerId = (int) $workerIdRaw;
    } else {
        $digitsOnly = preg_replace('/\D+/', '', (string) $workerIdRaw);
        $workerId = $digitsOnly !== '' ? (int) $digitsOnly : 0;
    }
    $siteId = (int) ($record['site_id'] ?? 0);

    if ($workerId <= 0) {
        mobile_json_error('Invalid worker ID in attendance record');
    }

    if ($siteId !== $assignedSiteId) {
        mobile_json_error('Access denied for this site.', 403);
    }

    if (!worker_assigned_to_site($conn, $workerId, $assignedSiteId)) {
        mobile_json_error('Worker is not assigned to your site.', 403);
    }

    $dateRaw = (string) ($record['date'] ?? '');
    $date = mobile_parse_attendance_date($dateRaw);
    if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        mobile_json_error('Invalid attendance date');
    }

    $attendanceType = attendance_photo_normalize_type((string) ($record['attendance_type'] ?? 'Time In'));
    if (!in_array($attendanceType, ['Time In', 'Lunch Out', 'Lunch In', 'Time Out'], true)) {
        mobile_json_error('Unsupported attendance sync type.', 400);
    }
    if ($attendanceType === 'Lunch Out') {
        mobile_json_error('Lunch out is recorded automatically at the scheduled lunch time.', 400);
    }

    $eventTime = attendance_photo_parse_event_time($record);
    $isAutomatic = filter_var($record['is_automatic'] ?? false, FILTER_VALIDATE_BOOLEAN);
    if ($isAutomatic && ($attendanceType !== 'Lunch In' || $eventTime !== '12:00:00')) {
        mobile_json_error('Automatic attendance must be Lunch In at 12:00 PM.', 400);
    }
    $requiresPhoto = attendance_photo_requires_evidence($attendanceType) && !$isAutomatic;

    $timeInRaw = (string) ($record['time_in'] ?? '');
    $timeOutRaw = $record['time_out'] ?? null;
    $timeIn = mobile_parse_attendance_time($timeInRaw !== '' ? $timeInRaw : $eventTime);
    $timeOut = null;
    if ($timeOutRaw !== null && $timeOutRaw !== '') {
        $timeOut = mobile_parse_attendance_time((string) $timeOutRaw);
    } elseif ($attendanceType === 'Time Out') {
        $timeOut = $eventTime;
    }

    $latitude = isset($record['latitude']) ? (float) $record['latitude'] : null;
    $longitude = isset($record['longitude']) ? (float) $record['longitude'] : null;
    $distanceFromSite = isset($record['distance_from_site']) ? (float) $record['distance_from_site'] : null;

    $uploadedPhotoPath = null;
    if ($isMultipart) {
        $photoFile = $_FILES['photo'] ?? null;
        if (is_array($photoFile) && ($photoFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $uploadedPhotoPath = attendance_photo_save_uploaded_file($photoFile, $workerId, $attendanceType);
            if ($uploadedPhotoPath === null) {
                mobile_json_error('Failed to save attendance photo.');
            }
            $responsePhotoUrl = $uploadedPhotoPath;
        }
    } else {
        $uploadedPhotoPath = trim((string) ($record['photo_url'] ?? $record['image_path'] ?? ''));
        if ($uploadedPhotoPath !== '') {
            $responsePhotoUrl = $uploadedPhotoPath;
        }
    }

    if ($requiresPhoto && ($uploadedPhotoPath === null || $uploadedPhotoPath === '')) {
        mobile_json_error('Attendance photo is required for AM Time In, PM Time In, and Time Out.');
    }

    $clientStatus = trim((string) ($record['status'] ?? 'Present'));
    $status = attendance_schema_normalize_status(
        mobile_resolve_attendance_status(
            $conn,
            $assignedSiteId,
            $date,
            $timeIn,
            $clientStatus
        )
    );

    // payroll_db uses UNIQUE(WorkerID, Date) — not SiteID — so always look up by worker + date.
    $hasLunchColumns = attendance_lunch_columns_exist($conn);
    if ($attendanceType === 'Lunch In' && !$hasLunchColumns) {
        mobile_json_error('Attendance lunch columns are missing. Apply the lunch migration before retrying.', 500);
    }
    $selectFields = 'AttendanceID, SiteID, Time_In, Time_Out, AttendanceStatus, IsLate';
    if ($hasLunchColumns) {
        $selectFields .= ', Lunch_Out, Lunch_In';
    }

    // Serialize retries from multiple devices, including legacy schemas without
    // a unique worker/date index. Connection closure releases on error/exit.
    $lockName = 'attendance:' . $workerId . ':' . $date;
    $lockStmt = $conn->prepare('SELECT GET_LOCK(?, 10) AS acquired');
    if (!$lockStmt) mobile_json_error('Could not prepare attendance lock.', 500);
    $lockStmt->bind_param('s', $lockName);
    save_attendance_require_execute($lockStmt, 'Attendance lock');
    $lockRow = $lockStmt->get_result()->fetch_assoc();
    $lockStmt->close();
    if ((int) ($lockRow['acquired'] ?? 0) !== 1) {
        mobile_json_error('Attendance is busy. Retry this record.', 503);
    }

    $checkStmt = $conn->prepare("
        SELECT {$selectFields}
        FROM attendance
        WHERE WorkerID = ? AND Date = ?
        LIMIT 1
    ");
    if (!$checkStmt) {
        mobile_json_error('Database error', 500);
    }

    $checkStmt->bind_param('is', $workerId, $date);
    $checkStmt->execute();
    $existing = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    $attendanceId = 0;
    $wasUpdated = false;
    $recordAccepted = true;

    if ($existing) {
        if ((int) $existing['SiteID'] !== $assignedSiteId) {
            mobile_json_error('Attendance already exists at another site for this date.', 409);
        }
        $attendanceId = (int) $existing['AttendanceID'];
        $wasUpdated = true;
        if ($attendanceType !== 'Time In') {
            $status = attendance_schema_normalize_status((string) $existing['AttendanceStatus']);
        }

        if ($attendanceType === 'Time Out' && $timeOut !== null) {
            $existingTimeIn = trim((string) ($existing['Time_In'] ?? ''));
            $effectiveTimeIn = $existingTimeIn !== '' && $existingTimeIn !== '00:00:00'
                ? $existingTimeIn
                : $timeIn;
            $hoursWorked = 0.0;
            $inSeconds = strtotime($date . ' ' . $effectiveTimeIn);
            $outSeconds = strtotime($date . ' ' . $timeOut);
            if ($outSeconds > $inSeconds) {
                $hoursWorked = round(($outSeconds - $inSeconds) / 3600, 2);
            }

            $updateStmt = $conn->prepare("
                UPDATE attendance
                SET SiteID = ?, Time_In = ?, Time_Out = ?, Hours_Worked = ?, AttendanceStatus = ?
                WHERE AttendanceID = ?
            ");
            if (!$updateStmt) {
                mobile_json_error('Database error', 500);
            }
            $updateStmt->bind_param('issdsi', $assignedSiteId, $effectiveTimeIn, $timeOut, $hoursWorked, $status, $attendanceId);
            save_attendance_require_execute($updateStmt, 'Attendance time-out update');
            $updateStmt->close();
        } elseif ($attendanceType === 'Time In') {
            $updateStmt = $conn->prepare("
                UPDATE attendance
                SET SiteID = ?, Time_In = ?, AttendanceStatus = ?
                WHERE AttendanceID = ?
            ");
            if (!$updateStmt) {
                mobile_json_error('Database error', 500);
            }
            $updateStmt->bind_param('issi', $assignedSiteId, $timeIn, $status, $attendanceId);
            save_attendance_require_execute($updateStmt, 'Attendance time-in update');
            $updateStmt->close();
        } elseif ($attendanceType === 'Lunch Out' && $hasLunchColumns) {
            $existingTimeIn = trim((string) ($existing['Time_In'] ?? ''));
            if ($existingTimeIn === '' || $existingTimeIn === '00:00:00') {
                mobile_json_error('Worker must be clocked in before lunch out.', 400);
            }

            $lunchOutTime = $eventTime;
            $updateStmt = $conn->prepare('
                UPDATE attendance
                SET SiteID = ?, Lunch_Out = ?, AttendanceStatus = ?
                WHERE AttendanceID = ?
            ');
            if (!$updateStmt) {
                mobile_json_error('Database error', 500);
            }
            $updateStmt->bind_param('issi', $assignedSiteId, $lunchOutTime, $status, $attendanceId);
            save_attendance_require_execute($updateStmt, 'Attendance lunch-out update');
            $updateStmt->close();
        } elseif ($attendanceType === 'Lunch In' && $hasLunchColumns) {
            $existingTimeIn = trim((string) ($existing['Time_In'] ?? ''));
            if ($existingTimeIn === '' || $existingTimeIn === '00:00:00' ||
                ($isAutomatic && $existingTimeIn > '12:00:00')) {
                mobile_json_error('A valid Time In before noon is required for automatic Lunch In.', 400);
            }
            save_attendance_apply_auto_lunch_out($conn, $attendanceId, $assignedSiteId);

            $lunchInTime = $eventTime;
            $existingLunchIn = trim((string) ($existing['Lunch_In'] ?? ''));
            // Preserve an earlier manual punch even when an offline noon punch
            // or a duplicate from another device arrives later.
            if ($existingLunchIn !== '' && $existingLunchIn !== '00:00:00' &&
                $existingLunchIn <= $lunchInTime) {
                $lunchInTime = $existingLunchIn;
                $recordAccepted = false;
            }
            $eventTime = $lunchInTime;
            $updateStmt = $conn->prepare('
                UPDATE attendance
                SET Lunch_In = ?
                WHERE AttendanceID = ?
            ');
            if (!$updateStmt) {
                mobile_json_error('Database error', 500);
            }
            $updateStmt->bind_param('si', $lunchInTime, $attendanceId);
            save_attendance_require_execute($updateStmt, 'Attendance lunch-in update');
            $updateStmt->close();
        } else {
            $updateStmt = $conn->prepare('
                UPDATE attendance
                SET SiteID = ?, AttendanceStatus = ?
                WHERE AttendanceID = ?
            ');
            if (!$updateStmt) {
                mobile_json_error('Database error', 500);
            }
            $updateStmt->bind_param('isi', $assignedSiteId, $status, $attendanceId);
            save_attendance_require_execute($updateStmt, 'Attendance update');
            $updateStmt->close();
        }
    } elseif ($attendanceType === 'Lunch Out' || $attendanceType === 'Lunch In') {
        mobile_json_error('Worker must be clocked in before recording lunch punches.', 400);
    } elseif ($attendanceType !== 'Time In' && $attendanceType !== 'Time Out') {
        $savedRecords[] = [
            'worker_id' => $workerId,
            'date' => $date,
            'status' => $status,
            'attendance_type' => $attendanceType,
            'updated' => false,
        ];
        $saved++;
        continue;
    } else {
        $defaultTimeOut = $timeOut ?? '00:00:00';
        $hoursWorked = 0.0;
        if ($timeOut !== null) {
            $inSeconds = strtotime($date . ' ' . $timeIn);
            $outSeconds = strtotime($date . ' ' . $timeOut);
            if ($outSeconds > $inSeconds) {
                $hoursWorked = round(($outSeconds - $inSeconds) / 3600, 2);
            }
        }

        $insertStmt = $conn->prepare("
            INSERT INTO attendance (WorkerID, SiteID, Date, Time_In, Time_Out, Hours_Worked, AttendanceStatus)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                SiteID = VALUES(SiteID),
                Time_In = VALUES(Time_In),
                Time_Out = VALUES(Time_Out),
                Hours_Worked = VALUES(Hours_Worked),
                AttendanceStatus = VALUES(AttendanceStatus)
        ");
        if (!$insertStmt) {
            mobile_json_error('Database error', 500);
        }

        $insertStmt->bind_param(
            'iisssds',
            $workerId,
            $assignedSiteId,
            $date,
            $timeIn,
            $defaultTimeOut,
            $hoursWorked,
            $status
        );
        save_attendance_require_execute($insertStmt, 'Attendance insert');
        $insertStmt->close();

        $lookupStmt = $conn->prepare('
            SELECT AttendanceID
            FROM attendance
            WHERE WorkerID = ? AND Date = ?
            LIMIT 1
        ');
        if (!$lookupStmt) {
            mobile_json_error('Database error', 500);
        }
        $lookupStmt->bind_param('is', $workerId, $date);
        $lookupStmt->execute();
        $lookupRow = $lookupStmt->get_result()->fetch_assoc();
        $lookupStmt->close();
        $attendanceId = (int) ($lookupRow['AttendanceID'] ?? 0);
        if ($attendanceId <= 0) {
            mobile_json_error('Attendance row could not be saved.', 500);
        }
    }

    if ($attendanceId > 0 && $hasLunchColumns) {
        save_attendance_apply_auto_lunch_out($conn, $attendanceId, $assignedSiteId);
    }

    if ($attendanceId > 0 && $recordAccepted && $requiresPhoto && $uploadedPhotoPath !== null && $uploadedPhotoPath !== '') {
        attendance_photo_log_punch(
            $conn,
            $workerId,
            $assignedSiteId,
            $timekeeperId,
            $attendanceType,
            $date,
            $eventTime,
            $latitude,
            $longitude,
            $distanceFromSite,
            $uploadedPhotoPath,
            $attendanceId
        );
        save_attendance_apply_photo_evidence(
            $conn,
            $attendanceId,
            $uploadedPhotoPath,
            $latitude,
            $longitude,
            $distanceFromSite
        );
    }

    // Use the saved Time In, never a lunch/time-out timestamp, for lateness.
    $lateStmt = $conn->prepare('SELECT Time_In, AttendanceStatus FROM attendance WHERE AttendanceID = ?');
    if (!$lateStmt) mobile_json_error('Could not read saved attendance.', 500);
    $lateStmt->bind_param('i', $attendanceId);
    save_attendance_require_execute($lateStmt, 'Read saved attendance');
    $savedRow = $lateStmt->get_result()->fetch_assoc();
    $lateStmt->close();
    if (!$savedRow) mobile_json_error('Saved attendance was not found.', 500);
    $status = attendance_schema_normalize_status((string) $savedRow['AttendanceStatus']);
    $isLate = $status === 'Present' && mobile_resolve_attendance_status(
        $conn, $assignedSiteId, $date, (string) $savedRow['Time_In'], $clientStatus
    ) === 'Late' ? 1 : 0;
    $lateStmt = $conn->prepare('UPDATE attendance SET AttendanceStatus = ?, IsLate = ? WHERE AttendanceID = ?');
    if (!$lateStmt) mobile_json_error('Could not save late indicator.', 500);
    $lateStmt->bind_param('sii', $status, $isLate, $attendanceId);
    save_attendance_require_execute($lateStmt, 'Save late indicator');
    $lateStmt->close();

    $savedRecords[] = [
        'is_late' => $isLate,
        'worker_id' => $workerId,
        'date' => $date,
        'status' => $status,
        'attendance_type' => $attendanceType,
        'updated' => $wasUpdated,
        'attendance_id' => $attendanceId,
        'event_time' => $eventTime,
    ];
    $saved++;
    error_log('[AttendanceSync] saved attendance_id=' . $attendanceId . ' worker_id=' . $workerId . ' date=' . $date . ' type=' . $attendanceType);
    $releaseStmt = $conn->prepare('SELECT RELEASE_LOCK(?)');
    if ($releaseStmt) {
        $releaseStmt->bind_param('s', $lockName);
        $releaseStmt->execute();
        $releaseStmt->close();
    }
}

$response = [
    'status' => 'success',
    'saved' => $saved,
    'site_id' => $assignedSiteId,
    'records' => $savedRecords,
];

if ($responsePhotoUrl !== null) {
    $response['photo_url'] = $responsePhotoUrl;
}

echo json_encode($response);
if (ob_get_level() > 0) {
    ob_end_flush();
}

$conn->close();
