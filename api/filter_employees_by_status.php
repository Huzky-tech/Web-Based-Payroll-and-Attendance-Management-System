<?php
/**
 * Filter Employees by Status API
 * Returns employees based on status (Active, Inactive, On Leave)
 */

header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/../includes/worker_position_helpers.php';
worker_position_ensure_column($conn);
require_once __DIR__ . '/../includes/employee_scope_helpers.php';
require_once __DIR__ . '/employee_helpers.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR', 'Timekeeper']);
$userId = (int) ($_SESSION['user_id'] ?? 0);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $status = $_GET['status'] ?? '';
    
    if (empty($status)) {
        echo json_encode(['success' => false, 'message' => 'Status is required']);
        exit;
    }
    
    // Validate status
    $validStatuses = ['Active', 'Inactive', 'OnLeave'];
    if (!in_array($status, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status. Use: Active, Inactive, or OnLeave']);
        exit;
    }
    
    $types = 's';
    $params = [$status];
    $scope = employee_scope_condition($conn, $currentRole, $userId, 'wa.SiteID', $types, $params);
    $scopeSql = $scope !== '' ? " AND {$scope}" : '';

    $sql = "SELECT 
                w.WorkerID,
                w.First_Name,
                w.Last_Name,
                CONCAT(w.First_Name, ' ', w.Last_Name) AS full_name,
                w.RateType,
                w.RateAmount AS salary,
                w.photo_path,
                w.qr_code_path,
                w.DateHired AS join_date,
                ws.Status AS worker_status,
                wa.SiteID,
                ps.Site_Name,
                COALESCE(NULLIF(wa.Role_On_Site, ''), NULLIF(w.Position, '')) AS position,
                COALESCE(latest_approval.Approval_Status, CASE WHEN LOWER(ws.Status) = 'active' THEN 'Approved' ELSE 'Pending' END) AS approval_status,
                latest_approval.Approval_By AS approval_by,
                latest_approval.Date AS approval_date,
                approver.full_name AS approved_by_name
            FROM worker w
            INNER JOIN workerstatus ws ON w.WorkerStatusID = ws.WorkerStatusID
            LEFT JOIN workerassignment wa ON w.WorkerID = wa.WorkerID
            LEFT JOIN projectsite ps ON wa.SiteID = ps.SiteID
            LEFT JOIN (
                SELECT a.*
                FROM approvals a
                INNER JOIN (
                    SELECT WorkerID, MAX(ApprovalID) AS ApprovalID
                    FROM approvals
                    GROUP BY WorkerID
                ) latest ON latest.ApprovalID = a.ApprovalID
            ) latest_approval ON latest_approval.WorkerID = w.WorkerID
            LEFT JOIN users approver ON approver.id = latest_approval.Approval_By
            WHERE ws.Status = ?
              {$scopeSql}
            ORDER BY w.WorkerID DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $employees = [];
    while ($row = $result->fetch_assoc()) {
        if (empty($row['qr_code_path'])) {
            try {
                $row['qr_code_path'] = ensureEmployeeQrCode($conn, (int) $row['WorkerID'], $row['qr_code_path'] ?? null);
            } catch (Throwable $qrError) {
                $row['qr_generation_error'] = $qrError->getMessage();
            }
        }
        $row['approval'] = !empty($row['approval_status']) ? [
            'Approval_Status' => $row['approval_status'],
            'Approval_By' => $row['approval_by'],
            'approved_by_name' => $row['approved_by_name'],
            'Date' => $row['approval_date'],
        ] : null;
        $employees[] = $row;
    }
    
    echo json_encode([
        'success' => true, 
        'employees' => $employees,
        'count' => count($employees),
        'status' => $status
    ]);
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
