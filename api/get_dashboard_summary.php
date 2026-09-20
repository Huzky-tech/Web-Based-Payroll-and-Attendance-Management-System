<?php
header('Content-Type: application/json');

include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/payroll_approval_helpers.php';

$role = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR']);
$userId = (int) ($_SESSION['user_id'] ?? 0);
require_once __DIR__ . '/attendance_schema_helpers.php';
attendance_schema_ensure_table($conn);

function dashboard_json_error(string $message, int $statusCode = 500): void
{
    http_response_code($statusCode);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

function dashboard_scalar(mysqli $conn, string $sql, string $types = '', array $params = [])
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_row() : null;
    $stmt->close();

    return $row[0] ?? null;
}

function derive_pay_period(string $payPeriods): array
{
    $today = new DateTime('today');
    $normalized = strtolower($payPeriods);

    if (strpos($normalized, 'weekly') !== false || trim($normalized) === 'week') {
        $year = (int) $today->format('o');
        $week = (int) $today->format('W');
        $start = new DateTime();
        $start->setISODate($year, $week, 1);
        $end = new DateTime();
        $end->setISODate($year, $week, 7);
    } elseif (strpos($normalized, 'semi') !== false) {
        if ((int) $today->format('d') <= 15) {
            $start = new DateTime($today->format('Y-m-01'));
            $end = new DateTime($today->format('Y-m-15'));
        } else {
            $start = new DateTime($today->format('Y-m-16'));
            $end = new DateTime($today->format('Y-m-t'));
        }
    } else {
        $start = new DateTime($today->format('Y-m-01'));
        $end = new DateTime($today->format('Y-m-t'));
    }

    return [
        'start' => $start->format('Y-m-d'),
        'end' => $end->format('Y-m-d'),
        'label' => $start->format('M d') . ' - ' . $end->format('M d, Y')
    ];
}

function get_payroll_staff_id(mysqli $conn, int $userId): int
{
    return (int) (dashboard_scalar(
        $conn,
        "SELECT PayrollStaff_ID FROM payrollstaff WHERE UserID = ? LIMIT 1",
        'i',
        [$userId]
    ) ?? 0);
}

function get_scoped_sites(mysqli $conn, string $role, int $userId, string $today): array
{
    $joins = '';
    $where = [];
    $types = 's';
    $params = [$today];

    if ($role === 'Payroll Staff') {
        $payrollStaffId = get_payroll_staff_id($conn, $userId);
        if ($payrollStaffId <= 0) {
            return [];
        }

        $joins .= " INNER JOIN payrollstaffassignment psa_scope ON psa_scope.SiteID = ps.SiteID ";
        $where[] = "psa_scope.PayrollStaff_ID = ?";
        $types .= 'i';
        $params[] = $payrollStaffId;
    }

    $whereSql = '';
    if (!empty($where)) {
        $whereSql = 'WHERE ' . implode(' AND ', $where);
    }

    $sql = "
        SELECT
            ps.SiteID,
            ps.Site_Name,
            ps.Location,
            ps.Coordinates,
            ps.Start_Date,
            ps.End_Date,
            ps.Required_Workers,
            ps.Site_Manager,
            ps.Status,
            TIME_FORMAT(sc.ShiftStart, '%H:%i') AS Start_Time,
            TIME_FORMAT(sc.ShiftEnd, '%H:%i') AS End_Time,
            CASE
                WHEN sc.ShiftStart IS NOT NULL AND sc.ShiftEnd IS NOT NULL THEN
                    CONCAT(TIMESTAMPDIFF(HOUR, sc.ShiftStart, sc.ShiftEnd), ' hrs')
                ELSE NULL
            END AS Total_Hours,
            COUNT(DISTINCT wa.WorkerID) AS assigned_workers,
            SUM(CASE WHEN a.AttendanceStatus = 'Present' THEN 1 ELSE 0 END) AS present_count,
            SUM(CASE WHEN a.IsLate = 1 THEN 1 ELSE 0 END) AS late_count,
            SUM(CASE WHEN a.AttendanceStatus = 'Absent' THEN 1 ELSE 0 END) AS absent_marked_count
        FROM projectsite ps
        {$joins}
        LEFT JOIN (
            SELECT ss.SiteID, ss.ShiftStart, ss.ShiftEnd
            FROM site_schedule ss
            INNER JOIN (
                SELECT SiteID, MIN(Site_ScheduleID) AS FirstScheduleID
                FROM site_schedule
                GROUP BY SiteID
            ) first_schedule
                ON first_schedule.FirstScheduleID = ss.Site_ScheduleID
        ) sc ON sc.SiteID = ps.SiteID
        LEFT JOIN workerassignment wa ON wa.SiteID = ps.SiteID
        LEFT JOIN attendance a
            ON a.SiteID = ps.SiteID
            AND a.WorkerID = wa.WorkerID
            AND a.Date = ?
        {$whereSql}
        GROUP BY ps.SiteID, ps.Site_Name, ps.Location, ps.Coordinates, ps.Start_Date, ps.End_Date, ps.Required_Workers, ps.Site_Manager, ps.Status, sc.ShiftStart, sc.ShiftEnd
        ORDER BY ps.Site_Name
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $sites = [];
    while ($row = $result->fetch_assoc()) {
        $assignedWorkers = (int) ($row['assigned_workers'] ?? 0);
        $presentCount = (int) ($row['present_count'] ?? 0);
        $lateCount = (int) ($row['late_count'] ?? 0);
        $attendedCount = $presentCount;
        $absentCount = max(0, $assignedWorkers - $attendedCount);
        $requiredWorkers = (int) ($row['Required_Workers'] ?? 0);
        $attendanceRate = $assignedWorkers > 0
            ? round(($attendedCount / $assignedWorkers) * 100)
            : 0;

        $row['assigned_workers'] = $assignedWorkers;
        $row['present_count'] = $presentCount;
        $row['late_count'] = $lateCount;
        $row['absent_count'] = $absentCount;
        $row['attended_count'] = $attendedCount;
        $row['Required_Workers'] = $requiredWorkers;
        $row['attendance_rate'] = $attendanceRate;
        $row['needs_workers'] = max(0, $requiredWorkers - $assignedWorkers);
        $row['is_active'] = strtolower((string) ($row['Status'] ?? '')) === 'active';
        $sites[] = $row;
    }

    $stmt->close();

    return $sites;
}

