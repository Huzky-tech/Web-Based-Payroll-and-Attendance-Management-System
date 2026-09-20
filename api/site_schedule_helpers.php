<?php

if (!function_exists('site_schedule_columns_exist')) {
    function site_schedule_columns_exist(mysqli $conn): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $check = $conn->query("SHOW COLUMNS FROM projectsite LIKE 'ShiftStart'");
        $cached = $check && $check->num_rows > 0;
        return $cached;
    }
}

if (!function_exists('geofence_columns_exist')) {
    function geofence_columns_exist(mysqli $conn, bool $refresh = false): bool
    {
        static $cached = null;
        if (!$refresh && $cached !== null) {
            return $cached;
        }

        $check = $conn->query("SHOW COLUMNS FROM projectsite LIKE 'Geofence_Radius_M'");
        $cached = $check && $check->num_rows > 0;
        return $cached;
    }
}

if (!function_exists('ensure_geofence_columns')) {
    function ensure_geofence_columns(mysqli $conn): bool
    {
        if (geofence_columns_exist($conn)) {
            return true;
        }

        $sql = "
            ALTER TABLE projectsite
            ADD COLUMN Geofence_Radius_M DECIMAL(10,2) DEFAULT NULL AFTER Coordinates,
            ADD COLUMN Geofence_Lat DECIMAL(10,7) DEFAULT NULL AFTER Geofence_Radius_M,
            ADD COLUMN Geofence_Lng DECIMAL(10,7) DEFAULT NULL AFTER Geofence_Lat
        ";

        if (!$conn->query($sql)) {
            return false;
        }

        return geofence_columns_exist($conn, true);
    }
}

if (!function_exists('normalize_site_time_input')) {
    function normalize_site_time_input(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $value) === 1) {
            return $value . ':00';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) === 1) {
            return $value;
        }

        return null;
    }
}

if (!function_exists('site_schedule_time_to_minutes')) {
    function site_schedule_time_to_minutes(?string $timeValue): ?int
    {
        $normalized = normalize_site_time_input($timeValue);
        if ($normalized === null) {
            return null;
        }

        $parts = explode(':', $normalized);
        if (count($parts) < 2) {
            return null;
        }

        return ((int) $parts[0] * 60) + (int) $parts[1];
    }
}

if (!function_exists('attendance_display_status')) {
    /**
     * A missing time-in is Not Started until the site's scheduled shift has ended.
     */
    function attendance_display_status(?string $storedStatus, ?string $timeIn, string $attendanceDate, ?string $shiftEnd): string
    {
        $hasTimeIn = trim((string) $timeIn) !== '' && trim((string) $timeIn) !== '00:00:00';
        if ($hasTimeIn) {
            $status = ucfirst(strtolower(trim((string) $storedStatus)));
            return in_array($status, ['Present', 'Late'], true) ? $status : 'Present';
        }

        $timezone = new DateTimeZone('Asia/Manila');
        $now = new DateTimeImmutable('now', $timezone);
        $today = $now->format('Y-m-d');
        if ($attendanceDate < $today) {
            return 'Absent';
        }
        if ($attendanceDate > $today) {
            return 'Not Started';
        }

        $shiftEndMinutes = site_schedule_time_to_minutes($shiftEnd) ?? (17 * 60);
        $nowMinutes = ((int) $now->format('H') * 60) + (int) $now->format('i');
        return $nowMinutes >= $shiftEndMinutes ? 'Absent' : 'Not Started';
    }
}

if (!function_exists('validate_site_schedule_times')) {
    function validate_site_schedule_times(
        ?string $shiftStart,
        ?string $lunchStart,
        ?string $lunchEnd,
        ?string $shiftEnd
    ): ?string {
        $shiftStartMin = site_schedule_time_to_minutes($shiftStart);
        $lunchStartMin = site_schedule_time_to_minutes($lunchStart);
        $lunchEndMin = site_schedule_time_to_minutes($lunchEnd);
        $shiftEndMin = site_schedule_time_to_minutes($shiftEnd);

        if ($shiftStartMin === null || $lunchStartMin === null || $lunchEndMin === null || $shiftEndMin === null) {
            return 'Shift start, lunch start, lunch end, and shift end are required.';
        }

        if ($shiftStartMin >= $lunchStartMin) {
            return 'Lunch start must be after shift start.';
        }

        if ($lunchStartMin >= $lunchEndMin) {
            return 'Lunch end must be after lunch start.';
        }

        if ($lunchEndMin >= $shiftEndMin) {
            return 'Shift end must be after lunch end.';
        }

        return null;
    }
}

