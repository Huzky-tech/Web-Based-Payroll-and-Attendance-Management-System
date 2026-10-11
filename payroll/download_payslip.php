<?php

ob_start();
define('AUTH_BINARY_DOWNLOAD', true);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api/connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../api/payroll_snapshot_helpers.php';
require_once __DIR__ . '/../api/payroll_deduction_helpers.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$role = require_auth($conn, ['Admin', 'Assistant Admin', 'HR', 'Payroll Staff', 'Worker']);
$userId = (int) ($_SESSION['user_id'] ?? 0);
$payrollId = (int) ($_GET['payroll_id'] ?? 0);
$siteId = (int) ($_GET['site_id'] ?? 0);
$workerId = (int) ($_GET['worker_id'] ?? 0);
$preview = (int) ($_GET['preview'] ?? 0) === 1;
$periodStart = (string) ($_GET['period_start'] ?? '');
$periodEnd = (string) ($_GET['period_end'] ?? '');

if ($siteId <= 0 || (!$preview && $payrollId <= 0) || ($preview && $workerId <= 0)) {
    http_response_code(400);
    exit('A worker payroll record and site are required.');
}

if ($preview && $role === 'Worker') {
    http_response_code(403);
    exit('Only payroll staff can download an unreleased payslip preview.');
}

if ($role === 'Payroll Staff' && !auth_payroll_staff_has_site_access($conn, $userId, $siteId)) {
    http_response_code(403);
    exit('You are not assigned to this site.');
}

$snapshot = null;
$workerSnapshot = null;