function get_scope_filter(array $siteIds, string $column): array
{
    if (empty($siteIds)) {
        return [
            'sql' => ' AND 1 = 0',
            'types' => '',
            'params' => []
        ];
    }

    $placeholders = implode(',', array_fill(0, count($siteIds), '?'));

    return [
        'sql' => " AND {$column} IN ({$placeholders})",
        'types' => str_repeat('i', count($siteIds)),
        'params' => array_map('intval', $siteIds)
    ];
}

function derive_week_period(): array
{
    $today = new DateTime('today');
    $start = clone $today;
    $start->modify('monday this week');
    if ($today->format('N') === '1' && $today->format('H:i') === '00:00') {
        $start = clone $today;
    }
    $end = clone $start;
    $end->modify('sunday this week');

    return [
        'start' => $start->format('Y-m-d'),
        'end' => $end->format('Y-m-d'),
        'label' => $start->format('M d') . ' - ' . $end->format('M d, Y')
    ];
}

function get_payroll_totals(mysqli $conn, array $sites, array $payPeriod, float $deductionRate, float $overtimeRate): array
{
    if (empty($sites)) {
        return [
            'gross' => 0,
            'regular' => 0,
            'overtime' => 0,
            'deductions' => 0,
            'net' => 0,
            'processed' => 0,
            'pending' => 0
        ];
    }

    $siteIds = array_map(static fn($site) => (int) $site['SiteID'], $sites);
    $scope = get_scope_filter($siteIds, 'SiteID');

    $processed = 0;
    $recordSql = "
        SELECT COUNT(DISTINCT SiteID) AS processed_count
        FROM payroll_records
        WHERE Period_start = ? AND Period_end = ?
        {$scope['sql']}
    ";
    $processedStmt = $conn->prepare($recordSql);
    if ($processedStmt) {
        $types = 'ss' . $scope['types'];
        $params = array_merge([$payPeriod['start'], $payPeriod['end']], $scope['params']);
        $processedStmt->bind_param($types, ...$params);
        $processedStmt->execute();
        $processedRow = $processedStmt->get_result()->fetch_assoc();
        $processed = (int) ($processedRow['processed_count'] ?? 0);
        $processedStmt->close();
    }

    $workerStmt = $conn->prepare("
        SELECT
            w.RateType,
            w.RateAmount,
            COALESCE(SUM(CASE
                WHEN a.Time_In IS NOT NULL AND a.Time_In <> '' AND a.Time_In <> '00:00:00'
                 AND a.Time_Out IS NOT NULL AND a.Time_Out <> '' AND a.Time_Out <> '00:00:00'
                THEN a.Hours_Worked ELSE 0
            END), 0) AS total_hours,
            COALESCE(SUM(CASE
                WHEN a.Time_In IS NOT NULL AND a.Time_In <> '' AND a.Time_In <> '00:00:00'
                 AND a.Time_Out IS NOT NULL AND a.Time_Out <> '' AND a.Time_Out <> '00:00:00'
                THEN a.Overtime_Hours ELSE 0
            END), 0) AS overtime_hours
        FROM workerassignment wa
        INNER JOIN worker w ON wa.WorkerID = w.WorkerID
        LEFT JOIN attendance a
            ON a.WorkerID = w.WorkerID
            AND a.SiteID = wa.SiteID
            AND a.Date BETWEEN ? AND ?
        WHERE wa.SiteID = ?
        GROUP BY w.WorkerID, w.RateType, w.RateAmount
    ");

    $gross = 0.0;
    $regular = 0.0;
    $overtimePay = 0.0;
    $deductions = 0.0;
    $net = 0.0;

    if ($workerStmt) {
        foreach ($sites as $site) {
            $siteId = (int) $site['SiteID'];
            $workerStmt->bind_param('ssi', $payPeriod['start'], $payPeriod['end'], $siteId);
            $workerStmt->execute();
            $workerResult = $workerStmt->get_result();

            while ($worker = $workerResult->fetch_assoc()) {
                $rateType = strtolower((string) ($worker['RateType'] ?? 'hourly'));
                $rateAmount = (float) ($worker['RateAmount'] ?? 0);
                $totalHours = (float) ($worker['total_hours'] ?? 0);
                $overtimeHours = (float) ($worker['overtime_hours'] ?? 0);
                $regularHours = max(0, $totalHours - $overtimeHours);

                if ($rateType === 'salary') {
                    $workerRegular = $rateAmount;
                    $workerOvertime = 0.0;
                } else {
                    $workerRegular = $regularHours * $rateAmount;
                    $workerOvertime = $overtimeHours * $rateAmount * $overtimeRate;
                }

                $grossPay = $workerRegular + $workerOvertime;
                $workerDeductions = round($grossPay * $deductionRate, 2);
                $workerNet = round($grossPay - $workerDeductions, 2);

                $gross += $grossPay;
                $regular += $workerRegular;
                $overtimePay += $workerOvertime;
                $deductions += $workerDeductions;
                $net += $workerNet;
            }
        }

        $workerStmt->close();
    }

    return [
        'gross' => round($gross, 2),
        'regular' => round($regular, 2),
        'overtime' => round($overtimePay, 2),
        'deductions' => round($deductions, 2),
        'net' => round($net, 2),
        'processed' => $processed,
        'pending' => max(0, count($sites) - $processed)
    ];
}

function get_active_timekeepers_count(mysqli $conn): int
{
    $check = $conn->query("SHOW TABLES LIKE 'timekeeper'");
    if (!$check || $check->num_rows === 0) {
        return 0;
    }

    return (int) (dashboard_scalar(
        $conn,
        "SELECT COUNT(DISTINCT tk.Timekeeper_ID)
         FROM timekeeper tk
         INNER JOIN users u ON u.id = tk.UserID
         WHERE LOWER(COALESCE(u.status, 'active')) = 'active'"
    ) ?? 0);
}

function get_pending_overtime_summary(mysqli $conn): array
{
    $check = $conn->query("SHOW TABLES LIKE 'overtime_requests'");
    if (!$check || $check->num_rows === 0) {
        return ['count' => 0, 'items' => []];
    }

    $count = (int) (dashboard_scalar(
        $conn,
        "SELECT COUNT(*) FROM overtime_requests WHERE Status = 'Pending'"
    ) ?? 0);

    $items = [];
    $stmt = $conn->prepare("
        SELECT
            ot.OvertimeID,
            ot.TotalHours,
            ot.Status,
            TRIM(CONCAT(w.First_Name, ' ', w.Last_Name)) AS worker_name,
            ps.Site_Name
        FROM overtime_requests ot
        INNER JOIN worker w ON w.WorkerID = ot.WorkerID
        INNER JOIN projectsite ps ON ps.SiteID = ot.SiteID
        WHERE ot.Status = 'Pending'
        ORDER BY ot.OvertimeID DESC
        LIMIT 8
    ");

    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'id' => (int) ($row['OvertimeID'] ?? 0),
                'worker_name' => $row['worker_name'] ?? 'Unknown Worker',
                'site_name' => $row['Site_Name'] ?? 'Unknown Site',
                'hours' => round((float) ($row['TotalHours'] ?? 0), 2),
                'status' => $row['Status'] ?? 'Pending'
            ];
        }
        $stmt->close();
    }

    return ['count' => $count, 'items' => $items];
}

