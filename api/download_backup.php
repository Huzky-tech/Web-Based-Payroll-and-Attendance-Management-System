<?php
// The backup is deliberately kept outside public_html and can only be fetched
// through this authenticated endpoint.
define('AUTH_BINARY_DOWNLOAD', true);

include 'connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_auth($conn, ['Admin', 'Assistant Admin']);

$filename = basename((string) ($_GET['file'] ?? ''));
if (!preg_match('/^payroll_backup_\d{8}_\d{6}\.sql$/', $filename)) {
    http_response_code(400);
    exit('Invalid backup file.');
}

$backupPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . $filename;
if (!is_file($backupPath) || !is_readable($backupPath)) {
    http_response_code(404);
    exit('Backup file not found.');
}

header('Content-Type: application/sql; charset=utf-8');
header('Content-Length: ' . (string) filesize($backupPath));
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
readfile($backupPath);
exit;
