<?php
header('Content-Type: application/json; charset=utf-8');

include 'connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_auth($conn, ['Admin', 'Assistant Admin']);
include 'system_settings_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

function backup_sql_identifier(string $value): string
{
    return '`' . str_replace('`', '``', $value) . '`';
}

function backup_write($handle, string $contents): void
{
    if (fwrite($handle, $contents) === false) {
        throw new RuntimeException('Unable to write the backup file.');
    }
}

/** Export the current database through the authenticated MySQL connection.
 * This works on shared hosting where the mysqldump executable is unavailable.
 */
function backup_export_database(mysqli $conn, string $backupPath): void
{
    $handle = @fopen($backupPath, 'xb');
    if ($handle === false) {
        throw new RuntimeException('Unable to create the backup file.');
    }

    $transactionStarted = false;
    try {
        // A consistent read avoids mixing rows from different points in time.
        if ($conn->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ')
            && $conn->query('START TRANSACTION WITH CONSISTENT SNAPSHOT')) {
            $transactionStarted = true;
        }

        backup_write($handle, "-- Payroll and Attendance System database backup\n");
        backup_write($handle, '-- Generated: ' . date('Y-m-d H:i:s P') . "\n\n");
        backup_write($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = $conn->query('SHOW TABLES');
        if (!$tables) {
            throw new RuntimeException('Unable to read database tables.');
        }

        while ($tableRow = $tables->fetch_row()) {
            $table = (string) ($tableRow[0] ?? '');
            if ($table === '') {
                continue;
            }

            $safeTable = backup_sql_identifier($table);
            $createResult = $conn->query("SHOW CREATE TABLE {$safeTable}");
            $createRow = $createResult ? $createResult->fetch_row() : null;
            if (!$createRow || empty($createRow[1])) {
                throw new RuntimeException("Unable to read the structure for table {$table}.");
            }

            backup_write($handle, "DROP TABLE IF EXISTS {$safeTable};\n");
            backup_write($handle, $createRow[1] . ";\n\n");

            $rows = $conn->query("SELECT * FROM {$safeTable}");
            if (!$rows) {
                throw new RuntimeException("Unable to read data from table {$table}.");
            }

            $fields = $rows->fetch_fields();
            $columns = array_map(static function ($field): string {
                return backup_sql_identifier((string) $field->name);
            }, $fields);
            $columnList = implode(', ', $columns);

            while ($row = $rows->fetch_assoc()) {
                $values = [];
                foreach ($fields as $field) {
                    $value = $row[$field->name] ?? null;
                    $values[] = $value === null
                        ? 'NULL'
                        : "'" . $conn->real_escape_string((string) $value) . "'";
                }
                backup_write($handle, "INSERT INTO {$safeTable} ({$columnList}) VALUES (" . implode(', ', $values) . ");\n");
            }
            $rows->free();
            backup_write($handle, "\n");
        }
        $tables->free();
        backup_write($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
    } finally {
        if ($transactionStarted) {
            $conn->commit();
        }
        fclose($handle);
    }
}

$backupPath = '';
try {
    // Store backup files above public_html so they cannot be accessed directly.
    $backupDirectory = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'backups';
    if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0755, true)) {
        throw new RuntimeException('Unable to create the backup directory.');
    }

    $filename = 'payroll_backup_' . date('Ymd_His') . '.sql';
    $backupPath = $backupDirectory . DIRECTORY_SEPARATOR . $filename;
    backup_export_database($conn, $backupPath);

    if (!is_file($backupPath) || filesize($backupPath) === 0) {
        throw new RuntimeException('The database backup was empty.');
    }

    if (!ensure_system_last_backup_column($conn)) {
        throw new RuntimeException('Backup completed, but its timestamp could not be saved.');
    }
    $stmt = $conn->prepare('UPDATE system_settings SET last_backup_at = NOW() WHERE id = 1');
    if (!$stmt) {
        throw new RuntimeException('Backup completed, but its timestamp could not be saved.');
    }
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Database backup created successfully. Your SQL file will download now.',
        'download_url' => '../api/download_backup.php?file=' . rawurlencode($filename),
    ]);
} catch (Throwable $e) {
    if ($backupPath !== '' && is_file($backupPath)) {
        @unlink($backupPath);
    }
    error_log('System backup failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