function get_today_attendance_records(mysqli $conn, string $today, array $siteIds, int $limit = 12): array
{
    if (empty($siteIds)) {
        return [];
    }

    $scope = get_scope_filter($siteIds, 'a.SiteID');
    $sql = "
        SELECT
            TRIM(CONCAT(w.First_Name, ' ', w.Last_Name)) AS worker_name,
            ps.Site_Name AS site_name,
            a.Time_In,
            COALESCE(a.AttendanceStatus, 'Absent') AS status, COALESCE(a.IsLate, 0) AS is_late
        FROM attendance a
        INNER JOIN worker w ON w.WorkerID = a.WorkerID
        INNER JOIN projectsite ps ON ps.SiteID = a.SiteID
        WHERE a.Date = ?
        {$scope['sql']}
        ORDER BY a.Time_In DESC, a.AttendanceID DESC
        LIMIT ?
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $types = 's' . $scope['types'] . 'i';
    $params = array_merge([$today], $scope['params'], [$limit]);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $records = [];
    while ($row = $result->fetch_assoc()) {
        $timeIn = $row['Time_In'] ?? '';
        $records[] = [
            'worker_name' => $row['worker_name'] ?? 'Unknown Worker',
            'site_name' => $row['site_name'] ?? 'Unknown Site',
            'time_in' => $timeIn ? date('g:i A', strtotime((string) $timeIn)) : '--',
            'status' => $row['status'] ?? 'Absent'
        ];
    }

    $stmt->close();

    return $records;
}

