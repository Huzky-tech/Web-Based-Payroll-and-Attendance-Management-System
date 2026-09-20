<?php
include '../api/connection/db_config.php';
include '../includes/auth.php';

require_auth($conn, ['Admin', 'Assistant Admin']);
$embeddedDashboard = $embeddedDashboard ?? false;

$projectHistory = [];
$actionHistory = [];
$projectSummary = [
    'completed_projects' => 0,
    'total_workers' => 0,
    'total_payroll' => 0
];

$payrollHistoryQuery = $conn->query("
    SELECT
        pr.SiteID,
        pr.Period_start,
        pr.Period_end,
        pr.Total_net_pay,
        pr.Status,
        pr.submitted_at,
        ps.Site_Name,
        COALESCE(NULLIF(TRIM(submitter.full_name), ''), submitter.email, 'Unknown payroll staff') AS submitted_by_name
    FROM payroll_records pr
    INNER JOIN projectsite ps ON pr.SiteID = ps.SiteID
    LEFT JOIN users submitter ON submitter.id = pr.submitted_by
    ORDER BY pr.Period_end DESC, pr.Payroll_RecordsID DESC
    LIMIT 20
");

if ($payrollHistoryQuery) {
    while ($row = $payrollHistoryQuery->fetch_assoc()) {
        $siteName = $row['Site_Name'] ?: 'Unnamed Site';
        $periodStart = $row['Period_start'] ?: '';
        $periodEnd = $row['Period_end'] ?: '';
        $periodLabel = ($periodStart && $periodEnd)
            ? (date('M j, Y', strtotime($periodStart)) . ' - ' . date('M j, Y', strtotime($periodEnd)))
            : 'Unknown pay period';

        $actionHistory[] = [
            'category' => 'payroll',
            'title' => 'Processed Payroll',
            'site' => $siteName,
            'description' => 'Processed payroll for ' . $siteName . ' - ' . $periodLabel . '. Total amount: PHP ' . number_format((float) ($row['Total_net_pay'] ?? 0), 2) . '.',
            'performed_by' => $row['submitted_by_name'] ?: 'Unknown payroll staff',
            'ts' => $row['submitted_at'] ?: ($periodEnd ?: $periodStart),
            'status' => $row['Status'] ?: 'Processed'
        ];
    }
}

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
        COALESCE(SUM(DISTINCT pr.Total_net_pay), 0) AS total_payroll,
        (
            SELECT COUNT(*)
            FROM attendance a
            WHERE a.SiteID = ps.SiteID
        ) AS attendance_count
    FROM projectsite ps
    LEFT JOIN workerassignment wa ON wa.SiteID = ps.SiteID
    LEFT JOIN payroll_records pr ON pr.SiteID = ps.SiteID
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
            'attendance_records' => (int) ($project['attendance_count'] ?? 0),
            'start_date' => $project['Start_Date'],
            'end_date' => $project['End_Date'],
            'manager' => $project['Site_Manager'] ?: 'Not assigned',
            'status' => $normalizedStatus,
            'project_type' => $project['Project_Type'] ?: 'Construction',
            'required_workers' => (int) ($project['Required_Workers'] ?? 0),
            'completed_by' => 'Not recorded'
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
                'total_payroll' => round((float) $projectSummary['total_payroll'], 2)
            ],
            'projects' => $projectHistory
        ], ( JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
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

            <div class="stat-card">
                <div>
                    <h4>Payroll Actions</h4>
                    <h2 class="stat-green" id="historyPayrollActions">0</h2>
                </div>
                <div class="icon-box green">
                    <i class="fa-solid fa-dollar-sign"></i>
                </div>
            </div>

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
                <button class="filter-btn" type="button" data-filter="payroll">Payroll</button>
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

                <div class="stat-card">
                    <div>
                        <h4>Total Payroll</h4>
                        <h2 class="stat-purple" id="projectPayrollTotal">PHP 0</h2>
                    </div>
                    <div class="icon-box purple">
                        <i class="fa-solid fa-dollar-sign"></i>
                    </div>
                </div>
            </div>

            <div class="project-search-box">
                <input type="text" id="projectHistorySearchInput" placeholder="Search projects by name, location, or manager...">
            </div>

            <div class="projects-grid" id="projectHistoryGrid">
                <div class="history-empty">Loading projects...</div>
            </div>
        </div>
    </div>

    <div class="project-modal-overlay" id="projectDetailsModal" aria-hidden="true">
        <div class="project-modal" role="dialog" aria-modal="true" aria-labelledby="projectDetailsTitle">
            <div class="project-modal-header">
                <div class="project-modal-title">
                    <h2 id="projectDetailsTitle">Project Details</h2>
                    <p id="projectDetailsSubtitle">Completed Project Details</p>
                </div>

                <button type="button" class="project-modal-close" id="closeProjectDetailsModal" aria-label="Close project details">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <hr class="project-modal-divider">

            <div class="project-modal-section-title">Project Information</div>

            <div class="project-modal-info-grid">
                <div>
                    <div class="project-modal-info-item">
                        <div class="project-modal-label">Location</div>
                        <div class="project-modal-value" id="projectDetailsLocation">-</div>
                    </div>

                    <div class="project-modal-info-item">
                        <div class="project-modal-label">Start Date</div>
                        <div class="project-modal-value" id="projectDetailsStartDate">-</div>
                    </div>

                    <div class="project-modal-info-item">
                        <div class="project-modal-label">Project Duration</div>
                        <div class="project-modal-value" id="projectDetailsDuration">-</div>
                    </div>

                    <div class="project-modal-info-item">
                        <div class="project-modal-label">Project Type</div>
                        <div class="project-modal-value" id="projectDetailsType">-</div>
                    </div>
                </div>

                <div>
                    <div class="project-modal-info-item">
                        <div class="project-modal-label">Site Manager</div>
                        <div class="project-modal-value" id="projectDetailsManager">-</div>
                    </div>

                    <div class="project-modal-info-item">
                        <div class="project-modal-label">Completion Date</div>
                        <div class="project-modal-value" id="projectDetailsCompletionDate">-</div>
                    </div>

                    <div class="project-modal-info-item">
                        <div class="project-modal-label">Completed By</div>
                        <div class="project-modal-value" id="projectDetailsCompletedBy">-</div>
                    </div>

                    <div class="project-modal-info-item">
                        <div class="project-modal-label">Required Workers</div>
                        <div class="project-modal-value" id="projectDetailsRequiredWorkers">-</div>
                    </div>
                </div>
            </div>

            <div class="project-modal-description">
                <div class="project-modal-label">Description</div>
                <div class="project-modal-value" id="projectDetailsDescription">-</div>
            </div>

            <div class="project-modal-section-title">Project Statistics</div>

            <div class="project-modal-stats">
                <div class="project-modal-card blue">
                    <i class="fa-solid fa-users"></i>
                    <h3 id="projectDetailsWorkers">0</h3>
                    <p>Total Workers</p>
                </div>

                <div class="project-modal-card green">
                    <i class="fa-solid fa-dollar-sign"></i>
                    <h3 id="projectDetailsPayroll">PHP 0</h3>
                    <p>Payroll Processed</p>
                </div>

                <div class="project-modal-card purple">
                    <i class="fa-regular fa-file-lines"></i>
                    <h3 id="projectDetailsAttendance">0</h3>
                    <p>Attendance Records</p>
                </div>
            </div>

            <div class="project-modal-footer">
                <button type="button" class="project-modal-footer-close" id="closeProjectDetailsFooter">Close</button>
            </div>
        </div>
    </div>

    <div class="action-modal-overlay" id="actionDetailsModal" aria-hidden="true">
        <div class="action-modal" role="dialog" aria-modal="true" aria-labelledby="actionDetailsTitle">
            <div class="action-modal-header">
                <h2 id="actionDetailsTitle">Action Details</h2>

                <button type="button" class="action-modal-close" id="closeActionDetailsModal" aria-label="Close action details">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="action-modal-body">
                <div class="action-modal-section">
                    <div class="action-modal-label">Action Type</div>
                    <div class="action-modal-value" id="actionDetailsType">-</div>
                </div>

                <div class="action-modal-section">
                    <div class="action-modal-label">Related Entity</div>
                    <div class="action-modal-value" id="actionDetailsEntity">-</div>
                </div>

                <div class="action-modal-section">
                    <div class="action-modal-label">Details</div>
                    <div class="action-modal-description" id="actionDetailsDescription">-</div>
                </div>

                <div class="action-modal-section">
                    <div class="action-modal-label">Timestamp</div>
                    <div class="action-modal-value normal" id="actionDetailsTimestamp">-</div>
                </div>

                <div class="action-modal-section">
                    <div class="action-modal-label">Performed By</div>
                    <div class="action-modal-value normal" id="actionDetailsPerformedBy">-</div>
                </div>

                <div class="action-modal-section">
                    <div class="action-modal-label">Status</div>
                    <div class="action-modal-status" id="actionDetailsStatus">
                        <i class="fa-regular fa-circle-check"></i>
                        <span id="actionDetailsStatusText">Completed</span>
                    </div>
                </div>
            </div>

            <div class="action-modal-footer">
                <button type="button" class="action-modal-footer-close" id="closeActionDetailsFooter">Close</button>
            </div>
        </div>
    </div>
</div>

<?php if (!$embeddedDashboard): ?>
</body>
</html>
<?php endif; ?>
