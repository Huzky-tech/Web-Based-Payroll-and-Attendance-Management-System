<?php
require_once __DIR__ . '/../includes/security.php';

require_once __DIR__ . '/connection/db_config.php';

if (!function_exists('record_audit_log')) {
    function record_audit_log($user_id, $action, $details = '') {
        global $conn;

        $user_id = (int) $user_id;
        $action = trim((string) $action);
        $details = trim((string) $details);

        if ($user_id <= 0 || $action === '' || !isset($conn) || !($conn instanceof mysqli)) {
            return false;
        }

        $stmt = $conn->prepare("INSERT INTO audit_logs (UserID, Action, Details, Date) VALUES (?, ?, ?, NOW())");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('iss', $user_id, $action, $details);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $userId = (int) ($data['user_id'] ?? ($_SESSION['user_id'] ?? 0));
    $action = $data['action'] ?? '';
    $details = $data['details'] ?? '';

    if ($action === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Action is required']);
        exit;
    }

    if (!record_audit_log($userId, $action, $details)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to record audit log']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Audit log recorded successfully']);
}
