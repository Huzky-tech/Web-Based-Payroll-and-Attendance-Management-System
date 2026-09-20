<?php
// Use the same hardened, named session as the login page. Starting PHP's
// default PHPSESSID here makes authenticated users bounce forever between
// index.php and their dashboard.
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/manager_role.php';

if (!function_exists('auth_get_user_role')) {
    function auth_get_user_role($conn, $user_id) {
        manager_role_ensure_table($conn);
        $roleTables = [
            'admin' => 'Admin',
            'assistantmanager' => 'Assistant Admin',
            'managers' => 'Manager',
            'hr' => 'HR',
            'payrollstaff' => 'Payroll Staff',
            'timekeeper' => 'Timekeeper',
        ];

        foreach ($roleTables as $table => $role) {
            $stmt = $conn->prepare("SELECT 1 FROM {$table} WHERE UserID = ?");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();

            if ($stmt->get_result()->num_rows > 0) {
                $stmt->close();
                return $role;
            }

            $stmt->close();
        }

        $workerStmt = $conn->prepare('SELECT 1 FROM worker WHERE UserID = ? LIMIT 1');
        $workerStmt->bind_param('i', $user_id);
        $workerStmt->execute();
        if ($workerStmt->get_result()->num_rows > 0) {
            $workerStmt->close();
            return 'Worker';
        }
        $workerStmt->close();

        return 'User';
    }
}

if (!function_exists('auth_get_redirect_path')) {
    function auth_get_redirect_path($role) {
        switch ($role) {
            case 'Admin':
                return '../admin/dashboard.php';
            case 'Assistant Admin':
                return '../users/ass_dashboard.php';
            case 'Payroll Staff':
                return '../users/payroll_dashboard.php';
            case 'HR':
                return '../users/hr_dashboard.php';
            case 'Timekeeper':
                return '../users/timekeeper_dashboard.php?page=attendance';
            case 'Manager':
                return '../users/pending_dashboard.php';
            case 'Worker':
                return '../users/worker_dashboard.php';
            case 'User':
                return '../users/pending_dashboard.php';
            default:
                return '../users/pending_dashboard.php';
        }
    }
}

if (!function_exists('auth_get_access_denied_redirect_path')) {
    function auth_get_access_denied_redirect_path($role): string {
        $path = auth_get_redirect_path($role);
        $separator = str_contains($path, '?') ? '&' : '?';
        return $path . $separator . 'access_denied=1';
    }
}

if (!function_exists('auth_is_maintenance_mode')) {
    function auth_is_maintenance_mode($conn): bool {
        if (!$conn instanceof mysqli) {
            return false;
        }

        $stmt = $conn->prepare("SELECT maintenance_mode FROM system_settings WHERE id = 1 LIMIT 1");
        if (!$stmt) {
            return false;
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return (int) ($row['maintenance_mode'] ?? 0) === 1;
    }
}

if (!function_exists('auth_maintenance_message')) {
    function auth_maintenance_message(): string {
        return 'Sorry, the system is currently under maintenance. Please try again later.';
    }
}

if (!function_exists('auth_render_maintenance_page')) {
    function auth_render_maintenance_page(): void {
        $message = auth_maintenance_message();
        $scriptName = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $loginHref = $scriptName === 'index.php' ? 'index.php' : '../index.php';
        http_response_code(503);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Under Maintenance</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            color: #111827;
        }
        .maintenance-box {
            width: min(460px, 100%);
            border-radius: 8px;
            background: #ffffff;
            padding: 32px;
            text-align: center;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.14);
        }
        .maintenance-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fef3c7;
            color: #92400e;
            font-size: 30px;
            font-weight: 700;
        }
        h1 {
            margin: 0 0 10px;
            font-size: 24px;
            line-height: 1.25;
        }
        p {
            margin: 0;
            color: #4b5563;
            line-height: 1.6;
        }
        a {
            display: inline-block;
            margin-top: 22px;
            color: #2563eb;
            font-weight: 700;
            text-decoration: none;
        }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <main class="maintenance-box">
        <div class="maintenance-icon">!</div>
        <h1>Currently Under Maintenance</h1>
        <p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>
        <a href="' . htmlspecialchars($loginHref, ENT_QUOTES, 'UTF-8') . '">Back to login</a>
    </main>
</body>
</html>';
        exit();
    }
}

