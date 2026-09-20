<?php
/**
 * CLI helper for local testing only.
 * Usage: php database/run_sample_seeder.php
 */
$_SERVER['HTTP_HOST'] = 'localhost';

require dirname(__DIR__) . '/api/connection/db_config.php';
require dirname(__DIR__) . '/api/sample_data_seeder.php';

try {
    $conn->begin_transaction();
    $result = sample_data_run_seeder($conn, 1);
    $conn->commit();
    echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable $e) {
    $conn->rollback();
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
