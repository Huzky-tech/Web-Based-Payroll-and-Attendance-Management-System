<?php
require_once __DIR__ . '/login_security.php';

function password_policy_settings(mysqli $conn): array
{
    $defaults = [
        'min_password_length' => 8,
        'require_special_char' => 1,
        'require_number' => 1,
        'require_uppercase' => 1,
    ];
    $result = $conn->query('SELECT min_password_length, require_special_char, require_number, require_uppercase FROM security_settings WHERE id = 1 LIMIT 1');
    $settings = $result ? $result->fetch_assoc() : [];
    return array_merge($defaults, $settings ?: []);
}

function password_policy_validate(mysqli $conn, string $password): ?string
{
    $settings = password_policy_settings($conn);
    $minimum = max(1, (int) $settings['min_password_length']);
    if (strlen($password) < $minimum) return "Password must be at least {$minimum} characters long.";
    if ((int) $settings['require_special_char'] === 1 && !preg_match('/[^A-Za-z0-9]/', $password)) return 'Password must include a special character.';
    if ((int) $settings['require_number'] === 1 && !preg_match('/\d/', $password)) return 'Password must include a number.';
    if ((int) $settings['require_uppercase'] === 1 && !preg_match('/[A-Z]/', $password)) return 'Password must include an uppercase letter.';
    return null;
}
