<?php
declare(strict_types=1);

require_once __DIR__ . '/environment.php';
require_once __DIR__ . '/rate_limiter.php';

$requestIsHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
$forceHttps = filter_var(getenv('APP_FORCE_HTTPS') ?: 'false', FILTER_VALIDATE_BOOLEAN);
if ($forceHttps && !$requestIsHttps && !headers_sent() && !empty($_SERVER['HTTP_HOST'])) {
    $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $requestUri, true, 308);
    exit;
}

if (!headers_sent()) {
    // Never let authenticated or login responses be reused from browser history.
    // A restored protected page must return to PHP so its session is checked.
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('X-Content-Type-Options: nosniff');
    header("Content-Security-Policy: object-src 'none'; base-uri 'self'; frame-ancestors 'none'");
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(self)');
    header('Cross-Origin-Opener-Policy: same-origin');
    if ($requestIsHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

if (session_status() === PHP_SESSION_NONE) {
    $https = $requestIsHttps;
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', $https ? '1' : '0');
    session_name('capstone_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || strlen($_SESSION['csrf_token']) < 64) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('security_csrf_token')) {
    function security_csrf_token(): string { return (string) $_SESSION['csrf_token']; }
}

if (!function_exists('security_verify_csrf')) {
    function security_verify_csrf(?string $token = null): bool {
        $token ??= (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? ''));
        return $token !== '' && hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token);
    }
}

if (!function_exists('security_reject_invalid_csrf')) {
    function security_reject_invalid_csrf(): void {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Your security token is invalid or expired. Refresh the page and try again.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        exit;
    }
}

if (!function_exists('security_enforce_request_limits')) {
    function security_enforce_request_limits(): void {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $allowedMethods = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
        if (!in_array($method, $allowedMethods, true)) {
            header('Allow: ' . implode(', ', $allowedMethods));
            http_response_code(405);
            exit;
        }

        $contentLength = filter_var($_SERVER['CONTENT_LENGTH'] ?? 0, FILTER_VALIDATE_INT);
        if ($contentLength !== false && $contentLength > 15 * 1024 * 1024) {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(413);
            echo json_encode(['success' => false, 'message' => 'The request is too large.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            exit;
        }

        $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'api'));
        $isMutation = in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);
        $limit = security_rate_limit('api:' . $script . ':' . ($isMutation ? 'write' : 'read'), $isMutation ? 120 : 300, 60);
        if (!$limit['allowed']) {
            header('Content-Type: application/json; charset=UTF-8');
            header('Retry-After: ' . $limit['retry_after']);
            http_response_code(429);
            echo json_encode(['success' => false, 'message' => 'Too many requests. Please wait and try again.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            exit;
        }
    }
}

if (!function_exists('security_enforce_api_csrf')) {
    function security_enforce_api_csrf(): void {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) return;
        $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        // Mobile clients are stateless API consumers and do not rely on browser
        // cookies, so browser CSRF tokens do not apply to their endpoints.
        if (str_starts_with($script, 'mobile_')) return;

        if (!security_verify_csrf()) security_reject_invalid_csrf();
    }
}

if (!function_exists('security_enforce_api_authentication')) {
    function security_enforce_api_authentication(): void {
        $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        if (str_starts_with($script, 'mobile_')) return;

        $publicEndpoints = [
            // Logout must remain reachable after an inactivity check has already
            // destroyed the session, so browser navigation can finish at login.
            'logout.php',
            'start_registration.php',
            'send_verification.php',
            'verify_code.php',
            // The login form uses this endpoint to replace its server-side
            // CAPTCHA challenge before a user has authenticated.
            'reroll_login_captcha.php',
            // A user who has supplied their initial password is deliberately
            // not fully signed in yet.  These endpoints use the scoped
            // first-time-login session values and must be reachable before
            // `user_id` is established.
            'start_first_login_verification.php',
            'verify_first_login_otp.php',
            'cancel_first_login.php',
            'set_password.php',
            'start_password_reset.php',
            'verify_reset_code.php',
            'reset_password.php',
        ];
        if (in_array($script, $publicEndpoints, true)) return;
        if (!empty($_SESSION['user_id'])) return;

        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required. Please log in and try again.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        exit;
    }
}
if (!isset($_SESSION['security_rotated_at'])) {
    $_SESSION['security_rotated_at'] = time();
} elseif (time() - (int) $_SESSION['security_rotated_at'] >= 900) {
    session_regenerate_id(true);
    $_SESSION['security_rotated_at'] = time();
}

$securityMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (in_array($securityMethod, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    $fetchSite = strtolower((string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''));
    $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $crossSite = $fetchSite === 'cross-site';
    if ($origin !== '') {
        $originHost = strtolower((string) parse_url($origin, PHP_URL_HOST));
        $originPort = parse_url($origin, PHP_URL_PORT);
        $originAuthority = $originHost . ($originPort ? ':' . $originPort : '');
        $crossSite = $originAuthority !== '' && !hash_equals($host, $originAuthority);
    }
    if ($crossSite) {
        http_response_code(403);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Cross-site request rejected.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        exit;
    }
}

$securityScriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
if (str_contains($securityScriptPath, '/api/')) {
    security_enforce_request_limits();
    security_enforce_api_csrf();
    security_enforce_api_authentication();
}
