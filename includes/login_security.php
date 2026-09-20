<?php

if (!function_exists('login_security_ensure_columns')) {
    function login_security_ensure_columns(mysqli $conn): bool
    {
        static $checked = false;
        if ($checked) {
            return true;
        }

        $requiredColumns = [
            'failed_login_attempts' => 'INT UNSIGNED NOT NULL DEFAULT 0',
            'account_locked_at' => 'DATETIME NULL DEFAULT NULL',
            'admin_login_cooldown_until' => 'DATETIME NULL DEFAULT NULL',
            'password_last_set_at' => 'DATETIME NULL DEFAULT NULL',
            'must_change_password' => 'TINYINT(1) NOT NULL DEFAULT 0',
        ];

        foreach ($requiredColumns as $column => $definition) {
            $escapedColumn = $conn->real_escape_string($column);
            $result = $conn->query("SHOW COLUMNS FROM users LIKE '{$escapedColumn}'");
            if (!$result) {
                return false;
            }

            $exists = $result->num_rows > 0;
            $result->free();
            if (!$exists && !$conn->query("ALTER TABLE users ADD COLUMN {$column} {$definition}")) {
                return false;
            }
        }

        $conn->query('UPDATE users SET password_last_set_at = NOW() WHERE password_last_set_at IS NULL AND password IS NOT NULL');

        $checked = true;
        return true;
    }
}

if (!function_exists('login_security_password_is_expired')) {
    function login_security_password_is_expired(mysqli $conn, ?string $lastSetAt): bool
    {
        $result = $conn->query('SELECT password_expiry_days FROM security_settings WHERE id = 1 LIMIT 1');
        $row = $result ? $result->fetch_assoc() : [];
        $expiryDays = max(1, (int) ($row['password_expiry_days'] ?? 90));
        if (!$lastSetAt) return false;
        return strtotime($lastSetAt) <= strtotime("-{$expiryDays} days");
    }
}

if (!function_exists('login_security_max_attempts')) {
    function login_security_max_attempts(mysqli $conn): int
    {
        $result = $conn->query('SELECT max_login_attempts FROM security_settings WHERE id = 1 LIMIT 1');
        $row = $result ? $result->fetch_assoc() : null;
        if ($result) {
            $result->free();
        }

        return max(1, (int) ($row['max_login_attempts'] ?? 5));
    }
}

if (!function_exists('login_security_record_failed_attempt')) {
    function login_security_record_failed_attempt(mysqli $conn, int $userId, int $maxAttempts): array
    {
        $stmt = $conn->prepare(
            'UPDATE users
             SET failed_login_attempts = failed_login_attempts + 1,
                 account_locked_at = CASE
                     WHEN failed_login_attempts + 1 >= ? THEN COALESCE(account_locked_at, NOW())
                     ELSE account_locked_at
                 END
             WHERE id = ?'
        );
        if (!$stmt) {
            return ['attempts' => 0, 'locked' => false];
        }

        $stmt->bind_param('ii', $maxAttempts, $userId);
        $stmt->execute();
        $stmt->close();

        $lookup = $conn->prepare('SELECT failed_login_attempts, account_locked_at FROM users WHERE id = ? LIMIT 1');
        if (!$lookup) {
            return ['attempts' => 0, 'locked' => false];
        }

        $lookup->bind_param('i', $userId);
        $lookup->execute();
        $row = $lookup->get_result()->fetch_assoc() ?: [];
        $lookup->close();

        return [
            'attempts' => (int) ($row['failed_login_attempts'] ?? 0),
            'locked' => !empty($row['account_locked_at']),
        ];
    }
}

if (!function_exists('login_security_record_admin_failed_attempt')) {
    function login_security_record_admin_failed_attempt(mysqli $conn, int $userId, int $maxAttempts): array
    {
        $stmt = $conn->prepare(
            'UPDATE users
             SET failed_login_attempts = failed_login_attempts + 1,
                 account_locked_at = NULL,
                 admin_login_cooldown_until = CASE
                     WHEN failed_login_attempts + 1 >= ? THEN DATE_ADD(NOW(), INTERVAL 5 MINUTE)
                     ELSE NULL
                 END
             WHERE id = ?'
        );
        if (!$stmt) return ['attempts' => 0, 'cooldown_until' => null];
        $stmt->bind_param('ii', $maxAttempts, $userId);
        $stmt->execute();
        $stmt->close();

        $lookup = $conn->prepare('SELECT failed_login_attempts, admin_login_cooldown_until FROM users WHERE id = ? LIMIT 1');
        if (!$lookup) return ['attempts' => 0, 'cooldown_until' => null];
        $lookup->bind_param('i', $userId);
        $lookup->execute();
        $row = $lookup->get_result()->fetch_assoc() ?: [];
        $lookup->close();
        return [
            'attempts' => (int) ($row['failed_login_attempts'] ?? 0),
            'cooldown_until' => $row['admin_login_cooldown_until'] ?? null,
        ];
    }
}

if (!function_exists('login_security_reset_attempts')) {
    function login_security_reset_attempts(mysqli $conn, int $userId, bool $updateLastLogin = false): void
    {
        $sql = $updateLastLogin
            ? 'UPDATE users SET failed_login_attempts = 0, account_locked_at = NULL, admin_login_cooldown_until = NULL, last_login = NOW() WHERE id = ?'
            : 'UPDATE users SET failed_login_attempts = 0, account_locked_at = NULL, admin_login_cooldown_until = NULL WHERE id = ?';
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();
        }
    }
}
