<?php
ob_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../api/connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../api/payroll_approval_helpers.php';
require_once __DIR__ . '/../api/payroll_deduction_helpers.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

function payroll_export_build_period_dates(string $start, string $end): array
{
    $dates = [];
    $current = new DateTime($start);
    $endDate = new DateTime($end);

    while ($current <= $endDate) {
        $dates[] = [
            'date' => $current->format('Y-m-d'),
            'label' => $current->format('D') . "\n" . $current->format('M j'),
        ];
        $current->modify('+1 day');
    }

    return $dates;
}

function payroll_export_format_time_label(?string $time): string
{
    $time = trim((string) ($time ?? ''));
    if ($time === '' || $time === '00:00:00') {
        return '';
    }

    $timestamp = strtotime($time);
    if ($timestamp === false) {
        return $time;
    }

    return date('g:i A', $timestamp);
}

function payroll_export_format_day_cell(?array $attendance): string
{
    if (!$attendance) {
        return '';
    }

    $status = (string) ($attendance['AttendanceStatus'] ?? '');
    $timeIn = payroll_export_format_time_label($attendance['Time_In'] ?? null);
    $timeOut = payroll_export_format_time_label($attendance['Time_Out'] ?? null);
    $hours = round((float) ($attendance['Hours_Worked'] ?? 0), 2);

    if (strcasecmp($status, 'Absent') === 0 || ($timeIn === '' && $hours <= 0)) {
        return 'Absent';
    }

    if ($timeIn !== '' && $timeOut !== '') {
        return number_format($hours, 2) . "h\n{$timeIn} - {$timeOut}";
    }

    if ($timeIn !== '') {
        return number_format($hours, 2) . "h\n{$timeIn}";
    }

    return number_format($hours, 2) . 'h';
}

$currentRole = require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR']);
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$recordId = (int) ($_GET['id'] ?? 0);

if ($recordId <= 0) {
    http_response_code(400);
    exit('Payroll record ID is required.');
}

$record = payroll_approval_fetch_record_by_id($conn, $recordId);
if (!$record) {
    http_response_code(404);
    exit('Payroll record not found.');
}

if ($record['status'] !== 'Approved') {
    http_response_code(403);
    exit('Only approved payroll records can be exported.');
}

if ($currentRole === 'Payroll Staff' && payroll_approval_columns_ready($conn)) {
    if ((int) ($record['submitted_by'] ?? 0) !== $currentUserId) {
        http_response_code(403);
        exit('You can only export payroll submissions that you created.');
    }
}

