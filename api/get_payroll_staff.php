<?php
/**
 * Get Payroll Staff API
 * Retrieve all payroll staff with their site assignment counts
 */

header('Content-Type: application/json');
include 'connection/db_config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sql = "SELECT 
                ps.PayrollStaff_ID,
                u.full_name,
                u.email,
                u.status as user_status,
                (SELECT COUNT(*) FROM payrollstaffassignment psa WHERE psa.PayrollStaff_ID = ps.PayrollStaff_ID) AS assigned_sites_count,
                (SELECT GROUP_CONCAT(DISTINCT projectsite.Site_Name ORDER BY projectsite.Site_Name SEPARATOR ', ')
                 FROM payrollstaffassignment psa
                 INNER JOIN projectsite ON projectsite.SiteID = psa.SiteID
                 WHERE psa.PayrollStaff_ID = ps.PayrollStaff_ID) AS assigned_site_names
            FROM payrollstaff ps
            INNER JOIN users u ON ps.UserID = u.id
            ORDER BY u.full_name";

    $result = $conn->query($sql);

    $staff = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $staff[] = [
                'id' => $row['PayrollStaff_ID'],
                'name' => $row['full_name'] ?? 'Unknown',
                'email' => $row['email'],
                'user_status' => $row['user_status'],
                'assigned_sites_count' => (int)$row['assigned_sites_count'],
                'assigned_site_names' => $row['assigned_site_names'] ?? ''
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'staff' => $staff,
        'count' => count($staff)
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