if ($preview) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodStart) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodEnd)) {
        http_response_code(400);
        exit('A valid payroll period is required for a payslip preview.');
    }

    $workerStmt = $conn->prepare(
        "SELECT w.*, ps.Site_Name, ps.Site_Manager,
                COALESCE(NULLIF(TRIM(wa.Role_On_Site), ''), NULLIF(TRIM(w.Position), ''), 'Worker') AS PositionName
         FROM workerassignment wa
         INNER JOIN worker w ON w.WorkerID = wa.WorkerID
         INNER JOIN projectsite ps ON ps.SiteID = wa.SiteID
         WHERE wa.SiteID = ? AND w.WorkerID = ? LIMIT 1"
    );
    $workerStmt->bind_param('ii', $siteId, $workerId);
    $workerStmt->execute();
    $worker = $workerStmt->get_result()->fetch_assoc();
    $workerStmt->close();
    if (!$worker) {
        http_response_code(404);
        exit('Worker assignment was not found.');
    }

    $settingsResult = $conn->query('SELECT * FROM payroll_settings WHERE id = 1 LIMIT 1');
    $settings = $settingsResult ? $settingsResult->fetch_assoc() : [];
    $attendanceStmt = $conn->prepare(
        "SELECT Date, Time_In, Time_Out, Hours_Worked, Overtime_Hours,
                CASE WHEN AttendanceStatus = 'Present' AND COALESCE(IsLate, 0) = 1 THEN 'Late' ELSE AttendanceStatus END AS AttendanceStatus
         FROM attendance WHERE SiteID = ? AND WorkerID = ? AND Date BETWEEN ? AND ? ORDER BY Date"
    );
    $attendanceStmt->bind_param('iiss', $siteId, $workerId, $periodStart, $periodEnd);
    $attendanceStmt->execute();
    $attendanceResult = $attendanceStmt->get_result();
    $attendance = [];
    $totalHours = 0.0;
    $attendanceOvertime = 0.0;
    $lateDays = 0;
    while ($day = $attendanceResult->fetch_assoc()) {
        $attendance[$day['Date']] = $day;
        if (!empty($day['Time_In']) && $day['Time_In'] !== '00:00:00' && !empty($day['Time_Out']) && $day['Time_Out'] !== '00:00:00') {
            $totalHours += (float) $day['Hours_Worked'];
            $attendanceOvertime += (float) $day['Overtime_Hours'];
        }
        if (strcasecmp((string) $day['AttendanceStatus'], 'Late') === 0) {
            $lateDays++;
        }
    }
    $attendanceStmt->close();

    $rateAmount = (float) $worker['RateAmount'];
    $overtimeRate = (float) ($settings['overtime_rate'] ?? 1.25);
    $grossPay = strtolower((string) $worker['RateType']) === 'salary'
        ? $rateAmount
        : (($totalHours - $attendanceOvertime) * $rateAmount) + ($attendanceOvertime * $rateAmount * $overtimeRate);
    $grossPay = round(max(0, $grossPay), 2);
    $deductionBreakdown = compute_worker_payroll_deductions($grossPay, $worker['GovernmentDeductionStatus'] ?? null, $settings, $worker['GovernmentDeductionTypes'] ?? null);
    $fixedDeductions = compute_fixed_payroll_deductions($worker['PositionName'], $lateDays, $settings);
    $totalDeductions = round($deductionBreakdown['total'] + $fixedDeductions['total'], 2);
    $payslip = [
        'PayrollID' => 0, 'WorkerID' => $workerId, 'UserID' => $worker['UserID'], 'First_Name' => $worker['First_Name'], 'Last_Name' => $worker['Last_Name'],
        'RateType' => $worker['RateType'], 'RateAmount' => $rateAmount, 'Site_Name' => $worker['Site_Name'], 'PositionName' => $worker['PositionName'],
        'Pay_Period_Start' => $periodStart, 'Pay_Period_End' => $periodEnd, 'Gross_Pay' => $grossPay, 'Total_Deductions' => $totalDeductions,
        'Net_Pay' => round($grossPay - $totalDeductions, 2), 'Date_Processed' => date('Y-m-d'), 'Issue_Date' => null, 'approved_at' => null, 'Payslip_Number' => 'PS-' . str_pad((string) $workerId, 6, '0', STR_PAD_LEFT),
    ];
    $workerSnapshot = array_merge($worker, [
        'position' => $worker['PositionName'], 'overtime_hours' => $attendanceOvertime,
        'deduction_breakdown' => $deductionBreakdown, 'fixed_deductions' => $fixedDeductions,
    ]);
} else {
$stmt = $conn->prepare(
    "SELECT p.*, w.UserID, w.First_Name, w.Last_Name, w.RateType, w.RateAmount,
            ps.Site_Name, ps.Site_Manager,
            COALESCE(NULLIF(TRIM(wa.Role_On_Site), ''), NULLIF(TRIM(w.Position), ''), 'Worker') AS PositionName,
            pr.Payroll_RecordsID, pr.Status, pr.approved_at,
            slip.Payslip_Number, slip.Issue_Date
     FROM payroll p
     INNER JOIN worker w ON w.WorkerID = p.WorkerID
     INNER JOIN workerassignment wa ON wa.WorkerID = p.WorkerID AND wa.SiteID = ?
     INNER JOIN projectsite ps ON ps.SiteID = wa.SiteID
     INNER JOIN payroll_records pr
        ON pr.SiteID = wa.SiteID
       AND pr.Period_start = p.Pay_Period_Start
       AND pr.Period_end = p.Pay_Period_End
       AND pr.Status = 'Approved'
     LEFT JOIN payslip slip ON slip.PayrollID = p.PayrollID
     WHERE p.PayrollID = ?
     LIMIT 1"
);
if (!$stmt) {
    http_response_code(500);
    exit('Unable to prepare the payslip.');
}
$stmt->bind_param('ii', $siteId, $payrollId);
$stmt->execute();
$payslip = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payslip || ($role === 'Worker' && (int) $payslip['UserID'] !== $userId)) {
    http_response_code(404);
    exit('The released payslip was not found.');
}

$snapshot = payroll_snapshot_load($conn, (int) $payslip['Payroll_RecordsID']);
foreach (($snapshot['workers'] ?? []) as $worker) {
    if ((int) ($worker['WorkerID'] ?? 0) === (int) $payslip['WorkerID']) {
        $workerSnapshot = $worker;
        break;
    }
}
}

