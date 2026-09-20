<?php
declare(strict_types=1);

const REMEMBER_LOGIN_COOKIE = 'capstone_remember';
const REMEMBER_LOGIN_DAYS = 30;

function remember_login_cookie_options(int $expires): array {
    $https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
    return [
        'expires' => $expires,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function remember_login_ensure_table(mysqli $conn): bool {
    return (bool) $conn->query("CREATE TABLE IF NOT EXISTS remember_login_tokens (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT NOT NULL,
        selector CHAR(24) NOT NULL,
        token_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_remember_selector (selector),
        KEY idx_remember_user (user_id),
        KEY idx_remember_expiry (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function remember_login_clear_cookie(): void {
    setcookie(REMEMBER_LOGIN_COOKIE, '', remember_login_cookie_options(time() - 3600));
    unset($_COOKIE[REMEMBER_LOGIN_COOKIE]);
}

function remember_login_revoke_current(mysqli $conn): void {
    $parts = explode(':', (string) ($_COOKIE[REMEMBER_LOGIN_COOKIE] ?? ''), 2);
    if (count($parts) === 2 && preg_match('/^[a-f0-9]{24}$/', $parts[0])) {
        $stmt = $conn->prepare('DELETE FROM remember_login_tokens WHERE selector = ?');
        if ($stmt) {
            $stmt->bind_param('s', $parts[0]);
            $stmt->execute();
            $stmt->close();
        }
    }
    remember_login_clear_cookie();
}

function remember_login_issue(mysqli $conn, int $userId): void {
    remember_login_revoke_current($conn);
    $selector = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $validator);
    $expires = time() + (REMEMBER_LOGIN_DAYS * 86400);
    $expiresAt = date('Y-m-d H:i:s', $expires);
    $stmt = $conn->prepare('INSERT INTO remember_login_tokens (user_id, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)');
    if (!$stmt) return;
    $stmt->bind_param('isss', $userId, $selector, $tokenHash, $expiresAt);
    if ($stmt->execute()) {
        setcookie(REMEMBER_LOGIN_COOKIE, $selector . ':' . $validator, remember_login_cookie_options($expires));
        $_COOKIE[REMEMBER_LOGIN_COOKIE] = $selector . ':' . $validator;
    }
    $stmt->close();
}

function remember_login_user(mysqli $conn): ?array {
    $parts = explode(':', (string) ($_COOKIE[REMEMBER_LOGIN_COOKIE] ?? ''), 2);
    if (count($parts) !== 2 || !preg_match('/^[a-f0-9]{24}$/', $parts[0]) || !preg_match('/^[a-f0-9]{64}$/', $parts[1])) {
        if (!empty($_COOKIE[REMEMBER_LOGIN_COOKIE])) remember_login_clear_cookie();
        return null;
    }
    [$selector, $validator] = $parts;
    $stmt = $conn->prepare("SELECT t.user_id, t.token_hash, u.full_name, u.email, u.status
        FROM remember_login_tokens t INNER JOIN users u ON u.id = t.user_id
        WHERE t.selector = ? AND t.expires_at > NOW() LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param('s', $selector);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row || !hash_equals((string) $row['token_hash'], hash('sha256', $validator)) || strcasecmp((string) $row['status'], 'Active') !== 0) {
        remember_login_revoke_current($conn);
        return null;
    }
    return $row;
}
