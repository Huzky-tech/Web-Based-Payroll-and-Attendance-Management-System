<?php
$embeddedReport = $embeddedReport ?? false;

if (!$embeddedReport) {
    include '../api/connection/db_config.php';
    include '../includes/auth.php';

    require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR']);
}

require_once __DIR__ . '/../api/attendance_photo_helpers.php';
require_once __DIR__ . '/../api/site_schedule_helpers.php';
require_once __DIR__ . '/../includes/report_scope_helpers.php';
require_once __DIR__ . '/../api/attendance_schema_helpers.php';
attendance_schema_ensure_table($conn);
ensure_attendance_photo_columns($conn);                              
$attendancePhotoColumns = attendance_photo_column_map($conn, true);
$amPhotoSql = isset($attendancePhotoColumns['AMTimeInPhoto']) ? 'a.AMTimeInPhoto' : 'NULL';
$pmPhotoSql = isset($attendancePhotoColumns['PMTimeInPhoto']) ? 'a.PMTimeInPhoto' : 'NULL';
$outPhotoSql = isset($attendancePhotoColumns['TimeOutPhoto']) ? 'a.TimeOutPhoto' : 'NULL';

function attendance_report_h($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}                          

function attendance_report_photo_url(?string $photoPath): string {
    $photoPath = trim((string) $photoPath);
    if ($photoPath === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $photoPath) || strpos($photoPath, '../') === 0) {
        return $photoPath;
    }

    return '../' . ltrim($photoPath, '/\\');
}

function attendance_report_timestamp(?string $date, ?string $timeIn): string {
    if ($date === null || $date === '' || $timeIn === null || $timeIn === '' || $timeIn === '00:00:00') {
        return '--';
    }

    return trim($date . ' ' . substr($timeIn, 0, 8));
}

$attendanceRows = [];
$attendanceSiteOptions = report_site_options($conn);
$attendanceSummary = [
    'sites' => 0,
    'records' => 0,
    'present' => 0,
    'late' => 0,
    'absent' => 0,
    'hours' => 0.0,
];

$attendanceTypes = '';
$attendanceParams = [];
$attendanceScope = report_scope_condition($conn, 'a.SiteID', $attendanceTypes, $attendanceParams);
$attendanceWhere = $attendanceScope !== '' ? "WHERE {$attendanceScope}" : '';

$attendanceSql = "
    SELECT
        ps.SiteID,
        ps.Site_Name,
        COUNT(a.AttendanceID) AS total_records,
        COUNT(DISTINCT a.WorkerID) AS workers_count,
        SUM(CASE WHEN a.AttendanceStatus = 'Present' THEN 1 ELSE 0 END) AS present_count,
        SUM(CASE WHEN a.IsLate = 1 THEN 1 ELSE 0 END) AS late_count,
        SUM(CASE WHEN a.AttendanceStatus = 'Absent' THEN 1 ELSE 0 END) AS absent_count,
        COALESCE(SUM(a.Hours_Worked), 0) AS total_hours,
        COALESCE(SUM(a.Overtime_Hours), 0) AS overtime_hours,
        MIN(a.Date) AS first_date,
        MAX(a.Date) AS latest_date
    FROM attendance a
    INNER JOIN projectsite ps ON ps.SiteID = a.SiteID
    {$attendanceWhere}
    GROUP BY ps.SiteID, ps.Site_Name
    ORDER BY latest_date DESC, ps.Site_Name ASC
";