function get_offline_attendance_count(mysqli $conn, string $today, array $siteIds): int
{
    if (empty($siteIds)) {
        return 0;
    }

    $scope = get_scope_filter($siteIds, 'a.SiteID');
    $manualCount = (int) (dashboard_scalar(
        $conn,
        "SELECT COUNT(DISTINCT a.AttendanceID)
         FROM attendance a
         INNER JOIN audit_logs al
            ON al.Action = 'Update Attendance'
            AND al.Details LIKE CONCAT('%AttendanceID ', a.AttendanceID, '%')
         WHERE a.Date = ?
           AND DATE(al.Date) = ?
           {$scope['sql']}",
        'ss' . $scope['types'],
        array_merge([$today, $today], $scope['params'])
    ) ?? 0);

    if ($manualCount > 0) {
        return $manualCount;
    }

    return (int) (dashboard_scalar(
        $conn,
        "SELECT COUNT(*)
         FROM attendance a
         WHERE a.Date = ?
           AND a.AttendanceStatus IN ('Present', 'Late')
           AND a.Time_In = '00:00:00'
           {$scope['sql']}",
        's' . $scope['types'],
        array_merge([$today], $scope['params'])
    ) ?? 0);
}

function get_activity_timeline(mysqli $conn, int $limit = 10): array
{
    $activitySql = "
        SELECT
            al.Action,
            al.Details,
            DATE_FORMAT(al.Date, '%b %d, %Y %h:%i %p') AS activity_time,
            al.Date AS sort_date
        FROM audit_logs al
        ORDER BY al.Date DESC
        LIMIT ?
    ";
    $stmt = $conn->prepare($activitySql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $iconMap = [
        'Add Worker' => 'worker',
        'Assign Worker to Site' => 'worker',
        'Clock In' => 'attendance',
        'Clock Out' => 'attendance',
        'Update Attendance' => 'attendance',
        'Generate' => 'qr',
        'QR' => 'qr',
        'Process Payroll' => 'payroll',
        'Payroll' => 'payroll',
        'Overtime Approved' => 'overtime',
        'Overtime Rejected' => 'overtime',
        'Overtime Request' => 'overtime',
        'Site Archived' => 'site',
        'Site Restored' => 'site'
    ];

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $action = (string) ($row['Action'] ?? 'Activity');
        $type = 'system';
        foreach ($iconMap as $needle => $mappedType) {
            if (stripos($action, $needle) !== false) {
                $type = $mappedType;
                break;
            }
        }

        $items[] = [
            'action' => $action,
            'details' => $row['Details'] ?? '-',
            'time' => $row['activity_time'] ?? '',
            'type' => $type
        ];
    }

    $stmt->close();

    return $items;
}

