<?php
// Keep API requests on the same session cookie used by the login page and
// dashboards. Without this, API calls start PHPSESSID and appear logged out.
require_once __DIR__ . '/../../includes/security.php';

date_default_timezone_set('Asia/Manila');
mysqli_report(MYSQLI_REPORT_OFF);

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dbName = getenv('DB_NAME') ?: 'payroll_db';
$dbPort = (int) (getenv('DB_PORT') ?: 3306);

$conn = @new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);

if ($conn->connect_errno) {
    $errorMessage = 'Database connection failed. Make sure XAMPP MySQL is running and that the database settings are correct.';

    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
    $isApiRequest = strpos($requestUri, '/api/') !== false || stripos($acceptHeader, 'application/json') !== false;

    if ($isApiRequest) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'error' => $errorMessage,
        ]);
        exit;
    }

    http_response_code(500);
    echo '<h1>Database Connection Failed</h1>';
    echo '<p>' . htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') . '</p>';
    exit;
}

$conn->set_charset('utf8mb4');

if (!function_exists('api_maintenance_message')) {
    function api_maintenance_message(): string
    {
        return 'Sorry, the system is currently under maintenance. Please try again later.';
    }
}

if (!function_exists('api_block_for_maintenance_if_needed')) {
    function api_block_for_maintenance_if_needed(mysqli $conn): void
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($requestUri, '/api/') === false) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (($_SESSION['role'] ?? '') === 'Admin') {
            return;
        }

        $stmt = $conn->prepare("SELECT maintenance_mode FROM system_settings WHERE id = 1 LIMIT 1");
        if (!$stmt) {
            return;
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if ((int) ($row['maintenance_mode'] ?? 0) !== 1) {
            return;
        }

        header('Content-Type: application/json');
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'message' => api_maintenance_message()
        ]);
        exit;
    }
}

api_block_for_maintenance_if_needed($conn);


/**
 * Compatibility helper: Trixie 2.0 attendance endpoints use PDO + getDbConnection().
 * CAPSTONE already provides $conn (mysqli), so we create a PDO wrapper using the
 * same environment variables.
 */
if (!function_exists('getDbConnection')) {
    function getDbConnection(): PDO
    {
        static $pdo = null;
        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $dbHost = getenv('DB_HOST') ?: '127.0.0.1';
        $dbUser = getenv('DB_USER') ?: 'root';
        $dbPass = getenv('DB_PASS') ?: '';
        $dbName = getenv('DB_NAME') ?: 'payroll_db';
        $dbPort = (int)(getenv('DB_PORT') ?: 3306);

        $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return $pdo;
    }
}
?>