$settingsResult = $conn->query('SELECT sss_rate, philhealth_rate, pagibig_rate FROM payroll_settings WHERE id = 1 LIMIT 1');
$settings = $settingsResult ? $settingsResult->fetch_assoc() : [];
$sssRate = (float) ($settings['sss_rate'] ?? 0);
$philhealthRate = (float) ($settings['philhealth_rate'] ?? 0);
$pagibigRate = (float) ($settings['pagibig_rate'] ?? 0);
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
        COALESCE(NULLIF(TRIM(wa.Role_On_Site), ''), 'Construction Worker') AS position,
        w.RateType,
        w.RateAmount,
        {$governmentDeductionSelect},
        p.Gross_Pay,
        p.Total_Deductions,
        p.Net_Pay,
        COALESCE(SUM(CASE
            WHEN a.AttendanceStatus = 'Present'
             AND a.Time_In IS NOT NULL AND a.Time_In <> '' AND a.Time_In <> '00:00:00'
             AND a.Time_Out IS NOT NULL AND a.Time_Out <> '' AND a.Time_Out <> '00:00:00'
            THEN 1 ELSE 0
        END), 0) AS present_days,
        COALESCE(SUM(CASE
            WHEN a.AttendanceStatus = 'Late'
             AND a.Time_In IS NOT NULL AND a.Time_In <> '' AND a.Time_In <> '00:00:00'
             AND a.Time_Out IS NOT NULL AND a.Time_Out <> '' AND a.Time_Out <> '00:00:00'
            THEN 1 ELSE 0
        END), 0) AS late_days,
        COALESCE(SUM(CASE WHEN a.AttendanceStatus = 'Absent' THEN 1 ELSE 0 END), 0) AS absent_days,
        COALESCE(SUM(CASE
            WHEN a.Time_In IS NOT NULL AND a.Time_In <> '' AND a.Time_In <> '00:00:00'
             AND a.Time_Out IS NOT NULL AND a.Time_Out <> '' AND a.Time_Out <> '00:00:00'
            THEN a.Overtime_Hours ELSE 0
        END), 0) AS overtime_hours,
        COALESCE(SUM(CASE
            WHEN a.AttendanceStatus = 'Late'
             AND a.Time_In IS NOT NULL AND a.Time_In <> '' AND a.Time_In <> '00:00:00'
             AND a.Time_Out IS NOT NULL AND a.Time_Out <> '' AND a.Time_Out <> '00:00:00'
            THEN GREATEST(0, 8 - a.Hours_Worked) ELSE 0
        END), 0) AS late_hours
    FROM payroll_records pr
    INNER JOIN payroll p
        ON p.Pay_Period_Start = pr.Period_start
        AND p.Pay_Period_End = pr.Period_end
    INNER JOIN workerassignment wa
        ON wa.WorkerID = p.WorkerID
        AND wa.SiteID = pr.SiteID
    INNER JOIN worker w ON w.WorkerID = p.WorkerID
    LEFT JOIN attendance a
        ON a.WorkerID = w.WorkerID
        AND a.SiteID = pr.SiteID
        AND a.Date BETWEEN pr.Period_start AND pr.Period_end
    WHERE pr.Payroll_RecordsID = ?
    GROUP BY
        w.WorkerID, w.First_Name, w.Last_Name, wa.Role_On_Site,
        w.RateType, w.RateAmount{$governmentDeductionGroupBy}, p.Gross_Pay, p.Total_Deductions, p.Net_Pay
    ORDER BY w.Last_Name, w.First_Name
");

if (!$workerStmt) {
    http_response_code(500);
    exit('Failed to prepare payroll export query.');
}

$workerStmt->bind_param('i', $recordId);
$workerStmt->execute();
$workerResult = $workerStmt->get_result();

$workers = [];
while ($row = $workerResult->fetch_assoc()) {
    $workers[] = $row;
}
$workerStmt->close();

if (count($workers) === 0) {
    http_response_code(404);
    exit('No worker payroll rows found for this record.');
}

$periodStart = $record['period_start'];
$periodEnd = $record['period_end'];
$periodDays = max(1, (int) ((strtotime($periodEnd) - strtotime($periodStart)) / 86400) + 1);
$periodDates = payroll_export_build_period_dates($periodStart, $periodEnd);