$workerSnapshot = $workerSnapshot ?: [];

function payslip_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function payslip_money($amount): string
{
    return 'PHP ' . number_format((float) $amount, 2);
}

$name = trim((string) $payslip['First_Name'] . ' ' . (string) $payslip['Last_Name']);
$period = date('M j, Y', strtotime($payslip['Pay_Period_Start']))
    . ' - ' . date('M j, Y', strtotime($payslip['Pay_Period_End']));
$issueDate = $payslip['Issue_Date'] ?: ($payslip['approved_at'] ?: $payslip['Date_Processed']);
$number = $payslip['Payslip_Number'] ?: 'PS-' . str_pad((string) ($payrollId ?: $workerId), 6, '0', STR_PAD_LEFT);
$siteContactsStmt = $conn->prepare(
    "SELECT
        COALESCE(NULLIF(TRIM(ps.Site_Manager), ''), 'Site Manager') AS site_manager_name,
        COALESCE(NULLIF(GROUP_CONCAT(DISTINCT NULLIF(TRIM(u.full_name), '') ORDER BY u.full_name SEPARATOR ', '), ''), 'Payroll Staff') AS payroll_staff_name
     FROM projectsite ps
     LEFT JOIN payrollstaffassignment psa ON psa.SiteID = ps.SiteID
     LEFT JOIN payrollstaff pstaff ON pstaff.PayrollStaff_ID = psa.PayrollStaff_ID
     LEFT JOIN users u ON u.id = pstaff.UserID
     WHERE ps.SiteID = ?
     GROUP BY ps.SiteID, ps.Site_Manager"
);
$siteContactsStmt->bind_param('i', $siteId);
$siteContactsStmt->execute();
$siteContacts = $siteContactsStmt->get_result()->fetch_assoc() ?: [];
$siteContactsStmt->close();
$payrollStaffName = (string) ($siteContacts['payroll_staff_name'] ?? 'Payroll Staff');
$siteManagerName = (string) ($siteContacts['site_manager_name'] ?? 'Site Manager');
$rate = $workerSnapshot['RateAmount'] ?? $payslip['RateAmount'];
$rateType = $workerSnapshot['RateType'] ?? $payslip['RateType'];
$attendance = $preview ? $attendance : ($snapshot['attendance'][(int) $payslip['WorkerID']] ?? []);
$presentDays = 0;
$lateDays = 0;
$overtimeHours = (float) ($workerSnapshot['overtime_hours'] ?? 0);
foreach ($attendance as $day) {
    if (strcasecmp((string) ($day['AttendanceStatus'] ?? ''), 'Absent') !== 0) {
        $presentDays++;
    }
    if (strcasecmp((string) ($day['AttendanceStatus'] ?? ''), 'Late') === 0) {
        $lateDays++;
    }
}
$breakdown = $workerSnapshot['deduction_breakdown'] ?? [];
$fixed = $workerSnapshot['fixed_deductions'] ?? [];
$deductionRows = [
    'SSS' => $breakdown['sss'] ?? 0,
    'PhilHealth' => $breakdown['philhealth'] ?? 0,
    'Pag-IBIG' => $breakdown['pagibig'] ?? 0,
    'Late deduction' => $fixed['late'] ?? 0,
    'Position deduction' => $fixed['position'] ?? 0,
];

$deductionMarkup = '';
foreach ($deductionRows as $label => $amount) {
    if ((float) $amount > 0) {
        $deductionMarkup .= '<tr><td>' . payslip_escape($label) . '</td><td class="amount">'
            . payslip_escape(payslip_money($amount)) . '</td></tr>';
    }
}
if ($deductionMarkup === '') {
    $deductionMarkup = '<tr><td>No itemized deductions recorded</td><td class="amount">PHP 0.00</td></tr>';
}

