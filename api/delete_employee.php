<?php
/**
 * Delete Employee API
 * Removes an employee from the database
 */

header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';

$currentRole = require_auth($conn, ['Admin']);

// Helper function for audit logging
function logAudit($conn, $userId, $action, $details) {
    $stmt = $conn->prepare("INSERT INTO audit_logs (UserID, Action, Details, Date) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $userId, $action, $details);
    $stmt->execute();
    $stmt->close();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST' || $method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $employeeId = $data['id'] ?? $_GET['id'] ?? 0;
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    
    if (empty($employeeId)) {
        echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
        exit;
    }

    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized audit user']);
        exit;
    }
    
    // Get employee name before deletion
    $nameSql = "SELECT CONCAT(First_Name, ' ', Last_Name) AS full_name, UserID FROM worker WHERE WorkerID = ?";
    $nameStmt = $conn->prepare($nameSql);
    $nameStmt->bind_param("i", $employeeId);
    $nameStmt->execute();
    $nameResult = $nameStmt->get_result();
    
    if ($nameResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        $nameStmt->close();
        exit;
    }
    
    $employeeRow = $nameResult->fetch_assoc();
    $employeeName = $employeeRow['full_name'];
    $employeeUserId = (int) ($employeeRow['UserID'] ?? 0);
    $nameStmt->close();

    $statusStmt = $conn->prepare("SELECT WorkerStatusID FROM workerstatus WHERE Status = 'Inactive' LIMIT 1");
    $statusStmt->execute();
    $inactiveStatus = $statusStmt->get_result()->fetch_assoc();
    $statusStmt->close();
    if (!$inactiveStatus) {
        $createStatusStmt = $conn->prepare("INSERT INTO workerstatus (Status) VALUES ('Inactive')");
        if (!$createStatusStmt || !$createStatusStmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Unable to prepare the employee archive status']);
            exit;
        }
        $inactiveStatus = ['WorkerStatusID' => (int) $conn->insert_id];
        $createStatusStmt->close();
    }

    $conn->begin_transaction();
    try {
        $inactiveStatusId = (int) $inactiveStatus['WorkerStatusID'];
        $stmt = $conn->prepare('UPDATE worker SET WorkerStatusID = ? WHERE WorkerID = ?');
        $stmt->bind_param('ii', $inactiveStatusId, $employeeId);
        if (!$stmt->execute()) throw new RuntimeException('Failed to archive employee');
        $stmt->close();

        $assignStmt = $conn->prepare('DELETE FROM workerassignment WHERE WorkerID = ?');
        $assignStmt->bind_param('i', $employeeId);
        if (!$assignStmt->execute()) throw new RuntimeException('Failed to remove active site assignments');
        $assignStmt->close();

        if ($employeeUserId > 0) {
            $userStmt = $conn->prepare("UPDATE users SET status = 'Inactive' WHERE id = ?");
            $userStmt->bind_param('i', $employeeUserId);
            if (!$userStmt->execute()) throw new RuntimeException('Failed to deactivate employee account');
            $userStmt->close();
        }

        logAudit($conn, $userId, 'Employee Archived', "Archived employee: $employeeName (ID: $employeeId)");
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Employee archived successfully']);
    } catch (Throwable $error) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $error->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