$attendanceByWorker = [];
$attendanceStmt = $conn->prepare("
    SELECT
        a.WorkerID,
        a.Date,
        a.Time_In,
        a.Time_Out,
        a.Hours_Worked,
        a.AttendanceStatus
    FROM attendance a
    INNER JOIN payroll_records pr ON pr.SiteID = a.SiteID
    WHERE pr.Payroll_RecordsID = ?
      AND a.Date BETWEEN pr.Period_start AND pr.Period_end
");

if ($attendanceStmt) {
    $attendanceStmt->bind_param('i', $recordId);
    $attendanceStmt->execute();
    $attendanceResult = $attendanceStmt->get_result();

    while ($attendanceRow = $attendanceResult->fetch_assoc()) {
        $workerId = (int) ($attendanceRow['WorkerID'] ?? 0);
        $dateKey = (string) ($attendanceRow['Date'] ?? '');
        if ($workerId <= 0 || $dateKey === '') {
            continue;
        }
        $attendanceByWorker[$workerId][$dateKey] = $attendanceRow;
    }

    $attendanceStmt->close();
}

$fixedColumnCount = 2;
$summaryColumnCount = 11;
$dayColumnCount = count($periodDates);
$totalColumnCount = $fixedColumnCount + $dayColumnCount + $summaryColumnCount;
$lastCol = Coordinate::stringFromColumnIndex($totalColumnCount);

$summaryStartCol = $fixedColumnCount + $dayColumnCount + 1;
$summaryHeaders = [
    'Days Absent',
    'Hours Late',
    'OT Hours',
    'Gross Pay',
    'Deduction Status',
    'SSS',
    'PhilHealth',
    'Pag-IBIG',
    'Tax',
    'Total Deductions',
    'Net Pay',
];
$dayHourTotals = array_fill(0, $dayColumnCount, 0.0);

$totals = [
    'absent' => 0,
    'late_hours' => 0.0,
    'overtime' => 0.0,
    'gross' => 0.0,
    'deductions' => 0.0,
    'net' => 0.0,
];

$breakdown = [
    'absence' => 0.0,
    'late' => 0.0,
    'sss' => 0.0,
    'philhealth' => 0.0,
    'pagibig' => 0.0,
    'other' => 0.0,
];

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Payroll Report');

$currencyFormat = '₱#,##0.00';

$row = 1;
$sheet->mergeCells("A{$row}:{$lastCol}{$row}");
$sheet->setCellValue("A{$row}", 'PHILIPPIANS CDO');
$sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(16);
$sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$row++;
$sheet->mergeCells("A{$row}:{$lastCol}{$row}");
$sheet->setCellValue("A{$row}", 'PAYROLL REPORT');
$sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
$sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$row += 2;
$headerLines = [
    'Site Name' => $record['site_name'],
    'Pay Period' => $record['period_label'],
    'Submitted By' => $record['submitted_by_name'] ?: 'Unknown',
    'Generated On' => date('F j, Y'),
    'Payroll Status' => 'Approved',
];

foreach ($headerLines as $label => $value) {
    $sheet->setCellValue("A{$row}", $label . ':');
    $sheet->setCellValue("B{$row}", $value);
    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $row++;
}

$row++;
$tableHeaderRow = $row;

$sheet->setCellValue('A' . $row, 'Employee Name');
$sheet->setCellValue('B' . $row, 'Position');

foreach ($periodDates as $index => $periodDate) {
    $col = Coordinate::stringFromColumnIndex($fixedColumnCount + $index + 1);
    $sheet->setCellValue($col . $row, $periodDate['label']);
    $sheet->getStyle($col . $row)->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER);
}

foreach ($summaryHeaders as $index => $headerLabel) {
    $col = Coordinate::stringFromColumnIndex($summaryStartCol + $index);
    $sheet->setCellValue($col . $row, $headerLabel);
}

$sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setBold(true);
$sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FFF3F4F6');
$sheet->getRowDimension($row)->setRowHeight(32);

$row++;
$dataStartRow = $row;

