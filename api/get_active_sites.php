<?php
/**
 * Get Active Sites API
 * Retrieve only construction sites with Active status
 */

header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/site_schedule_helpers.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR']);
$userId = (int) ($_SESSION['user_id'] ?? 0);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $joinSql = '';
    $whereParts = ["LOWER(s.Status) = 'active'"];
    $types = '';
    $params = [];

    if ($currentRole === 'Payroll Staff') {
        $payrollStaffId = auth_get_payroll_staff_id($conn, $userId);
        if ($payrollStaffId <= 0) {
            echo json_encode([
                'success' => true,
                'sites' => [],
                'count' => 0
            ]);
            exit;
        }

        $joinSql = 'INNER JOIN payrollstaffassignment psa ON psa.SiteID = s.SiteID';
        $whereParts[] = 'psa.PayrollStaff_ID = ?';
        $types .= 'i';
        $params[] = $payrollStaffId;
    }

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

    $sql = "SELECT
                s.SiteID,
                s.Site_Name,
                s.Location,
                s.Coordinates,
                s.Start_Date,
                s.End_Date,
                s.Required_Workers,
                s.Site_Manager,
                (SELECT psa_owner.PayrollStaff_ID FROM payrollstaffassignment psa_owner WHERE psa_owner.SiteID = s.SiteID LIMIT 1) AS PayrollStaff_ID,
                (SELECT u_owner.full_name
                 FROM payrollstaffassignment psa_owner
                 INNER JOIN payrollstaff p_owner ON p_owner.PayrollStaff_ID = psa_owner.PayrollStaff_ID
                 INNER JOIN users u_owner ON u_owner.id = p_owner.UserID
                 WHERE psa_owner.SiteID = s.SiteID LIMIT 1) AS PayrollStaff_Name,
                {$timekeeperSelect},
                s.Status,
                {$scheduleSelect},
                (SELECT COUNT(*) FROM WorkerAssignment wa WHERE wa.SiteID = s.SiteID) AS Current_Workers
            FROM ProjectSite s
            {$timekeeperJoin}
            {$joinSql}
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
            WHERE " . implode(' AND ', $whereParts) . "
            ORDER BY s.SiteID DESC";

    if ($types !== '') {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Failed to prepare site query']);
            exit;
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }

    $sites = [];
    while ($row = $result->fetch_assoc()) {
        $sites[] = enrich_site_schedule_row($row);
    }

    if (isset($stmt)) {
        $stmt->close();
    }

    echo json_encode([
        'success' => true,
        'sites' => $sites,
        'count' => count($sites)
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);

$conn->close();
?>
