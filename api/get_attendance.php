<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'connection/db_config.php';
require_once '../includes/auth.php';
require_once __DIR__ . '/site_schedule_helpers.php';
require_once __DIR__ . '/timekeeper_assignment_helpers.php';
require_once __DIR__ . '/attendance_photo_helpers.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR', 'Timekeeper']);
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);

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
    require_once __DIR__ . '/attendance_schema_helpers.php';
    attendance_schema_ensure_table($conn);
    ensure_attendance_punch_photo_columns($conn);
    auto_close_open_attendance_at_shift_end($conn);
    $date = isset($_GET['date']) && $_GET['date'] !== '' ? $_GET['date'] : date('Y-m-d');
    $siteId = isset($_GET['site_id']) ? (int) $_GET['site_id'] : 0;
    $search = trim((string) ($_GET['search'] ?? ''));
    $position = trim((string) ($_GET['position'] ?? ''));
    $manager = trim((string) ($_GET['manager'] ?? ''));
    $status = trim((string) ($_GET['status'] ?? ''));
    $sortBy = trim((string) ($_GET['sort_by'] ?? 'employee'));
    $sortDir = strtolower(trim((string) ($_GET['sort_dir'] ?? 'asc'))) === 'desc' ? 'DESC' : 'ASC';
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 10)));
    $exportAll = (string) ($_GET['export'] ?? '') === '1';

    $sortMap = [
        'employee' => "CONCAT(COALESCE(w.Last_Name, ''), ', ', COALESCE(w.First_Name, ''))",
        'site' => 'ps.Site_Name',
        'manager' => "COALESCE(NULLIF(TRIM(ps.Site_Manager), ''), 'Not assigned')",
        'position' => "COALESCE(NULLIF(TRIM(wa.Role_On_Site), ''), 'Construction Worker')",
        'status' => "COALESCE(a.AttendanceStatus, 'Absent')",
        'time_in' => 'a.Time_In',
        'time_out' => 'a.Time_Out',
        'hours_worked' => 'COALESCE(a.Hours_Worked, 0)'
    ];
    $orderBy = $sortMap[$sortBy] ?? $sortMap['employee'];

    $where = ["LOWER(ps.Status) = 'active'"];
    $types = 's';
    $params = [$date];

    if ($currentRole === 'Payroll Staff') {
        $payrollStaffId = getCurrentPayrollStaffId($conn, $currentUserId);
        if ($payrollStaffId <= 0) {
            echo json_encode([
                'success' => true,
                'data' => [],
                'summary' => [
                    'total' => 0,
                    'present' => 0,
                    'late' => 0,
                    'absent' => 0,
                    'rate' => 0,
                    'hours' => 0
                ],
                'pagination' => [
                    'page' => 1,
                    'per_page' => $perPage,
                    'total_records' => 0,
                    'total_pages' => 1
                ],
                'filters' => [
                    'sites' => [],
                    'positions' => [],
                    'managers' => []
                ]
            ]);
            exit;
        }

        $where[] = 'EXISTS (SELECT 1 FROM payrollstaffassignment psa WHERE psa.SiteID = wa.SiteID AND psa.PayrollStaff_ID = ?)';
        $types .= 'i';
        $params[] = $payrollStaffId;
    }

    if ($currentRole === 'Timekeeper') {
        $assignedSiteId = get_timekeeper_assigned_site_id($conn, $currentUserId);
        if ($assignedSiteId <= 0) {
            echo json_encode([
                'success' => true,
                'data' => [],
                'summary' => [
                    'total' => 0,
                    'present' => 0,
                    'late' => 0,
                    'absent' => 0,
                    'rate' => 0,
                    'hours' => 0
                ],
                'pagination' => [
                    'page' => 1,
                    'per_page' => $perPage,
                    'total_records' => 0,
                    'total_pages' => 1
                ],
                'filters' => [
                    'sites' => [],
                    'positions' => [],
                    'managers' => []
                ]
            ]);
            exit;
        }

        $where[] = 'wa.SiteID = ?';
        $types .= 'i';
        $params[] = $assignedSiteId;
    }

    if ($siteId > 0) {
        $where[] = 'wa.SiteID = ?';
        $types .= 'i';
        $params[] = $siteId;
    }

    if ($search !== '') {
        $likeSearch = '%' . $search . '%';
        $where[] = "(CONCAT(COALESCE(w.First_Name, ''), ' ', COALESCE(w.Last_Name, '')) LIKE ? OR CAST(w.WorkerID AS CHAR) LIKE ? OR ps.Site_Name LIKE ?)";
        $types .= 'sss';
        $params[] = $likeSearch;
        $params[] = $likeSearch;
        $params[] = $likeSearch;
    }

    if ($position !== '') {
        $where[] = "COALESCE(NULLIF(TRIM(wa.Role_On_Site), ''), 'Construction Worker') = ?";
        $types .= 's';
        $params[] = $position;
    }

    if ($manager !== '') {
        $where[] = "COALESCE(NULLIF(TRIM(ps.Site_Manager), ''), 'Not assigned') = ?";
        $types .= 's';
        $params[] = $manager;
    }

    $whereSql = 'WHERE ' . implode(' AND ', $where);
    $fromSql = "
        FROM workerassignment wa
        INNER JOIN worker w ON w.WorkerID = wa.WorkerID
        INNER JOIN projectsite ps ON ps.SiteID = wa.SiteID
        LEFT JOIN attendance a ON a.WorkerID = w.WorkerID AND a.SiteID = wa.SiteID AND a.Date = ?
        {$whereSql}
    ";

    $summarySql = "
        SELECT
            COUNT(*) AS total_records,
            SUM(CASE WHEN COALESCE(a.AttendanceStatus, 'Absent') = 'Present' THEN 1 ELSE 0 END) AS present_count,
            SUM(CASE WHEN COALESCE(a.IsLate, 0) = 1 THEN 1 ELSE 0 END) AS late_count,
            SUM(CASE WHEN COALESCE(a.AttendanceStatus, 'Absent') = 'Absent' THEN 1 ELSE 0 END) AS absent_count,
            SUM(COALESCE(a.Hours_Worked, 0)) AS total_hours
        {$fromSql}
    ";
    $summaryStmt = $conn->prepare($summarySql);
    if (!$summaryStmt) {
        throw new Exception('Failed to prepare attendance summary query');
    }
    bindDynamicParams($summaryStmt, $types, $params);
    $summaryStmt->execute();
    $summaryResult = $summaryStmt->get_result();
    $summaryRow = $summaryResult ? $summaryResult->fetch_assoc() : null;
    $summaryStmt->close();

    $totalRecords = (int) ($summaryRow['total_records'] ?? 0);
    $presentCount = (int) ($summaryRow['present_count'] ?? 0);
    $lateCount = (int) ($summaryRow['late_count'] ?? 0);
    $absentCount = (int) ($summaryRow['absent_count'] ?? 0);
    $totalHours = (float) ($summaryRow['total_hours'] ?? 0);
    $attendanceRate = $totalRecords > 0 ? round(($presentCount / $totalRecords) * 100, 1) : 0;

    $filterSql = "
        SELECT
            wa.SiteID,
            ps.Site_Name,
            COALESCE(NULLIF(TRIM(wa.Role_On_Site), ''), 'Construction Worker') AS position,
            COALESCE(NULLIF(TRIM(ps.Site_Manager), ''), 'Not assigned') AS site_manager
        {$fromSql}
        ORDER BY ps.Site_Name ASC
    ";
    $filterStmt = $conn->prepare($filterSql);
    if (!$filterStmt) {
        throw new Exception('Failed to prepare attendance filter query');
    }
    bindDynamicParams($filterStmt, $types, $params);
    $filterStmt->execute();
    $filterResult = $filterStmt->get_result();
    $siteOptions = [];
    $positionOptions = [];
    $managerOptions = [];
    while ($filterRow = $filterResult->fetch_assoc()) {
        $siteKey = (string) ($filterRow['SiteID'] ?? '');
        if ($siteKey !== '' && !isset($siteOptions[$siteKey])) {
            $siteOptions[$siteKey] = [
                'id' => (int) $filterRow['SiteID'],
                'name' => $filterRow['Site_Name'] ?? 'Unknown Site'
            ];
        }

        $positionValue = trim((string) ($filterRow['position'] ?? ''));
        if ($positionValue !== '') {
            $positionOptions[$positionValue] = $positionValue;
        }

        $managerValue = trim((string) ($filterRow['site_manager'] ?? ''));
        if ($managerValue !== '') {
            $managerOptions[$managerValue] = $managerValue;
        }
    }
    $filterStmt->close();

    $dataSql = "
        SELECT
            w.WorkerID,
            w.WorkerID AS ID,
            w.First_Name,
            w.Last_Name,
            wa.SiteID,
            ps.Site_Name,
            COALESCE(NULLIF(TRIM(wa.Role_On_Site), ''), 'Construction Worker') AS position,
            COALESCE(NULLIF(TRIM(ps.Site_Manager), ''), 'Not assigned') AS site_manager,
            a.AttendanceID,
            a.Time_In,
            " . (attendance_lunch_columns_exist($conn)
                ? "a.Lunch_Out, a.Lunch_In,"
                : "NULL AS Lunch_Out, NULL AS Lunch_In,") . "
            a.Time_Out,
            COALESCE(a.Hours_Worked, 0) AS Hours_Worked,
            COALESCE(a.Overtime_Hours, 0) AS Overtime_Hours,
            a.AttendanceStatus AS AttendanceStatus,
            a.IsLate AS IsLate,
            a.Date,
            " . attendance_photo_select_columns($conn) . "
        {$fromSql}
        ORDER BY {$orderBy} {$sortDir}, w.WorkerID ASC
    ";

    $dataTypes = $types;
    $dataParams = $params;

    $dataStmt = $conn->prepare($dataSql);
    if (!$dataStmt) {
        throw new Exception('Failed to prepare attendance data query');
    }
    bindDynamicParams($dataStmt, $dataTypes, $dataParams);
    $dataStmt->execute();
    $dataResult = $dataStmt->get_result();
    $records = [];
    while ($row = $dataResult->fetch_assoc()) {
        $records[] = $row;
    }
    $dataStmt->close();

    // Resolve the status after we know each site's scheduled shift end. This keeps
    // unstarted workers out of the Absent count until their shift has actually ended.
    $scheduleCache = [];
    foreach ($records as &$recordRow) {
        $recordSiteId = (int) ($recordRow['SiteID'] ?? 0);
        if (!array_key_exists($recordSiteId, $scheduleCache)) {
            $scheduleCache[$recordSiteId] = get_site_schedule_row($conn, $recordSiteId);
        }
        $schedule = $scheduleCache[$recordSiteId] ?? [];
        $recordRow['AttendanceStatus'] = attendance_display_status(
            $recordRow['AttendanceStatus'] ?? null,
            $recordRow['Time_In'] ?? null,
            (string) ($recordRow['Date'] ?? $date),
            $schedule['ShiftEnd'] ?? null
        );
    }
    unset($recordRow);

    if ($status !== '') {
        $records = array_values(array_filter($records, static function (array $record) use ($status): bool {
            return $status === 'Late' ? (int) ($record['IsLate'] ?? 0) === 1 : (string) ($record['AttendanceStatus'] ?? '') === $status;
        }));
    }

    $totalRecords = count($records);
    $presentCount = 0;
    $lateCount = 0;
    $absentCount = 0;
    $totalHours = 0.0;
    foreach ($records as $recordRow) {
        $recordStatus = (string) ($recordRow['AttendanceStatus'] ?? 'Not Started');
        $presentCount += $recordStatus === 'Present' ? 1 : 0;
        $lateCount += (int) ($recordRow['IsLate'] ?? 0) === 1 ? 1 : 0;
        $absentCount += $recordStatus === 'Absent' ? 1 : 0;
        $totalHours += (float) ($recordRow['Hours_Worked'] ?? 0);
    }
    $attendanceRate = $totalRecords > 0 ? round(($presentCount / $totalRecords) * 100, 1) : 0;

    $photoLogsByKey = attendance_photo_fetch_logs_for_records($conn, $records);
    foreach ($records as &$recordRow) {
        $attendanceId = (int) ($recordRow['AttendanceID'] ?? 0);
        $workerId = (int) ($recordRow['WorkerID'] ?? $recordRow['ID'] ?? 0);
        $recordDate = (string) ($recordRow['Date'] ?? $date);
        $photoLogs = [];

        if ($attendanceId > 0 && isset($photoLogsByKey[$attendanceId])) {
            $photoLogs = $photoLogsByKey[$attendanceId];
        } else {
            $legacyKey = 'worker:' . $workerId . ':' . $recordDate;
            $photoLogs = $photoLogsByKey[$legacyKey] ?? [];
        }

        $recordRow['photo_logs'] = attendance_photo_merge_logs(
            $photoLogs,
            attendance_photo_build_logs_from_record($recordRow)
        );
        if ($recordRow['photo_logs'] !== [] && empty($recordRow['PhotoPath'])) {
            $recordRow['PhotoPath'] = $recordRow['photo_logs'][0]['photo_path'] ?? null;
        }
    }
    unset($recordRow);

    $recordsForResponse = $exportAll
        ? $records
        : array_slice($records, ($page - 1) * $perPage, $perPage);

    $notifications = [];
    if ($absentCount > 0) {
        $notifications[] = [
            'type' => 'warning',
            'title' => 'Absent workers detected',
            'message' => $absentCount . ' worker' . ($absentCount === 1 ? '' : 's') . ' marked absent for ' . $date . '.'
        ];
    }
    if ($lateCount > 0) {
        $notifications[] = [
            'type' => 'info',
            'title' => 'Late arrivals require attention',
            'message' => $lateCount . ' worker' . ($lateCount === 1 ? '' : 's') . ' clocked in late.'
        ];
    }
    if ($attendanceRate < 80 && $totalRecords > 0) {
        $notifications[] = [
            'type' => 'critical',
            'title' => 'Attendance rate below target',
            'message' => 'Current attendance is ' . $attendanceRate . '%, below the 80% target.'
        ];
    }
    if (!$notifications) {
        $notifications[] = [
            'type' => 'success',
            'title' => 'Attendance looks healthy',
            'message' => 'No urgent attendance issues detected for the selected filters.'
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $recordsForResponse,
        'summary' => [
            'total' => $totalRecords,
            'present' => $presentCount,
            'late' => $lateCount,
            'absent' => $absentCount,
            'rate' => $attendanceRate,
            'hours' => round($totalHours, 2)
        ],
        'pagination' => [
            'page' => $exportAll ? 1 : $page,
            'per_page' => $exportAll ? max(1, count($recordsForResponse)) : $perPage,
            'total_records' => $totalRecords,
            'total_pages' => $exportAll ? 1 : max(1, (int) ceil($totalRecords / $perPage))
        ],
        'filters' => [
            'sites' => array_values($siteOptions),
            'positions' => array_values($positionOptions),
            'managers' => array_values($managerOptions)
        ],
        'notifications' => $notifications,
        'applied' => [
            'date' => $date,
            'site_id' => $siteId,
            'search' => $search,
            'position' => $position,
            'manager' => $manager,
            'status' => $status,
            'sort_by' => $sortBy,
            'sort_dir' => strtolower($sortDir)
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