function enrich_sites_with_timekeeper(mysqli $conn, array $sites): array
{
    $columnCheck = $conn->query("SHOW COLUMNS FROM projectsite LIKE 'Timekeeper_UserID'");
    $hasTimekeeperColumn = $columnCheck && $columnCheck->num_rows > 0;

    foreach ($sites as &$site) {
        $siteId = (int) ($site['SiteID'] ?? 0);
        $timekeeperName = 'Not assigned';

        if ($hasTimekeeperColumn) {
            $stmt = $conn->prepare("
                SELECT COALESCE(
                    u.full_name,
                    (
                        SELECT TRIM(CONCAT(w.First_Name, ' ', w.Last_Name))
                        FROM workerassignment wa
                        INNER JOIN worker w ON w.WorkerID = wa.WorkerID
                        WHERE wa.SiteID = ?
                          AND LOWER(wa.Role_On_Site) LIKE '%timekeeper%'
                        LIMIT 1
                    )
                ) AS timekeeper_name
                FROM projectsite ps
                LEFT JOIN users u ON u.id = ps.Timekeeper_UserID
                WHERE ps.SiteID = ?
                LIMIT 1
            ");
            if ($stmt) {
                $stmt->bind_param('ii', $siteId, $siteId);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                if (!empty($row['timekeeper_name'])) {
                    $timekeeperName = $row['timekeeper_name'];
                }
                $stmt->close();
            }
        } else {
            $stmt = $conn->prepare("
                SELECT TRIM(CONCAT(w.First_Name, ' ', w.Last_Name)) AS timekeeper_name
                FROM workerassignment wa
                INNER JOIN worker w ON w.WorkerID = wa.WorkerID
                WHERE wa.SiteID = ?
                  AND LOWER(wa.Role_On_Site) LIKE '%timekeeper%'
                LIMIT 1
            ");
            if ($stmt) {
                $stmt->bind_param('i', $siteId);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                if (!empty($row['timekeeper_name'])) {
                    $timekeeperName = $row['timekeeper_name'];
                }
                $stmt->close();
            }
        }

        $site['Timekeeper'] = $timekeeperName;
    }
    unset($site);

    return $sites;
}

function get_recent_reports(mysqli $conn, array $siteIds): array
{
    if (empty($siteIds)) {
        return [];
    }

    $scopeReports = get_scope_filter($siteIds, 'tr.SiteID');
    $scopeBroken = get_scope_filter($siteIds, 'be.SiteID');

    $reportSql = "
        SELECT
            report_type,
            site_name,
            reported_by,
            report_date,
            status,
            subject,
            report_id
        FROM (
            SELECT
                COALESCE(NULLIF(tr.ReportType, ''), NULLIF(tr.DelayType, ''), 'Site Report') AS report_type,
                ps.Site_Name AS site_name,
                COALESCE(u.full_name, u.email, 'Unknown User') AS reported_by,
                COALESCE(tr.CreatedAt, CONCAT(tr.ReportDate, ' 00:00:00')) AS report_date,
                COALESCE(NULLIF(tr.Status, ''), 'Pending') AS status,
                tr.Subject AS subject,
                tr.TK_ReportsID AS report_id
            FROM timekeeper_reports tr
            INNER JOIN projectsite ps ON ps.SiteID = tr.SiteID
            INNER JOIN users u ON u.id = tr.UserID
            WHERE 1 = 1 {$scopeReports['sql']}

            UNION ALL

            SELECT
                CONCAT('Broken Equipment: ', be.Equipment_Name) AS report_type,
                ps.Site_Name AS site_name,
                COALESCE(u.full_name, u.email, 'Unknown User') AS reported_by,
                be.Date AS report_date,
                'Pending' AS status,
                be.Equipment_Name AS subject,
                0 AS report_id
            FROM brokenequipment be
            INNER JOIN projectsite ps ON ps.SiteID = be.SiteID
            INNER JOIN timekeeper tk ON tk.Timekeeper_ID = be.Timekeeper_ID
            INNER JOIN users u ON u.id = tk.UserID
            WHERE 1 = 1 {$scopeBroken['sql']}
        ) combined_reports
        ORDER BY report_date DESC
        LIMIT 5
    ";

    $stmt = $conn->prepare($reportSql);
    if (!$stmt) {
        return [];
    }

    $types = $scopeReports['types'] . $scopeBroken['types'];
    $params = array_merge($scopeReports['params'], $scopeBroken['params']);

    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];

    while ($row = $result->fetch_assoc()) {
        $status = trim((string) ($row['status'] ?? 'Pending'));
        $reportDate = (string) ($row['report_date'] ?? '');
        $isNew = $status === 'Pending'
            && $reportDate !== ''
            && date('Y-m-d', strtotime($reportDate)) === date('Y-m-d');

        $items[] = [
            'type' => $row['report_type'] ?? 'Report',
            'site' => $row['site_name'] ?? 'Unknown Site',
            'reported_by' => $row['reported_by'] ?? 'Unknown User',
            'date' => $reportDate !== '' ? date('Y-m-d', strtotime($reportDate)) : null,
            'status' => $status === '' ? 'Pending' : $status,
            'subject' => $row['subject'] ?? '',
            'report_id' => (int) ($row['report_id'] ?? 0),
            'is_new' => $isNew,
        ];
    }

    $stmt->close();

    return $items;
}

function get_timekeeper_report_stats(mysqli $conn, array $siteIds): array
{
    if (empty($siteIds)) {
        return [
            'total' => 0,
            'pending' => 0,
            'reviewed' => 0,
            'resolved' => 0,
            'submitted_today' => 0,
        ];
    }

    require_once __DIR__ . '/timekeeper_report_helpers.php';
    tk_report_ensure_schema($conn);

    if (!tk_report_table_exists($conn)) {
        return [
            'total' => 0,
            'pending' => 0,
            'reviewed' => 0,
            'resolved' => 0,
            'submitted_today' => 0,
        ];
    }

    $scope = get_scope_filter($siteIds, 'tr.SiteID');
    return tk_report_get_stats($conn, '1=1' . $scope['sql'], $scope['types'], $scope['params']);
}

function get_pending_employee_approvals_count(mysqli $conn): int
{
    return (int) (dashboard_scalar(
        $conn,
        "SELECT COUNT(*)
         FROM worker w
         LEFT JOIN (
             SELECT a.WorkerID, a.Approval_Status
             FROM approvals a
             INNER JOIN (
                 SELECT WorkerID, MAX(ApprovalID) AS ApprovalID
                 FROM approvals
                 GROUP BY WorkerID
             ) latest ON latest.ApprovalID = a.ApprovalID
         ) latest_approval ON latest_approval.WorkerID = w.WorkerID
         WHERE COALESCE(latest_approval.Approval_Status, 'Pending') <> 'Approved'"
    ) ?? 0);
}

function get_employee_status_breakdown(mysqli $conn): array
{
    $breakdown = ['active' => 0, 'inactive' => 0, 'archived' => 0];
    $result = $conn->query("
        SELECT LOWER(COALESCE(ws.Status, 'inactive')) AS status_name, COUNT(*) AS total
        FROM worker w
        LEFT JOIN workerstatus ws ON ws.WorkerStatusID = w.WorkerStatusID
        GROUP BY LOWER(COALESCE(ws.Status, 'inactive'))
    ");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $status = (string) ($row['status_name'] ?? '');
            $count = (int) ($row['total'] ?? 0);
            if ($status === 'active' || $status === 'onleave') {
                $breakdown['active'] += $count;
            } elseif ($status === 'archived') {
                $breakdown['archived'] += $count;
            } else {
                $breakdown['inactive'] += $count;
            }
        }
    }

    return $breakdown;
}

