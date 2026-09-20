<?php
/**
 * Get Employee by ID API
 * Retrieves a single employee's details using their ID
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
$deductionTypesColumnResult = $conn->query("SHOW COLUMNS FROM worker LIKE 'GovernmentDeductionTypes'");
$hasGovernmentDeductionTypesColumn = $deductionTypesColumnResult !== false && $deductionTypesColumnResult->num_rows > 0;
$deductionTypesSelect = $hasGovernmentDeductionTypesColumn
    ? 'w.GovernmentDeductionTypes'
    : 'NULL AS GovernmentDeductionTypes';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $employeeId = $_GET['id'] ?? 0;
    
    if (empty($employeeId)) {
        echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
        exit;
    }
    
    $types = 'i';
    $params = [(int) $employeeId];
    $scope = employee_scope_condition($conn, $currentRole, $userId, 'wa.SiteID', $types, $params);
    $scopeSql = $scope !== '' ? " AND {$scope}" : '';

    // Get employee with status and current assignment
    $sql = "SELECT 
                w.WorkerID,
                w.First_Name,
                w.Last_Name,
                CONCAT(w.First_Name, ' ', w.Last_Name) AS full_name,
                w.RateType,
                w.RateAmount AS salary,
                COALESCE(w.GovernmentDeductionStatus, 'With Deductions') AS GovernmentDeductionStatus,
                w.Phone,
                w.DateHired AS join_date,
                w.WorkerStatusID,
                {$deductionTypesSelect},
                w.photo_path,
                w.qr_code_path,
                ws.Status AS worker_status,
                wa.SiteID,
                ps.Site_Name,
                ps.Location,
                COALESCE(NULLIF(wa.Role_On_Site, ''), NULLIF(w.Position, '')) AS position,
                wa.Assigned_Date
            FROM worker w
            LEFT JOIN workerstatus ws ON w.WorkerStatusID = ws.WorkerStatusID
            LEFT JOIN workerassignment wa ON w.WorkerID = wa.WorkerID
            LEFT JOIN projectsite ps ON wa.SiteID = ps.SiteID
            WHERE w.WorkerID = ?
              {$scopeSql}";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $employee = $result->fetch_assoc();
        try {
            $employee['qr_code_path'] = ensureEmployeeQrCode(
                $conn,
                (int) $employee['WorkerID'],
                $employee['qr_code_path'] ?? null
            );
        } catch (Throwable $qrError) {
            $employee['qr_generation_error'] = $qrError->getMessage();
        }
        
        // Get approval details
        $approvalSql = "SELECT 
                            a.ApprovalID,
                            a.Approval_Status,
                            a.Date,
                            u.full_name AS approved_by_name
                        FROM approvals a
                        LEFT JOIN users u ON a.Approval_By = u.id
                        WHERE a.WorkerID = ?
                        ORDER BY a.Date DESC, a.ApprovalID DESC
                        LIMIT 1";
        $approvalStmt = $conn->prepare($approvalSql);
        $approvalStmt->bind_param("i", $employeeId);
        $approvalStmt->execute();
        $approvalResult = $approvalStmt->get_result();
        
        $approvalData = null;
        if ($approvalResult->num_rows > 0) {
            $approvalData = $approvalResult->fetch_assoc();
        }
        $approvalStmt->close();
        
        $employee['approval'] = $approvalData;
        
        // Get worker profile details (address, emergency contact, DOB, etc.)
        $profileSql = "SELECT 
                            wp.Email,
                            wp.DateOfBirth AS DateOfBirth,
                            wp.StreetAddress,
                            wp.City,
                            wp.StateProvince,
                            wp.PostalCode,
                            wp.Country,
                            wp.EmergencyContactName,
                            wp.EmergencyContactPhone,
                            wp.EmergencyContactRelationship
                        FROM worker_profile wp
                        WHERE wp.WorkerID = ?";

        $profileStmt = $conn->prepare($profileSql);
        $profileStmt->bind_param("i", $employeeId);
        $profileStmt->execute();
        $profileResult = $profileStmt->get_result();

        $profile = null;
        if ($profileResult && $profileResult->num_rows > 0) {
            $profileRow = $profileResult->fetch_assoc();
            $profile = [
                'email' => $profileRow['Email'] ?? null,
                'date_of_birth' => $profileRow['DateOfBirth'] ?? null,
                'street_address' => $profileRow['StreetAddress'] ?? null,
                'city' => $profileRow['City'] ?? null,
                'state_province' => $profileRow['StateProvince'] ?? null,
                'postal_code' => $profileRow['PostalCode'] ?? null,
                'country' => $profileRow['Country'] ?? null,
                'emergency_contact_name' => $profileRow['EmergencyContactName'] ?? null,
                'emergency_contact_phone' => $profileRow['EmergencyContactPhone'] ?? null,
                'emergency_contact_relationship' => $profileRow['EmergencyContactRelationship'] ?? null
            ];
        }
        $profileStmt->close();

        $employee['profile'] = $profile;

        $workerStatuses = [];
        $statusResult = $conn->query("
            SELECT WorkerStatusID, Status
            FROM workerstatus
            WHERE LOWER(REPLACE(TRIM(Status), ' ', '')) <> 'onleave'
            ORDER BY Status
        ");
        if ($statusResult) {
            while ($statusRow = $statusResult->fetch_assoc()) {
                $workerStatuses[] = $statusRow;
            }
        }

        echo json_encode([
            'success' => true, 
            'employee' => $employee,
            'worker_statuses' => $workerStatuses
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
