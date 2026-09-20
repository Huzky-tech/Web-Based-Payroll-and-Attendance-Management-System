<?php

function ensure_user_profile_photo_column(mysqli $conn): bool
{
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_photo'");
    if ($result && $result->num_rows > 0) {
        return true;
    }

    return (bool) $conn->query("ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) NULL AFTER full_name");
}

function user_profile_photo_url(?string $path): string
{
    $path = trim((string) $path);
    return $path !== '' ? '../' . ltrim($path, '/\\') : '';
}
