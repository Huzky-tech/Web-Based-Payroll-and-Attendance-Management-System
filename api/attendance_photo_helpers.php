<?php
/**
 * Helpers for storing attendance proof photos and punch metadata.
 */

if (!function_exists('attendance_photo_upload_dir')) {
    function attendance_photo_upload_dir(): string
    {
        $uploadDir = __DIR__ . '/../uploads/attendance_photos';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }
        return $uploadDir;
    }
}

if (!function_exists('attendance_photo_requires_evidence')) {
    function attendance_photo_requires_evidence(string $attendanceType): bool
    {
        return in_array($attendanceType, ['Time In', 'Lunch In', 'Time Out'], true);
    }
}

if (!function_exists('attendance_photo_display_label')) {
    function attendance_photo_display_label(string $attendanceType): string
    {
        switch ($attendanceType) {
            case 'Time In':
                return 'AM Time In Photo';
            case 'Lunch In':
                return 'PM Time In Photo';
            case 'Time Out':
                return 'Time Out Photo';
            default:
                return 'Attendance Photo';
        }
    }
}

if (!function_exists('attendance_photo_save_uploaded_file')) {
    function attendance_photo_save_uploaded_file(array $file, int $workerId, string $attendanceType): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return null;
        }

        $mime = mime_content_type($tmpName) ?: '';
        $extension = 'jpg';
        if (strpos($mime, 'png') !== false) {
            $extension = 'png';
        } elseif (strpos($mime, 'webp') !== false) {
            $extension = 'webp';
        }

        $safeType = preg_replace('/[^a-z0-9_]+/i', '_', strtolower($attendanceType)) ?: 'attendance';
        $filename = 'attendance_' . $workerId . '_' . $safeType . '_' . time() . '.' . $extension;
        $fullPath = attendance_photo_upload_dir() . '/' . $filename;

        if (!move_uploaded_file($tmpName, $fullPath)) {
            return null;
        }

        return 'uploads/attendance_photos/' . $filename;
    }
}

