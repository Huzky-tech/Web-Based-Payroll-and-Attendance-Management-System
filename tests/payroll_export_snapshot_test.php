<?php
require_once __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Exercise the real workbook renderer without a session or production database.
$source = file_get_contents(__DIR__ . '/../payroll/export_payroll_excel.php');
$functionsStart = strpos($source, 'function payroll_export_build_period_dates');
$functionsEnd = strpos($source, '$currentRole =', $functionsStart);
eval(substr($source, $functionsStart, $functionsEnd - $functionsStart));
$renderStart = strpos($source, '$fixedColumnCount =');
$renderEnd = strpos($source, '$siteSlug =', $renderStart);
$imports = 'use PhpOffice\\PhpSpreadsheet\\Spreadsheet; use PhpOffice\\PhpSpreadsheet\\Style\\Alignment; use PhpOffice\\PhpSpreadsheet\\Style\\Border; use PhpOffice\\PhpSpreadsheet\\Style\\Fill; use PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate; use PhpOffice\\PhpSpreadsheet\\Worksheet\\PageSetup;';
foreach ([false, true] as $summaryOnly) {
    $record = ['site_name' => 'Original site', 'period_label' => 'Oct 1, 2026', 'submitted_by_name' => 'Original submitter', 'approved_by_name' => 'Approver', 'worker_count' => 1, 'regular_hours' => 8, 'overtime_hours' => 2, 'total_gross_pay' => 100, 'total_deductions' => 5, 'total_net_pay' => 95];
    $periodDays = 1;
    $periodDates = payroll_export_build_period_dates('2026-10-01', '2026-10-01');
    $sssRate = 5; $philhealthRate = 0; $pagibigRate = 0;
    $workers = $summaryOnly ? [] : [['WorkerID' => 7, 'First_Name' => 'Original', 'Last_Name' => 'Worker', 'position' => 'Carpenter', 'Gross_Pay' => 100, 'Total_Deductions' => 5, 'Net_Pay' => 95, 'deduction_breakdown' => ['sss' => 5, 'philhealth' => 0, 'pagibig' => 0, 'tax' => 0, 'total' => 5, 'government_deduction_label' => 'With Deductions']]];
    $attendanceByWorker = [7 => ['2026-10-01' => ['Time_In' => '08:00:00', 'Time_Out' => '17:00:00', 'Hours_Worked' => 8, 'AttendanceStatus' => 'Present']]];
    eval($imports . substr($source, $renderStart, $renderEnd - $renderStart));
    $rows = $sheet->toArray();
    $text = json_encode($rows);
    if (strpos($text, $summaryOnly ? 'Historical summary only' : 'Original Worker') === false) {
        throw new RuntimeException('Missing exported data');
    }
    if ((float) $sheet->getCell(Coordinate::stringFromColumnIndex($summaryStartCol + 10) . $totalRow)->getValue() !== 95.0) {
        throw new RuntimeException('Incorrect saved net total');
    }
    $file = tempnam(sys_get_temp_dir(), 'payroll-test-');
    try {
        (new Xlsx($spreadsheet))->save($file);
        $zip = new ZipArchive();
        if ($zip->open($file) !== true || $zip->locateName('xl/worksheets/sheet1.xml') === false) {
            throw new RuntimeException('Invalid Excel workbook');
        }
        $zip->close();
    } finally {
        unlink($file);
        $spreadsheet->disconnectWorksheets();
    }
}
echo "Passed: snapshot and historical-summary Excel generation, saved net totals, valid XLSX files.\n";