function get_recent_employees(mysqli $conn, int $limit = 6): array
{
    $stmt = $conn->prepare("
        SELECT
            w.WorkerID,
            TRIM(CONCAT(w.First_Name, ' ', w.Last_Name)) AS employee_name,
            w.DateHired,
            COALESCE(ws.Status, 'Inactive') AS worker_status,
            COALESCE(ps.Site_Name, 'Unassigned') AS site_name
        FROM worker w
        LEFT JOIN workerstatus ws ON ws.WorkerStatusID = w.WorkerStatusID
        LEFT JOIN workerassignment wa ON wa.AssignmentID = (
            SELECT wa2.AssignmentID
            FROM workerassignment wa2
            WHERE wa2.WorkerID = w.WorkerID
            ORDER BY wa2.Assigned_Date DESC, wa2.AssignmentID DESC
            LIMIT 1
        )
        LEFT JOIN projectsite ps ON ps.SiteID = wa.SiteID
        ORDER BY COALESCE(w.DateHired, '1900-01-01') DESC, w.WorkerID DESC
        LIMIT ?
    ");

    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'id' => (int) ($row['WorkerID'] ?? 0),
            'name' => $row['employee_name'] ?: 'Unnamed Employee',
            'status' => $row['worker_status'] ?? 'Inactive',
            'site' => $row['site_name'] ?? 'Unassigned',
            'date_hired' => $row['DateHired'] ?? null
        ];
    }
    $stmt->close();

    return $items;
}

function get_hr_updates(mysqli $conn, int $limit = 6): array
{
    $stmt = $conn->prepare("
        SELECT
            Action,
            Details,
            DATE_FORMAT(Date, '%b %d, %Y %h:%i %p') AS update_time
        FROM audit_logs
        WHERE Action IN (
            'Add Worker',
            'Employee Approved',
            'Employee Archived',
            'Update Employee',
            'Assign Worker to Site',
            'Update Worker Site Role',
            'Site Assignment Removed'
        )
        ORDER BY Date DESC
        LIMIT ?
    ");

    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'action' => $row['Action'] ?? 'Update',
            'details' => $row['Details'] ?? '-',
            'time' => $row['update_time'] ?? ''
        ];
    }
    $stmt->close();

    return $items;
}

