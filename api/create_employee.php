<?php
require_once __DIR__ . '/../includes/user_identity.php';
header('Content-Type: application/json');

include 'connection/db_config.php';
include '../includes/auth.php';
include 'employee_helpers.php';
require_once __DIR__ . '/worker_email_helpers.php';
require_once __DIR__ . '/payroll_deduction_helpers.php';
require_once __DIR__ . '/../includes/worker_position_helpers.php';
require_once __DIR__ . '/../includes/password_policy.php';
worker_position_ensure_column($conn);

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR']);
$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

function createEmployeeAudit(mysqli $conn, int $userId, string $action, string $details): void
{
    $stmt = $conn->prepare("INSERT INTO audit_logs (UserID, Action, Details, Date) VALUES (?, ?, ?, NOW())");
    if (!$stmt) {
        return;
    }

    $stmt->bind_param("iss", $userId, $action, $details);
    $stmt->execute();
    $stmt->close();
}

function createEmployeeApproval(mysqli $conn, int $workerId, int $userId, string $approvalStatus): void
{
    $stmt = $conn->prepare("
        INSERT INTO approvals (WorkerID, Action_Type, Approval_By, Approval_Status, Date)
        VALUES (?, 'Employee Creation', ?, ?, NOW())
    ");

    if (!$stmt) {
        throw new RuntimeException('Failed to prepare employee approval record.');
    }

    $stmt->bind_param('iis', $workerId, $userId, $approvalStatus);
    if (!$stmt->execute()) {
        throw new RuntimeException('Failed to save employee approval record: ' . $stmt->error);
    }
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

$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
if (!preg_match('/^[\p{L}]+(?: [\p{L}]+)*$/u', $firstName) || !preg_match('/^[\p{L}]+(?: [\p{L}]+)*$/u', $lastName)
    || mb_strlen($firstName) > 50 || mb_strlen($lastName) > 50) {
    echo json_encode(['success' => false, 'message' => 'First name and last name must contain letters and single spaces only, up to 50 characters. Numbers and special characters are not allowed.']);
    exit;
}
$position = trim($_POST['position'] ?? '');
$accountPassword = 'password';
$rateAmount = (float) ($_POST['salary'] ?? 0);
$phone = trim($_POST['phone'] ?? '');
$joinDate = trim($_POST['join_date'] ?? date('Y-m-d'));
$rateType = trim($_POST['rate_type'] ?? 'Hourly');
$governmentDeductionStatus = normalize_government_deduction_status($_POST['government_deduction_status'] ?? GOVERNMENT_DEDUCTION_WITH);

// Per-worker deduction type selection
$governmentDeductionTypesRaw = $_POST['government_deduction_types'] ?? [];
if (!is_array($governmentDeductionTypesRaw)) {
    $governmentDeductionTypesRaw = [];
}

$allowedDeductionTypes = ['sss', 'philhealth', 'pagibig'];
$governmentDeductionTypes = [];
foreach ($governmentDeductionTypesRaw as $t) {
    $tt = strtolower(trim((string) $t));
    if (in_array($tt, $allowedDeductionTypes, true)) {
        $governmentDeductionTypes[$tt] = true;
    }
}
$governmentDeductionTypes = array_keys($governmentDeductionTypes);
$governmentDeductionTypesJson = null;
if ($governmentDeductionStatus === GOVERNMENT_DEDUCTION_WITH) {
    // store as JSON array
    $governmentDeductionTypesJson = json_encode(array_values($governmentDeductionTypes), JSON_UNESCAPED_UNICODE);
}


// Personal/address/emergency fields (worker_profile)
$email = trim($_POST['email'] ?? '');
$dateOfBirth = $_POST['date_of_birth'] ?? null;
$streetAddress = trim($_POST['street_address'] ?? '');
$city = trim($_POST['city'] ?? '');
$stateProvince = trim($_POST['state_province'] ?? '');
$postalCode = trim($_POST['postal_code'] ?? '');
$country = trim($_POST['country'] ?? '');

$emergencyContactName = trim($_POST['emergency_contact_name'] ?? '');
if (!preg_match('/^[\p{L}]+(?: [\p{L}]+)*$/u', $emergencyContactName) || mb_strlen($emergencyContactName) > 50) {
    echo json_encode(['success' => false, 'message' => 'Emergency contact name must contain letters and single spaces only, up to 50 characters.']);
    exit;
}
$emergencyContactPhone = trim($_POST['emergency_contact_phone'] ?? '');
$emergencyContactRelationship = trim($_POST['emergency_contact_relationship'] ?? '');

// Normalize empty strings to null so SQL stores NULL
$normalize = function($v) { $t = is_string($v) ? trim($v) : $v; return ($t === '' ? null : $t); };
$email = $normalize($email);
$streetAddress = $normalize($streetAddress);
$city = $normalize($city);
$stateProvince = $normalize($stateProvince);
$postalCode = $normalize($postalCode);
$country = $normalize($country);
$emergencyContactName = $normalize($emergencyContactName);
$emergencyContactPhone = $normalize($emergencyContactPhone);
$emergencyContactRelationship = $normalize($emergencyContactRelationship);
$dateOfBirth = $normalize($dateOfBirth);
$phone = $normalize($phone);


if (
    $firstName === '' || $lastName === '' || $position === '' || $rateAmount <= 0 || !$email ||
    !$phone || !$dateOfBirth || !$streetAddress || !$city || !$stateProvince || !$postalCode || !$country ||
    !$emergencyContactName || !$emergencyContactPhone || !$emergencyContactRelationship
) {
    echo json_encode(['success' => false, 'message' => 'Complete all required employee, contact, address, and emergency contact fields.']);
    exit;
}
if ($governmentDeductionStatus === GOVERNMENT_DEDUCTION_WITH && !$governmentDeductionTypes) {
    echo json_encode(['success' => false, 'message' => 'Select at least one government deduction.']);
    exit;
}
if (!isset($_FILES['photo']) || (int) ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Employee photo is required.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Enter a valid worker login email.']); exit;
}
if ($phone !== null && !isValidElevenDigitPhone($phone)) {
    echo json_encode(['success' => false, 'message' => 'Phone number must be exactly 11 digits.']);
    exit;
}

if ($emergencyContactPhone !== null && !isValidElevenDigitPhone($emergencyContactPhone)) {
    echo json_encode(['success' => false, 'message' => 'Emergency contact phone must be exactly 11 digits.']);
    exit;
}

if (!isValidEmployeeDate($joinDate)) {
    echo json_encode(['success' => false, 'message' => 'Join date must be a valid date.']);
    exit;
}

if (!isValidEmployeeDate($dateOfBirth) || !isAtLeastEighteenYearsOld($dateOfBirth)) {
    echo json_encode(['success' => false, 'message' => 'Employee must have a valid date of birth and be at least 18 years old.']);
    exit;
}

if ($joinDate < date('Y-m-d')) {
    echo json_encode(['success' => false, 'message' => 'Join date cannot be in the past.']);
    exit;
}

if (!in_array($rateType, ['Hourly', 'Salary'], true)) {
    $rateType = 'Hourly';
}

try {
    if (worker_email_in_use($conn, $email)) {
        echo json_encode(['success' => false, 'message' => 'This email address is already registered.']);
        exit;
    }
} catch (Throwable $error) {
    echo json_encode(['success' => false, 'message' => 'Unable to verify email availability. Please try again.']);
    exit;
}

try {
    user_identity_ensure_columns($conn);
    user_identity_lock($conn);
    if (user_identity_full_name_exists($conn, $firstName . ' ' . $lastName)) {
        throw new RuntimeException('This first and last name combination is already registered.');
    }
    login_security_ensure_columns($conn);
} catch (Throwable $error) {
    echo json_encode(['success' => false, 'message' => $error->getMessage()]);
    exit;
}
$conn->begin_transaction();

try {
    $existingUserStmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $existingUserStmt->bind_param('s', $email);
    $existingUserStmt->execute();
    if ($existingUserStmt->get_result()->fetch_assoc()) throw new RuntimeException('This email is already registered.');
    $existingUserStmt->close();

    $workerFullName = trim($firstName . ' ' . $lastName);
    $accountHash = password_hash($accountPassword, PASSWORD_DEFAULT);
    $accountStmt = $conn->prepare("INSERT INTO users (email, password, full_name, first_name, last_name, status, password_last_set_at, must_change_password) VALUES (?, ?, ?, ?, ?, 'Active', NOW(), 1)");
    if (!$accountStmt) throw new RuntimeException('Failed to prepare worker account.');
    $accountStmt->bind_param('sssss', $email, $accountHash, $workerFullName, $firstName, $lastName);
    if (!$accountStmt->execute()) throw new RuntimeException('Failed to create worker login account.');
    $workerUserId = (int) $conn->insert_id;
    $accountStmt->close();
    $hasGovernmentDeductionColumn = worker_government_deduction_column_exists($conn);

    // Per-worker deduction types column existence check
    $hasGovernmentDeductionTypesColumn = (function(mysqli $conn): bool {
        $result = $conn->query("SHOW COLUMNS FROM worker LIKE 'GovernmentDeductionTypes'");
        return $result !== false && $result->num_rows > 0;
    })($conn);

$insertSql = "
        INSERT INTO worker (
            First_Name,
            Last_Name,
            RateType,
            RateAmount" . ($hasGovernmentDeductionColumn ? ",\n            GovernmentDeductionStatus" : '') . ($hasGovernmentDeductionTypesColumn ? ",\n            GovernmentDeductionTypes" : '') . ",\n            Phone,
            DateHired,
            WorkerStatusID,
            UserID
        ) VALUES (?, ?, ?, ?" . ($hasGovernmentDeductionColumn ? ', ?' : '') . ($hasGovernmentDeductionTypesColumn ? ', ?' : '') . ", ?, ?, 1, ?)
    ";


$stmt = $conn->prepare($insertSql);

    if (!$stmt) {
        throw new RuntimeException('Failed to prepare employee insert.');
        }

    if ($hasGovernmentDeductionColumn && $hasGovernmentDeductionTypesColumn) {
        $stmt->bind_param(
            "sssdssssi",
            $firstName,
            $lastName,
            $rateType,
            $rateAmount,
            $governmentDeductionStatus,
            $governmentDeductionTypesJson,
            $phone,
            $joinDate,
            $workerUserId
        );
    } elseif ($hasGovernmentDeductionColumn) {
        // columns: First_Name, Last_Name, RateType, RateAmount, GovernmentDeductionStatus, Phone, DateHired, WorkerStatusID, UserID
        $stmt->bind_param(
            "sssdsssi",
            $firstName,
            $lastName,
            $rateType,
            $rateAmount,
            $governmentDeductionStatus,
            $phone,
            $joinDate,
            $workerUserId
        );
    } elseif ($hasGovernmentDeductionTypesColumn) {
        // columns: First_Name, Last_Name, RateType, RateAmount, GovernmentDeductionTypes, Phone, DateHired, WorkerStatusID, UserID
        $stmt->bind_param(
            "sssdsssi",
            $firstName,
            $lastName,
            $rateType,
            $rateAmount,
            $governmentDeductionTypesJson,
            $phone,
            $joinDate,
            $workerUserId
        );

    } else {
        $stmt->bind_param("sssdssi", $firstName, $lastName, $rateType, $rateAmount, $phone, $joinDate, $workerUserId);
    }

    if (!$stmt->execute()) {
        throw new RuntimeException('Failed to add employee: ' . $stmt->error);
    }

    $workerId = (int) $conn->insert_id;
    $positionStmt = $conn->prepare('UPDATE worker SET Position = ? WHERE WorkerID = ?');
    if (!$positionStmt) throw new RuntimeException('Failed to prepare employee position update.');
    $positionStmt->bind_param('si', $position, $workerId);
    $positionStmt->execute();
    $positionStmt->close();
    $stmt->close();

    // Ensure worker_profile row exists and populate personal/address/emergency fields.
    // If a row already exists, update it.
    $existsSql = "SELECT 1 FROM worker_profile WHERE WorkerID = ?";
    $existsStmt = $conn->prepare($existsSql);
    if (!$existsStmt) {
        throw new RuntimeException('Failed to prepare worker_profile existence check.');
    }
    $existsStmt->bind_param('i', $workerId);
    $existsStmt->execute();
    $existsResult = $existsStmt->get_result();
    $profileExists = ($existsResult && $existsResult->num_rows > 0);
    $existsStmt->close();

    if ($profileExists) {
        $profileUpdateSql = "UPDATE worker_profile SET
            Email = ?,
            DateOfBirth = ?,
            StreetAddress = ?,
            City = ?,
            StateProvince = ?,
            PostalCode = ?,
            Country = ?,
            EmergencyContactName = ?,
            EmergencyContactPhone = ?,
            EmergencyContactRelationship = ?
            WHERE WorkerID = ?";

        $profileUpdateStmt = $conn->prepare($profileUpdateSql);
        if (!$profileUpdateStmt) {
            throw new RuntimeException('Failed to prepare worker_profile update.');
        }

        $profileUpdateStmt->bind_param(
            'ssssssssssi',
            $email,
            $dateOfBirth,
            $streetAddress,
            $city,
            $stateProvince,
            $postalCode,
            $country,
            $emergencyContactName,
            $emergencyContactPhone,
            $emergencyContactRelationship,
            $workerId
        );

        $profileUpdateStmt->execute();
        $profileUpdateStmt->close();
    } else {
        $profileInsertSql = "INSERT INTO worker_profile (
            WorkerID,
            Email,
            DateOfBirth,
            StreetAddress,
            City,
            StateProvince,
            PostalCode,
            Country,
            EmergencyContactName,
            EmergencyContactPhone,
            EmergencyContactRelationship
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $profileInsertStmt = $conn->prepare($profileInsertSql);
        if (!$profileInsertStmt) {
            throw new RuntimeException('Failed to prepare worker_profile insert.');
        }

        $profileInsertStmt->bind_param(
            'issssssssss',
            $workerId,
            $email,
            $dateOfBirth,
            $streetAddress,
            $city,
            $stateProvince,
            $postalCode,
            $country,
            $emergencyContactName,
            $emergencyContactPhone,
            $emergencyContactRelationship
        );

        $profileInsertStmt->execute();
        $profileInsertStmt->close();
    }


    $employeeReference = 'employee_' . $workerId;
    $photoPath = saveEmployeePhoto($_FILES['photo'] ?? [], $employeeReference);


    $profileUrl = buildEmployeeProfileUrl(buildApplicationBaseUrlFromRequest(), $workerId);
    $qrCodePath = null;
    $qrWarning = null;
    try {
        $qrCodePath = generateEmployeeQrCode($profileUrl, $employeeReference);
    } catch (Throwable $qrError) {
        $qrWarning = $qrError->getMessage();
    }

    $updateSql = "UPDATE worker SET photo_path = ?, qr_code_path = ? WHERE WorkerID = ?";
    $updateStmt = $conn->prepare($updateSql);
    if (!$updateStmt) {
        throw new RuntimeException('Failed to prepare employee asset update.');
    }

    $updateStmt->bind_param("ssi", $photoPath, $qrCodePath, $workerId);
    if (!$updateStmt->execute()) {
        throw new RuntimeException('Failed to save employee image paths: ' . $updateStmt->error);
    }
    $updateStmt->close();

    if ($position !== '') {
        $positionAudit = "Preferred position captured during employee creation: {$position}";
        createEmployeeAudit($conn, $userId, 'Employee Position Note', $positionAudit);
    }

    $approvalStatus = in_array($currentRole, ['Admin', 'Assistant Admin', 'HR'], true) ? 'Approved' : 'Pending';
    createEmployeeApproval($conn, $workerId, $userId, $approvalStatus);

    createEmployeeAudit(
        $conn,
        $userId,
        'Worker Added',
        "{$currentRole} added worker: {$firstName} {$lastName} (ID: {$workerId}) with {$approvalStatus} approval status"
    );

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => $qrWarning
            ? 'Employee added successfully, but QR code generation was skipped: ' . $qrWarning
            : ($approvalStatus === 'Approved'
                ? 'Employee added and approved successfully.'
                : 'Employee added successfully and is pending approval.'),
        'worker_id' => $workerId,
        'approval_status' => $approvalStatus,
        'photo_path' => $photoPath,
        'qr_code_path' => $qrCodePath,
        'profile_url' => $profileUrl,
        'qr_warning' => $qrWarning,
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    $message = ((int) $e->getCode() === 1062)
        ? 'This email is already registered.'
        : 'Unable to create the employee. Please check the details and try again.';
    echo json_encode([
        'success' => false,
        'message' => $message,
    ]);
}

$conn->close();
?>
