<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
require_once __DIR__ . '/site_schedule_helpers.php';
require_once __DIR__ . '/../includes/site_priority_helpers.php';
require_once __DIR__ . '/site_activation_helpers.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    site_priority_ensure_user_table($conn);

    // Bring existing site records in line with the three-worker activation rule
    // before filters such as active_only are applied.
    $siteIdsResult = $conn->query("SELECT SiteID FROM projectsite WHERE LOWER(COALESCE(Status, '')) <> 'archived'");
    if ($siteIdsResult) {
        while ($siteIdRow = $siteIdsResult->fetch_assoc()) {
            site_sync_activation_status($conn, (int) $siteIdRow['SiteID']);
        }
        $siteIdsResult->free();
    }

    $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
    $timekeeperSelect = "NULL AS Timekeeper_UserID, NULL AS Timekeeper";
    $timekeeperJoin = '';
    $columnCheck = $conn->query("SHOW COLUMNS FROM projectsite LIKE 'Timekeeper_UserID'");
    if ($columnCheck && $columnCheck->num_rows > 0) {
        $timekeeperJoin = 'LEFT JOIN users tk_u ON tk_u.id = s.Timekeeper_UserID';
        $timekeeperSelect = "s.Timekeeper_UserID,
                COALESCE(
                    tk_u.full_name,
                    (
                        SELECT TRIM(CONCAT(w.First_Name, ' ', w.Last_Name))
                        FROM workerassignment wa
                        INNER JOIN worker w ON w.WorkerID = wa.WorkerID
                        WHERE wa.SiteID = s.SiteID
                          AND LOWER(wa.Role_On_Site) LIKE '%timekeeper%'
                        LIMIT 1
                    )
                ) AS Timekeeper";
    }

    $scheduleSelect = site_schedule_select_sql($conn);
    $geofenceSelect = "NULL AS Geofence_Radius_M, NULL AS Geofence_Lat, NULL AS Geofence_Lng";
    $geofenceColumn = $conn->query("SHOW COLUMNS FROM projectsite LIKE 'Geofence_Radius_M'");
    if ($geofenceColumn && $geofenceColumn->num_rows > 0) {
        $geofenceSelect = 's.Geofence_Radius_M, s.Geofence_Lat, s.Geofence_Lng';
    }
    $activeOnly = isset($_GET['active_only']) && (string) $_GET['active_only'] === '1';
    $excludeArchived = isset($_GET['exclude_archived']) && (string) $_GET['exclude_archived'] === '1';
    $whereClause = $activeOnly
        ? "WHERE LOWER(COALESCE(s.Status, '')) = 'active'"
        : ($excludeArchived ? "WHERE LOWER(COALESCE(s.Status, '')) <> 'archived'" : '');

    $sql = "SELECT
                s.SiteID,
                s.Site_Name,
                s.Location,
                s.Coordinates,
                s.Start_Date,
                s.End_Date,
                s.Required_Workers,
                s.Site_Manager,
                {$timekeeperSelect},
                s.Status,
                EXISTS(
                    SELECT 1
                    FROM attendance a
                    WHERE a.SiteID = s.SiteID
                ) AS Has_Attendance,
                EXISTS(
                    SELECT 1
                    FROM user_site_priorities usp
                    WHERE usp.UserID = {$currentUserId} AND usp.SiteID = s.SiteID
                ) AS Is_Priority,
                {$geofenceSelect},
                {$scheduleSelect},
                (SELECT COUNT(*) FROM workerassignment wa WHERE wa.SiteID = s.SiteID) AS Current_Workers
            FROM projectsite s
            {$timekeeperJoin}
            LEFT JOIN (
                SELECT ss.SiteID, ss.ShiftStart, ss.ShiftEnd
                FROM site_schedule ss
                INNER JOIN (
                    SELECT SiteID, MIN(Site_ScheduleID) AS FirstScheduleID
                    FROM site_schedule
                    GROUP BY SiteID
                ) first_schedule
                    ON first_schedule.FirstScheduleID = ss.Site_ScheduleID
            ) sc ON sc.SiteID = s.SiteID
            {$whereClause}
            ORDER BY s.SiteID DESC, Is_Priority DESC";

    $result = $conn->query($sql);
    if (!$result) {
        error_log('get_sites query failed: ' . $conn->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Unable to load sites. Please contact the administrator.']);
        exit;
    }

    $sites = [];
    while ($row = $result->fetch_assoc()) {
        $sites[] = enrich_site_schedule_row($row);
    }

    echo json_encode($sites);
    exit;
}

?>
