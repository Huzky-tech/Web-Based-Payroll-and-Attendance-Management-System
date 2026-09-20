<?php
include 'connection/db_config.php';
include '../includes/auth.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_auth($conn, ['Admin', 'Assistant Admin', 'Payroll Staff', 'HR', 'Timekeeper']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']); exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$headers = is_array($payload['headers'] ?? null) ? array_values($payload['headers']) : [];
$rows = is_array($payload['rows'] ?? null) ? array_values($payload['rows']) : [];
if (!$headers || !$rows || count($headers) > 26 || count($rows) > 10000) {
    http_response_code(422); header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid or empty Excel export data']); exit;
}

$filename = trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($payload['filename'] ?? 'export')), '-') ?: 'export';
$sheetTitle = mb_substr(trim((string) preg_replace('/[\\\/?*\[\]:]+/', '', (string) ($payload['sheet'] ?? 'Export'))), 0, 31) ?: 'Export';
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle($sheetTitle);
foreach ($headers as $column => $header) {
    $sheet->setCellValueExplicit([$column + 1, 1], (string) $header, DataType::TYPE_STRING);
}
foreach ($rows as $rowIndex => $row) {
    if (!is_array($row)) continue;
    foreach ($headers as $column => $_header) {
        $value = $row[$column] ?? '';
        if (!is_scalar($value) && $value !== null) $value = '';
        $sheet->setCellValueExplicit([$column + 1, $rowIndex + 2], (string) ($value ?? ''), DataType::TYPE_STRING);
    }
}
$lastColumn = Coordinate::stringFromColumnIndex(count($headers));
$lastRow = count($rows) + 1;
$sheet->freezePane('A2');
$sheet->setAutoFilter("A1:{$lastColumn}{$lastRow}");
$sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
$sheet->getStyle("A1:{$lastColumn}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD97706');
$sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
for ($column = 1; $column <= count($headers); $column++) $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
header('Cache-Control: max-age=0, no-store');
(new Xlsx($spreadsheet))->save('php://output');
$spreadsheet->disconnectWorksheets();
exit;
