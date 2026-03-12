<?php
/**
 * Create Employee API
 * Adds a new employee to the database
 */

header('Content-Type: application/json');
include 'connection/db_config.php';

// Helper function for audit logging
function logAudit($conn, $userId, $action, $details) {
    $stmt = $conn->prepare("INSERT INTO audit_logs (UserID, Action, Details, Date) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $userId, $action, $details);
    $stmt->execute();
    $stmt->close();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Get input fields
    $firstName = $data['first_name'] ?? '';
    $lastName = $data['last_name'] ?? '';
    $position = $data['position'] ?? '';
    $siteId = $data['site_id'] ?? null;
    $salary = $data['salary'] ?? 0;
    $salaryType = $data['salary_type'] ?? 'Salary';
    $phone = $data['phone'] ?? '';
    $joinDate = $data['join_date'] ?? date('Y-m-d');
    $userId = $data['user_id'] ?? 1;
    
    // Validation
    if (empty($firstName) || empty($lastName)) {
        echo json_encode(['success' => false, 'message' => 'First name and last name are required']);
        exit;
    }
    
    // Get default worker status (Active = 1)
    $statusId = 1;
    
    // Insert worker
    $sql = "INSERT INTO worker (First_Name, Last_Name, RateType, RateAmount, Phone, DateHired, WorkerStatusID, UserID) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdssii", $firstName, $lastName, $salaryType, $salary, $phone, $joinDate, $statusId, $userId);
    
    if ($stmt->execute()) {
        $workerId = $stmt->insert_id;
        
        // If site is specified, create assignment
        if ($siteId) {
            $assignSql = "INSERT INTO workerassignment (WorkerID, SiteID, Assigned_Date, Role_On_Site) 
                         VALUES (?, ?, ?, ?)";
            $assignStmt = $conn->prepare($assignSql);
            $assignStmt->bind_param("iiss", $workerId, $siteId, $joinDate, $position);
            $assignStmt->execute();
            $assignStmt->close();
        }
        
        // Log the action
        $fullName = "$firstName $lastName";
        logAudit($conn, $userId, 'Employee Created', "Created employee: $fullName (ID: $workerId)");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Employee created successfully',
            'employee_id' => $workerId
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create employee']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

