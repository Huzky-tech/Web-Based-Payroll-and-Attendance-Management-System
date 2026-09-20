<?php
require_once __DIR__ . '/../includes/security.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session expired.']);
    exit;
}

require_once __DIR__ . '/connection/db_config.php';
$timeoutResult = $conn->query('SELECT session_timeout_minutes FROM security_settings WHERE id = 1 LIMIT 1');
$timeoutRow = $timeoutResult ? ($timeoutResult->fetch_assoc() ?: []) : [];
$timeoutSeconds = max(1, (int) ($timeoutRow['session_timeout_minutes'] ?? 30)) * 60;
$lastActivity = (int) ($_SESSION['last_activity'] ?? time());

if (time() - $lastActivity > $timeoutSeconds) {
    $_SESSION = [];
    session_destroy();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session expired due to inactivity.']);
    exit;
}

$_SESSION['last_activity'] = time();
echo json_encode(['success' => true]);
