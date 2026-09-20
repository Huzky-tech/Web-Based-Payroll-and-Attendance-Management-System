<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/timekeeper_report_helpers.php';
require_once __DIR__ . '/payroll_approval_helpers.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR']);
$adminUserId = (int) ($_SESSION['user_id'] ?? 0);
tk_report_ensure_schema($conn);

// Turn Assistant Admin and Payroll Staff audit activity into persistent Admin notifications.
$categoryActions = [
    'leave_request_updates' => ['Worker Added', 'Employee Updated', 'Employee Deleted'],
    'payroll_processing' => ['Payroll Processed', 'Payroll Approved'],
    'attendance_issues' => ['Attendance Modified'],
    'system_updates' => ['Assign Worker to Site', 'Update Worker Site Role', 'Site Assignment', 'Site Assigned to Staff', 'Site Assignment Removed', 'Site Created', 'Site Archived', 'Site Restored', 'Site Timekeeper Assigned'],
    'daily_reports' => ['Overtime Request Approved'],
];
if ($currentRole === 'HR') {
    $categoryActions = [
        'leave_request_updates' => ['Worker Added', 'Add Worker', 'Employee Approved', 'Employee Updated', 'Update Employee', 'Employee Archived'],
        'system_updates' => ['Assign Worker to Site', 'Update Worker Site Role', 'Site Assignment', 'Site Assignment Removed'],
    ];
}
$settingsResult = $conn->query('SELECT * FROM notification_settings WHERE id = 1 LIMIT 1');
$notificationSettings = $settingsResult ? ($settingsResult->fetch_assoc() ?: []) : [];
$notifiableActions = [];
if ((int) ($notificationSettings['in_system_notifications'] ?? 1) === 1) {
    foreach ($categoryActions as $setting => $actions) {
        if ((int) ($notificationSettings[$setting] ?? 0) === 1) {
            $notifiableActions = array_merge($notifiableActions, $actions);
        }
    }
}
$notifiableActions = array_values(array_unique($notifiableActions));
if (!$notifiableActions && $currentRole !== 'Payroll Staff') {
    echo json_encode(['success' => true, 'items' => [], 'unread_count' => 0]);
    exit;
}