foreach ($workers as $worker) {
    $rateType = strtolower((string) ($worker['RateType'] ?? 'hourly'));
    $rateAmount = (float) ($worker['RateAmount'] ?? 0);
    $hourlyRate = $rateType === 'salary' ? ($rateAmount / max(1, $periodDays * 8)) : $rateAmount;
    $dailyRate = $rateType === 'salary' ? ($rateAmount / max(1, $periodDays)) : ($rateAmount * 8);

    $absentDays = (int) ($worker['absent_days'] ?? 0);
    $lateHours = round((float) ($worker['late_hours'] ?? 0), 2);
    $otHours = round((float) ($worker['overtime_hours'] ?? 0), 2);
    $grossPay = round((float) ($worker['Gross_Pay'] ?? 0), 2);
    $deductionBreakdown = compute_worker_payroll_deductions(
        $grossPay,
        (string) ($worker['GovernmentDeductionStatus'] ?? GOVERNMENT_DEDUCTION_WITH),
        $settings
    );
    $deductions = round((float) ($worker['Total_Deductions'] ?? $deductionBreakdown['total']), 2);
    $netPay = round((float) ($worker['Net_Pay'] ?? 0), 2);

    $absenceDeduction = round($dailyRate * $absentDays, 2);
    $lateDeduction = round($hourlyRate * $lateHours, 2);
    $sssDeduction = $deductionBreakdown['sss'];
    $philhealthDeduction = $deductionBreakdown['philhealth'];
    $pagibigDeduction = $deductionBreakdown['pagibig'];
    $taxDeduction = $deductionBreakdown['tax'];

    $breakdown['absence'] += $absenceDeduction;
    $breakdown['late'] += $lateDeduction;
    $breakdown['sss'] += $sssDeduction;
    $breakdown['philhealth'] += $philhealthDeduction;
    $breakdown['pagibig'] += $pagibigDeduction;

    $totals['absent'] += $absentDays;
    $totals['late_hours'] += $lateHours;
    $totals['overtime'] += $otHours;
    $totals['gross'] += $grossPay;
    $totals['deductions'] += $deductions;
    $totals['net'] += $netPay;

    $fullName = trim(($worker['First_Name'] ?? '') . ' ' . ($worker['Last_Name'] ?? ''));
    $workerId = (int) ($worker['WorkerID'] ?? 0);
    $workerAttendance = $attendanceByWorker[$workerId] ?? [];

    $sheet->setCellValue('A' . $row, $fullName);
    $sheet->setCellValue('B' . $row, $worker['position'] ?? 'Construction Worker');

    foreach ($periodDates as $index => $periodDate) {
        $col = Coordinate::stringFromColumnIndex($fixedColumnCount + $index + 1);
        $dayAttendance = $workerAttendance[$periodDate['date']] ?? null;
        $cellValue = payroll_export_format_day_cell($dayAttendance);
        $sheet->setCellValue($col . $row, $cellValue);
        $sheet->getStyle($col . $row)->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER);

        if ($dayAttendance && strcasecmp((string) ($dayAttendance['AttendanceStatus'] ?? ''), 'Absent') !== 0) {
            $dayHourTotals[$index] += round((float) ($dayAttendance['Hours_Worked'] ?? 0), 2);
        }
    }

    $sheet->setCellValue(Coordinate::stringFromColumnIndex($summaryStartCol) . $row, $absentDays);
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($summaryStartCol + 1) . $row, $lateHours);
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($summaryStartCol + 2) . $row, $otHours);
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($summaryStartCol + 3) . $row, $grossPay);
    $sheet->setCellValue(
        Coordinate::stringFromColumnIndex($summaryStartCol + 4) . $row,
        $deductionBreakdown['government_deduction_label']
    );
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($summaryStartCol + 5) . $row, $sssDeduction);
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($summaryStartCol + 6) . $row, $philhealthDeduction);
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($summaryStartCol + 7) . $row, $pagibigDeduction);
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($summaryStartCol + 8) . $row, $taxDeduction);
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($summaryStartCol + 9) . $row, $deductions);
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($summaryStartCol + 10) . $row, $netPay);

    $grossCol = Coordinate::stringFromColumnIndex($summaryStartCol + 3);
    $netCol = Coordinate::stringFromColumnIndex($summaryStartCol + 10);
    $sheet->getStyle("{$grossCol}{$row}:{$netCol}{$row}")->getNumberFormat()->setFormatCode($currencyFormat);
    $row++;
}

$dataEndRow = $row - 1;
$totalRow = $row;

$sheet->setCellValue("A{$totalRow}", 'TOTAL');

foreach ($periodDates as $index => $periodDate) {
    $col = Coordinate::stringFromColumnIndex($fixedColumnCount + $index + 1);
    $sheet->setCellValue($col . $totalRow, round($dayHourTotals[$index], 2));
}