if (!function_exists('attendance_photo_ensure_log_table')) {
    function attendance_photo_ensure_log_table(mysqli $conn): void
    {
        $created = $conn->query("
            CREATE TABLE IF NOT EXISTS attendance_photo_logs (
                LogID INT AUTO_INCREMENT PRIMARY KEY,
                AttendanceID INT NULL,
                WorkerID INT NOT NULL,
                SiteID INT NOT NULL,
                TimekeeperID INT NOT NULL,
                AttendanceType VARCHAR(30) NOT NULL,
                EventDate DATE NOT NULL,
                EventTime TIME NOT NULL,
                Latitude DECIMAL(10,7) NULL,
                Longitude DECIMAL(10,7) NULL,
                DistanceFromSite DECIMAL(10,2) NULL,
                PhotoPath VARCHAR(500) NOT NULL,
                CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");

        if (!$created) {
            mobile_json_error('Could not create attendance photo log.', 500);
        }
        // Inspect the log table, not the attendance table.
        $columns = $conn->query("SHOW COLUMNS FROM attendance_photo_logs LIKE 'AttendanceID'");
        if (!$columns) {
            mobile_json_error('Could not inspect attendance photo log.', 500);
        }
        if ($columns->num_rows === 0 &&
            !$conn->query('ALTER TABLE attendance_photo_logs ADD COLUMN AttendanceID INT NULL AFTER LogID')) {
            mobile_json_error('Could not update attendance photo log.', 500);
        }
    }
}

if (!function_exists('attendance_photo_log_punch')) {
    function attendance_photo_log_punch(
        mysqli $conn,
        int $workerId,
        int $siteId,
        int $timekeeperId,
        string $attendanceType,
        string $eventDate,
        string $eventTime,
        ?float $latitude,
        ?float $longitude,
        ?float $distanceFromSite,
        string $photoPath,
        ?int $attendanceId = null
    ): void {
        if (!attendance_photo_requires_evidence($attendanceType)) {
            return;
        }

        attendance_photo_ensure_log_table($conn);

        if ($attendanceId !== null && $attendanceId > 0) {
            $deleteStmt = $conn->prepare('
                DELETE FROM attendance_photo_logs
                WHERE AttendanceID = ? AND AttendanceType = ?
            ');
            if ($deleteStmt) {
                $deleteStmt->bind_param('is', $attendanceId, $attendanceType);
                if (!$deleteStmt->execute()) {
                    mobile_json_error('Could not replace attendance photo evidence.', 500);
                }
                $deleteStmt->close();
            } else {
                mobile_json_error('Could not prepare attendance photo replacement.', 500);
            }
        }

        $stmt = $conn->prepare("
            INSERT INTO attendance_photo_logs (
                AttendanceID, WorkerID, SiteID, TimekeeperID, AttendanceType, EventDate, EventTime,
                Latitude, Longitude, DistanceFromSite, PhotoPath
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            mobile_json_error('Could not prepare attendance photo insert.', 500);
        }

        $lat = $latitude;
        $lng = $longitude;
        $distance = $distanceFromSite;

        $stmt->bind_param(
            'iiiisssddds',
            $attendanceId,
            $workerId,
            $siteId,
            $timekeeperId,
            $attendanceType,
            $eventDate,
            $eventTime,
            $lat,
            $lng,
            $distance,
            $photoPath
        );
        if (!$stmt->execute()) {
            mobile_json_error('Could not save attendance photo evidence.', 500);
        }
        $stmt->close();
    }
}

if (!function_exists('attendance_photo_fetch_logs_for_records')) {
    /**
     * @param array<int, array<string, mixed>> $records
     * @return array<int, list<array<string, mixed>>>
     */
    function attendance_photo_fetch_logs_for_records(mysqli $conn, array $records): array
    {
        if ($records === []) {
            return [];
        }

        attendance_photo_ensure_log_table($conn);

        $attendanceIds = [];
        $workerDates = [];
        foreach ($records as $record) {
            $attendanceId = (int) ($record['AttendanceID'] ?? 0);
            if ($attendanceId > 0) {
                $attendanceIds[$attendanceId] = $attendanceId;
            }

            $workerId = (int) ($record['WorkerID'] ?? $record['ID'] ?? 0);
            $date = (string) ($record['Date'] ?? '');
            if ($workerId > 0 && $date !== '') {
                $workerDates[$workerId . '|' . $date] = [$workerId, $date];
            }
        }

        $grouped = [];
        $allowedTypes = ['Time In', 'Lunch In', 'Time Out'];

        if ($attendanceIds !== []) {
            $idList = implode(',', array_map('intval', array_values($attendanceIds)));
            $result = $conn->query("
                SELECT LogID, AttendanceID, WorkerID, AttendanceType, EventDate, EventTime,
                       Latitude, Longitude, DistanceFromSite, PhotoPath
                FROM attendance_photo_logs
                WHERE AttendanceID IN ({$idList})
                  AND AttendanceType IN ('Time In', 'Lunch In', 'Time Out')
                ORDER BY FIELD(AttendanceType, 'Time In', 'Lunch In', 'Time Out'), EventTime ASC
            ");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $attendanceId = (int) ($row['AttendanceID'] ?? 0);
                    if ($attendanceId <= 0) {
                        continue;
                    }
                    $grouped[$attendanceId][] = attendance_photo_format_log_row($row);
                }
                $result->close();
            }
        }

        foreach ($workerDates as [$workerId, $date]) {
            $stmt = $conn->prepare("
                SELECT LogID, AttendanceID, WorkerID, AttendanceType, EventDate, EventTime,
                       Latitude, Longitude, DistanceFromSite, PhotoPath
                FROM attendance_photo_logs
                WHERE WorkerID = ? AND EventDate = ?
                  AND AttendanceType IN ('Time In', 'Lunch In', 'Time Out')
                ORDER BY FIELD(AttendanceType, 'Time In', 'Lunch In', 'Time Out'), EventTime ASC
            ");
            if (!$stmt) {
                continue;
            }
            $stmt->bind_param('is', $workerId, $date);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $attendanceId = (int) ($row['AttendanceID'] ?? 0);
                $matchedAttendanceId = 0;
                foreach ($records as $record) {
                    $recordAttendanceId = (int) ($record['AttendanceID'] ?? 0);
                    $recordWorkerId = (int) ($record['WorkerID'] ?? $record['ID'] ?? 0);
                    $recordDate = (string) ($record['Date'] ?? $date);
                    if ($recordWorkerId === $workerId && $recordDate === $date && $recordAttendanceId > 0) {
                        $matchedAttendanceId = $recordAttendanceId;
                        break;
                    }
                }

                $key = $matchedAttendanceId > 0 ? $matchedAttendanceId : ('worker:' . $workerId . ':' . $date);
                $formatted = attendance_photo_format_log_row($row);
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [];
                }
                $exists = false;
                foreach ($grouped[$key] as $existing) {
                    if (($existing['log_id'] ?? 0) === ($formatted['log_id'] ?? -1)) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $grouped[$key][] = $formatted;
                }
            }
            $stmt->close();
        }

        return $grouped;
    }
}

if (!function_exists('attendance_photo_format_log_row')) {
    function attendance_photo_format_log_row(array $row): array
    {
        $type = (string) ($row['AttendanceType'] ?? '');
        return [
            'log_id' => (int) ($row['LogID'] ?? 0),
            'attendance_id' => (int) ($row['AttendanceID'] ?? 0),
            'worker_id' => (int) ($row['WorkerID'] ?? 0),
            'attendance_type' => $type,
            'label' => attendance_photo_display_label($type),
            'event_date' => (string) ($row['EventDate'] ?? ''),
            'event_time' => (string) ($row['EventTime'] ?? ''),
            'photo_path' => (string) ($row['PhotoPath'] ?? ''),
            'latitude' => $row['Latitude'] ?? null,
            'longitude' => $row['Longitude'] ?? null,
            'distance_from_site' => $row['DistanceFromSite'] ?? null,
        ];
    }
}

if (!function_exists('attendance_photo_parse_event_time')) {
    function attendance_photo_parse_event_time(array $record): string
    {
        $raw = (string) ($record['event_time'] ?? $record['time_in'] ?? '');
        return mobile_parse_attendance_time($raw);
    }
}

if (!function_exists('attendance_photo_normalize_type')) {
    function attendance_photo_normalize_type(string $raw): string
    {
        $value = trim($raw);
        $allowed = ['Time In', 'Lunch Out', 'Lunch In', 'Time Out', 'Overtime'];
        if (in_array($value, $allowed, true)) {
            return $value;
        }
        return 'Time In';
    }
}

if (!function_exists('attendance_photo_column_map')) {
    function attendance_photo_column_map(mysqli $conn): array
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }

        $cache = [];
        $result = $conn->query('SHOW COLUMNS FROM attendance');
        if (!$result) {
            return $cache;
        }

        while ($row = $result->fetch_assoc()) {
            $field = (string) ($row['Field'] ?? '');
            if ($field !== '') {
                $cache[$field] = true;
            }
        }
        $result->close();

        return $cache;
    }
}

