<?php
/**
 * Update Site API
 * Edit site information such as name or address
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
    
    $siteId = $data['site_id'] ?? 0;
    $siteName = $data['site_name'] ?? '';
    $location = $data['location'] ?? '';
    $projectType = $data['project_type'] ?? null;
    $endDate = $data['end_date'] ?? null;
    $requiredWorkers = $data['required_workers'] ?? null;
    $siteManager = $data['site_manager'] ?? null;
    $status = $data['status'] ?? null;
    $userId = $data['user_id'] ?? 1;
    
    // Validation
    if (empty($siteId) || empty($siteName)) {
        echo json_encode(['success' => false, 'message' => 'Site ID and name are required']);
        exit;
    }
    
    // Check if site exists
    $checkSql = "SELECT Site_Name FROM projectsite WHERE SiteID = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $siteId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Site not found']);
        $checkStmt->close();
        exit;
    }
    
    $oldSiteName = $checkResult->fetch_assoc()['Site_Name'];
    $checkStmt->close();
    
    // Build update query dynamically
    $updates = [];
    $params = [];
    $types = "";
    
    if (!empty($siteName)) {
        $updates[] = "Site_Name = ?";
        $params[] = $siteName;
        $types .= "s";
    }
    if (!empty($location)) {
        $updates[] = "Location = ?";
        $params[] = $location;
        $types .= "s";
    }
    if ($projectType !== null) {
        $updates[] = "Project_Type = ?";
        $params[] = $projectType;
        $types .= "s";
    }
    if ($endDate !== null) {
        $updates[] = "End_Date = ?";
        $params[] = $endDate;
        $types .= "s";
    }
    if ($requiredWorkers !== null) {
        $updates[] = "Required_Workers = ?";
        $params[] = $requiredWorkers;
        $types .= "i";
    }
    if ($siteManager !== null) {
        $updates[] = "Site_Manager = ?";
        $params[] = $siteManager;
        $types .= "s";
    }
    if ($status !== null) {
        $updates[] = "Status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        exit;
    }
    
    $params[] = $siteId;
    $types .= "i";
    
    $sql = "UPDATE projectsite SET " . implode(", ", $updates) . " WHERE SiteID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        // Log the action
        logAudit($conn, $userId, 'Site Updated', "Updated site: $oldSiteName (ID: $siteId)");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Site updated successfully',
            'site_id' => $siteId
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update site']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

