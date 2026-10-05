<?php
require_once __DIR__ . '/../api/payroll_snapshot_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli(getenv('DB_HOST') ?: '127.0.0.1', getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '', getenv('DB_NAME') ?: 'payroll_db', (int) (getenv('DB_PORT') ?: 3306));
// Only connection-local temporary tables are used; no real payroll is altered.
$conn->query('CREATE TEMPORARY TABLE payroll_submission_snapshots (Payroll_RecordsID INT PRIMARY KEY, SnapshotData LONGTEXT NOT NULL) ENGINE=InnoDB');
function checkSnapshot(bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
}
$snapshot = ['settings' => ['sss_rate' => 5], 'workers' => [['WorkerID' => 12, 'First_Name' => 'Original name', 'Gross_Pay' => 100, 'Net_Pay' => 95]], 'attendance' => [12 => ['2026-10-01' => ['Hours_Worked' => 8]]]];
$conn->begin_transaction();
payroll_snapshot_save($conn, 1, $snapshot);
$conn->commit();
$snapshot['workers'][0]['First_Name'] = 'Changed name';
$snapshot['settings']['sss_rate'] = 20;
$saved = payroll_snapshot_load($conn, 1);
checkSnapshot($saved['workers'][0]['First_Name'] === 'Original name', 'Worker snapshot changed');
checkSnapshot($saved['settings']['sss_rate'] === 5, 'Settings snapshot changed');
checkSnapshot($saved['attendance'][12]['2026-10-01']['Hours_Worked'] === 8, 'Attendance missing');
checkSnapshot(payroll_snapshot_load($conn, 999) === null, 'Legacy record should have no snapshot');
$conn->begin_transaction();
payroll_snapshot_save($conn, 2, $snapshot);
$conn->rollback();
checkSnapshot(payroll_snapshot_load($conn, 2) === null, 'Rolled-back snapshot persisted');
try {
    payroll_snapshot_save($conn, 1, $snapshot);
    throw new RuntimeException('Existing snapshot was overwritten');
} catch (mysqli_sql_exception $error) {
    checkSnapshot($error->getCode() === 1062, 'Unexpected database failure');
}
echo "Passed: immutable worker/settings/attendance data, legacy fallback, rollback, duplicate protection.\n";