if ($currentRole !== 'Payroll Staff') {
    $escapedActions = array_map(fn($action) => "'" . $conn->real_escape_string($action) . "'", $notifiableActions);
    $actionListSql = implode(',', $escapedActions);

// Remove previously generated routine alerts (login, clock, lunch, etc.).
$conn->query("DELETE n FROM admin_notifications n
    INNER JOIN audit_logs al ON al.Audit_logsID = n.ReferenceID
    WHERE n.NotificationType = 'User Activity'
      AND al.Action NOT IN ({$actionListSql})");

$auditResult = $conn->query("SELECT al.Audit_logsID, al.UserID, al.Action, al.Details, al.Date, u.full_name
    FROM audit_logs al
    LEFT JOIN users u ON u.id = al.UserID
    WHERE NOT EXISTS (
        SELECT 1 FROM admin_notifications n
        WHERE n.NotificationType = 'User Activity' AND n.ReferenceID = al.Audit_logsID
          AND (n.RecipientUserID = {$adminUserId} OR n.RecipientUserID IS NULL)
    ) AND al.Action IN ({$actionListSql})
    ORDER BY al.Audit_logsID DESC LIMIT 100");
if ($auditResult) {
    $insertActivity = $conn->prepare("INSERT INTO admin_notifications
        (RecipientUserID, NotificationType, ReferenceID, Title, Message, IsRead, CreatedAt)
        VALUES (?, 'User Activity', ?, ?, ?, 0, ?)");
    while ($audit = $auditResult->fetch_assoc()) {
        $actorId = (int) ($audit['UserID'] ?? 0);
        $actorRole = $actorId > 0 ? auth_get_user_role($conn, $actorId) : '';
        if (!in_array($actorRole, ['Assistant Admin', 'Payroll Staff'], true) || $actorId === $adminUserId) continue;
        $actorName = trim((string) ($audit['full_name'] ?? '')) ?: $actorRole;
        $title = $actorName . ' · ' . $actorRole;
        $message = trim((string) ($audit['Action'] ?? 'Activity'));
        $details = trim((string) ($audit['Details'] ?? ''));
        if ($details !== '') $message .= ': ' . $details;
        $auditId = (int) $audit['Audit_logsID'];
        $createdAt = (string) $audit['Date'];
        $insertActivity->bind_param('iisss', $adminUserId, $auditId, $title, $message, $createdAt);
        $insertActivity->execute();
    }
    $insertActivity?->close();
}
}

$result = $conn->query("SHOW TABLES LIKE 'admin_notifications'");
if ($result === false || $result->num_rows === 0) {
    echo json_encode(['success' => true, 'items' => [], 'unread_count' => 0]);
    exit;
}

// Backfill notifications for pending payroll batches created before direct
// submission notifications were added. This also makes existing pending cards
// appear in the notification center immediately.
$payrollNotificationsEnabled = (int) ($notificationSettings['in_system_notifications'] ?? 1) === 1
    && (int) ($notificationSettings['payroll_processing'] ?? 1) === 1;
if (in_array($currentRole, ['Admin', 'Assistant Admin'], true) && $payrollNotificationsEnabled && payroll_approval_columns_ready($conn)) {
    $conn->query("
        INSERT INTO admin_notifications
            (RecipientUserID, NotificationType, ReferenceID, Title, Message, IsRead, CreatedAt)
        SELECT
            NULL,
            'Payroll Submitted',
            pr.Payroll_RecordsID,
            'Payroll awaiting approval',
            CONCAT(
                COALESCE(u.full_name, 'Payroll Staff'),
                ' submitted payroll for ', COALESCE(ps.Site_Name, 'a site'),
                ' (', DATE_FORMAT(pr.Period_start, '%b %e, %Y'),
                ' to ', DATE_FORMAT(pr.Period_end, '%b %e, %Y'),
                ', ', COALESCE(pr.worker_count, 0), ' workers).'
            ),
            0,
            COALESCE(pr.submitted_at, NOW())
        FROM payroll_records pr
        LEFT JOIN projectsite ps ON ps.SiteID = pr.SiteID
        LEFT JOIN users u ON u.id = pr.submitted_by
        WHERE LOWER(pr.Status) IN ('pending', 'processed')
          AND NOT EXISTS (
              SELECT 1 FROM admin_notifications existing
              WHERE existing.NotificationType = 'Payroll Submitted'
                AND existing.ReferenceID = pr.Payroll_RecordsID
          )
    ");
}

// Payroll Staff only receive workflow updates for payroll records they
// personally submitted. Remove broad alerts created by the older behavior.
if ($currentRole === 'Payroll Staff') {
    // Payroll Staff receive both their payroll workflow updates and site
    // assignment notices. Do not delete the latter during notification sync.
    $cleanupPayrollAlerts = $conn->prepare("DELETE FROM admin_notifications
        WHERE RecipientUserID = ?
          AND NotificationType NOT LIKE 'Payroll %'
          AND NotificationType NOT IN ('Site Assignment', 'Site Assignment Removed')");
    if ($cleanupPayrollAlerts) {
        $cleanupPayrollAlerts->bind_param('i', $adminUserId);
        $cleanupPayrollAlerts->execute();
        $cleanupPayrollAlerts->close();
    }

    if (payroll_approval_columns_ready($conn)) {
        $syncPayrollUpdates = $conn->prepare("
            INSERT INTO admin_notifications
                (RecipientUserID, NotificationType, ReferenceID, Title, Message, IsRead, CreatedAt)
            SELECT
                ?,
                CASE
                    WHEN LOWER(pr.Status) = 'approved' THEN 'Payroll Approved'
                    WHEN LOWER(pr.Status) = 'rejected' THEN 'Payroll Rejected'
                    ELSE 'Payroll Submitted'
                END,
                pr.Payroll_RecordsID,
                CASE
                    WHEN LOWER(pr.Status) = 'approved' THEN 'Payroll approved'
                    WHEN LOWER(pr.Status) = 'rejected' THEN 'Payroll rejected'
                    ELSE 'Payroll submitted'
                END,
                CONCAT(
                    COALESCE(ps.Site_Name, 'Assigned site'),
                    ' - ', DATE_FORMAT(pr.Period_start, '%b %e, %Y'),
                    ' to ', DATE_FORMAT(pr.Period_end, '%b %e, %Y'),
                    CASE
                        WHEN LOWER(pr.Status) = 'rejected' AND COALESCE(pr.rejection_reason, '') <> ''
                            THEN CONCAT('. Reason: ', pr.rejection_reason)
                        ELSE ''
                    END
                ),
                0,
                COALESCE(pr.approved_at, pr.rejected_at, pr.submitted_at, NOW())
            FROM payroll_records pr
            LEFT JOIN projectsite ps ON ps.SiteID = pr.SiteID
            WHERE pr.submitted_by = ?
              AND LOWER(pr.Status) IN ('pending', 'processed', 'approved', 'rejected')
              AND NOT EXISTS (
                  SELECT 1 FROM admin_notifications own
                  WHERE own.RecipientUserID = ?
                    AND own.NotificationType = CASE
                        WHEN LOWER(pr.Status) = 'approved' THEN 'Payroll Approved'
                        WHEN LOWER(pr.Status) = 'rejected' THEN 'Payroll Rejected'
                        ELSE 'Payroll Submitted'
                    END
                    AND own.ReferenceID = pr.Payroll_RecordsID
              )
        ");
        if ($syncPayrollUpdates) {
            $syncPayrollUpdates->bind_param('iii', $adminUserId, $adminUserId, $adminUserId);
            $syncPayrollUpdates->execute();
            $syncPayrollUpdates->close();
        }
    }

    // Existing assignments may have been made before recipient notifications
    // were introduced. Backfill one notification per assignment so the user is
    // informed the next time they open the notification center.
    $syncSiteAssignments = $conn->prepare("
        INSERT INTO admin_notifications
            (RecipientUserID, NotificationType, ReferenceID, Title, Message, IsRead, CreatedAt)
        SELECT
            ?,
            'Site Assignment',
            psa.staffAssignID,
            'New site assignment',
            CONCAT('You have been assigned to ', COALESCE(ps.Site_Name, 'a project site'), '.'),
            0,
            COALESCE(psa.Created_at, NOW())
        FROM payrollstaffassignment psa
        INNER JOIN payrollstaff pst ON pst.PayrollStaff_ID = psa.PayrollStaff_ID
        LEFT JOIN projectsite ps ON ps.SiteID = psa.SiteID
        WHERE pst.UserID = ?
          AND NOT EXISTS (
              SELECT 1 FROM admin_notifications own
              WHERE own.RecipientUserID = ?
                AND own.NotificationType = 'Site Assignment'
                AND own.ReferenceID = psa.staffAssignID
          )
    ");
    if ($syncSiteAssignments) {
        $syncSiteAssignments->bind_param('iii', $adminUserId, $adminUserId, $adminUserId);
        $syncSiteAssignments->execute();
        $syncSiteAssignments->close();
    }
}

// Give Assistant Admin users their own copy of global operational notifications so
// reading an item never changes another user's notification state.
if ($currentRole === 'Assistant Admin') {
    $copyGlobal = $conn->prepare("
        INSERT INTO admin_notifications
            (RecipientUserID, NotificationType, ReferenceID, Title, Message, IsRead, CreatedAt)
        SELECT ?, source.NotificationType, source.ReferenceID, source.Title, source.Message, 0, source.CreatedAt
        FROM admin_notifications source
        WHERE source.RecipientUserID IS NULL
          AND NOT EXISTS (
              SELECT 1 FROM admin_notifications own
              WHERE own.RecipientUserID = ?
                AND own.NotificationType = source.NotificationType
                AND own.ReferenceID = source.ReferenceID
          )
    ");
    if ($copyGlobal) {
        $copyGlobal->bind_param('ii', $adminUserId, $adminUserId);
        $copyGlobal->execute();
        $copyGlobal->close();
    }
}

$limit = max(1, min(50, (int) ($_GET['limit'] ?? 20)));
$unreadOnly = !empty($_GET['unread_only']);

$recipientClause = $currentRole === 'Admin'
    ? '(RecipientUserID = ? OR RecipientUserID IS NULL)'
    : 'RecipientUserID = ?';
$sql = "
    SELECT NotificationID, NotificationType, ReferenceID, Title, Message, IsRead, CreatedAt
    FROM admin_notifications
    WHERE {$recipientClause}
";
if ($currentRole === 'HR') {
    $sql .= " AND NotificationType <> 'Payroll Submitted'
              AND NotificationType <> 'Payroll Approved'
              AND NotificationType <> 'Payroll Rejected'
              AND Message NOT LIKE '%Payroll%'";
}
if ($unreadOnly) {
    $sql .= " AND IsRead = 0";
}
$sql .= " ORDER BY NotificationID DESC LIMIT ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to load notifications']);
    exit;
}

$stmt->bind_param('ii', $adminUserId, $limit);
$stmt->execute();
$queryResult = $stmt->get_result();

$items = [];
while ($row = $queryResult->fetch_assoc()) {
    $message = (string) ($row['Message'] ?? '');
    $message = preg_replace('/\s*\(\s*Assignment\s*ID\s*:\s*\d+\s*\)/i', '', $message);
    $message = preg_replace('/\s*\bAssignment\s*ID\s*:\s*\d+\b/i', '', $message);
    $message = trim((string) $message);
    $items[] = [
        'id' => (int) ($row['NotificationID'] ?? 0),
        'type' => (string) ($row['NotificationType'] ?? ''),
        'reference_id' => (int) ($row['ReferenceID'] ?? 0),
        'title' => (string) ($row['Title'] ?? ''),
        'message' => $message,
        'is_read' => (bool) ($row['IsRead'] ?? false),
        'created_at' => (string) ($row['CreatedAt'] ?? ''),
    ];
}
$stmt->close();

$unreadSql = "SELECT COUNT(*) AS total FROM admin_notifications WHERE IsRead = 0 AND {$recipientClause}";
if ($currentRole === 'HR') {
    $unreadSql .= " AND NotificationType <> 'Payroll Submitted'
                    AND NotificationType <> 'Payroll Approved'
                    AND NotificationType <> 'Payroll Rejected'
                    AND Message NOT LIKE '%Payroll%'";
}
$unreadStmt = $conn->prepare($unreadSql);
$unreadCount = 0;
if ($unreadStmt) {
    $unreadStmt->bind_param('i', $adminUserId);
    $unreadStmt->execute();
    $unreadRow = $unreadStmt->get_result()->fetch_assoc();
    $unreadCount = (int) ($unreadRow['total'] ?? 0);
    $unreadStmt->close();
}

echo json_encode([
    'success' => true,
    'items' => $items,
    'unread_count' => $unreadCount,
]);

$conn->close();