$sheet->setCellValue(Coordinate::stringFromColumnIndex($summaryStartCol) . $totalRow, $totals['absent']);
$sheet->setCellValue(Coordinate::stringFromColumnIndex($summaryStartCol + 1) . $totalRow, round($totals['late_hours'], 2));
$sheet->setCellValue(Coordinate::stringFromColumnIndex($summaryStartCol + 2) . $totalRow, round($totals['overtime'], 2));
$sheet->setCellValue(Coordinate::stringFromColumnIndex($summaryStartCol + 3) . $totalRow, round($totals['gross'], 2));
$sheet->setCellValue(Coordinate::stringFromColumnIndex($summaryStartCol + 9) . $totalRow, round($totals['deductions'], 2));
$sheet->setCellValue(Coordinate::stringFromColumnIndex($summaryStartCol + 10) . $totalRow, round($totals['net'], 2));
$sheet->getStyle("A{$totalRow}:{$lastCol}{$totalRow}")->getFont()->setBold(true);

$grossTotalCol = Coordinate::stringFromColumnIndex($summaryStartCol + 3);
$netTotalCol = Coordinate::stringFromColumnIndex($summaryStartCol + 10);
$moneyTotalStartCol = Coordinate::stringFromColumnIndex($summaryStartCol + 5);
$moneyTotalEndCol = Coordinate::stringFromColumnIndex($summaryStartCol + 10);
$sheet->getStyle("{$moneyTotalStartCol}{$totalRow}:{$moneyTotalEndCol}{$totalRow}")->getNumberFormat()->setFormatCode($currencyFormat);
$sheet->getStyle("{$grossTotalCol}{$totalRow}:{$netTotalCol}{$totalRow}")->getNumberFormat()->setFormatCode($currencyFormat);

$tableRange = "A{$tableHeaderRow}:{$lastCol}{$totalRow}";
$sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

$row += 2;
$sheet->setCellValue("A{$row}", 'DEDUCTIONS BREAKDOWN');
$sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
$row++;

$breakdown['other'] = max(0, round($totals['deductions'] - ($breakdown['sss'] + $breakdown['philhealth'] + $breakdown['pagibig']), 2));

$breakdownLines = [
    'Absences (Daily Rate × Absent Days)' => round($breakdown['absence'], 2),
    'Late (Hourly Rate × Late Hours)' => round($breakdown['late'], 2),
];

if ($sssRate > 0) {
    $breakdownLines['SSS (' . $sssRate . '%)'] = round($breakdown['sss'], 2);
}
if ($philhealthRate > 0) {
    $breakdownLines['PhilHealth (' . $philhealthRate . '%)'] = round($breakdown['philhealth'], 2);
}
if ($pagibigRate > 0) {
    $breakdownLines['Pag-IBIG (' . $pagibigRate . '%)'] = round($breakdown['pagibig'], 2);
}
if ($breakdown['other'] > 0) {
    $breakdownLines['Other Deductions'] = $breakdown['other'];
}

foreach ($breakdownLines as $label => $amount) {
    $sheet->setCellValue("A{$row}", $label);
    $sheet->setCellValue("B{$row}", $amount);
    $sheet->getStyle("B{$row}")->getNumberFormat()->setFormatCode($currencyFormat);
    $row++;
}

$row += 2;
$sheet->setCellValue("A{$row}", 'Prepared By:');
$sheet->getStyle("A{$row}")->getFont()->setBold(true);
$row++;
$sheet->setCellValue("A{$row}", $record['submitted_by_name'] ?: 'Payroll Staff');
$row += 2;

$sheet->setCellValue("A{$row}", 'Reviewed By:');
$sheet->getStyle("A{$row}")->getFont()->setBold(true);
$row++;
$sheet->setCellValue("A{$row}", '____________________');
$row += 2;

$sheet->setCellValue("A{$row}", 'Approved By:');
$sheet->getStyle("A{$row}")->getFont()->setBold(true);
$row++;
$sheet->setCellValue("A{$row}", $record['approved_by_name'] ?: 'Administrator');

foreach (range(1, $totalColumnCount) as $columnIndex) {
    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
}

$siteSlug = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $record['site_name']);
$periodSlug = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $record['period_label']);
$filename = 'Payroll_Report_' . trim($siteSlug, '_') . '_' . trim($periodSlug, '_') . '.xlsx';

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

$spreadsheet->disconnectWorksheets();
unset($spreadsheet);
$conn->close();
exit;
