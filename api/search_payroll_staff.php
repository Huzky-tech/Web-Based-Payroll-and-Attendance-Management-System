<?php
/**
 * Search Payroll Staff API
 * Search payroll staff by name or email
 */

header('Content-Type: application/json');
include 'connection/db_config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $keyword = $_GET['keyword'] ?? '';
    
    if (empty($keyword)) {
        // If no keyword, return all payroll staff
        $sql = "SELECT 
                    ps.PayrollStaff_ID,
                    u.full_name,
                    u.email,
                    (SELECT COUNT(*) FROM payrollstaffassignment psa WHERE psa.PayrollStaff_ID = ps.PayrollStaff_ID) AS assigned_sites_count
                FROM payrollstaff ps
                INNER JOIN users u ON ps.UserID = u.id
                ORDER BY u.full_name";
        
        $result = $conn->query($sql);
    } else {
        // Search by name or email
        $searchTerm = '%' . $keyword . '%';
        $sql = "SELECT 
                    ps.PayrollStaff_ID,
                    u.full_name,
                    u.email,
                    (SELECT COUNT(*) FROM payrollstaffassignment psa WHERE psa.PayrollStaff_ID = ps.PayrollStaff_ID) AS assigned_sites_count
                FROM payrollstaff ps
                INNER JOIN users u ON ps.UserID = u.id
                WHERE u.full_name LIKE ? OR u.email LIKE ?
                ORDER BY u.full_name";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
    }
    
    $staff = [];
    while ($row = $result->fetch_assoc()) {
        $staff[] = [
            'id' => $row['PayrollStaff_ID'],
            'name' => $row['full_name'] ?? 'Unknown',
            'email' => $row['email'],
            'assigned_sites_count' => (int)$row['assigned_sites_count']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'staff' => $staff,
        'count' => count($staff)
    ]);
    
    if (isset($stmt)) {
        $stmt->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

