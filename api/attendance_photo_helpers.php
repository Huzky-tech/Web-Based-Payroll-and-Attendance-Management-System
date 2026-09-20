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
        $conn->query("
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

        $columns = attendance_photo_column_map($conn);
        if (!isset($columns['AttendanceID'])) {
            $conn->query('ALTER TABLE attendance_photo_logs ADD COLUMN AttendanceID INT NULL AFTER LogID');
        }
    }
}

if (!function_exists('attendance_photo_punch_column_name')) {
    function attendance_photo_punch_column_name(string $attendanceType): ?string
    {
        switch ($attendanceType) {
            case 'Time In':
                return 'AMTimeInPhoto';
            case 'Lunch In':
                return 'PMTimeInPhoto';
            case 'Time Out':
                return 'TimeOutPhoto';
            default:
                return null;
        }
    }
}

if (!function_exists('ensure_attendance_punch_photo_columns')) {
    function ensure_attendance_punch_photo_columns(mysqli $conn): bool
    {
        $columns = attendance_photo_column_map($conn);
        $required = [
            'AMTimeInPhoto' => 'VARCHAR(500) DEFAULT NULL',
            'PMTimeInPhoto' => 'VARCHAR(500) DEFAULT NULL',
            'TimeOutPhoto' => 'VARCHAR(500) DEFAULT NULL',
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

        attendance_photo_column_map($conn, true);

        return true;
    }
}

if (!function_exists('attendance_photo_apply_punch_column')) {
    function attendance_photo_apply_punch_column(
        mysqli $conn,
        int $attendanceId,
        string $attendanceType,
        string $photoPath
    ): void {
        if ($attendanceId <= 0 || $photoPath === '') {
            return;
        }

        $column = attendance_photo_punch_column_name($attendanceType);
        if ($column === null) {
            return;
        }

        if (!ensure_attendance_punch_photo_columns($conn)) {
            return;
        }

        $columns = attendance_photo_column_map($conn);
        if (!isset($columns[$column])) {
            return;
        }

        $stmt = $conn->prepare("UPDATE attendance SET {$column} = ? WHERE AttendanceID = ? LIMIT 1");
        if (!$stmt) {
            return;
        }

        $stmt->bind_param('si', $photoPath, $attendanceId);
        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('attendance_photo_haversine_distance_m')) {
    function attendance_photo_haversine_distance_m(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $earthRadius = 6371000.0;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return 2 * $earthRadius * asin(min(1.0, sqrt($a)));
    }
}

if (!function_exists('attendance_calculate_distance_from_site')) {
    function attendance_calculate_distance_from_site(
        mysqli $conn,
        int $siteId,
        ?float $latitude,
        ?float $longitude
    ): ?float {
        if ($siteId <= 0 || $latitude === null || $longitude === null) {
            return null;
        }

        require_once __DIR__ . '/site_schedule_helpers.php';
        if (!geofence_columns_exist($conn)) {
            return null;
        }

        $stmt = $conn->prepare('
            SELECT Geofence_Lat, Geofence_Lng
            FROM projectsite
            WHERE SiteID = ?
            LIMIT 1
        ');
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $siteId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $siteLat = isset($row['Geofence_Lat']) ? (float) $row['Geofence_Lat'] : null;
        $siteLng = isset($row['Geofence_Lng']) ? (float) $row['Geofence_Lng'] : null;
        if ($siteLat === null || $siteLng === null) {
            return null;
        }

        return round(attendance_photo_haversine_distance_m($latitude, $longitude, $siteLat, $siteLng), 2);
    }
}

if (!function_exists('attendance_gps_verification_label')) {
    function attendance_gps_verification_label(?float $distance, ?float $radius): string
    {
        if ($distance === null) {
            return 'No GPS';
        }

        if ($radius === null || $radius <= 0) {
            return 'GPS Recorded';
        }

        return $distance <= $radius ? 'Within Geofence' : 'Outside Geofence';
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
                $deleteStmt->execute();
                $deleteStmt->close();
            }
        }

        $stmt = $conn->prepare("
            INSERT INTO attendance_photo_logs (
                AttendanceID, WorkerID, SiteID, TimekeeperID, AttendanceType, EventDate, EventTime,
                Latitude, Longitude, DistanceFromSite, PhotoPath
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            return;
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
        $stmt->execute();
        $stmt->close();

        if ($attendanceId !== null && $attendanceId > 0) {
            attendance_photo_apply_punch_column($conn, $attendanceId, $attendanceType, $photoPath);
        }
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
                SELECT l.LogID, l.AttendanceID, l.WorkerID, l.TimekeeperID, l.AttendanceType, l.EventDate, l.EventTime,
                       l.Latitude, l.Longitude, l.DistanceFromSite, l.PhotoPath, u.full_name AS TimekeeperName
                FROM attendance_photo_logs l
                LEFT JOIN users u ON u.id = l.TimekeeperID
                WHERE l.AttendanceID IN ({$idList})
                  AND l.AttendanceType IN ('Time In', 'Lunch In', 'Time Out')
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
                SELECT l.LogID, l.AttendanceID, l.WorkerID, l.TimekeeperID, l.AttendanceType, l.EventDate, l.EventTime,
                       l.Latitude, l.Longitude, l.DistanceFromSite, l.PhotoPath, u.full_name AS TimekeeperName
                FROM attendance_photo_logs l
                LEFT JOIN users u ON u.id = l.TimekeeperID
                WHERE l.WorkerID = ? AND l.EventDate = ?
                  AND l.AttendanceType IN ('Time In', 'Lunch In', 'Time Out')
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
            'timekeeper_id' => (int) ($row['TimekeeperID'] ?? 0),
            'timekeeper_name' => (string) ($row['TimekeeperName'] ?? ''),
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
    function attendance_photo_column_map(mysqli $conn, bool $refresh = false): array
    {
        static $cache = null;
        if (is_array($cache) && !$refresh) {
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

if (!function_exists('attendance_photo_select_columns')) {
    function attendance_photo_select_columns(mysqli $conn): string
    {
        $columns = attendance_photo_column_map($conn);
        $parts = [];

        $parts[] = isset($columns['PhotoPath']) ? 'a.PhotoPath' : 'NULL AS PhotoPath';
        $parts[] = isset($columns['AMTimeInPhoto']) ? 'a.AMTimeInPhoto' : 'NULL AS AMTimeInPhoto';
        $parts[] = isset($columns['PMTimeInPhoto']) ? 'a.PMTimeInPhoto' : 'NULL AS PMTimeInPhoto';
        $parts[] = isset($columns['TimeOutPhoto']) ? 'a.TimeOutPhoto' : 'NULL AS TimeOutPhoto';
        $parts[] = isset($columns['Latitude']) ? 'a.Latitude' : 'NULL AS Latitude';
        $parts[] = isset($columns['Longitude']) ? 'a.Longitude' : 'NULL AS Longitude';
        $parts[] = isset($columns['DistanceFromSite']) ? 'a.DistanceFromSite' : 'NULL AS DistanceFromSite';

        return implode(', ', $parts);
    }
}

if (!function_exists('attendance_photo_build_logs_from_record')) {
    /**
     * @return list<array<string, mixed>>
     */
    function attendance_photo_build_logs_from_record(array $record): array
    {
        $typeMap = [
            'Time In' => [
                'column' => 'AMTimeInPhoto',
                'time_column' => 'Time_In',
            ],
            'Lunch In' => [
                'column' => 'PMTimeInPhoto',
                'time_column' => 'Lunch_In',
            ],
            'Time Out' => [
                'column' => 'TimeOutPhoto',
                'time_column' => 'Time_Out',
            ],
        ];

        $logs = [];
        foreach ($typeMap as $type => $config) {
            $photoPath = trim((string) ($record[$config['column']] ?? ''));
            if ($photoPath === '') {
                continue;
            }

            $logs[] = [
                'log_id' => 0,
                'attendance_id' => (int) ($record['AttendanceID'] ?? 0),
                'worker_id' => (int) ($record['WorkerID'] ?? $record['ID'] ?? 0),
                'attendance_type' => $type,
                'label' => attendance_photo_display_label($type),
                'event_date' => (string) ($record['Date'] ?? ''),
                'event_time' => (string) ($record[$config['time_column']] ?? ''),
                'photo_path' => $photoPath,
                'latitude' => $record['Latitude'] ?? null,
                'longitude' => $record['Longitude'] ?? null,
                'distance_from_site' => $record['DistanceFromSite'] ?? null,
            ];
        }

        return $logs;
    }
}

if (!function_exists('attendance_photo_merge_logs')) {
    /**
     * @param list<array<string, mixed>> $existingLogs
     * @param list<array<string, mixed>> $columnLogs
     * @return list<array<string, mixed>>
     */
    function attendance_photo_merge_logs(array $existingLogs, array $columnLogs): array
    {
        $merged = $existingLogs;
        $typesPresent = [];
        foreach ($merged as $entry) {
            $typesPresent[(string) ($entry['attendance_type'] ?? '')] = true;
        }

        foreach ($columnLogs as $entry) {
            $type = (string) ($entry['attendance_type'] ?? '');
            if ($type === '' || isset($typesPresent[$type])) {
                continue;
            }
            $merged[] = $entry;
            $typesPresent[$type] = true;
        }

        usort($merged, static function (array $left, array $right): int {
            $order = ['Time In' => 0, 'Lunch In' => 1, 'Time Out' => 2];
            $leftOrder = $order[$left['attendance_type'] ?? ''] ?? 99;
            $rightOrder = $order[$right['attendance_type'] ?? ''] ?? 99;
            if ($leftOrder !== $rightOrder) {
                return $leftOrder <=> $rightOrder;
            }

            return strcmp((string) ($left['event_time'] ?? ''), (string) ($right['event_time'] ?? ''));
        });

        return $merged;
    }
}