try {
    $today = (new DateTime('today'))->format('Y-m-d');

    $userStmt = $conn->prepare("SELECT full_name, email, status FROM users WHERE id = ? LIMIT 1");
    if (!$userStmt) {
        dashboard_json_error('Failed to prepare user query.');
    }

    $userStmt->bind_param('i', $userId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $userRow = $userResult ? $userResult->fetch_assoc() : null;
    $userStmt->close();

    $sites = get_scoped_sites($conn, $role, $userId, $today);
    $sites = enrich_sites_with_timekeeper($conn, $sites);
    $siteIds = array_map(static fn($site) => (int) $site['SiteID'], $sites);

    $payrollSettings = null;
    if ($role !== 'HR') {
        $settingsResult = $conn->query("SELECT * FROM payroll_settings WHERE id = 1 LIMIT 1");
        $payrollSettings = $settingsResult ? $settingsResult->fetch_assoc() : null;
    }
    $payrollSettings = $payrollSettings ?: [
        'pay_periods' => 'Semi-monthly (1-15, 16-end)',
        'sss_rate' => 0,
        'philhealth_rate' => 3,
        'pagibig_rate' => 2,
        'overtime_rate' => 1.25
    ];

    $payPeriod = derive_pay_period((string) $payrollSettings['pay_periods']);
    $deductionRate = (
        (float) ($payrollSettings['sss_rate'] ?? 0) +
        (float) ($payrollSettings['philhealth_rate'] ?? 0) +
        (float) ($payrollSettings['pagibig_rate'] ?? 0)
    ) / 100;
    $overtimeRate = (float) ($payrollSettings['overtime_rate'] ?? 1.25);

    $emptyPayrollTotals = [
        'gross' => 0,
        'regular' => 0,
        'overtime' => 0,
        'deductions' => 0,
        'net' => 0,
        'processed' => 0,
        'pending' => 0
    ];
    $payrollTotals = $role === 'HR'
        ? $emptyPayrollTotals
        : get_payroll_totals($conn, $sites, $payPeriod, $deductionRate, $overtimeRate);
    $weekPeriod = derive_week_period();
    $payrollWeek = $role === 'HR'
        ? $emptyPayrollTotals
        : get_payroll_totals($conn, $sites, $weekPeriod, $deductionRate, $overtimeRate);
    $payrollApprovalSubmittedBy = ($role === 'Payroll Staff' && payroll_approval_columns_ready($conn)) ? $userId : null;
    $payrollApprovalCounts = payroll_approval_get_summary_counts($conn, $payrollApprovalSubmittedBy);
    $reports = get_recent_reports($conn, $siteIds);
    $timekeeperReportStats = get_timekeeper_report_stats($conn, $siteIds);
    $pendingOvertime = get_pending_overtime_summary($conn);
    $activeTimekeepers = get_active_timekeepers_count($conn);
    $todayAttendance = get_today_attendance_records($conn, $today, $siteIds);
    $offlineAttendance = get_offline_attendance_count($conn, $today, $siteIds);
    $activityTimeline = get_activity_timeline($conn, 10);

    $totalUsers = (int) (dashboard_scalar($conn, "SELECT COUNT(*) FROM users") ?? 0);
    $activeUsers = (int) (dashboard_scalar($conn, "SELECT COUNT(*) FROM users WHERE status = 'Active'") ?? 0);
    $totalWorkers = (int) (dashboard_scalar($conn, "SELECT COUNT(*) FROM worker") ?? 0);
    $activeWorkers = (int) (dashboard_scalar(
        $conn,
        "SELECT COUNT(*) FROM worker w INNER JOIN workerstatus ws ON ws.WorkerStatusID = w.WorkerStatusID WHERE ws.Status = 'Active'"
    ) ?? 0);

    $lastBackup = dashboard_scalar(
        $conn,
        "SELECT DATE_FORMAT(Date, '%b %d, %Y %h:%i %p') FROM audit_logs WHERE Action = 'Database Backup' ORDER BY Date DESC LIMIT 1"
    ) ?? 'Not recorded';

    $attendanceRowsToday = (int) (dashboard_scalar($conn, "SELECT COUNT(*) FROM attendance WHERE Date = ?", 's', [$today]) ?? 0);

    $attendanceOverall = [
        'present' => 0,
        'late' => 0,
        'absent' => 0,
        'total' => 0,
        'rate' => 0
    ];
    foreach ($sites as $site) {
        $attendanceOverall['present'] += (int) $site['present_count'];
        $attendanceOverall['late'] += (int) $site['late_count'];
        $attendanceOverall['absent'] += (int) $site['absent_count'];
        $attendanceOverall['total'] += (int) $site['assigned_workers'];
    }
    $attendanceOverall['offline'] = $offlineAttendance;
    $attendanceOverall['present_today'] = $attendanceOverall['present'];
    $attendanceOverall['rate'] = $attendanceOverall['total'] > 0
        ? round((($attendanceOverall['present']) / $attendanceOverall['total']) * 100)
        : 0;

    $activeSites = count(array_filter($sites, static fn($site) => !empty($site['is_active'])));
    $atCapacity = count(array_filter($sites, static fn($site) => (int) $site['needs_workers'] <= 0));
    $needsWorkers = count(array_filter($sites, static fn($site) => (int) $site['needs_workers'] > 0));
    $scopedWorkers = array_sum(array_map(static fn($site) => (int) $site['assigned_workers'], $sites));
    $pendingReports = (int) ($timekeeperReportStats['pending'] ?? count(array_filter($reports, static fn($report) => strtolower((string) $report['status']) === 'pending')));
    $laborEfficiency = $payrollTotals['gross'] > 0
        ? round(($payrollTotals['net'] / $payrollTotals['gross']) * 100, 1)
        : 0;

    $activitySql = "
        SELECT
            al.Action,
            COALESCE(u.full_name, u.email, 'Unknown User') AS user_name,
            al.Details,
            DATE_FORMAT(al.Date, '%b %d, %Y %h:%i %p') AS activity_time
        FROM audit_logs al
        INNER JOIN users u ON u.id = al.UserID
        ORDER BY al.Date DESC
        LIMIT 5
    ";
    $activityResult = $conn->query($activitySql);
    $recentActivity = [];
    if ($activityResult) {
        while ($row = $activityResult->fetch_assoc()) {
            $recentActivity[] = [
                'action' => $row['Action'] ?? 'Activity',
                'user' => $row['user_name'] ?? 'Unknown User',
                'target' => $row['Details'] ?? '-',
                'time' => $row['activity_time'] ?? ''
            ];
        }
    }

    $activeSitesOnly = array_values(array_filter($sites, static fn($site) => !empty($site['is_active'])));
    $assignedWorkers = (int) (dashboard_scalar($conn, "SELECT COUNT(DISTINCT WorkerID) FROM workerassignment") ?? 0);
    $unassignedWorkers = max(0, $totalWorkers - $assignedWorkers);
    $pendingEmployeeApprovals = get_pending_employee_approvals_count($conn);
    $employeeStatusBreakdown = get_employee_status_breakdown($conn);
    $recentEmployees = get_recent_employees($conn, 6);
    $hrUpdates = get_hr_updates($conn, 6);

    echo json_encode([
        'success' => true,
        'role' => $role,
        'generated_at' => date(DATE_ATOM),
        'user' => [
            'id' => $userId,
            'name' => $userRow['full_name'] ?: ($userRow['email'] ?? 'User'),
            'role' => $role,
            'status' => $userRow['status'] ?? 'Active'
        ],
        'summary' => [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'total_workers' => $totalWorkers,
            'active_workers' => $activeWorkers,
            'scoped_workers' => $scopedWorkers,
            'active_sites' => $activeSites,
            'assigned_sites' => count($sites),
            'at_capacity' => $atCapacity,
            'needs_workers' => $needsWorkers,
            'avg_attendance' => $attendanceOverall['rate'],
            'payroll_gross' => $payrollTotals['gross'],
            'payroll_deductions' => $payrollTotals['deductions'],
            'payroll_net' => $payrollTotals['net'],
            'processed_payrolls' => $payrollTotals['processed'],
            'pending_payrolls' => $payrollApprovalCounts['pending'] ?? $payrollTotals['pending'],
            'approved_payrolls' => $payrollApprovalCounts['approved'] ?? 0,
            'rejected_payrolls' => $payrollApprovalCounts['rejected'] ?? 0,
            'payroll_approval_total' => $payrollApprovalCounts['total'] ?? 0,
            'pending_reports' => $pendingReports,
            'pending_incidents' => $pendingReports,
            'labor_efficiency' => $laborEfficiency,
            'attendance_sync' => $attendanceRowsToday > 0 ? 'Updated Today' : 'No Records Today',
            'system_health' => 'Healthy',
            'last_backup' => $lastBackup,
            'present_today' => $attendanceOverall['present_today'],
            'payroll_due' => $payrollTotals['net'],
            'pending_overtime' => $pendingOvertime['count'],
            'active_timekeepers' => $activeTimekeepers,
            'payroll_regular' => $payrollTotals['regular'],
            'payroll_overtime' => $payrollTotals['overtime']
            ,
            'pending_employee_approvals' => $pendingEmployeeApprovals,
            'assigned_workers' => $assignedWorkers,
            'unassigned_workers' => $unassignedWorkers,
            'employee_status_active' => $employeeStatusBreakdown['active'],
            'employee_status_inactive' => $employeeStatusBreakdown['inactive'],
            'employee_status_archived' => $employeeStatusBreakdown['archived']
        ],
        'payroll_week' => [
            'label' => $weekPeriod['label'],
            'regular' => $payrollWeek['regular'],
            'overtime' => $payrollWeek['overtime'],
            'deductions' => $payrollWeek['deductions'],
            'net' => $payrollWeek['net']
        ],
        'pay_period' => [
            'start' => $payPeriod['start'],
            'end' => $payPeriod['end'],
            'label' => $payPeriod['label'],
            'next_run' => date('M d, Y', strtotime($payPeriod['end']))
        ],
        'attendance' => [
            'date' => $today,
            'overall' => $attendanceOverall,
            'sites' => array_map(static function ($site) {
                return [
                    'site_id' => (int) $site['SiteID'],
                    'site_name' => $site['Site_Name'],
                    'present' => (int) $site['present_count'],
                    'late' => (int) $site['late_count'],
                    'absent' => (int) $site['absent_count'],
                    'total' => (int) $site['assigned_workers'],
                    'rate' => (int) $site['attendance_rate']
                ];
            }, $sites)
        ],
        'sites' => array_map(static function ($site) {
            return [
                'SiteID' => (int) $site['SiteID'],
                'Site_Name' => $site['Site_Name'],
                'Location' => $site['Location'],
                'Coordinates' => $site['Coordinates'],
                'Start_Date' => $site['Start_Date'],
                'End_Date' => $site['End_Date'],
                'Required_Workers' => (int) $site['Required_Workers'],
                'Site_Manager' => $site['Site_Manager'],
                'Status' => $site['Status'],
                'Start_Time' => $site['Start_Time'],
                'End_Time' => $site['End_Time'],
                'Total_Hours' => $site['Total_Hours'],
                'Current_Workers' => (int) $site['assigned_workers'],
                'present_count' => (int) $site['present_count'],
                'late_count' => (int) $site['late_count'],
                'absent_count' => (int) $site['absent_count'],
                'attended_count' => (int) $site['attended_count'],
                'attendance_rate' => (int) $site['attendance_rate'],
                'needs_workers' => (int) $site['needs_workers'],
                'Timekeeper' => $site['Timekeeper'] ?? 'Not assigned'
            ];
        }, $sites),
        'active_sites_panel' => array_map(static function ($site) {
            $requiredWorkers = (int) ($site['Required_Workers'] ?? 0);
            $currentWorkers = (int) ($site['assigned_workers'] ?? 0);
            $capacityPercent = $requiredWorkers > 0
                ? min(100, (int) round(($currentWorkers / $requiredWorkers) * 100))
                : 0;

            return [
                'site_id' => (int) $site['SiteID'],
                'site_name' => $site['Site_Name'],
                'location' => $site['Location'],
                'timekeeper' => $site['Timekeeper'] ?? 'Not assigned',
                'assigned_workers' => $currentWorkers,
                'target_capacity' => $requiredWorkers,
                'capacity_percent' => $capacityPercent,
                'status' => $site['Status'] ?? 'Active'
            ];
        }, $activeSitesOnly),
        'today_attendance' => $todayAttendance,
        'pending_overtime' => $pendingOvertime,
        'activity_timeline' => $activityTimeline,
        'reports' => [
            'pending_count' => $pendingReports,
            'items' => $reports,
            'stats' => $timekeeperReportStats,
        ],
        'hr' => [
            'recent_employees' => $recentEmployees,
            'updates' => $hrUpdates,
            'employee_status' => $employeeStatusBreakdown
        ],
        'recent_activity' => $recentActivity
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    dashboard_json_error($e->getMessage());
} finally {
    $conn->close();
}
?>
