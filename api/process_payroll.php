<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
include '../includes/auth.php';

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR']);
require_once __DIR__ . '/overtime_helpers.php';
require_once __DIR__ . '/payroll_approval_helpers.php';
require_once __DIR__ . '/payroll_deduction_helpers.php';
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);

function get_current_payroll_staff_id(mysqli $conn, int $userId): int {
    $stmt = $conn->prepare("SELECT PayrollStaff_ID FROM payrollstaff WHERE UserID = ? LIMIT 1");
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return (int) ($row['PayrollStaff_ID'] ?? 0);
}

function payroll_effective_start(string $periodStart, ?string $siteStart): string {
    $siteStart = trim((string) $siteStart);
    return ($siteStart !== '' && $siteStart > $periodStart) ? $siteStart : $periodStart;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$siteId = (int) ($data['site_id'] ?? 0);
$periodStart = $data['period_start'] ?? '';
$periodEnd = $data['period_end'] ?? '';

if ($siteId <= 0 || !$periodStart || !$periodEnd) {
    echo json_encode(['success' => false, 'message' => 'Site and pay period are required']);
    exit;
}

if ($currentRole === 'Payroll Staff') {
    $currentPayrollStaffId = get_current_payroll_staff_id($conn, $currentUserId);
    if ($currentPayrollStaffId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Your payroll staff account is not linked to any staff profile']);
        exit;
    }

    $scopeStmt = $conn->prepare("
        SELECT 1
        FROM payrollstaffassignment
        WHERE PayrollStaff_ID = ? AND SiteID = ?
        LIMIT 1
    ");

    if (!$scopeStmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to validate payroll site access: ' . $conn->error]);
        exit;
    }

    $scopeStmt->bind_param("ii", $currentPayrollStaffId, $siteId);
    $scopeStmt->execute();
    $scopeResult = $scopeStmt->get_result();
    $hasAccess = $scopeResult && $scopeResult->num_rows > 0;
    $scopeStmt->close();

    if (!$hasAccess) {
        echo json_encode(['success' => false, 'message' => 'You can only process payroll for your assigned sites']);
        exit;
    }
}

$siteStmt = $conn->prepare("SELECT SiteID, Site_Name, Start_Date FROM projectsite WHERE SiteID = ?");
if (!$siteStmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare site query: ' . $conn->error]);
    exit;
}
$siteStmt->bind_param("i", $siteId);
$siteStmt->execute();
$siteResult = $siteStmt->get_result();
$site = $siteResult->fetch_assoc();
$siteStmt->close();

if (!$site) {
    echo json_encode(['success' => false, 'message' => 'Site not found']);
    exit;
}

$periodStart = payroll_effective_start($periodStart, $site['Start_Date'] ?? null);

$validPayrollRecordClause = payroll_approval_columns_ready($conn)
    ? " AND COALESCE(pr.worker_count, 0) > 0"
    : "";

$existingStmt = $conn->prepare("
    SELECT pr.Payroll_RecordsID
    FROM payroll_records pr
    INNER JOIN payroll p
        ON p.PayrollID = pr.PayrollID
        AND p.Pay_Period_Start = pr.Period_start
        AND p.Pay_Period_End = pr.Period_end
    WHERE pr.SiteID = ? AND pr.Period_start = ? AND pr.Period_end = ?
      AND COALESCE(pr.Status, 'Pending') <> 'Rejected'{$validPayrollRecordClause}
    LIMIT 1
");
$existingStmt->bind_param("iss", $siteId, $periodStart, $periodEnd);
$existingStmt->execute();
$existingResult = $existingStmt->get_result();
$existingRecord = $existingResult->fetch_assoc();
$existingStmt->close();

if ($existingRecord) {
    echo json_encode(['success' => false, 'message' => 'Payroll for this site and period has already been processed']);
    exit;
}

$settingsResult = $conn->query("SELECT * FROM payroll_settings WHERE id = 1 LIMIT 1");
$settings = $settingsResult ? $settingsResult->fetch_assoc() : null;
if (!$settings) {
    echo json_encode(['success' => false, 'message' => 'Payroll settings not found']);
    exit;
}

$effectivePeriodStart = $periodStart;

$approvedOvertimeJoin = overtime_table_exists($conn)
    ? "LEFT JOIN (
            SELECT WorkerID, SiteID, COALESCE(SUM(TotalHours), 0) AS approved_overtime_hours
            FROM overtime_requests
            WHERE Status = 'Approved'
              AND RequestDate BETWEEN ? AND ?
            GROUP BY WorkerID, SiteID
        ) ot ON ot.WorkerID = w.WorkerID AND ot.SiteID = wa.SiteID"
    : '';

$approvedOvertimeSelect = overtime_table_exists($conn)
    ? 'COALESCE(ot.approved_overtime_hours, 0) AS approved_overtime_hours'
    : '0 AS approved_overtime_hours';

$hasGovernmentDeductionColumn = worker_government_deduction_column_exists($conn);
$governmentDeductionSelect = $hasGovernmentDeductionColumn
    ? "COALESCE(w.GovernmentDeductionStatus, 'With Deductions') AS GovernmentDeductionStatus"
    : "'With Deductions' AS GovernmentDeductionStatus";
$governmentDeductionGroupBy = $hasGovernmentDeductionColumn ? ', w.GovernmentDeductionStatus' : '';

$workerStmt = $conn->prepare("
    SELECT 
        w.WorkerID,
        w.First_Name,
        w.Last_Name,
        COALESCE(NULLIF(TRIM(wa.Role_On_Site), ''), 'Construction Worker') AS role_on_site,
        w.RateType,
        w.RateAmount,
        {$governmentDeductionSelect},
        COALESCE(SUM(CASE
            WHEN a.Time_In IS NOT NULL AND a.Time_In <> '' AND a.Time_In <> '00:00:00'
             AND a.Time_Out IS NOT NULL AND a.Time_Out <> '' AND a.Time_Out <> '00:00:00'
            THEN a.Hours_Worked ELSE 0
        END), 0) AS total_hours,
        COALESCE(SUM(CASE
            WHEN a.Time_In IS NOT NULL AND a.Time_In <> '' AND a.Time_In <> '00:00:00'
             AND a.Time_Out IS NOT NULL AND a.Time_Out <> '' AND a.Time_Out <> '00:00:00'
            THEN a.Overtime_Hours ELSE 0
        END), 0) AS attendance_overtime_hours,
        SUM(CASE
            WHEN a.AttendanceStatus = 'Late'
             AND a.Time_In IS NOT NULL AND a.Time_In <> '' AND a.Time_In <> '00:00:00'
             AND a.Time_Out IS NOT NULL AND a.Time_Out <> '' AND a.Time_Out <> '00:00:00'
            THEN 1 ELSE 0
        END) AS late_days,
        {$approvedOvertimeSelect}
    FROM workerassignment wa
    INNER JOIN worker w ON wa.WorkerID = w.WorkerID
    LEFT JOIN attendance a
        ON a.WorkerID = w.WorkerID
        AND a.SiteID = wa.SiteID
        AND a.Date BETWEEN ? AND ?
    {$approvedOvertimeJoin}
    WHERE wa.SiteID = ?
    GROUP BY w.WorkerID, w.First_Name, w.Last_Name, wa.Role_On_Site, w.RateType, w.RateAmount{$governmentDeductionGroupBy}" .
    (overtime_table_exists($conn) ? ', ot.approved_overtime_hours' : '') . "
    ORDER BY w.Last_Name, w.First_Name
");

if (!$workerStmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare payroll worker query: ' . $conn->error]);
    exit;
}

if (overtime_table_exists($conn)) {
    $workerStmt->bind_param("ssssi", $effectivePeriodStart, $periodEnd, $effectivePeriodStart, $periodEnd, $siteId);
} else {
    $workerStmt->bind_param("ssi", $effectivePeriodStart, $periodEnd, $siteId);
}
$workerStmt->execute();
$workersResult = $workerStmt->get_result();

$workers = [];
while ($row = $workersResult->fetch_assoc()) {
    $workers[] = $row;
}
$workerStmt->close();

if (count($workers) === 0) {
    echo json_encode(['success' => false, 'message' => 'No workers assigned to this site for payroll processing']);
    exit;
}

$overtimeRate = (float) ($settings['overtime_rate'] ?? 1.25);

$conn->begin_transaction();

try {
    $insertPayrollStmt = $conn->prepare("
        INSERT INTO payroll (
            WorkerID,
            Pay_Period_Start,
            Pay_Period_End,
            Gross_Pay,
            Total_Deductions,
            Net_Pay,
            Date_Processed
        ) VALUES (?, ?, ?, ?, ?, ?, CURDATE())
    ");

    if (!$insertPayrollStmt) {
        throw new Exception('Failed to prepare payroll insert: ' . $conn->error);
    }

    $existingPayrollStmt = $conn->prepare("
        SELECT PayrollID, Gross_Pay, Total_Deductions, Net_Pay
        FROM payroll
        WHERE WorkerID = ?
          AND Pay_Period_Start = ?
          AND Pay_Period_End = ?
        LIMIT 1
    ");

    if (!$existingPayrollStmt) {
        throw new Exception('Failed to prepare existing payroll check: ' . $conn->error);
    }

    $updatePayrollStmt = $conn->prepare("
        UPDATE payroll
        SET Gross_Pay = ?, Total_Deductions = ?, Net_Pay = ?, Date_Processed = CURDATE()
        WHERE PayrollID = ?
    ");
    if (!$updatePayrollStmt) {
        throw new Exception('Failed to prepare payroll correction update: ' . $conn->error);
    }

    $totalGross = 0;
    $totalDeductions = 0;
    $totalNet = 0;
    $processedWorkers = 0;
    $totalRegularHours = 0.0;
    $totalOvertimeHours = 0.0;
    $firstPayrollId = null;

    foreach ($workers as $worker) {
        $rateType = strtolower((string) ($worker['RateType'] ?? 'hourly'));
        $rateAmount = (float) ($worker['RateAmount'] ?? 0);
        $totalHours = (float) ($worker['total_hours'] ?? 0);
        $attendanceOvertimeHours = (float) ($worker['attendance_overtime_hours'] ?? 0);
        $approvedOvertimeHours = (float) ($worker['approved_overtime_hours'] ?? 0);
        $regularHours = max(0, $totalHours - $attendanceOvertimeHours);
        $overtimeHours = $attendanceOvertimeHours + $approvedOvertimeHours;
        $lateDays = (int) ($worker['late_days'] ?? 0);
        $roleOnSite = (string) ($worker['role_on_site'] ?? 'Construction Worker');

        if ($rateType === 'salary') {
            $grossPay = $rateAmount;
        } else {
            $grossPay = ($regularHours * $rateAmount) + ($overtimeHours * $rateAmount * $overtimeRate);
        }

        $grossPay = round($grossPay, 2);
        $deductionBreakdown = compute_worker_payroll_deductions(
            $grossPay,
            (string) ($worker['GovernmentDeductionStatus'] ?? GOVERNMENT_DEDUCTION_WITH),
            $settings
        );
        $fixedDeductionBreakdown = compute_fixed_payroll_deductions($roleOnSite, $lateDays, $settings);
        $deductions = round($deductionBreakdown['total'] + $fixedDeductionBreakdown['total'], 2);
        $netPay = round($grossPay - $deductions, 2);

        $workerId = (int) $worker['WorkerID'];

        $existingPayrollStmt->bind_param("iss", $workerId, $periodStart, $periodEnd);
        $existingPayrollStmt->execute();
        $existingPayroll = $existingPayrollStmt->get_result()->fetch_assoc();

        if ($existingPayroll) {
            if ($firstPayrollId === null) {
                $firstPayrollId = (int) $existingPayroll['PayrollID'];
            }

            $existingPayrollId = (int) $existingPayroll['PayrollID'];
            $updatePayrollStmt->bind_param('dddi', $grossPay, $deductions, $netPay, $existingPayrollId);
            if (!$updatePayrollStmt->execute()) {
                throw new Exception('Failed to update corrected payroll row: ' . $updatePayrollStmt->error);
            }
            $totalGross += $grossPay;
            $totalDeductions += $deductions;
            $totalNet += $netPay;
            $totalRegularHours += $regularHours;
            $totalOvertimeHours += $overtimeHours;
            $processedWorkers++;
            continue;
        }

        $insertPayrollStmt->bind_param(
            "issddd",
            $workerId,
            $periodStart,
            $periodEnd,
            $grossPay,
            $deductions,
            $netPay
        );

        if (!$insertPayrollStmt->execute()) {
            throw new Exception('Failed to insert payroll row: ' . $insertPayrollStmt->error);
        }

        if ($firstPayrollId === null) {
            $firstPayrollId = $insertPayrollStmt->insert_id;
        }

        $totalGross += $grossPay;
        $totalDeductions += $deductions;
        $totalNet += $netPay;
        $totalRegularHours += $regularHours;
        $totalOvertimeHours += $overtimeHours;
        $processedWorkers++;
    }

    $existingPayrollStmt->close();
    $updatePayrollStmt->close();
    $insertPayrollStmt->close();

    $roundedGross = round($totalGross, 2);

    // Admin/Assistant Admin auto-approve; Payroll Staff and HR submissions
    // stay Pending so they follow the approval workflow.
    // This matches UI behavior in admin/payroll_status.php.
    $status = ($currentRole === 'Admin' || $currentRole === 'Assistant Admin') ? 'Approved' : 'Pending';

    $roundedDeductions = round($totalDeductions, 2);
    $roundedNet = round($totalNet, 2);
    $roundedRegularHours = round($totalRegularHours, 2);
    $roundedOvertimeHours = round($totalOvertimeHours, 2);
    $hasWorkflowColumns = payroll_approval_columns_ready($conn);

    if ($hasWorkflowColumns) {
        // When workflow exists, ensure submitted_by/submitted_at exist.
        // If Admin/Assistant Admin auto-approves, also set approved_by/approved_at.
        if ($status === 'Approved') {
            $recordStmt = $conn->prepare(
                "INSERT INTO payroll_records (\n"
                . "    PayrollID,\n"
                . "    SiteID,\n"
                . "    Period_start,\n"
                . "    Period_end,\n"
                . "    Total_gross_pay,\n"
                . "    Total_deductions,\n"
                . "    Total_net_pay,\n"
                . "    Status,\n"
                . "    submitted_by,\n"
                . "    submitted_at,\n"
                . "    approved_by,\n"
                . "    approved_at,\n"
                . "    rejected_by,\n"
                . "    rejected_at,\n"
                . "    rejection_reason,\n"
                . "    worker_count,\n"
                . "    regular_hours,\n"
                . "    overtime_hours\n"
                . ") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, NOW(), NULL, NULL, NULL, ?, ?, ?)"
            );

            if (!$recordStmt) {
                throw new Exception('Failed to prepare payroll record insert (approved): ' . $conn->error);
            }

            // bind_param types: i i s s d d d s i i i d d d? (mysql types)
            // We'll match: PayrollID(i), SiteID(i), Period_start(s), Period_end(s),
            // Total_gross_pay(d), Total_deductions(d), Total_net_pay(d), Status(s),
            // submitted_by(i), approved_by(i), worker_count(i), regular_hours(d), overtime_hours(d)
            $recordStmt->bind_param(
                "iissdddsiiidd",
                $firstPayrollId,
                $siteId,
                $periodStart,
                $periodEnd,
                $roundedGross,
                $roundedDeductions,
                $roundedNet,
                $status,
                $currentUserId,
                $currentUserId,
                $processedWorkers,
                $roundedRegularHours,
                $roundedOvertimeHours
            );
        } else {
            $recordStmt = $conn->prepare(
                "INSERT INTO payroll_records (\n"
                . "    PayrollID,\n"
                . "    SiteID,\n"
                . "    Period_start,\n"
                . "    Period_end,\n"
                . "    Total_gross_pay,\n"
                . "    Total_deductions,\n"
                . "    Total_net_pay,\n"
                . "    Status,\n"
                . "    submitted_by,\n"
                . "    submitted_at,\n"
                . "    worker_count,\n"
                . "    regular_hours,\n"
                . "    overtime_hours\n"
                . ") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)"
            );

            if (!$recordStmt) {
                throw new Exception('Failed to prepare payroll record insert (pending): ' . $conn->error);
            }

            $recordStmt->bind_param(
                "iissdddsiidd",
                $firstPayrollId,
                $siteId,
                $periodStart,
                $periodEnd,
                $roundedGross,
                $roundedDeductions,
                $roundedNet,
                $status,
                $currentUserId,
                $processedWorkers,
                $roundedRegularHours,
                $roundedOvertimeHours
            );
        }
    } else {
        $recordStmt = $conn->prepare("
            INSERT INTO payroll_records (
                PayrollID,
                SiteID,
                Period_start,
                Period_end,
                Total_gross_pay,
                Total_deductions,
                Total_net_pay,
                Status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$recordStmt) {
            throw new Exception('Failed to prepare payroll record insert: ' . $conn->error);
        }

        $recordStmt->bind_param(
            "iissddds",
            $firstPayrollId,
            $siteId,
            $periodStart,
            $periodEnd,
            $roundedGross,
            $roundedDeductions,
            $roundedNet,
            $status
        );
    }




    if (!$recordStmt->execute()) {
        throw new Exception('Failed to insert payroll summary: ' . $recordStmt->error);
    }

    $recordId = $recordStmt->insert_id;
    $recordStmt->close();

    // A site payroll batch requires review when Payroll Staff or HR submits it.
    // Store a workflow notification immediately so Admin and Assistant Admin
    // do not have to discover the pending batch manually.
    if ($status === 'Pending') {
        $notificationTable = $conn->query("SHOW TABLES LIKE 'admin_notifications'");
        if ($notificationTable && $notificationTable->num_rows > 0) {
            $notificationTitle = 'Payroll awaiting approval';
            $submitterName = trim((string) ($_SESSION['full_name'] ?? $currentRole)) ?: $currentRole;
            $notificationMessage = sprintf(
                '%s submitted payroll for %s (%s to %s, %d workers).',
                $submitterName,
                (string) $site['Site_Name'],
                $periodStart,
                $periodEnd,
                $processedWorkers
            );
            $notificationType = 'Payroll Submitted';
            $notificationStmt = $conn->prepare("
                INSERT INTO admin_notifications
                    (RecipientUserID, NotificationType, ReferenceID, Title, Message, IsRead, CreatedAt)
                VALUES (NULL, ?, ?, ?, ?, 0, NOW())
            ");
            if (!$notificationStmt) {
                throw new Exception('Payroll was not submitted because its approval notification could not be prepared.');
            }
            $notificationStmt->bind_param('siss', $notificationType, $recordId, $notificationTitle, $notificationMessage);
            if (!$notificationStmt->execute()) {
                throw new Exception('Payroll was not submitted because its approval notification could not be created.');
            }
            $notificationStmt->close();
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Payroll processed successfully',
        'record_id' => $recordId,
        'site_name' => $site['Site_Name'],
        'workers_processed' => $processedWorkers,
        'summary' => [
            'gross_pay' => $roundedGross,
            'total_deductions' => $roundedDeductions,
            'net_pay' => $roundedNet
        ]
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