$attendanceResult = report_query($conn, $attendanceSql, $attendanceTypes, $attendanceParams);
if ($attendanceResult) {
    while ($row = $attendanceResult->fetch_assoc()) {
        $row['total_records'] = (int) ($row['total_records'] ?? 0);
        $row['workers_count'] = (int) ($row['workers_count'] ?? 0);
        $row['present_count'] = (int) ($row['present_count'] ?? 0);
        $row['late_count'] = (int) ($row['late_count'] ?? 0);
        $row['absent_count'] = (int) ($row['absent_count'] ?? 0);
        $row['total_hours'] = (float) ($row['total_hours'] ?? 0);
        $row['overtime_hours'] = (float) ($row['overtime_hours'] ?? 0);
        $row['attendance_rate'] = $row['total_records'] > 0
            ? round(($row['present_count'] / $row['total_records']) * 100, 1)
            : 0;

        $attendanceSummary['sites']++;
        $attendanceSummary['records'] += $row['total_records'];
        $attendanceSummary['present'] += $row['present_count'];
        $attendanceSummary['late'] += $row['late_count'];
        $attendanceSummary['absent'] += $row['absent_count'];
        $attendanceSummary['hours'] += $row['total_hours'];
        $attendanceRows[] = $row;
    }
}

$overallAttendanceRate = $attendanceSummary['records'] > 0
    ? round(($attendanceSummary['present'] / $attendanceSummary['records']) * 100, 1)
    : 0;

$evidenceRows = [];
$geofenceAvailable = geofence_columns_exist($conn);
$geofenceJoin = $geofenceAvailable ? 'ps.Geofence_Radius_M' : 'NULL AS Geofence_Radius_M';

$evidenceTypes = '';
$evidenceParams = [];
$evidenceScope = report_scope_condition($conn, 'a.SiteID', $evidenceTypes, $evidenceParams);
$evidenceWhere = $evidenceScope !== '' ? $evidenceScope : '1=1';

$evidenceSql = "
    SELECT
        l.LogID,
        a.SiteID,
        COALESCE(
            NULLIF(l.AttendanceType, ''),
            CASE
                WHEN NULLIF({$amPhotoSql}, '') IS NOT NULL THEN 'Time In'
                WHEN NULLIF({$pmPhotoSql}, '') IS NOT NULL THEN 'Lunch In'
                WHEN NULLIF({$outPhotoSql}, '') IS NOT NULL THEN 'Time Out'
                ELSE NULL
            END
        ) AS AttendanceType,
        a.Date,
        COALESCE(l.EventTime, NULLIF(a.Time_In, '00:00:00')) AS EventTime,
        COALESCE(
            NULLIF(l.PhotoPath, ''),
            NULLIF({$amPhotoSql}, ''),
            NULLIF({$pmPhotoSql}, ''),
            NULLIF({$outPhotoSql}, ''),
            NULLIF(a.PhotoPath, '')
        ) AS PhotoPath,
        COALESCE(l.Latitude, a.Latitude) AS Latitude,
        COALESCE(l.Longitude, a.Longitude) AS Longitude,
        COALESCE(l.DistanceFromSite, a.DistanceFromSite) AS DistanceFromSite,
        a.AttendanceID,
        a.Time_In,
        a.AttendanceStatus,
        a.IsLate,
        CONCAT(COALESCE(w.First_Name, ''), ' ', COALESCE(w.Last_Name, '')) AS worker_name,
        ps.Site_Name,
        {$geofenceJoin}
    FROM attendance a
    INNER JOIN worker w ON w.WorkerID = a.WorkerID
    INNER JOIN projectsite ps ON ps.SiteID = a.SiteID
    LEFT JOIN attendance_photo_logs l
        ON l.WorkerID = a.WorkerID
        AND l.SiteID = a.SiteID
        AND l.EventDate = a.Date
        AND l.AttendanceType IN ('Time In', 'Lunch In', 'Time Out')
    WHERE {$evidenceWhere}
    ORDER BY a.Date DESC, a.WorkerID ASC, FIELD(l.AttendanceType, 'Time In', 'Lunch In', 'Time Out'), l.EventTime ASC
    LIMIT 500
";

