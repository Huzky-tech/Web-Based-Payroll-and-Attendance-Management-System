<?php
header('Content-Type: application/json');

require_once __DIR__ . '/connection/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/sample_data_seeder.php';

require_auth($conn, ['Admin']);
$adminUserId = (int) ($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$confirmed = filter_var($payload['confirm'] ?? false, FILTER_VALIDATE_BOOLEAN);
if (!$confirmed) {
    echo json_encode([
        'success' => false,
        'message' => 'Confirmation is required before generating sample data.',
    ]);
    exit;
}

try {
    if (!sample_data_is_dev_environment($conn)) {
        echo json_encode([
            'success' => false,
            'message' => 'Sample data generation is disabled. Enable Debug Mode or use a local development environment.',
        ]);
        exit;
    }

    user_identity_lock($conn);
    $conn->begin_transaction();
    $result = sample_data_run_seeder($conn, (int) $adminUserId);
    $conn->commit();

    echo json_encode($result);
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}

$conn->close();
