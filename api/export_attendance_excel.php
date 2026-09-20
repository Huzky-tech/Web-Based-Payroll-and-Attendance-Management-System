<?php

include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR', 'Timekeeper']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$rows = is_array($payload['rows'] ?? null) ? $payload['rows'] : [];
$date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($payload['date'] ?? ''))
    ? (string) $payload['date']
    : date('Y-m-d');

if (!$rows) {
    http_response_code(422);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No attendance records available for export']);
    exit;
}

if (count($rows) > 10000) {
    http_response_code(422);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'The export is limited to 10,000 records']);
    exit;
}

$headers = [
    'Employee', 'Site', 'Date', 'Time In', 'Time Out', 'Status', 'Position',
    'Site Manager', 'Hours Worked', 'Photo Evidence', 'Attendance Timestamp',
    'GPS Coordinates', 'Distance From Site (m)', 'GPS Verification'
];

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Attendance');
$sheet->fromArray($headers, null, 'A1');

$safeRows = [];
foreach ($rows as $row) {
    if (!is_array($row)) {
        continue;
    }
    $safeRows[] = array_map(
        static fn ($value) => is_scalar($value) || $value === null ? (string) ($value ?? '') : '',
        array_slice(array_pad(array_values($row), count($headers), ''), 0, count($headers))
    );
}

if ($safeRows) {
    $sheet->fromArray($safeRows, null, 'A2', true);
}

$lastRow = count($safeRows) + 1;
$sheet->freezePane('A2');
$sheet->setAutoFilter("A1:N{$lastRow}");
$sheet->getStyle('A1:N1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
$sheet->getStyle('A1:N1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD97706');
$sheet->getStyle("A1:N{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getStyle("I2:I{$lastRow}")->getNumberFormat()->setFormatCode('0.00');

foreach (range('A', 'N') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

$filename = "attendance-{$date}.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0, no-store');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
$spreadsheet->disconnectWorksheets();
exit;
