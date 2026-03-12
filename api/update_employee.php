<?php
/**
 * Update Employee API
 * Update employee information such as position, salary, site, or status
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
    
    $employeeId = $data['employee_id'] ?? 0;
    $firstName = $data['first_name'] ?? '';
    $lastName = $data['last_name'] ?? '';
    $position = $data['position'] ?? '';
    $siteId = $data['site_id'] ?? null;
    $salary = $data['salary'] ?? null;
    $salaryType = $data['salary_type'] ?? null;
    $phone = $data['phone'] ?? '';
    $userId = $data['user_id'] ?? 1;
    
    // Validation
    if (empty($employeeId)) {
        echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
        exit;
    }
    
    // Check if employee exists
    $checkSql = "SELECT CONCAT(First_Name, ' ', Last_Name) AS full_name FROM worker WHERE WorkerID = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $employeeId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        $checkStmt->close();
        exit;
    }
    
    $employeeName = $checkResult->fetch_assoc()['full_name'];
    $checkStmt->close();
    
    // Build update query dynamically for worker table
    $updates = [];
    $params = [];
    $types = "";
    
    if (!empty($firstName)) {
        $updates[] = "First_Name = ?";
        $params[] = $firstName;
        $types .= "s";
    }
    if (!empty($lastName)) {
        $updates[] = "Last_Name = ?";
        $params[] = $lastName;
        $types .= "s";
    }
    if ($salary !== null) {
        $updates[] = "RateAmount = ?";
        $params[] = $salary;
        $types .= "d";
    }
    if (!empty($salaryType)) {
        $updates[] = "RateType = ?";
        $params[] = $salaryType;
        $types .= "s";
    }
    if (!empty($phone)) {
        $updates[] = "Phone = ?";
        $params[] = $phone;
        $types .= "s";
    }
    
    // Update worker table
    if (!empty($updates)) {
        $params[] = $employeeId;
        $types .= "i";
        
        $sql = "UPDATE worker SET " . implode(", ", $updates) . " WHERE WorkerID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();
    }
    
    // Update position in workerassignment
    if (!empty($position) && $siteId) {
        $posSql = "UPDATE workerassignment SET Role_On_Site = ? WHERE WorkerID = ? AND SiteID = ?";
        $posStmt = $conn->prepare($posSql);
        $posStmt->bind_param("sii", $position, $employeeId, $siteId);
        $posStmt->execute();
        $posStmt->close();
    }
    
    // Handle site assignment
    if ($siteId !== null) {
        // Check if assignment exists
        $assignCheckSql = "SELECT AssignmentID FROM workerassignment WHERE WorkerID = ?";
        $assignCheckStmt = $conn->prepare($assignCheckSql);
        $assignCheckStmt->bind_param("i", $employeeId);
        $assignCheckStmt->execute();
        $assignCheckResult = $assignCheckStmt->get_result();
        
        if ($assignCheckResult->num_rows > 0) {
            // Update existing assignment
            $updateAssignSql = "UPDATE workerassignment SET SiteID = ? WHERE WorkerID = ?";
            $updateAssignStmt = $conn->prepare($updateAssignSql);
            $updateAssignStmt->bind_param("ii", $siteId, $employeeId);
            $updateAssignStmt->execute();
            $updateAssignStmt->close();
        } else if ($siteId > 0) {
            // Create new assignment
            $insertAssignSql = "INSERT INTO workerassignment (WorkerID, SiteID, Assigned_Date) VALUES (?, ?, NOW())";
            $insertAssignStmt = $conn->prepare($insertAssignSql);
            $insertAssignStmt->bind_param("ii", $employeeId, $siteId);
            $insertAssignStmt->execute();
            $insertAssignStmt->close();
        }
        $assignCheckStmt->close();
    }
    
    // Log the action
    logAudit($conn, $userId, 'Employee Updated', "Updated employee: $employeeName (ID: $employeeId)");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Employee updated successfully',
        'employee_id' => $employeeId
    ]);
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