if (!function_exists('calculate_site_working_hours')) {
    function calculate_site_working_hours(
        ?string $shiftStart,
        ?string $lunchStart,
        ?string $lunchEnd,
        ?string $shiftEnd
    ): float {
        $shiftStartMin = site_schedule_time_to_minutes($shiftStart);
        $lunchStartMin = site_schedule_time_to_minutes($lunchStart);
        $lunchEndMin = site_schedule_time_to_minutes($lunchEnd);
        $shiftEndMin = site_schedule_time_to_minutes($shiftEnd);

        if ($shiftStartMin === null || $lunchStartMin === null || $lunchEndMin === null || $shiftEndMin === null) {
            return 0.0;
        }

        $shiftMinutes = max(0, $shiftEndMin - $shiftStartMin);
        $lunchMinutes = max(0, $lunchEndMin - $lunchStartMin);

        return round(max(0, $shiftMinutes - $lunchMinutes) / 60, 2);
    }
}

if (!function_exists('get_site_schedule_row')) {
    function get_site_schedule_row(mysqli $conn, int $siteId): ?array
    {
        if ($siteId <= 0) {
            return null;
        }

        if (site_schedule_columns_exist($conn)) {
            $stmt = $conn->prepare("
                SELECT ShiftStart, LunchStart, LunchEnd, ShiftEnd
                FROM projectsite
                WHERE SiteID = ?
                LIMIT 1
            ");
            if ($stmt) {
                $stmt->bind_param('i', $siteId);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result ? $result->fetch_assoc() : null;
                $stmt->close();

                if ($row && !empty($row['ShiftStart']) && !empty($row['ShiftEnd'])) {
                    if (empty($row['LunchStart'])) {
                        $row['LunchStart'] = '12:00:00';
                    }
                    if (empty($row['LunchEnd'])) {
                        $row['LunchEnd'] = '13:00:00';
                    }
                    return $row;
                }
            }
        }

        $fallback = $conn->prepare("
            SELECT ShiftStart, ShiftEnd
            FROM site_schedule
            WHERE SiteID = ?
            ORDER BY Site_ScheduleID ASC
            LIMIT 1
        ");
        if (!$fallback) {
            return null;
        }

        $fallback->bind_param('i', $siteId);
        $fallback->execute();
        $fallbackResult = $fallback->get_result();
        $schedule = $fallbackResult ? $fallbackResult->fetch_assoc() : null;
        $fallback->close();

        if (!$schedule) {
            return null;
        }

        return [
            'ShiftStart' => $schedule['ShiftStart'] ?? '07:00:00',
            'LunchStart' => '12:00:00',
            'LunchEnd' => '13:00:00',
            'ShiftEnd' => $schedule['ShiftEnd'] ?? '17:00:00',
        ];
    }
}

if (!function_exists('sync_site_schedule_legacy_table')) {
    function sync_site_schedule_legacy_table(
        mysqli $conn,
        int $siteId,
        string $shiftStart,
        string $shiftEnd
    ): void {
        $scheduleCheckStmt = $conn->prepare("SELECT Site_ScheduleID FROM site_schedule WHERE SiteID = ? LIMIT 1");
        if (!$scheduleCheckStmt) {
            return;
        }

        $scheduleCheckStmt->bind_param('i', $siteId);
        $scheduleCheckStmt->execute();
        $scheduleResult = $scheduleCheckStmt->get_result();
        $existingSchedule = $scheduleResult ? $scheduleResult->fetch_assoc() : null;
        $scheduleCheckStmt->close();

        $lunchMinutes = 0;
        if (site_schedule_columns_exist($conn)) {
            $lunchStmt = $conn->prepare("SELECT LunchStart, LunchEnd FROM projectsite WHERE SiteID = ? LIMIT 1");
            if ($lunchStmt) {
                $lunchStmt->bind_param('i', $siteId);
                $lunchStmt->execute();
                $lunchResult = $lunchStmt->get_result();
                $lunchRow = $lunchResult ? $lunchResult->fetch_assoc() : null;
                $lunchStmt->close();

                $lunchStartMin = site_schedule_time_to_minutes($lunchRow['LunchStart'] ?? null);
                $lunchEndMin = site_schedule_time_to_minutes($lunchRow['LunchEnd'] ?? null);
                if ($lunchStartMin !== null && $lunchEndMin !== null && $lunchEndMin > $lunchStartMin) {
                    $lunchMinutes = $lunchEndMin - $lunchStartMin;
                }
            }
        }

        if ($existingSchedule) {
            $scheduleStmt = $conn->prepare("UPDATE site_schedule SET ShiftStart = ?, ShiftEnd = ?, BreakDuration = ? WHERE Site_ScheduleID = ?");
            if ($scheduleStmt) {
                $scheduleId = (int) $existingSchedule['Site_ScheduleID'];
                $scheduleStmt->bind_param('ssii', $shiftStart, $shiftEnd, $lunchMinutes, $scheduleId);
                $scheduleStmt->execute();
                $scheduleStmt->close();
            }
            return;
        }

        $defaultDay = 'Monday';
        $scheduleStmt = $conn->prepare("INSERT INTO site_schedule (SiteID, DayOfWeek, ShiftStart, ShiftEnd, BreakDuration) VALUES (?, ?, ?, ?, ?)");
        if ($scheduleStmt) {
            $scheduleStmt->bind_param('isssi', $siteId, $defaultDay, $shiftStart, $shiftEnd, $lunchMinutes);
            $scheduleStmt->execute();
            $scheduleStmt->close();
        }
    }
}

if (!function_exists('calculate_attendance_hours_worked')) {
    function calculate_attendance_hours_worked(
        ?string $timeIn,
        ?string $lunchOut,
        ?string $lunchIn,
        ?string $timeOut,
        ?array $siteSchedule
    ): array {
        $schedule = $siteSchedule ?? [];
        $shiftStart = (string) ($schedule['ShiftStart'] ?? '07:00:00');
        $lunchStart = (string) ($schedule['LunchStart'] ?? '12:00:00');
        $lunchEnd = (string) ($schedule['LunchEnd'] ?? '13:00:00');
        $shiftEnd = (string) ($schedule['ShiftEnd'] ?? '17:00:00');

        $timeIn = $timeIn ?: $shiftStart;
        $lunchOut = $lunchOut ?: $lunchStart;
        $lunchIn = $lunchIn ?: $lunchEnd;
        $timeOut = $timeOut ?: $shiftEnd;

        $timeInMin = site_schedule_time_to_minutes($timeIn) ?? 0;
        $lunchOutMin = site_schedule_time_to_minutes($lunchOut) ?? site_schedule_time_to_minutes($lunchStart) ?? 0;
        $lunchInMin = site_schedule_time_to_minutes($lunchIn) ?? site_schedule_time_to_minutes($lunchEnd) ?? 0;
        $timeOutMin = site_schedule_time_to_minutes($timeOut) ?? 0;

        $morningMinutes = max(0, $lunchOutMin - $timeInMin);
        $afternoonMinutes = max(0, $timeOutMin - $lunchInMin);
        $totalMinutes = $morningMinutes + $afternoonMinutes;

        $scheduledRegularHours = calculate_site_working_hours($shiftStart, $lunchStart, $lunchEnd, $shiftEnd);
        $hoursWorked = round($totalMinutes / 60, 2);
        $overtimeHours = round(max(0, $hoursWorked - $scheduledRegularHours), 2);
        $regularHours = round(max(0, $hoursWorked - $overtimeHours), 2);

        return [
            'hours_worked' => $regularHours,
            'overtime_hours' => $overtimeHours,
            'total_hours' => $hoursWorked,
            'scheduled_regular_hours' => $scheduledRegularHours,
        ];
    }
}

if (!function_exists('auto_close_open_attendance_at_shift_end')) {
    /**
     * Closes today's open attendance records at their site's configured shift end.
     * This is intentionally idempotent: records already clocked out are untouched.
     */
    function auto_close_open_attendance_at_shift_end(mysqli $conn): int
    {
        $hasLunchColumns = attendance_lunch_columns_exist($conn);
        $lunchSelect = $hasLunchColumns ? 'Lunch_Out, Lunch_In,' : 'NULL AS Lunch_Out, NULL AS Lunch_In,';
        $sql = "SELECT AttendanceID, SiteID, Time_In, {$lunchSelect} IsLate
                FROM attendance
                WHERE Date = CURDATE()
                  AND Time_In IS NOT NULL AND Time_In <> '00:00:00'
                  AND (Time_Out IS NULL OR Time_Out = '00:00:00')";
        $result = $conn->query($sql);
        if (!$result) return 0;

        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
        $nowMinutes = ((int) $now->format('H') * 60) + (int) $now->format('i');
        $scheduleCache = [];
        $closed = 0;

        while ($record = $result->fetch_assoc()) {
            $siteId = (int) ($record['SiteID'] ?? 0);
            if (!array_key_exists($siteId, $scheduleCache)) {
                $scheduleCache[$siteId] = get_site_schedule_row($conn, $siteId);
            }
            $schedule = $scheduleCache[$siteId] ?? [];
            $shiftEnd = (string) ($schedule['ShiftEnd'] ?? '17:00:00');
            $shiftEndMinutes = site_schedule_time_to_minutes($shiftEnd) ?? (17 * 60);
            if ($nowMinutes < $shiftEndMinutes) continue;

            $totals = calculate_attendance_hours_worked(
                (string) ($record['Time_In'] ?? ''),
                $record['Lunch_Out'] ?? null,
                $record['Lunch_In'] ?? null,
                $shiftEnd,
                $schedule
            );
            $update = $conn->prepare('UPDATE attendance SET Time_Out = ?, Hours_Worked = ?, AttendanceStatus = \'Present\' WHERE AttendanceID = ? AND (Time_Out IS NULL OR Time_Out = \'00:00:00\')');
            if (!$update) continue;
            $attendanceId = (int) $record['AttendanceID'];
            $hoursWorked = (float) $totals['hours_worked'];
            $update->bind_param('sdi', $shiftEnd, $hoursWorked, $attendanceId);
            $update->execute();
            $closed += $update->affected_rows > 0 ? 1 : 0;
            $update->close();
        }
        $result->close();
        return $closed;
    }
}

if (!function_exists('site_schedule_select_sql')) {
    function site_schedule_select_sql(mysqli $conn): string
    {
        if (site_schedule_columns_exist($conn)) {
            return "
                TIME_FORMAT(COALESCE(s.ShiftStart, sc.ShiftStart), '%H:%i') AS Start_Time,
                TIME_FORMAT(COALESCE(s.ShiftEnd, sc.ShiftEnd), '%H:%i') AS End_Time,
                TIME_FORMAT(COALESCE(s.ShiftStart, sc.ShiftStart), '%H:%i') AS Shift_Start,
                TIME_FORMAT(COALESCE(s.LunchStart, '12:00:00'), '%H:%i') AS Lunch_Start,
                TIME_FORMAT(COALESCE(s.LunchEnd, '13:00:00'), '%H:%i') AS Lunch_End,
                TIME_FORMAT(COALESCE(s.ShiftEnd, sc.ShiftEnd), '%H:%i') AS Shift_End,
                COALESCE(s.ShiftStart, sc.ShiftStart) AS ShiftStartRaw,
                COALESCE(s.LunchStart, '12:00:00') AS LunchStartRaw,
                COALESCE(s.LunchEnd, '13:00:00') AS LunchEndRaw,
                COALESCE(s.ShiftEnd, sc.ShiftEnd) AS ShiftEndRaw
            ";
        }

        return "
            TIME_FORMAT(sc.ShiftStart, '%H:%i') AS Start_Time,
            TIME_FORMAT(sc.ShiftEnd, '%H:%i') AS End_Time,
            TIME_FORMAT(sc.ShiftStart, '%H:%i') AS Shift_Start,
            '12:00' AS Lunch_Start,
            '13:00' AS Lunch_End,
            TIME_FORMAT(sc.ShiftEnd, '%H:%i') AS Shift_End,
            sc.ShiftStart AS ShiftStartRaw,
            '12:00:00' AS LunchStartRaw,
            '13:00:00' AS LunchEndRaw,
            sc.ShiftEnd AS ShiftEndRaw
        ";
    }
}

if (!function_exists('enrich_site_schedule_row')) {
    function enrich_site_schedule_row(array $row): array
    {
        $shiftStart = $row['ShiftStartRaw'] ?? $row['Start_Time'] ?? null;
        $lunchStart = $row['LunchStartRaw'] ?? '12:00:00';
        $lunchEnd = $row['LunchEndRaw'] ?? '13:00:00';
        $shiftEnd = $row['ShiftEndRaw'] ?? $row['End_Time'] ?? null;

        $workingHours = calculate_site_working_hours($shiftStart, $lunchStart, $lunchEnd, $shiftEnd);
        $row['Working_Hours'] = $workingHours;
        $row['Total_Hours'] = $workingHours > 0
            ? rtrim(rtrim(number_format($workingHours, 1, '.', ''), '0'), '.') . ' hrs'
            : null;

        unset($row['ShiftStartRaw'], $row['LunchStartRaw'], $row['LunchEndRaw'], $row['ShiftEndRaw']);

        return $row;
    }
}

if (!function_exists('attendance_lunch_columns_exist')) {
    function attendance_lunch_columns_exist(mysqli $conn): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $check = $conn->query("SHOW COLUMNS FROM attendance LIKE 'Lunch_Out'");
        $cached = $check && $check->num_rows > 0;
        return $cached;
    }
}