if (!function_exists('ensure_attendance_photo_columns')) {
    function ensure_attendance_photo_columns(mysqli $conn): bool
    {
        $columns = attendance_photo_column_map($conn);
        $required = [
            'PhotoPath' => 'VARCHAR(500) DEFAULT NULL',
            'Latitude' => 'DECIMAL(10,7) DEFAULT NULL',
            'Longitude' => 'DECIMAL(10,7) DEFAULT NULL',
            'DistanceFromSite' => 'DECIMAL(10,2) DEFAULT NULL',
        ];

        foreach ($required as $column => $definition) {
            if (isset($columns[$column])) {
                continue;
            }

            if (!$conn->query("ALTER TABLE attendance ADD COLUMN {$column} {$definition}")) {
                return false;
            }
            $columns[$column] = true;
        }

        return true;
    }
}

// Kept as a compatibility name for the attendance records endpoint. Punch
// photos are stored in attendance_photo_logs; the attendance row only needs
// the shared legacy photo/location columns.
if (!function_exists('ensure_attendance_punch_photo_columns')) {
    function ensure_attendance_punch_photo_columns(mysqli $conn): bool
    {
        return ensure_attendance_photo_columns($conn);
    }
}

if (!function_exists('attendance_photo_build_logs_from_record')) {
    function attendance_photo_build_logs_from_record(array $record): array
    {
        $map = [
            'Time In' => ['AMTimeInPhoto', 'Time_In', 'AM IN Photo'],
            'Lunch In' => ['PMTimeInPhoto', 'Lunch_In', 'PM IN Photo'],
            'Time Out' => ['TimeOutPhoto', 'Time_Out', 'PM OUT Photo'],
        ];
        $logs = [];
        foreach ($map as $type => [$photoColumn, $timeColumn, $label]) {
            $path = trim((string) ($record[$photoColumn] ?? ''));
            if ($path === '') continue;
            $logs[] = [
                'attendance_type' => $type,
                'photo_path' => $path,
                'event_time' => (string) ($record[$timeColumn] ?? ''),
                'label' => $label,
            ];
        }
        if ($logs === [] && trim((string) ($record['PhotoPath'] ?? '')) !== '') {
            $logs[] = [
                'attendance_type' => 'Time In',
                'photo_path' => trim((string) $record['PhotoPath']),
                'event_time' => (string) ($record['Time_In'] ?? ''),
                'label' => 'AM IN Photo',
            ];
        }
        return $logs;
    }
}

if (!function_exists('attendance_photo_merge_logs')) {
    function attendance_photo_merge_logs(array $logs, array $fallback): array
    {
        $merged = [];
        foreach (array_merge($logs, $fallback) as $log) {
            $type = (string) ($log['attendance_type'] ?? '');
            if ($type === '' || isset($merged[$type])) continue;
            $merged[$type] = $log;
        }
        return array_values($merged);
    }
}

if (!function_exists('attendance_photo_select_columns')) {
    function attendance_photo_select_columns(mysqli $conn): string
    {
        $columns = attendance_photo_column_map($conn);
        if (isset($columns['PhotoPath'])) {
            return 'a.PhotoPath, a.Latitude, a.Longitude, a.DistanceFromSite';
        }

        return 'NULL AS PhotoPath, NULL AS Latitude, NULL AS Longitude, NULL AS DistanceFromSite';
    }
}
