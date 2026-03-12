<?php
/**
 * Get Assignment Summary API
 * Consolidated endpoint for site assignment dashboard summary
 * Returns: total payroll staff, active sites, total assignments
 */

header('Content-Type: application/json');
include 'connection/db_config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // 1. Count total payroll staff
    $staffSql = "SELECT COUNT(*) as count FROM payrollstaff";
    $staffResult = $conn->query($staffSql);
    $totalStaff = $staffResult ? $staffResult->fetch_assoc()['count'] ?? 0 : 0;
    
    // 2. Count active sites  
    $sitesSql = "SELECT COUNT(*) as count FROM projectsite WHERE LOWER(Status) = 'active'";
    $sitesResult = $conn->query($sitesSql);
    $activeSites = $sitesResult ? $sitesResult->fetch_assoc()['count'] ?? 0 : 0;
    
    // 3. Count total assignments
    $assignSql = "SELECT COUNT(*) as count FROM payrollstaffassignment";
    $assignResult = $conn->query($assignSql);
    $totalAssignments = $assignResult ? $assignResult->fetch_assoc()['count'] ?? 0 : 0;
    
    echo json_encode([
        'success' => true,
        'summary' => [
            'total_payroll_staff' => (int)$totalStaff,
            'active_sites' => (int)$activeSites, 
            'total_assignments' => (int)$totalAssignments
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