$html = '<!doctype html><html><head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans,sans-serif;color:#1f2937;font-size:11px;margin:28px} .sheet{border:1px solid #d1d5db;padding:26px} .head{border-bottom:3px solid #ea8500;padding-bottom:16px;margin-bottom:18px} h1{margin:0;color:#15233d;font-size:23px} .sub{color:#6b7280;margin-top:4px}.grid{width:100%;border-collapse:collapse;margin:14px 0 20px}.grid td{width:50%;padding:5px 0}.label{color:#6b7280}.box{background:#fff7ed;border:1px solid #fed7aa;padding:14px;margin:16px 0}.box table,.deductions{width:100%;border-collapse:collapse}.box td{padding:4px 0}.deductions th{background:#f3f4f6;text-align:left;padding:8px}.deductions td{padding:8px;border-bottom:1px solid #e5e7eb}.amount{text-align:right}.net{background:#15233d;color:#fff;padding:13px;font-size:15px;font-weight:bold}.signatures{width:100%;border-collapse:collapse;margin-top:54px}.signatures td{width:33.33%;text-align:center;padding:0 12px}.signature-line{border-top:1px solid #374151;padding-top:6px;font-weight:bold}.signature-name{color:#6b7280;font-size:9px;margin-top:3px}.footer{color:#6b7280;margin-top:22px;font-size:9px}
</style></head><body><div class="sheet">
<div class="head"><h1>PAYSLIP</h1><div class="sub">' . payslip_escape($payslip['Site_Name']) . ' &bull; ' . ($preview ? 'Generated ' : 'Released ') . payslip_escape(date('M j, Y', strtotime($issueDate))) . '</div></div>
<table class="grid"><tr><td><span class="label">Employee</span><br><b>' . payslip_escape($name) . '</b></td><td><span class="label">Payslip No.</span><br><b>' . payslip_escape($number) . '</b></td></tr><tr><td><span class="label">Position</span><br>' . payslip_escape($workerSnapshot['position'] ?? $payslip['PositionName']) . '</td><td><span class="label">Pay period</span><br>' . payslip_escape($period) . '</td></tr><tr><td><span class="label">Base rate</span><br>' . payslip_escape(payslip_money($rate)) . ' (' . payslip_escape(ucfirst((string) $rateType)) . ')</td><td><span class="label">Attendance</span><br>' . $presentDays . ' day(s), ' . $lateDays . ' late &bull; ' . number_format($overtimeHours, 2) . ' OT hour(s)</td></tr></table>
<div class="box"><table><tr><td>Gross Pay</td><td class="amount"><b>' . payslip_escape(payslip_money($payslip['Gross_Pay'])) . '</b></td></tr><tr><td>Total Deductions</td><td class="amount"><b>' . payslip_escape(payslip_money($payslip['Total_Deductions'])) . '</b></td></tr></table></div>
<table class="deductions"><tr><th>Deduction details</th><th class="amount">Amount</th></tr>' . $deductionMarkup . '</table>
<div class="net">NET PAY <span style="float:right">' . payslip_escape(payslip_money($payslip['Net_Pay'])) . '</span></div>
<table class="signatures"><tr><td><div class="signature-line">Worker</div><div class="signature-name">' . payslip_escape($name) . '</div></td><td><div class="signature-line">Payroll Staff</div><div class="signature-name">' . payslip_escape($payrollStaffName) . '</div></td><td><div class="signature-line">Site Manager</div><div class="signature-name">' . payslip_escape($siteManagerName) . '</div></td></tr></table>
<div class="footer">This is a system-generated payslip.</div></div></body></html>';

while (ob_get_level() > 0) {
    ob_end_clean();
}
$options = new Options();
$options->set('isRemoteEnabled', false);
$pdf = new Dompdf($options);
$pdf->loadHtml($html, 'UTF-8');
$pdf->setPaper('A4');
$pdf->render();
$filename = 'Payslip_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $name) . '_' . $payslip['Pay_Period_End'] . '.pdf';
$pdf->stream($filename, ['Attachment' => true]);
$conn->close();
exit;
