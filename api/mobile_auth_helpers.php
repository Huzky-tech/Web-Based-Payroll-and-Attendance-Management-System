<?php
/**
 * Shared helpers for mobile Timekeeper API endpoints.
 * Compatible with capstone deployments that use timekeeper_assignment_helpers.php.
 */

require_once __DIR__ . '/timekeeper_assignment_helpers.php';

if (!function_exists('mobile_json_headers')) {
    function mobile_json_headers(): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
    }
}

if (!function_exists('mobile_handle_options')) {
    function mobile_handle_options(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}

if (!function_exists('mobile_json_error')) {
    function mobile_json_error(string $message, int $httpCode = 200): void
    {
        if ($httpCode !== 200) {
            http_response_code($httpCode);
        }

        echo json_encode([
            'status' => 'error',
            'message' => $message,
        ]);
        exit;
    }
}

if (!function_exists('mobile_json_success')) {
    function mobile_json_success(array $payload): void
    {
        echo json_encode(array_merge(['status' => 'success'], $payload));
        exit;
    }
}

if (!function_exists('mobile_get_request_payload')) {
    function mobile_get_request_payload(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, true);

        // Merge query, form, and JSON body. Empty $_POST on GET must not hide $_GET.
        return array_merge(
            is_array($_GET) ? $_GET : [],
            is_array($_POST) ? $_POST : [],
            is_array($decoded) ? $decoded : []
        );
    }
}

if (!function_exists('mobile_resolve_timekeeper_id')) {
    function mobile_resolve_timekeeper_id(array $payload): int
    {
        return (int) ($payload['TimekeeperID'] ?? $payload['timekeeper_id'] ?? $payload['timekeeperId'] ?? $payload['user_id'] ?? 0);
    }
}

if (!function_exists('mobile_require_timekeeper_assignment')) {
    function mobile_require_timekeeper_assignment(mysqli $conn, int $timekeeperId): array
    {
        if ($timekeeperId <= 0) {
            mobile_json_error('TimekeeperID is required.');
        }

        if (!validate_timekeeper_is_role($conn, $timekeeperId)) {
            mobile_json_error('Invalid Timekeeper account.');
        }

        $assignment = get_active_timekeeper_assignment($conn, $timekeeperId);
        if (!$assignment || (int) ($assignment['SiteID'] ?? 0) <= 0) {
            mobile_json_error('No site assigned to this Timekeeper.');
        }

        return $assignment;
    }
}

if (!function_exists('mobile_parse_timekeeper_id')) {
    function mobile_parse_timekeeper_id(array $sources): int
    {
        foreach ($sources as $source) {
            if (!is_array($source)) {
                continue;
            }
            $id = mobile_resolve_timekeeper_id($source);
            if ($id > 0) {
                return $id;
            }
        }

        mobile_json_error('TimekeeperID is required.');
    }
}

if (!function_exists('mobile_validate_timekeeper')) {
    function mobile_validate_timekeeper(mysqli $conn, int $userId): array
    {
        mobile_require_timekeeper_assignment($conn, $userId);
        return ['id' => $userId];
    }
}

if (!function_exists('mobile_require_assigned_site')) {
    function mobile_require_assigned_site(mysqli $conn, int $userId): array
    {
        $assignment = mobile_require_timekeeper_assignment($conn, $userId);
        $siteId = (int) ($assignment['SiteID'] ?? 0);

        $assignedWorkers = 0;
        $countStmt = $conn->prepare(
            'SELECT COUNT(*) AS total FROM workerassignment WHERE SiteID = ?'
        );
        if ($countStmt) {
            $countStmt->bind_param('i', $siteId);
            $countStmt->execute();
            $countRow = $countStmt->get_result()->fetch_assoc();
            $countStmt->close();
            $assignedWorkers = (int) ($countRow['total'] ?? 0);
        }

        return [
            'site_id' => $siteId,
            'site_name' => (string) ($assignment['Site_Name'] ?? ''),
            'location' => (string) ($assignment['Location'] ?? ''),
            'shift_start' => substr((string) ($assignment['ShiftStart'] ?? '07:00:00'), 0, 5),
            'lunch_start' => substr((string) ($assignment['LunchStart'] ?? '12:00:00'), 0, 5),
            'lunch_end' => substr((string) ($assignment['LunchEnd'] ?? '13:00:00'), 0, 5),
            'shift_end' => substr((string) ($assignment['ShiftEnd'] ?? '17:00:00'), 0, 5),
            'assigned_workers' => $assignedWorkers,
        ];
    }
}

if (!function_exists('mobile_require_site_access')) {
    function mobile_require_site_access(mysqli $conn, int $userId, int $siteId): void
    {
        $assigned = mobile_require_assigned_site($conn, $userId);
        if ((int) $assigned['site_id'] !== $siteId) {
            mobile_json_error('Access denied for this site.', 403);
        }
    }
}

if (!function_exists('mobile_parse_attendance_date')) {
    function mobile_parse_attendance_date(string $raw): string
    {
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $raw, $matches)) {
            return $matches[1];
        }

        $timestamp = strtotime($raw);
        return $timestamp !== false ? date('Y-m-d', $timestamp) : '';
    }
}

if (!function_exists('mobile_parse_attendance_time')) {
    function mobile_parse_attendance_time(string $raw): string
    {
        if (preg_match('/T(\d{2}):(\d{2}):(\d{2})/', $raw, $matches)) {
            return $matches[1] . ':' . $matches[2] . ':' . $matches[3];
        }

        if (preg_match('/^(\d{2}):(\d{2})(?::(\d{2}))?$/', trim($raw), $matches)) {
            $seconds = $matches[3] ?? '00';
            return $matches[1] . ':' . $matches[2] . ':' . $seconds;
        }

        $timestamp = strtotime($raw);
        return $timestamp !== false ? date('H:i:s', $timestamp) : '00:00:00';
    }
}

if (!function_exists('mobile_resolve_attendance_status')) {
    function mobile_resolve_attendance_status(
        mysqli $conn,
        int $siteId,
        string $date,
        string $timeIn,
        string $clientStatus
    ): string {
        $shiftStart = '07:00:00';
        $stmt = $conn->prepare('SELECT ShiftStart FROM projectsite WHERE SiteID = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $siteId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!empty($row['ShiftStart'])) {
                $shiftStart = mobile_parse_attendance_time((string) $row['ShiftStart']);
            }
        }

        $timeInNormalized = mobile_parse_attendance_time($timeIn);
        $timeInTs = strtotime("{$date} {$timeInNormalized}");
        $shiftTs = strtotime("{$date} {$shiftStart}");
        if ($timeInTs !== false && $shiftTs !== false && $timeInTs > $shiftTs) {
            return 'Late';
        }

        if (strcasecmp($clientStatus, 'Late') === 0) {
            return 'Late';
        }

        if (in_array($clientStatus, ['Present', 'Absent'], true)) {
            return $clientStatus;
        }

        return 'Present';
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
