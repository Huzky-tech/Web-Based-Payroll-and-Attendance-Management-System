<?php
header('Content-Type: application/json');
require_once __DIR__ . '/connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_auth($conn, ['Admin', 'Assistant Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $cacheDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tmp';
    $removed = 0;
    if (is_dir($cacheDirectory)) {
        foreach (glob($cacheDirectory . DIRECTORY_SEPARATOR . 'cache_*') ?: [] as $file) {
            if (is_file($file) && unlink($file)) {
                $removed++;
            }
        }
    }
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
    echo json_encode(['success' => true, 'message' => "System cache cleared successfully ({$removed} cached file(s) removed)."]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
