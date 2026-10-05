<?php
ob_start();

// Tell the shared authentication layer that this is a protected binary
// response. It still performs the normal role and session checks, but must not
// attach HTML page helpers to an .xlsx download.
define('AUTH_BINARY_DOWNLOAD', true);

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
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

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

// Never reconstruct a historical submission from today's assignments or rates.
require_once __DIR__ . '/../api/payroll_snapshot_helpers.php';
try {
    $snapshot = payroll_snapshot_load($conn, $recordId);
} catch (Throwable $error) {
    http_response_code(500);
    exit('Unable to read the saved payroll data. Please contact the administrator.');
}
$summaryOnly = $snapshot === null;
$workers = $snapshot['workers'] ?? [];
$settings = $snapshot['settings'] ?? [];
$attendanceByWorker = $snapshot['attendance'] ?? [];
if ($snapshot !== null) {
    $record['site_name'] = $snapshot['site_name'] ?? $record['site_name'];
    $record['submitted_by_name'] = $snapshot['submitted_by_name'] ?? $record['submitted_by_name'];
}
$sssRate = (float) ($settings['sss_rate'] ?? 0);
$philhealthRate = (float) ($settings['philhealth_rate'] ?? 0);
$pagibigRate = (float) ($settings['pagibig_rate'] ?? 0);
$periodStart = $record['period_start'];
$periodEnd = $record['period_end'];
$periodDays = max(1, (int) ((strtotime($periodEnd) - strtotime($periodStart)) / 86400) + 1);
$periodDates = payroll_export_build_period_dates($periodStart, $periodEnd);
$fixedColumnCount = 2;
$summaryColumnCount = 11;
$dayColumnCount = count($periodDates);
$totalColumnCount = $fixedColumnCount + $dayColumnCount + $summaryColumnCount;
$lastCol = Coordinate::stringFromColumnIndex($totalColumnCount);
$signatureCol = Coordinate::stringFromColumnIndex($totalColumnCount + 1);

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

if ($summaryOnly) {
    $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
    $sheet->setCellValue("A{$row}", 'Historical summary only: worker details were not saved with this submission. Original details cannot be reconstructed reliably.');
    $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true);
    $sheet->getRowDimension($row)->setRowHeight(36);
    $row++;
    foreach (['Workers' => $record['worker_count'], 'Regular Hours' => $record['regular_hours'], 'Overtime Hours' => $record['overtime_hours']] as $label => $value) {
        $sheet->setCellValue("A{$row}", $label);
        $sheet->setCellValue("B{$row}", $value);
        $row++;
    }
    $totals['gross'] = $record['total_gross_pay'];
    $totals['deductions'] = $record['total_deductions'];
    $totals['net'] = $record['total_net_pay'];
    $totals['overtime'] = $record['overtime_hours'];
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
$sheet->setCellValue($signatureCol . $row, 'SIGNATURE OF THE WORKER');

$sheet->getStyle("A{$row}:{$signatureCol}{$row}")->getFont()->setBold(true);
$sheet->getStyle("A{$row}:{$signatureCol}{$row}")->getFill()
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
    $deductionBreakdown = $worker['deduction_breakdown'];
    $deductions = round((float) ($worker['Total_Deductions'] ?? $deductionBreakdown['total']), 2);
    $netPay = round((float) ($worker['Net_Pay'] ?? 0), 2);

    $absenceDeduction = 0.0;
    $lateDeduction = (float) ($worker['fixed_deductions']['late'] ?? 0);
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

$tableRange = "A{$tableHeaderRow}:{$signatureCol}{$totalRow}";
$sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

$row += 2;
if (!$summaryOnly) {
$sheet->setCellValue("A{$row}", 'DEDUCTIONS BREAKDOWN');
$sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
$row++;

$breakdown['other'] = max(0, round($totals['deductions'] - ($breakdown['sss'] + $breakdown['philhealth'] + $breakdown['pagibig'] + $breakdown['late']), 2));

$breakdownLines = [
    'Late deductions (saved at processing)' => round($breakdown['late'], 2),
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

// Keep the approved payroll report together when it is printed. Legal
// landscape provides the needed width for the attendance and payroll columns;
// Excel scales the defined print area to one legal sheet in each direction.
$sheet->getPageSetup()
    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
    ->setPaperSize(PageSetup::PAPERSIZE_LEGAL)
    ->setFitToWidth(1)
    ->setFitToHeight(1);
$sheet->getSheetView()->setZoomScale(75);
$sheet->getPageMargins()
    ->setTop(0.25)
    ->setRight(0.2)
    ->setBottom(0.25)
    ->setLeft(0.2)
    ->setHeader(0.1)
    ->setFooter(0.1);
$sheet->getPageSetup()
    ->setFitToPage(true)
    ->setHorizontalCentered(true)
    ->setPrintArea("A1:{$signatureCol}{$row}");

foreach (range(1, $totalColumnCount + 1) as $columnIndex) {
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
