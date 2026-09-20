<?php
header('Content-Type: application/json');
include 'connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_auth($conn, ['Admin', 'Assistant Admin']);
include 'system_settings_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Keep database dumps outside the web document root so they cannot be downloaded directly.
    $backupDirectory = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'backups';
    if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0755, true)) {
        throw new RuntimeException('Unable to create the backup directory.');
    }

    $dumpExecutable = getenv('MYSQLDUMP_PATH') ?: 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
    if (!is_file($dumpExecutable)) {
        throw new RuntimeException('mysqldump was not found. Set MYSQLDUMP_PATH to enable database backups.');
    }

    $filename = 'payroll_backup_' . date('Ymd_His') . '.sql';
    $backupPath = $backupDirectory . DIRECTORY_SEPARATOR . $filename;
    $command = escapeshellarg($dumpExecutable)
        . ' --host=' . escapeshellarg(getenv('DB_HOST') ?: '127.0.0.1')
        . ' --port=' . escapeshellarg((string) (getenv('DB_PORT') ?: 3306))
        . ' --user=' . escapeshellarg(getenv('DB_USER') ?: 'root')
        . ' --single-transaction --routines --events '
        . escapeshellarg(getenv('DB_NAME') ?: 'payroll_db')
        . ' > ' . escapeshellarg($backupPath);
    if (getenv('DB_PASS') !== false && getenv('DB_PASS') !== '') {
        $command = escapeshellarg($dumpExecutable)
            . ' --host=' . escapeshellarg(getenv('DB_HOST') ?: '127.0.0.1')
            . ' --port=' . escapeshellarg((string) (getenv('DB_PORT') ?: 3306))
            . ' --user=' . escapeshellarg(getenv('DB_USER') ?: 'root')
            . ' --password=' . escapeshellarg(getenv('DB_PASS'))
            . ' --single-transaction --routines --events '
            . escapeshellarg(getenv('DB_NAME') ?: 'payroll_db')
            . ' > ' . escapeshellarg($backupPath);
    }

    exec($command, $output, $exitCode);
    if ($exitCode !== 0 || !is_file($backupPath) || filesize($backupPath) === 0) {
        @unlink($backupPath);
        throw new RuntimeException('The database backup failed. Check MySQL credentials and mysqldump configuration.');
    }

    if (!ensure_system_last_backup_column($conn)) {
        throw new RuntimeException('Backup completed, but its timestamp could not be saved.');
    }
    $stmt = $conn->prepare('UPDATE system_settings SET last_backup_at = NOW() WHERE id = 1');
    $stmt->execute();
    echo json_encode(['success' => true, 'message' => 'Backup created successfully: ' . $filename]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
