<?php

require_once __DIR__ . '/site_schedule_helpers.php';

if (!function_exists('overtime_valid_types')) {
    function overtime_valid_types(): array
    {
        return [
            'Regular Overtime',
            'Lunch Overtime',
            'Emergency Overtime',
            'Weekend Overtime',
        ];
    }
}

if (!function_exists('overtime_valid_statuses')) {
    function overtime_valid_statuses(): array
    {
        return ['Pending', 'Approved', 'Rejected'];
    }
}

if (!function_exists('calculate_overtime_total_hours')) {
    function calculate_overtime_total_hours(?string $start, ?string $end): float
    {
        $startMin = site_schedule_time_to_minutes($start);
        $endMin = site_schedule_time_to_minutes($end);

        if ($startMin === null || $endMin === null || $endMin <= $startMin) {
            return 0.0;
        }

        return round(($endMin - $startMin) / 60, 2);
    }
}

if (!function_exists('minutes_to_site_time')) {
    function minutes_to_site_time(int $minutes): string
    {
        $minutes = max(0, $minutes);
        $hours = intdiv($minutes, 60) % 24;
        $mins = $minutes % 60;

        return sprintf('%02d:%02d:00', $hours, $mins);
    }
}

if (!function_exists('worker_assigned_to_site')) {
    function worker_assigned_to_site(mysqli $conn, int $workerId, int $siteId): bool
    {
        if ($workerId <= 0 || $siteId <= 0) {
            return false;
        }

        $tables = [
            ['workerassignment', 'WorkerID', 'SiteID'],
            ['worker_assignment', 'WorkerID', 'SiteID'],
        ];

        foreach ($tables as [$table, $workerColumn, $siteColumn]) {
            $exists = $conn->query("SHOW TABLES LIKE '{$table}'");
            if ($exists === false || $exists->num_rows === 0) {
                continue;
            }

            $sql = "SELECT 1 FROM {$table} WHERE {$workerColumn} = ? AND {$siteColumn} = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                continue;
            }

            $stmt->bind_param('ii', $workerId, $siteId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($row !== null) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('format_overtime_request_row')) {
    function format_overtime_request_row(array $row): array
    {
        $workerName = trim((string) (($row['First_Name'] ?? '') . ' ' . ($row['Last_Name'] ?? '')));
        if ($workerName === '') {
            $workerName = 'Unknown Worker';
        }

        $submittedByName = trim((string) ($row['submitted_by_name'] ?? ''));
        if ($submittedByName === '') {
            $submittedByName = (string) ($row['submitted_by_email'] ?? 'Unknown');
        }

        $approvedByName = trim((string) ($row['approved_by_name'] ?? ''));
        if ($approvedByName === '' && !empty($row['ApprovedBy'])) {
            $approvedByName = (string) ($row['approved_by_email'] ?? 'Unknown');
        }

        return [
            'id' => (int) ($row['OvertimeID'] ?? 0),
            'worker_id' => (int) ($row['WorkerID'] ?? 0),
            'worker_name' => $workerName,
            'site_id' => (int) ($row['SiteID'] ?? 0),
            'site_name' => (string) ($row['Site_Name'] ?? 'Unknown Site'),
            'request_date' => (string) ($row['RequestDate'] ?? ''),
            'request_date_label' => !empty($row['RequestDate'])
                ? date('n/j/Y', strtotime((string) $row['RequestDate']))
                : '',
            'overtime_type' => (string) ($row['OvertimeType'] ?? ''),
            'overtime_start' => substr((string) ($row['OvertimeStart'] ?? ''), 0, 5),
            'overtime_end' => substr((string) ($row['OvertimeEnd'] ?? ''), 0, 5),
            'total_hours' => (float) ($row['TotalHours'] ?? 0),
            'reason' => (string) ($row['Reason'] ?? ''),
            'submitted_by' => (int) ($row['SubmittedBy'] ?? 0),
            'submitted_by_name' => $submittedByName,
            'status' => (string) ($row['Status'] ?? 'Pending'),
            'approved_by' => !empty($row['ApprovedBy']) ? (int) $row['ApprovedBy'] : null,
            'approved_by_name' => $approvedByName,
            'approved_date' => (string) ($row['ApprovedDate'] ?? ''),
            'created_at' => (string) ($row['CreatedAt'] ?? ''),
            'lunch_schedule_label' => (string) ($row['lunch_schedule_label'] ?? ''),
        ];
    }
}

if (!function_exists('get_overtime_lunch_schedule_label')) {
    function get_overtime_lunch_schedule_label(mysqli $conn, int $siteId): string
    {
        $schedule = get_site_schedule_row($conn, $siteId);
        if (!$schedule) {
            return '';
        }

        $lunchStart = substr((string) ($schedule['LunchStart'] ?? '12:00:00'), 0, 5);
        $lunchEnd = substr((string) ($schedule['LunchEnd'] ?? '13:00:00'), 0, 5);

        return "{$lunchStart} - {$lunchEnd}";
    }
}

if (!function_exists('overtime_table_exists')) {
    function overtime_table_exists(mysqli $conn): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $check = $conn->query("SHOW TABLES LIKE 'overtime_requests'");
        $cached = $check && $check->num_rows > 0;
        return $cached;
    }
}

if (!function_exists('mobile_normalize_overtime_type')) {
    function mobile_normalize_overtime_type(string $raw): string
    {
        $trimmed = trim($raw);
        $aliases = [
            'Regular OT' => 'Regular Overtime',
            'Holiday OT' => 'Emergency Overtime',
            'Rest Day OT' => 'Weekend Overtime',
        ];

        if (isset($aliases[$trimmed])) {
            return $aliases[$trimmed];
        }

        if (in_array($trimmed, overtime_valid_types(), true)) {
            return $trimmed;
        }

        return '';
    }
}

if (!function_exists('overtime_resolve_worker_id')) {
    function overtime_resolve_worker_id(mysqli $conn, int $workerId, string $workerName, int $siteId): int
    {
        if ($workerId > 0) {
            return $workerId;
        }

        $workerName = trim($workerName);
        if ($workerName === '' || $siteId <= 0) {
            return 0;
        }

        $stmt = $conn->prepare("
            SELECT w.WorkerID
            FROM workerassignment wa
            INNER JOIN worker w ON w.WorkerID = wa.WorkerID
            WHERE wa.SiteID = ?
              AND LOWER(TRIM(CONCAT(w.First_Name, ' ', w.Last_Name))) = LOWER(?)
            LIMIT 1
        ");
        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param('is', $siteId, $workerName);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int) ($row['WorkerID'] ?? 0);
    }
}

if (!function_exists('overtime_site_shift_end')) {
    function overtime_site_shift_end(mysqli $conn, int $siteId): string
    {
        $default = '17:00:00';
        $stmt = $conn->prepare('SELECT ShiftEnd FROM projectsite WHERE SiteID = ? LIMIT 1');
        if (!$stmt) {
            return $default;
        }

        $stmt->bind_param('i', $siteId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $shiftEnd = normalize_site_time_input((string) ($row['ShiftEnd'] ?? ''));
        return $shiftEnd ?? $default;
    }
}
