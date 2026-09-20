<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/connection/db_config.php';
require_once __DIR__ . '/../includes/remember_login.php';

remember_login_ensure_table($conn);
remember_login_revoke_current($conn);
$conn->close();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

session_destroy();
$expired = isset($_GET['expired']) ? '?expired=1' : '';
header('Location: ../index.php' . $expired);
exit;
