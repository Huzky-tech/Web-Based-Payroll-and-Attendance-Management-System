<?php
/**
 * Helpers for recording completed site assignments in siteassignmenthistory.
 */

function record_site_assignment_history(
    mysqli $conn,
    int $workerId,
    int $siteId,
    string $startDate,
    ?string $endDate = null
): bool {
    if ($workerId <= 0 || $siteId <= 0 || $startDate === '' || $startDate === '0000-00-00') {
        return false;
    }

    $end = $endDate ?? date('Y-m-d');

    $stmt = $conn->prepare(
        'INSERT INTO siteassignmenthistory (WorkerID, SiteID, StartDate, EndDate) VALUES (?, ?, ?, ?)'
    );
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('iiss', $workerId, $siteId, $startDate, $end);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function close_assignment_to_history(mysqli $conn, int $workerId, int $siteId, string $endDate = ''): bool
{
    if ($endDate === '') {
        $endDate = date('Y-m-d');
    }

    $stmt = $conn->prepare(
        'SELECT Assigned_Date FROM workerassignment WHERE WorkerID = ? AND SiteID = ? LIMIT 1'
    );
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ii', $workerId, $siteId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row || empty($row['Assigned_Date'])) {
        return false;
    }

    return record_site_assignment_history(
        $conn,
        $workerId,
        $siteId,
        $row['Assigned_Date'],
        $endDate
    );
}

function close_assignment_row_to_history(mysqli $conn, array $assignment, string $endDate = ''): bool
{
    $workerId = (int) ($assignment['WorkerID'] ?? 0);
    $siteId = (int) ($assignment['SiteID'] ?? 0);
    $startDate = (string) ($assignment['Assigned_Date'] ?? '');

    if ($endDate === '') {
        $endDate = date('Y-m-d');
    }

    return record_site_assignment_history($conn, $workerId, $siteId, $startDate, $endDate);
}

/**
 * Remove internal database IDs from text shown in employment history.
 */
function sanitize_employment_timeline_text(string $text): string
{
    $text = preg_replace('/\s*\(Worker ID:\s*\d+\)/i', '', $text);
    $text = preg_replace('/\s*\(ID:\s*\d+\)/i', '', $text);
    $text = preg_replace('/\s*Worker ID:\s*\d+/i', '', $text);
    $text = preg_replace('/\s*\(Assignment ID:\s*\d+\)/i', '', $text);
    $text = preg_replace('/\s*Assignment ID:\s*\d+/i', '', $text);
    $text = preg_replace('/\bfor worker \d+\b/i', 'for this employee', $text);
    $text = preg_replace('/\bat site \d+\b/i', '', $text);
    $text = preg_replace('/\bworker \d+\b/i', 'employee', $text);
    $text = preg_replace('/\bsite \d+\b/i', 'site', $text);
    $text = preg_replace('/\s+/', ' ', trim($text));

    return $text;
}

function friendly_employment_timeline_action(string $action): string
{
    $map = [
        'Update Worker Site Role' => 'Role Updated',
        'Assign Worker to Site' => 'Site Assignment',
        'Site Assigned to Worker' => 'Site Assignment',
        'Site Assignment Removed' => 'Site Unassigned',
        'Site Transfer' => 'Site Transfer',
        'Worker Added' => 'Hired',
        'Employee Approved' => 'Employment Approved',
    ];

    return $map[$action] ?? $action;
}

/**
 * Payroll-staff site assignments are not part of a worker's employment history.
 */
function should_include_audit_in_worker_timeline(string $action, string $details): bool
{
    $blockedActions = [
        'Site Assigned to Staff',
        'Add Assignment',
        'Site Created',
        'Site Updated',
        'Site Deactivated',
    ];

    foreach ($blockedActions as $blocked) {
        if (strcasecmp($action, $blocked) === 0) {
            return false;
        }
    }

    if (preg_match('/\bto staff\b/i', $details) || preg_match('/\bfrom staff\b/i', $details)) {
        return false;
    }

    if (preg_match('/payroll\s*staff/i', $details)) {
        return false;
    }

    if (preg_match('/Assignment ID:\s*\d+/i', $details) && stripos($action, 'Worker') === false) {
        return false;
    }

    if (stripos($action, 'Staff') !== false && stripos($action, 'Worker') === false) {
        return false;
    }

    return true;
}
