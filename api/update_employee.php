<?php
require_once __DIR__ . '/../includes/user_identity.php';
/**
 * Update Employee API
 * Update employee information such as position, salary, site, or status
 */

header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/employee_helpers.php';
require_once __DIR__ . '/worker_email_helpers.php';
require_once __DIR__ . '/payroll_deduction_helpers.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'HR']);

// Helper function for audit logging
function logAudit($conn, $userId, $action, $details) {
    $stmt = $conn->prepare("INSERT INTO audit_logs (UserID, Action, Details, Date) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $userId, $action, $details);
    $stmt->execute();
    $stmt->close();
}

function isValidElevenDigitPhone($phone): bool
{
    return is_string($phone) && preg_match('/^[0-9]{11}$/', $phone) === 1;
}

function isValidEmployeeDate($date): bool
{
    if (!is_string($date) || $date === '') {
        return false;
    }

    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    return $parsed && $parsed->format('Y-m-d') === $date;
}

function isAtLeastEighteenYearsOld(string $date): bool
{
    $birthDate = DateTime::createFromFormat('Y-m-d', $date);
    return $birthDate->modify('+18 years') <= new DateTime('today');
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
  try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $data = json_decode(file_get_contents('php://input'), true);
    
    $employeeId = $data['employee_id'] ?? 0;
    $firstName = $data['first_name'] ?? '';
    $lastName = $data['last_name'] ?? '';
    foreach (['first_name' => $firstName, 'last_name' => $lastName] as $nameKey => $nameValue) {
        if (array_key_exists($nameKey, $data) && (!is_string($nameValue)
            || !preg_match('/^[\p{L}]+(?: [\p{L}]+)*$/u', $nameValue) || mb_strlen($nameValue) > 50)) {
            echo json_encode(['success' => false, 'message' => 'First name and last name must contain letters and single spaces only, up to 50 characters. Numbers and special characters are not allowed.']);
            exit;
        }
    }
    $position = $data['position'] ?? '';
    $siteId = $data['site_id'] ?? null;
    $salary = $data['salary'] ?? null;
    $salaryType = $data['rate_type'] ?? ($data['salary_type'] ?? null);
    $workerStatusId = isset($data['worker_status_id']) ? (int) $data['worker_status_id'] : 0;
    $phoneRaw = $data['phone'] ?? null;
    $phone = is_string($phoneRaw) ? trim($phoneRaw) : $phoneRaw;
    $phone = ($phone === '' || $phone === null) ? null : $phone;
    $profile = $data['profile'] ?? [];
    $governmentDeductionStatus = array_key_exists('government_deduction_status', $data)
        ? normalize_government_deduction_status((string) $data['government_deduction_status'])
        : null;
    $governmentDeductionTypes = array_values(array_intersect(
        ['sss', 'philhealth', 'pagibig'],
        array_map('strtolower', (array) ($data['government_deduction_types'] ?? []))
    ));

    // Profile fields (stored in worker_profile)
    $email = $profile['email'] ?? null;
    $dateOfBirth = $profile['date_of_birth'] ?? null;
    $streetAddress = $profile['street_address'] ?? null;
    $city = $profile['city'] ?? null;
    $stateProvince = $profile['state_province'] ?? null;
    $postalCode = $profile['postal_code'] ?? null;
    $country = $profile['country'] ?? null;

    $emergencyContactName = $profile['emergency_contact_name'] ?? null;
    if ($emergencyContactName !== null && $emergencyContactName !== '' && (!is_string($emergencyContactName)
        || !preg_match('/^[\p{L}]+(?: [\p{L}]+)*$/u', $emergencyContactName) || mb_strlen($emergencyContactName) > 50)) {
        echo json_encode(['success' => false, 'message' => 'Emergency contact name must contain letters and single spaces only, up to 50 characters.']);
        exit;
    }
    $emergencyContactPhoneRaw = $profile['emergency_contact_phone'] ?? null;
    $emergencyContactPhone = is_string($emergencyContactPhoneRaw) ? trim($emergencyContactPhoneRaw) : $emergencyContactPhoneRaw;
    $emergencyContactRelationship = $profile['emergency_contact_relationship'] ?? null;

    $userId = (int) ($_SESSION['user_id'] ?? ($data['user_id'] ?? 0));

    
    // Validation
    if (empty($employeeId)) {
        echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
        exit;
    }

    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized audit user']);
        exit;
    }

    if ($salary !== null && (!is_numeric($salary) || (float) $salary < 0)) {
        echo json_encode(['success' => false, 'message' => 'Salary must be zero or greater.']);
        exit;
    }

    if ($workerStatusId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Select a valid employment status.']);
        exit;
    }

    $statusCheckStmt = $conn->prepare('SELECT 1 FROM workerstatus WHERE WorkerStatusID = ? LIMIT 1');
    $statusCheckStmt->bind_param('i', $workerStatusId);
    $statusCheckStmt->execute();
    $validWorkerStatus = $statusCheckStmt->get_result()->fetch_assoc();
    $statusCheckStmt->close();
    if (!$validWorkerStatus) {
        echo json_encode(['success' => false, 'message' => 'The selected employment status is invalid.']);
        exit;
    }

    if ($phone !== null && !isValidElevenDigitPhone($phone)) {
        echo json_encode(['success' => false, 'message' => 'Phone number must be exactly 11 digits.']);
        exit;
    }

    if ($emergencyContactPhone !== null && $emergencyContactPhone !== '' && !isValidElevenDigitPhone($emergencyContactPhone)) {
        echo json_encode(['success' => false, 'message' => 'Emergency contact phone must be exactly 11 digits.']);
        exit;
    }

    if ($dateOfBirth !== null && $dateOfBirth !== '' && (!isValidEmployeeDate($dateOfBirth) || !isAtLeastEighteenYearsOld($dateOfBirth))) {
        echo json_encode(['success' => false, 'message' => 'Employee must be at least 18 years old.']);
        exit;
    }

    $streetAddress = is_string($streetAddress) ? trim($streetAddress) : $streetAddress;
    $city = is_string($city) ? trim($city) : $city;
    $stateProvince = is_string($stateProvince) ? trim($stateProvince) : $stateProvince;
    $postalCode = is_string($postalCode) ? trim($postalCode) : $postalCode;
    if ($streetAddress !== null && $streetAddress !== '' && (!is_string($streetAddress) || !preg_match('/^[\p{L}\p{N}\s,.-]+$/u', $streetAddress))) {
        echo json_encode(['success' => false, 'message' => 'Street address cannot contain special characters.']);
        exit;
    }
    if ($city !== null && $city !== '' && (!is_string($city) || !preg_match('/^[\p{L}]+(?: [\p{L}]+)*$/u', $city))) {
        echo json_encode(['success' => false, 'message' => 'City must contain letters and spaces only.']);
        exit;
    }
    if ($stateProvince !== null && $stateProvince !== '' && (!is_string($stateProvince) || !preg_match('/^[\p{L}]+(?: [\p{L}]+)*$/u', $stateProvince))) {
        echo json_encode(['success' => false, 'message' => 'State / Province must contain letters and spaces only.']);
        exit;
    }
    if ($postalCode !== null && $postalCode !== '' && (!is_string($postalCode) || !preg_match('/^\d{4,10}$/', $postalCode))) {
        echo json_encode(['success' => false, 'message' => 'Postal / ZIP Code must contain 4 to 10 digits only.']);
        exit;
    }
    
    user_identity_ensure_columns($conn);
    user_identity_lock($conn);

    // Check if employee exists
    $checkSql = "SELECT First_Name, Last_Name, CONCAT(First_Name, ' ', Last_Name) AS full_name FROM worker WHERE WorkerID = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $employeeId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        $checkStmt->close();
        exit;
    }
    
    $existingEmployee = $checkResult->fetch_assoc();
    $employeeName = $existingEmployee['full_name'];
    $firstName = array_key_exists('first_name', $data) ? $firstName : $existingEmployee['First_Name'];
    $lastName = array_key_exists('last_name', $data) ? $lastName : $existingEmployee['Last_Name'];
    $linkedUserId = user_identity_linked_user($conn, (int) $employeeId);
    $checkStmt->close();

    if (user_identity_full_name_exists($conn, $firstName . ' ' . $lastName, 0, (int) $employeeId)) {
        echo json_encode(['success' => false, 'message' => 'This first and last name combination is already registered.']);
        exit;
    }
    if ($phone !== null) {
        $duplicatePhoneStmt = $conn->prepare("SELECT WorkerID FROM worker WHERE WorkerID <> ? AND Phone = ? LIMIT 1");
        if ($duplicatePhoneStmt) {
            $duplicatePhoneStmt->bind_param('is', $employeeId, $phone);
            $duplicatePhoneStmt->execute();
            $duplicatePhone = $duplicatePhoneStmt->get_result()->fetch_assoc();
            $duplicatePhoneStmt->close();

            if ($duplicatePhone) {
                echo json_encode(['success' => false, 'message' => 'Another employee already uses this phone number.']);
                exit;
            }
        }
    }

    if ($email !== null && $email !== '') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Enter a valid employee email.']);
            exit;
        }

        try {
            if (worker_email_in_use($conn, trim($email), $employeeId)) {
                echo json_encode(['success' => false, 'message' => 'This email address is already registered.']);
                exit;
            }
        } catch (Throwable $error) {
            echo json_encode(['success' => false, 'message' => 'Unable to verify email availability. Please try again.']);
            exit;
        }

    }
    
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
    $updates[] = "WorkerStatusID = ?";
    $params[] = $workerStatusId;
    $types .= "i";
    if (array_key_exists('phone', $data)) {
        $updates[] = "Phone = ?";
        $params[] = $phone;
        $types .= "s";
    }
    if (
        $governmentDeductionStatus !== null
        && $currentRole === 'Admin'
        && worker_government_deduction_column_exists($conn)
    ) {
        $updates[] = "GovernmentDeductionStatus = ?";
        $params[] = $governmentDeductionStatus;
        $types .= "s";
    }
    $deductionTypesColumnResult = $conn->query("SHOW COLUMNS FROM worker LIKE 'GovernmentDeductionTypes'");
    $hasGovernmentDeductionTypesColumn = $deductionTypesColumnResult !== false && $deductionTypesColumnResult->num_rows > 0;
    if ($governmentDeductionStatus !== null && $currentRole === 'Admin' && $hasGovernmentDeductionTypesColumn) {
        $updates[] = "GovernmentDeductionTypes = ?";
        $params[] = $governmentDeductionStatus === GOVERNMENT_DEDUCTION_WITH
            ? json_encode($governmentDeductionTypes, JSON_UNESCAPED_UNICODE)
            : null;
        $types .= "s";
    }
    
    $conn->begin_transaction();

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
    
    // Keep the dedicated login and employee record on the same name.
    if ($linkedUserId > 0) {
        $fullName = trim($firstName . ' ' . $lastName);
        $nameStmt = $conn->prepare('UPDATE users SET full_name = ?, first_name = ?, last_name = ? WHERE id = ?');
        $nameStmt->bind_param('sssi', $fullName, $firstName, $lastName, $linkedUserId);
        $nameStmt->execute();
        $nameStmt->close();
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
        if ((int) $siteId > 0 && !isEmployeeApprovedForAssignment($conn, (int) $employeeId)) {
            throw new RuntimeException('This employee is pending approval and cannot be assigned to a site yet.');
        }

        // Check if assignment exists
        $assignCheckSql = "SELECT AssignmentID, SiteID FROM workerassignment WHERE WorkerID = ?";
        $assignCheckStmt = $conn->prepare($assignCheckSql);
        $assignCheckStmt->bind_param("i", $employeeId);
        $assignCheckStmt->execute();
        $assignCheckResult = $assignCheckStmt->get_result();
        
        if ($assignCheckResult->num_rows > 0) {
            $existingAssignment = $assignCheckResult->fetch_assoc();
            if ((int) ($existingAssignment['SiteID'] ?? 0) !== (int) $siteId) {
                // Only move the assignment when the selected site actually changed.
                $updateAssignSql = "UPDATE workerassignment SET SiteID = ? WHERE WorkerID = ?";
                $updateAssignStmt = $conn->prepare($updateAssignSql);
                $updateAssignStmt->bind_param("ii", $siteId, $employeeId);
                $updateAssignStmt->execute();
                $updateAssignStmt->close();
            }
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
    
    // Update worker_profile (personal/address/emergency contact)
    // Only update if at least one field was provided (including empty string).
    $profileUpdates = [];
    $profileParams = [];
    $profileTypes = "";

    $maybeAdd = function($col, $val) use (&$profileUpdates, &$profileParams, &$profileTypes) {
        if ($val !== null) {
            $profileUpdates[] = "$col = ?";
            $profileParams[] = $val;
            $profileTypes .= "s";
        }
    };

    $maybeAdd('Email', $email);
    $maybeAdd('DateOfBirth', $dateOfBirth);
    $maybeAdd('StreetAddress', $streetAddress);
    $maybeAdd('City', $city);
    $maybeAdd('StateProvince', $stateProvince);
    $maybeAdd('PostalCode', $postalCode);
    $maybeAdd('Country', $country);
    $maybeAdd('EmergencyContactName', $emergencyContactName);
    $maybeAdd('EmergencyContactPhone', $emergencyContactPhone);
    $maybeAdd('EmergencyContactRelationship', $emergencyContactRelationship);

    if (!empty($profileUpdates)) {
        // ensure row exists
        $existsSql = "SELECT 1 FROM worker_profile WHERE WorkerID = ?";
        $existsStmt = $conn->prepare($existsSql);
        $existsStmt->bind_param('i', $employeeId);
        $existsStmt->execute();
        $existsResult = $existsStmt->get_result();
        $exists = ($existsResult && $existsResult->num_rows > 0);
        $existsStmt->close();

        if ($exists) {
            $profileParams[] = $employeeId;
            $profileTypes .= 'i';
            $updateProfileSql = "UPDATE worker_profile SET " . implode(', ', $profileUpdates) . " WHERE WorkerID = ?";
            $updateProfileStmt = $conn->prepare($updateProfileSql);
            $updateProfileStmt->bind_param($profileTypes, ...$profileParams);
            $updateProfileStmt->execute();
            $updateProfileStmt->close();
        } else {
            // Insert with provided values; others left NULL
            $insertCols = ['WorkerID'];
            $insertPlaceholders = ['?'];
            $insertParams = [$employeeId];
            $insertTypes = 'i';

            $map = [
                'Email' => $email,
                'DateOfBirth' => $dateOfBirth,
                'StreetAddress' => $streetAddress,
                'City' => $city,
                'StateProvince' => $stateProvince,
                'PostalCode' => $postalCode,
                'Country' => $country,
                'EmergencyContactName' => $emergencyContactName,
                'EmergencyContactPhone' => $emergencyContactPhone,
                'EmergencyContactRelationship' => $emergencyContactRelationship
            ];

            foreach ($map as $col => $val) {
                if ($val !== null) {
                    $insertCols[] = $col;
                    $insertPlaceholders[] = '?';
                    $insertParams[] = $val;
                    $insertTypes .= 's';
                }
            }

            $insertSql = "INSERT INTO worker_profile (" . implode(',', $insertCols) . ") VALUES (" . implode(',', $insertPlaceholders) . ")";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param($insertTypes, ...$insertParams);
            $insertStmt->execute();
            $insertStmt->close();
        }
    }

    // Log the action
    logAudit($conn, $userId, 'Employee Updated', "{$currentRole} updated employee: $employeeName (ID: $employeeId)");

    
    $conn->commit();
    echo json_encode([
        'success' => true, 
        'message' => 'Employee updated successfully',
        'employee_id' => $employeeId
    ]);
    
  } catch (Throwable $error) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $error->getMessage()]);
  }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