if (!function_exists('require_auth')) {
    function require_auth($conn, array $allowedRoles = []) {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isApiRequest = strpos($requestUri, '/api/') !== false
            || stripos($acceptHeader, 'application/json') !== false
            || stripos($contentType, 'application/json') !== false;

        if (empty($_SESSION['user_id'])) {
            if ($isApiRequest) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Please log in again before processing payroll.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                exit();
            }

            header('Location: ../index.php');
            exit();
        }

        $accountStmt = $conn->prepare('SELECT status, account_locked_at FROM users WHERE id = ? LIMIT 1');
        $accountStmt->bind_param('i', $_SESSION['user_id']);
        $accountStmt->execute();
        $account = $accountStmt->get_result()->fetch_assoc();
        $accountStmt->close();
        if (!$account || strcasecmp((string) ($account['status'] ?? ''), 'Active') !== 0 || !empty($account['account_locked_at'])) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
            if ($isApiRequest) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Account is inactive or locked.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                exit();
            }
            header('Location: ../index.php');
            exit();
        }
        $timeoutResult = $conn->query('SELECT session_timeout_minutes FROM security_settings WHERE id = 1 LIMIT 1');
        $timeoutRow = $timeoutResult ? $timeoutResult->fetch_assoc() : [];
        $timeoutSeconds = max(1, (int) ($timeoutRow['session_timeout_minutes'] ?? 30)) * 60;
        $lastActivity = (int) ($_SESSION['last_activity'] ?? time());
        if (time() - $lastActivity > $timeoutSeconds) {
            $_SESSION = [];
            session_destroy();
            if ($isApiRequest) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Your session expired due to inactivity. Please log in again.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                exit();
            }
            header('Location: ../index.php?expired=1');
            exit();
        }
        $_SESSION['last_activity'] = time();

        // Inject the inactivity monitor only into rendered HTML pages. API and
        // download responses contain no </body> tag and remain untouched.
        if (!$isApiRequest && empty($GLOBALS['auth_inactivity_monitor_started'])) {
            $GLOBALS['auth_inactivity_monitor_started'] = true;
            $scriptPath = (string) ($_SERVER['SCRIPT_NAME'] ?? '/capstone/index.php');
            $baseUrl = rtrim(str_replace('\\', '/', dirname(dirname($scriptPath))), '/');
            $baseUrl = $baseUrl === '' ? '' : $baseUrl;
            $monitorMarkup = '<script>window.SESSION_TIMEOUT_CONFIG=' . json_encode([
                'timeoutSeconds' => $timeoutSeconds,
                'activityUrl' => $baseUrl . '/api/session_activity.php',
                'logoutUrl' => $baseUrl . '/api/logout.php?expired=1',
            ], ( JSON_UNESCAPED_SLASHES) | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>'
                . '<script>window.CSRF_CONFIG=' . json_encode([
                    'token' => security_csrf_token(),
                ], ( JSON_UNESCAPED_SLASHES) | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>'
                . '<script src="' . htmlspecialchars($baseUrl . '/js/csrf.js?v=20260820-1', ENT_QUOTES, 'UTF-8') . '"></script>'
                . '<script src="' . htmlspecialchars($baseUrl . '/js/session_timeout.js?v=20260821-12', ENT_QUOTES, 'UTF-8') . '"></script>';
            ob_start(static function (string $output) use ($monitorMarkup): string {
                return strpos($output, '</body>') !== false
                    ? str_replace('</body>', $monitorMarkup . '</body>', $output)
                    : $output;
            });
        }

        // Roles are managed in separate role tables. Re-read the role on every
        // request so an administrator's change takes effect immediately instead
        // of leaving the affected user on their previous dashboard until logout.
        $role = auth_get_user_role($conn, (int) $_SESSION['user_id']);
        $_SESSION['role'] = $role;

        $supportedRoles = ['Admin', 'Assistant Admin', 'HR', 'Payroll Staff', 'Timekeeper', 'Manager', 'Worker', 'User'];
        if (!in_array($role, $supportedRoles, true)) {
            $role = 'User';
            $_SESSION['role'] = $role;
        }

        if ($role !== 'Admin' && auth_is_maintenance_mode($conn)) {
            if ($isApiRequest) {
                header('Content-Type: application/json');
                http_response_code(503);
                echo json_encode(['success' => false, 'message' => auth_maintenance_message()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                exit();
            }

            auth_render_maintenance_page();
        }

        if ($allowedRoles && !in_array($role, $allowedRoles, true)) {
            if ($isApiRequest) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Your account is not allowed to perform this action.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                exit();
            }

            header('Location: ' . auth_get_access_denied_redirect_path($role));
            exit();
        }

        return $role;
    }
}

if (!function_exists('auth_get_payroll_staff_id')) {
    function auth_get_payroll_staff_id($conn, int $userId): int {
        $stmt = $conn->prepare("SELECT PayrollStaff_ID FROM payrollstaff WHERE UserID = ? LIMIT 1");
        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $payrollStaffId = 0;

        if ($result && $row = $result->fetch_assoc()) {
            $payrollStaffId = (int) ($row['PayrollStaff_ID'] ?? 0);
        }

        $stmt->close();
        return $payrollStaffId;
    }
}

if (!function_exists('auth_payroll_staff_has_site_access')) {
    function auth_payroll_staff_has_site_access($conn, int $userId, int $siteId): bool {
        if ($userId <= 0 || $siteId <= 0) {
            return false;
        }

        $payrollStaffId = auth_get_payroll_staff_id($conn, $userId);
        if ($payrollStaffId <= 0) {
            return false;
        }

        $stmt = $conn->prepare("
            SELECT 1
            FROM payrollstaffassignment
            WHERE PayrollStaff_ID = ? AND SiteID = ?
            LIMIT 1
        ");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ii', $payrollStaffId, $siteId);
        $stmt->execute();
        $hasAccess = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        return $hasAccess;
    }
}

if (!function_exists('auth_get_timekeeper_site_ids')) {
    /**
     * @return int[]
     */
    function auth_get_timekeeper_site_ids($conn, int $userId): array
    {
        if (!function_exists('get_timekeeper_site_ids')) {
            require_once __DIR__ . '/../api/timekeeper_assignment_helpers.php';
        }

        return get_timekeeper_site_ids($conn, $userId);
    }
}
?>
