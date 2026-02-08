<?php
header('Content-Type: application/json');
include 'connection/db_config.php';

// Total Users
$totalUsersQuery = $conn->query("SELECT COUNT(*) as count FROM users");
$totalUsers = $totalUsersQuery->fetch_assoc()['count'];

// System Health - hardcoded
$systemHealth = "Good";

// Last Backup - hardcoded
$lastBackup = "2023-07-05 03:00 AM";

// Active Users - count active workers
$activeUsersQuery = $conn->query("SELECT COUNT(*) as count FROM Worker w JOIN WorkerStatus ws ON w.WorkerStatusID = ws.WorkerStatusID WHERE ws.Status = 'Active'");
$activeUsers = $activeUsersQuery->fetch_assoc()['count'];

// Sites data with current workers and basic attendance calculation
$sitesQuery = "SELECT s.SiteID, s.Site_Name, s.Location, s.Required_Workers, s.Site_Manager, s.Status, COUNT(wa.WorkerID) as Current_Workers FROM ProjectSite s LEFT JOIN WorkerAssignment wa ON s.SiteID = wa.SiteID GROUP BY s.SiteID ORDER BY s.Site_Name";
$sitesResult = $conn->query($sitesQuery);
$sites = [];
$totalAttendance = 0;
$siteCount = 0;

while ($row = $sitesResult->fetch_assoc()) {
    $siteID = $row['SiteID'];
    // Calculate attendance rate: average hours worked over last 30 days / expected shift hours
    // Assuming shift duration from Site_schedule (simplified: average shift length)
    $shiftQuery = $conn->query("SELECT AVG(TIMEDIFF(ShiftEnd, ShiftStart)) as avg_shift FROM Site_schedule WHERE SiteID = $siteID");
    $shiftRow = $shiftQuery->fetch_assoc();
    $avgShiftHours = $shiftRow['avg_shift'] ? (strtotime($shiftRow['avg_shift']) - strtotime('00:00:00')) / 3600 : 8; // default 8 hours

    $attendanceQuery = $conn->query("SELECT AVG(a.Hours_Worked / $avgShiftHours) * 100 as attendance_rate FROM Attendance a WHERE a.SiteID = $siteID AND a.Date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $attendanceRow = $attendanceQuery->fetch_assoc();
    $attendanceRate = round($attendanceRow['attendance_rate'] ?? 0);

    $row['attendance_rate'] = $attendanceRate;
    $sites[] = $row;

    $totalAttendance += $attendanceRate;
    $siteCount++;
}

// KPIs
$activeSites = count(array_filter($sites, fn($s) => strtolower($s['Status']) == 'active'));
$atCapacity = count(array_filter($sites, fn($s) => $s['Current_Workers'] >= $s['Required_Workers']));
$needsWorkers = count(array_filter($sites, fn($s) => $s['Current_Workers'] < $s['Required_Workers']));
$avgAttendance = $siteCount > 0 ? round($totalAttendance / $siteCount) : 0;

// Recent Activity
$auditQuery = "SELECT al.Action, u.email as user, al.Details as target, al.Date as time FROM Audit_logs al JOIN users u ON al.UserID = u.id ORDER BY al.Date DESC LIMIT 5";
$auditResult = $conn->query($auditQuery);
$recentActivity = [];
while ($row = $auditResult->fetch_assoc()) {
    $recentActivity[] = $row;
}

$response = [
    'summary' => [
        'total_users' => $totalUsers,
        'system_health' => $systemHealth,
        'last_backup' => $lastBackup,
        'active_users' => $activeUsers
    ],
    'kpis' => [
        'active_sites' => $activeSites,
        'at_capacity' => $atCapacity,
        'needs_workers' => $needsWorkers,
        'avg_attendance' => $avgAttendance
    ],
    'sites' => $sites,
    'recent_activity' => $recentActivity
];

echo json_encode($response);
$conn->close();
?>