$evidenceResult = report_query($conn, $evidenceSql, $evidenceTypes, $evidenceParams);
if ($evidenceResult) {
    while ($row = $evidenceResult->fetch_assoc()) {
        $distance = isset($row['DistanceFromSite']) ? (float) $row['DistanceFromSite'] : null;
        $radius = isset($row['Geofence_Radius_M']) ? (float) $row['Geofence_Radius_M'] : null;
        $row['gps_verification'] = attendance_gps_verification_label($distance, $radius);
        $row['photo_label'] = !empty($row['AttendanceType'])
            ? attendance_photo_display_label((string) $row['AttendanceType'])
            : 'No Photo Uploaded';
        $row['attendance_timestamp'] = attendance_report_timestamp(
            $row['Date'] ?? '',
            $row['EventTime'] ?? $row['Time_In'] ?? ''
        );
        $evidenceRows[] = $row;
    }
}
?>
<?php if (!$embeddedReport): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Summary - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/reports.css?v=20260905-1">
    <script src="../js/action_result_modal.js?v=20260920-access-1" defer></script>
</head>
<body>
<div class="main-content">
    <div class="content-area">
<?php endif; ?>

<div class="report-panel">
    <h2 class="summary-header">Attendance Summary</h2>
    <div class="report-table-filters report-filter-bar report-inline-filter-card" aria-label="Attendance filters">
        <label class="report-filter-field" for="attendanceDateFilter">
            <span>Date</span>
            <input type="date" id="attendanceDateFilter" aria-label="Attendance date">
        </label>
        <label class="report-filter-field" for="attendanceSiteSelect">
            <span>Site</span>
            <select id="attendanceSiteSelect" aria-label="Attendance site">
                <option value="">All Sites</option>
                <?php foreach ($attendanceSiteOptions as $siteName): ?>
                <option value="<?php echo attendance_report_h($siteName); ?>"><?php echo attendance_report_h($siteName); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="report-filter-field report-search-field" for="attendanceSearchFilter">
            <span>Search</span>
            <span class="report-search-control">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="attendanceSearchFilter" placeholder="Search records">
            </span>
        </label>
        <label class="report-filter-field" for="attendanceStatusFilter">
            <span>Status</span>
            <select id="attendanceStatusFilter">
                <option value="">All statuses</option>
                <option value="Present">Present</option>
                <option value="Late">Late</option>
                <option value="Absent">Absent</option>
            </select>
        </label>
    </div>
    <div class="summary-cards">
        <div class="summary-card green">
            <div class="summary-card-label">Attendance Rate</div>
            <div class="summary-card-value"><?php echo attendance_report_h($overallAttendanceRate); ?>%</div>
        </div>
        <div class="summary-card yellow">
            <div class="summary-card-label">Present / Late</div>
            <div class="summary-card-value"><?php echo attendance_report_h($attendanceSummary['present']); ?></div>
        </div>
        <div class="summary-card pink">
            <div class="summary-card-label">Absent Records</div>
            <div class="summary-card-value"><?php echo attendance_report_h($attendanceSummary['absent']); ?></div>
        </div>
        <div class="summary-card purple">
            <div class="summary-card-label">Total Hours</div>
            <div class="summary-card-value"><?php echo attendance_report_h(number_format($attendanceSummary['hours'], 2)); ?></div>
        </div>
    </div>

    <div class="table-section">
        <h3 class="table-title">Attendance by Site</h3>
        <div class="table-container">
            <table class="payroll-table">
                <thead>
                    <tr>
                        <th>Site</th>
                        <th>Workers</th>
                        <th>Records</th>
                        <th>Present</th>
                        <th>Late</th>
                        <th>Absent</th>
                        <th>Hours</th>
                        <th>Overtime</th>
                        <th>Rate</th>
                        <th>Latest Date</th>
                    </tr>
                </thead>
                <tbody id="attendanceReportBody">
                    <?php if (!$attendanceRows): ?>
                    <tr><td colspan="10" style="text-align:center;">No attendance records found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($attendanceRows as $row): ?>
                    <tr data-site-id="<?php echo (int) $row['SiteID']; ?>" data-first-date="<?php echo attendance_report_h($row['first_date']); ?>" data-latest-date="<?php echo attendance_report_h($row['latest_date']); ?>">
                        <td class="department-name"><span class="attendance-site-name"><?php echo attendance_report_h($row['Site_Name']); ?></span></td>
                        <td><?php echo attendance_report_h($row['workers_count']); ?></td>
                        <td><?php echo attendance_report_h($row['total_records']); ?></td>
                        <td><?php echo attendance_report_h($row['present_count']); ?></td>
                        <td><?php echo attendance_report_h($row['late_count']); ?></td>
                        <td><?php echo attendance_report_h($row['absent_count']); ?></td>
                        <td><?php echo attendance_report_h(number_format($row['total_hours'], 2)); ?></td>
                        <td><?php echo attendance_report_h(number_format($row['overtime_hours'], 2)); ?></td>
                        <td><?php echo attendance_report_h($row['attendance_rate']); ?>%</td>
                        <td><?php echo attendance_report_h($row['latest_date']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="report-pagination" id="attendanceReportPagination" aria-label="Attendance table pagination"></div>
    </div>

    <div class="table-section" style="margin-top: 24px;">
        <h3 class="table-title">Attendance Photo Evidence</h3>
        <div class="table-container">
            <table class="payroll-table">
                <thead>
                    <tr>
                        <th>Worker</th>
                        <th>Site</th>
                        <th>Photo Type</th>
                        <th>Attendance Timestamp</th>
                        <th>Photo Evidence</th>
                        <th>GPS Coordinates</th>
                        <th>Distance From Site</th>
                        <th>GPS Verification</th>
                    </tr>
                </thead>
                <tbody id="attendanceEvidenceReportBody">
                    <?php if (!$evidenceRows): ?>
                    <tr><td colspan="8" style="text-align:center;">No attendance photo evidence uploaded yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($evidenceRows as $row): ?>
                    <?php
                        $photoUrl = attendance_report_photo_url($row['PhotoPath'] ?? '');
                        $latitude = $row['Latitude'] ?? null;
                        $longitude = $row['Longitude'] ?? null;
                        $gpsLabel = ($latitude !== null && $latitude !== '' && $longitude !== null && $longitude !== '')
                            ? number_format((float) $latitude, 6) . ', ' . number_format((float) $longitude, 6)
                            : 'Not recorded';
                        $distanceLabel = ($row['DistanceFromSite'] !== null && $row['DistanceFromSite'] !== '')
                            ? number_format((float) $row['DistanceFromSite'], 2) . ' m'
                            : 'Not recorded';
                    ?>
                    <tr data-site-id="<?php echo (int) ($row['SiteID'] ?? 0); ?>">
                        <td><?php echo attendance_report_h($row['worker_name']); ?></td>
                        <td><?php echo attendance_report_h($row['Site_Name']); ?></td>
                        <td><?php echo attendance_report_h($row['photo_label'] ?? 'Attendance Photo'); ?></td>
                        <td><?php echo attendance_report_h($row['attendance_timestamp']); ?></td>
                        <td>
                            <?php if ($photoUrl !== ''): ?>
                            <a href="<?php echo attendance_report_h($photoUrl); ?>" target="_blank" rel="noopener">View Photo</a>
                            <?php else: ?>
                            <span class="reports-no-photo">No Photo Uploaded</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo attendance_report_h($gpsLabel); ?></td>
                        <td><?php echo attendance_report_h($distanceLabel); ?></td>
                        <td><?php echo attendance_report_h($row['gps_verification']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="report-pagination" id="attendanceEvidenceReportPagination" aria-label="Attendance evidence table pagination"></div>
    </div>
</div>

<?php if (!$embeddedReport): ?>
    </div>
</div>
</body>
</html>
<?php endif; ?>
