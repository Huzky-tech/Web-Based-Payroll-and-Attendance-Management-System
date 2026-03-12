<?php
// Quick test to verify APIs are working
header('Content-Type: application/json');

include 'api/connection/db_config.php';

// Test 1: Get Payroll Staff
echo "=== Test 1: Get Payroll Staff ===\n";
$sql = "SELECT ps.PayrollStaff_ID, u.full_name, u.email,
        (SELECT COUNT(*) FROM payrollstaffassignment psa WHERE psa.PayrollStaff_ID = ps.PayrollStaff_ID) AS assigned_sites_count
        FROM payrollstaff ps
        INNER JOIN users u ON ps.UserID = u.id
        ORDER BY u.full_name";

$result = $conn->query($sql);
$staff = [];
while ($row = $result->fetch_assoc()) {
    $staff[] = $row;
}
echo json_encode(['success' => true, 'staff' => $staff, 'count' => count($staff)], JSON_PRETTY_PRINT);

echo "\n\n=== Test 2: Get Sites ===\n";
$sql2 = "SELECT s.SiteID, s.Site_Name, s.Location, s.Status,
        (SELECT COUNT(*) FROM WorkerAssignment wa WHERE wa.SiteID = s.SiteID) AS Current_Workers,
        s.Required_Workers
        FROM ProjectSite s ORDER BY s.Site_Name";
$result2 = $conn->query($sql2);
$sites = [];
while ($row = $result2->fetch_assoc()) {
    $sites[] = $row;
}
echo json_encode(['success' => true, 'sites' => $sites], JSON_PRETTY_PRINT);

echo "\n\n=== Test 3: Get Dashboard Stats ===\n";
$staffCount = $conn->query("SELECT COUNT(*) as cnt FROM payrollstaff")->fetch_assoc()['cnt'];
$siteCount = $conn->query("SELECT COUNT(*) as cnt FROM projectsite WHERE LOWER(Status) = 'active'")->fetch_assoc()['cnt'];
$assignCount = $conn->query("SELECT COUNT(*) as cnt FROM payrollstaffassignment")->fetch_assoc()['cnt'];

echo json_encode([
    'success' => true,
    'totalStaff' => $staffCount,
    'activeSites' => $siteCount,
    'totalAssignments' => $assignCount
], JSON_PRETTY_PRINT);

$conn->close();
?>

