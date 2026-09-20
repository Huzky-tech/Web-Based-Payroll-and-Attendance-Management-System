<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'connection/db_config.php';
require_once '../includes/auth.php';
require_once __DIR__ . '/site_schedule_helpers.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR', 'Timekeeper']);
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
require_once __DIR__ . '/attendance_schema_helpers.php';
attendance_schema_ensure_table($conn);
auto_close_open_attendance_at_shift_end($conn);

function getCurrentPayrollStaffId(mysqli $conn, int $userId): int
{
    $stmt = $conn->prepare("SELECT PayrollStaff_ID FROM payrollstaff WHERE UserID = ? LIMIT 1");
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return (int) ($row['PayrollStaff_ID'] ?? 0);
}

function bindDynamicParams(mysqli_stmt $stmt, string $types, array $params): void
{
    if ($types === '' || !$params) {
        return;
    }

    $references = [$types];
    foreach ($params as $index => $value) {
        $references[] = &$params[$index];
    }

    call_user_func_array([$stmt, 'bind_param'], $references);
}

try {
    $date = isset($_GET['date']) && $_GET['date'] !== '' ? $_GET['date'] : date('Y-m-d');

    $types = 's';
    $params = [$date];
    $where = ["LOWER(ps.Status) = 'active'"];

    if ($currentRole === 'Payroll Staff') {
        $payrollStaffId = getCurrentPayrollStaffId($conn, $currentUserId);
        if ($payrollStaffId <= 0) {
            echo json_encode([
                'success' => true,
                'data' => [],
                'summary' => [
                    'total_workers' => 0,
                    'present' => 0,
                    'late' => 0,
                    'absent' => 0,
                    'rate' => 0
                ],
                'date' => $date
            ]);
            exit;
        }

        $where[] = 'EXISTS (SELECT 1 FROM payrollstaffassignment psa WHERE psa.SiteID = ps.SiteID AND psa.PayrollStaff_ID = ?)';
        $types .= 'i';
        $params[] = $payrollStaffId;
    }

    $whereSql = 'WHERE ' . implode(' AND ', $where);
    $sql = "
        SELECT
            ps.SiteID,
            ps.Site_Name,
            COALESCE(NULLIF(TRIM(ps.Site_Manager), ''), 'Not assigned') AS Site_Manager,
            wa.WorkerID,
            a.Time_In,
            a.AttendanceStatus, a.IsLate
        FROM projectsite ps
        LEFT JOIN workerassignment wa ON wa.SiteID = ps.SiteID
        LEFT JOIN attendance a ON a.WorkerID = wa.WorkerID AND a.SiteID = ps.SiteID AND a.Date = ?
        {$whereSql}
        ORDER BY ps.Site_Name ASC, wa.WorkerID ASC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare attendance stats query');
    }

    bindDynamicParams($stmt, $types, $params);
    $stmt->execute();
    $result = $stmt->get_result();
    $statsBySite = [];
    $scheduleCache = [];
    $summary = [
        'total_workers' => 0,
        'present' => 0,
        'late' => 0,
        'absent' => 0,
        'not_started' => 0,
        'rate' => 0
    ];

    while ($row = $result->fetch_assoc()) {
        $siteId = (int) ($row['SiteID'] ?? 0);
        if (!isset($statsBySite[$siteId])) {
            $statsBySite[$siteId] = [
                'SiteID' => $siteId,
                'Site_Name' => $row['Site_Name'] ?? 'Unknown Site',
                'Site_Manager' => $row['Site_Manager'] ?? 'Not assigned',
                'total_workers' => 0,
                'present_count' => 0,
                'late_count' => 0,
                'absent_count' => 0,
                'not_started_count' => 0,
                'attendance_rate' => 0,
            ];
            $scheduleCache[$siteId] = get_site_schedule_row($conn, $siteId);
        }

        if (empty($row['WorkerID'])) {
            continue;
        }

        $statsBySite[$siteId]['total_workers']++;
        $status = attendance_display_status(
            $row['AttendanceStatus'] ?? null,
            $row['Time_In'] ?? null,
            $date,
            $scheduleCache[$siteId]['ShiftEnd'] ?? null
        );
        if ($status === 'Present') $statsBySite[$siteId]['present_count']++;
        if ((int) ($row['IsLate'] ?? 0) === 1) $statsBySite[$siteId]['late_count']++;
        if ($status === 'Absent') $statsBySite[$siteId]['absent_count']++;
        if ($status === 'Not Started') $statsBySite[$siteId]['not_started_count']++;
    }
    $stmt->close();

    $stats = array_values($statsBySite);
    foreach ($stats as &$row) {
        $worked = $row['present_count'];
        $row['attendance_rate'] = $row['total_workers'] > 0
            ? round(($worked / $row['total_workers']) * 100, 1)
            : 0;
        $summary['total_workers'] += $row['total_workers'];
        $summary['present'] += $row['present_count'];
        $summary['late'] += $row['late_count'];
        $summary['absent'] += $row['absent_count'];
        $summary['not_started'] += $row['not_started_count'];
    }
    unset($row);

    $summary['rate'] = $summary['total_workers'] > 0
        ? round((($summary['present']) / $summary['total_workers']) * 100, 1)
        : 0;

    echo json_encode([
        'success' => true,
        'data' => $stats,
        'summary' => $summary,
        'date' => $date
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
