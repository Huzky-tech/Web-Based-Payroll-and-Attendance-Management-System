<?php
include '../api/connection/db_config.php';
include '../includes/auth.php';

$currentRole = require_auth($conn, ['Assistant Admin']);
$embeddedDashboard = $embeddedDashboard ?? false;
$isHrHistory = ($currentRole === 'HR');

if (!$embeddedDashboard) {
    include '../admin/history.php';
    return;
}

$projectHistory = [];
$actionHistory = [];
$projectSummary = [
    'completed_projects' => 0,
    'total_workers' => 0,
    'total_payroll' => 0
];

$workerHistoryQuery = $conn->query("
    SELECT
        wa.Created_at,
        ps.Site_Name,
        CONCAT(COALESCE(w.First_Name, ''), ' ', COALESCE(w.Last_Name, '')) AS worker_name
    FROM workerassignment wa
    INNER JOIN projectsite ps ON wa.SiteID = ps.SiteID
    INNER JOIN worker w ON wa.WorkerID = w.WorkerID
    WHERE wa.Created_at IS NOT NULL
    ORDER BY wa.Created_at DESC
    LIMIT 20
");

if ($workerHistoryQuery) {
    while ($row = $workerHistoryQuery->fetch_assoc()) {
        $siteName = $row['Site_Name'] ?: 'Unnamed Site';
        $workerName = trim((string) ($row['worker_name'] ?? '')) ?: 'Worker';

        $actionHistory[] = [
            'category' => 'workers',
            'title' => 'Worker Assigned',
            'site' => $siteName,
            'description' => 'Assigned ' . $workerName . ' to ' . $siteName . '.',
            'performed_by' => 'Operations Team',
            'ts' => $row['Created_at'],
            'status' => 'Completed'
        ];
    }
}

$payrollTotalSelect = $isHrHistory ? "0 AS total_payroll" : "COALESCE(SUM(DISTINCT pr.Total_net_pay), 0) AS total_payroll";
$payrollJoin = $isHrHistory ? "" : "LEFT JOIN payroll_records pr ON pr.SiteID = ps.SiteID";
$projectQuery = $conn->query("
    SELECT
        ps.SiteID,
        ps.Site_Name,
        ps.Location,
        ps.Project_Type,
        ps.Start_Date,
        ps.End_Date,
        ps.Required_Workers,
        ps.Site_Manager,
        ps.Status,
        COUNT(DISTINCT wa.WorkerID) AS worker_count,
        {$payrollTotalSelect}
    FROM projectsite ps
    LEFT JOIN workerassignment wa ON wa.SiteID = ps.SiteID
    {$payrollJoin}
    GROUP BY
        ps.SiteID,
        ps.Site_Name,
        ps.Location,
        ps.Project_Type,
        ps.Start_Date,
        ps.End_Date,
        ps.Required_Workers,
        ps.Site_Manager,
        ps.Status
    ORDER BY
        CASE
            WHEN ps.End_Date IS NULL THEN 1
            ELSE 0
        END,
        ps.End_Date DESC,
        ps.Site_Name ASC
");

if ($projectQuery) {
    while ($project = $projectQuery->fetch_assoc()) {
        $startDate = $project['Start_Date'] ? new DateTime($project['Start_Date']) : null;
        $endDate = $project['End_Date'] ? new DateTime($project['End_Date']) : null;
        $durationDays = ($startDate && $endDate) ? $startDate->diff($endDate)->days + 1 : null;
        $status = trim((string) ($project['Status'] ?? ''));
        $normalizedStatus = $status !== '' ? ucwords(strtolower($status)) : ($endDate instanceof DateTime ? 'Finished' : 'In Progress');
        $isCompleted = strtolower($normalizedStatus) === 'completed' || strtolower($normalizedStatus) === 'finished';

        $projectHistory[] = [
            'site_id' => (int) $project['SiteID'],
            'title' => $project['Site_Name'] ?: 'Untitled Project',
            'location' => $project['Location'] ?: 'No location provided',
            'description' => $project['Project_Type']
                ? ($project['Project_Type'] . ' project managed by ' . ($project['Site_Manager'] ?: 'Unassigned manager') . '.')
                : ('Construction project managed by ' . ($project['Site_Manager'] ?: 'Unassigned manager') . '.'),
            'duration_days' => $durationDays,
            'workers' => (int) ($project['worker_count'] ?? 0),
            'payroll' => (float) ($project['total_payroll'] ?? 0),
            'start_date' => $project['Start_Date'],
            'end_date' => $project['End_Date'],
            'manager' => $project['Site_Manager'] ?: 'Not assigned',
            'status' => $normalizedStatus
        ];

        if ($isCompleted) {
            $projectSummary['completed_projects']++;
            $actionHistory[] = [
                'category' => 'sites',
                'title' => 'Project Completed',
                'site' => $project['Site_Name'] ?: 'Untitled Project',
                'description' => ($project['Site_Name'] ?: 'This project') . ' was marked ' . $normalizedStatus . ' on ' . ($project['End_Date'] ?: 'the recorded completion date') . '.',
                'performed_by' => $project['Site_Manager'] ?: 'Project Team',
                'ts' => $project['End_Date'],
                'status' => $normalizedStatus
            ];
        }
        $projectSummary['total_workers'] += (int) ($project['worker_count'] ?? 0);
        $projectSummary['total_payroll'] += (float) ($project['total_payroll'] ?? 0);
    }
}

usort($actionHistory, static function ($left, $right) {
    $leftTime = strtotime((string) ($left['ts'] ?? '')) ?: 0;
    $rightTime = strtotime((string) ($right['ts'] ?? '')) ?: 0;
    return $rightTime <=> $leftTime;
});

$actionHistory = array_slice($actionHistory, 0, 30);
?>
<?php if (!$embeddedDashboard): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History - Philippians CDO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/history.css">
    <script src="../js/action_result_modal.js?v=20260912-1" defer></script>
<script src="../js/history.js" defer></script>
</head>
<body>
<?php endif; ?>

<div class="content-area history-page">
    <script>
        window.historyActionData = <?php echo json_encode($actionHistory, ( JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.projectHistoryData = <?php echo json_encode([
            'summary' => [
                'completed_projects' => (int) $projectSummary['completed_projects'],
                'total_workers' => (int) $projectSummary['total_workers'],
            'total_payroll' => $isHrHistory ? 0 : round((float) $projectSummary['total_payroll'], 2)
            ],
            'projects' => $projectHistory
        ], ( JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.historyHidePayroll = <?php echo json_encode($isHrHistory, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>

    <div class="history-container">
        <div class="top-card">
            <h2>History</h2>
            <p>View completed actions and archived projects</p>

            <div class="history-tabs">
                <button class="history-tab active" type="button" data-tab="action">Action History</button>
                <button class="history-tab" type="button" data-tab="project">Project History</button>
            </div>
        </div>

        <div class="history-view active" id="actionHistoryView">
        <div class="stats">
            <div class="stat-card">
                <div>
                    <h4>Total Actions</h4>
                    <h2 id="historyTotalActions">0</h2>
                </div>
                <div class="icon-box gray">
                    <i class="fa-regular fa-calendar"></i>
                </div>
            </div>

            <?php if (!$isHrHistory): ?>
            <div class="stat-card">
                <div>
                    <h4>Payroll Actions</h4>
                    <h2 class="stat-green" id="historyPayrollActions">0</h2>
                </div>
                <div class="icon-box green">
                    <i class="fa-solid fa-dollar-sign"></i>
                </div>
            </div>
            <?php endif; ?>

            <div class="stat-card">
                <div>
                    <h4>Worker Actions</h4>
                    <h2 class="stat-blue" id="historyWorkerActions">0</h2>
                </div>
                <div class="icon-box blue">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>

            <div class="stat-card">
                <div>
                    <h4>Site Actions</h4>
                    <h2 class="stat-purple" id="historySiteActions">0</h2>
                </div>
                <div class="icon-box purple">
                    <i class="fa-solid fa-building"></i>
                </div>
            </div>
        </div>

        <div class="filter-bar">
            <div class="left-filter">
                <i class="fa-solid fa-filter history-filter-icon"></i>
                <button class="filter-btn active" type="button" data-filter="all">All</button>
                <?php if (!$isHrHistory): ?><button class="filter-btn" type="button" data-filter="payroll">Payroll</button><?php endif; ?>
                <button class="filter-btn" type="button" data-filter="workers">Workers</button>
                <button class="filter-btn" type="button" data-filter="sites">Sites</button>
            </div>

            <div class="search-box">
                <input type="text" id="historySearchInput" placeholder="Search history...">
            </div>
        </div>

        <div class="history-list" id="historyList">
            <div class="history-empty">Loading history...</div>
        </div>
        </div>

        <div class="history-view" id="projectHistoryView">
            <div class="project-stats">
                <div class="stat-card">
                    <div>
                        <h4>Completed Projects</h4>
                        <h2 class="stat-green" id="projectCompletedCount">0</h2>
                    </div>
                    <div class="icon-box green">
                        <i class="fa-solid fa-check"></i>
                    </div>
                </div>

                <div class="stat-card">
                    <div>
                        <h4>Total Workers</h4>
                        <h2 class="stat-blue" id="projectWorkerCount">0</h2>
                    </div>
                    <div class="icon-box blue">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>

                <?php if (!$isHrHistory): ?>
                <div class="stat-card">
                    <div>
                        <h4>Total Payroll</h4>
                        <h2 class="stat-purple" id="projectPayrollTotal">PHP 0</h2>
                    </div>
                    <div class="icon-box purple">
                        <i class="fa-solid fa-dollar-sign"></i>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="project-search-box">
                <input type="text" id="projectHistorySearchInput" placeholder="Search projects by name, location, or manager...">
            </div>

            <div class="projects-grid" id="projectHistoryGrid">
                <div class="history-empty">Loading projects...</div>
            </div>
        </div>
    </div>
</div>

<?php if (!$embeddedDashboard): ?>
</body>
</html>
<?php endif; ?>
