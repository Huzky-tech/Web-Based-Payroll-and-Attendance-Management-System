<?php
/**
 * Deactivate Site API
 * Mark a site as inactive
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
    $userId = $data['user_id'] ?? 1;
    
    // Validation
    if (empty($siteId)) {
        echo json_encode(['success' => false, 'message' => 'Site ID is required']);
        exit;
    }
    
    // Check if site exists
    $checkSql = "SELECT Site_Name, Status FROM projectsite WHERE SiteID = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $siteId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Site not found']);
        $checkStmt->close();
        exit;
    }
    
    $siteData = $checkResult->fetch_assoc();
    $siteName = $siteData['Site_Name'];
    $checkStmt->close();
    
    // Update status to inactive
    $sql = "UPDATE projectsite SET Status = 'Inactive' WHERE SiteID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $siteId);
    
    if ($stmt->execute()) {
        // Log the action
        logAudit($conn, $userId, 'Site Deactivated', "Deactivated site: $siteName (ID: $siteId)");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Site deactivated successfully',
            'site_id' => $siteId,
            'status' => 'Inactive'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to deactivate site']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

