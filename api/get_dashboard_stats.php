<?php
/**
 * Dashboard Statistics API
 * Returns system statistics for dashboard overview cards
 */

header('Content-Type: application/json');
include 'connection/db_config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Count total payroll staff
    $staffSql = "SELECT COUNT(*) as count FROM payrollstaff";
    $staffResult = $conn->query($staffSql);
    $payrollStaffCount = $staffResult->fetch_assoc()['count'] ?? 0;
    
    // Count active sites
    $activeSitesSql = "SELECT COUNT(*) as count FROM projectsite WHERE Status = 'active' OR Status = 'Active'";
    $activeSitesResult = $conn->query($activeSitesSql);
    $activeSitesCount = $activeSitesResult->fetch_assoc()['count'] ?? 0;
    
    // Count total assignments
    $assignmentsSql = "SELECT COUNT(*) as count FROM payrollstaffassignment";
    $assignmentsResult = $conn->query($assignmentsSql);
    $assignmentsCount = $assignmentsResult->fetch_assoc()['count'] ?? 0;
    
    // Count total employees
    $employeesSql = "SELECT COUNT(*) as count FROM worker";
    $employeesResult = $conn->query($employeesSql);
    $employeesCount = $employeesResult->fetch_assoc()['count'] ?? 0;
    
    // Count active employees
    $activeEmployeesSql = "SELECT COUNT(*) as count FROM worker w 
                           JOIN workerstatus ws ON w.WorkerStatusID = ws.WorkerStatusID 
                           WHERE ws.Status = 'Active'";
    $activeEmployeesResult = $conn->query($activeEmployeesSql);
    $activeEmployeesCount = $activeEmployeesResult->fetch_assoc()['count'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'statistics' => [
            'payroll_staff' => $payrollStaffCount,
            'active_sites' => $activeSitesCount,
            'total_assignments' => $assignmentsCount,
            'total_employees' => $employeesCount,
            'active_employees' => $activeEmployeesCount
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

