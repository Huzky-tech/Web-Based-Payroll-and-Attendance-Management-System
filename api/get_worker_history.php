<?php
header('Content-Type: application/json');

include 'connection/db_config.php';
include '../includes/auth.php';
require_once 'assignment_history_helpers.php';

require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR', 'Timekeeper']);

$workerId = (int) ($_GET['worker_id'] ?? 0);

if ($workerId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Worker ID is required']);
    exit;
}

$timeline = [];

function addTimelineEvent(array &$timeline, string $title, string $description, string $eventDate, string $processedBy = 'System'): void
{
    if ($eventDate === '' || $eventDate === '0000-00-00') {
        return;
    }
    $timeline[] = [
        'title' => $title,
        'description' => $description,
        'processed_by' => $processedBy,
        'event_date' => $eventDate,
    ];
}

$workerSql = "SELECT w.DateHired, w.First_Name, w.Last_Name,
        wa.Assigned_Date, wa.Role_On_Site, ps.Site_Name AS current_site, ps.Location AS current_location
    FROM worker w
    LEFT JOIN workerassignment wa ON w.WorkerID = wa.WorkerID
    LEFT JOIN projectsite ps ON wa.SiteID = ps.SiteID
    WHERE w.WorkerID = ?
    LIMIT 1";
$stmt = $conn->prepare($workerSql);
if ($stmt) {
    $stmt->bind_param('i', $workerId);
    $stmt->execute();
    $worker = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($worker && !empty($worker['DateHired'])) {
        $name = trim(($worker['First_Name'] ?? '') . ' ' . ($worker['Last_Name'] ?? ''));
        addTimelineEvent(
            $timeline,
            'Hired',
            'Joined the company' . ($name !== '' ? " as {$name}" : ''),
            $worker['DateHired'],
            'Assistant Admin'
        );
    }

    if ($worker && !empty($worker['current_site'])) {
        $assignedDate = $worker['Assigned_Date'] ?? date('Y-m-d');
        $role = trim((string) ($worker['Role_On_Site'] ?? ''));
        $location = trim((string) ($worker['current_location'] ?? ''));
        $desc = 'Currently assigned to ' . $worker['current_site'];
        if ($role !== '') {
            $desc .= ' as ' . $role;
        }
        if ($location !== '') {
            $desc .= ' (' . $location . ')';
        }
        addTimelineEvent(
            $timeline,
            'Site Transfer',
            $desc,
            $assignedDate,
            'Assistant Admin'
        );
    }
}

$pastSql = "SELECT 
        sah.StartDate,
        sah.EndDate,
        ps.Site_Name,
        ps.Location
    FROM siteassignmenthistory sah
    INNER JOIN projectsite ps ON sah.SiteID = ps.SiteID
    WHERE sah.WorkerID = ?
    ORDER BY COALESCE(sah.EndDate, sah.StartDate) DESC, sah.StartDate DESC";

$stmt = $conn->prepare($pastSql);
if ($stmt) {
    $stmt->bind_param('i', $workerId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $siteLabel = $row['Site_Name'] ?? 'project site';
        $location = $row['Location'] ?? '';
        $hasEnd = !empty($row['EndDate']) && $row['EndDate'] !== '0000-00-00';
        $eventDate = $hasEnd ? $row['EndDate'] : $row['StartDate'];

        if ($hasEnd) {
            $desc = "Transferred from {$siteLabel}";
            if ($location !== '') {
                $desc .= " ({$location})";
            }
            $desc .= '. Assignment ended ' . date('F j, Y', strtotime($row['EndDate']));
        } else {
            $desc = "Assigned to {$siteLabel}";
            if ($location !== '') {
                $desc .= " ({$location})";
            }
        }

        addTimelineEvent(
            $timeline,
            'Site Transfer',
            $desc,
            $eventDate,
            'Assistant Admin'
        );
    }
    $stmt->close();
}

$payrollSql = "SELECT Pay_Period_Start, Pay_Period_End, Gross_Pay, Date_Processed
    FROM payroll
    WHERE WorkerID = ?
    ORDER BY Date_Processed DESC
    LIMIT 5";
$stmt = $conn->prepare($payrollSql);
if ($stmt) {
    $stmt->bind_param('i', $workerId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $gross = number_format((float) ($row['Gross_Pay'] ?? 0), 2);
        addTimelineEvent(
            $timeline,
            'Salary Adjustment',
            "Payroll processed for period {$row['Pay_Period_Start']} to {$row['Pay_Period_End']} (Gross: PHP {$gross})",
            $row['Date_Processed'],
            'Admin User'
        );
    }
    $stmt->close();
}

$auditSql = "SELECT a.Action, a.Details, a.Date, u.full_name
    FROM audit_logs a
    LEFT JOIN users u ON a.UserID = u.id
    WHERE (
        a.Details LIKE ?
        OR a.Details LIKE ?
        OR a.Details LIKE ?
    )
    AND (
        a.Action LIKE '%Employee%'
        OR a.Action LIKE '%Worker%'
        OR a.Action = 'Site Transfer'
        OR a.Action = 'Assign Worker to Site'
        OR a.Action = 'Site Assigned to Worker'
        OR a.Action = 'Update Worker Site Role'
    )
    ORDER BY a.Date DESC
    LIMIT 20";
$stmt = $conn->prepare($auditSql);
if ($stmt) {
    $idPattern = '%(ID: ' . $workerId . ')%';
    $workerIdPattern = '%Worker ID: ' . $workerId . '%';
    $workerPattern = '%Worker ' . $workerId . ' %';
    $stmt->bind_param('sss', $idPattern, $workerIdPattern, $workerPattern);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $action = $row['Action'] ?? '';
        $rawDetails = $row['Details'] ?? '';

        if (!should_include_audit_in_worker_timeline($action, $rawDetails)) {
            continue;
        }

        if (stripos($action, 'Employee Updated') !== false && stripos($rawDetails, 'Transferred') === false) {
            continue;
        }

        $details = sanitize_employment_timeline_text($rawDetails);
        if ($details === '') {
            continue;
        }

        addTimelineEvent(
            $timeline,
            friendly_employment_timeline_action($action),
            $details,
            substr($row['Date'], 0, 10),
            $row['full_name'] ?? 'Admin User'
        );
    }
    $stmt->close();
}

usort($timeline, static function ($a, $b) {
    return strcmp($b['event_date'], $a['event_date']);
});

$seen = [];
$deduped = [];
foreach ($timeline as $event) {
    $key = $event['title'] . '|' . $event['event_date'] . '|' . $event['description'];
    if (isset($seen[$key])) {
        continue;
    }
    $seen[$key] = true;
    $deduped[] = $event;
}

$conn->close();

echo json_encode([
    'success' => true,
    'timeline' => $deduped,
    'history' => $deduped,
]);
