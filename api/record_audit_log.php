<?php
/**
 * Record Audit Log API
 * Store actions such as employee creation, deletion, and site assignment
 */

header('Content-Type: application/json');
include 'connection/db_config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $userId = $data['user_id'] ?? 1;
    $action = $data['action'] ?? '';
    $details = $data['details'] ?? '';
    
    // Validation
    if (empty($action)) {
        echo json_encode(['success' => false, 'message' => 'Action is required']);
        exit;
    }
    
    $sql = "INSERT INTO audit_logs (UserID, Action, Details, Date) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $userId, $action, $details);
    
    if ($stmt->execute()) {
        $logId = $stmt->insert_id;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Audit log recorded successfully',
            'log_id' => $logId
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to record audit log']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

