<?php

if (!function_exists('site_priority_ensure_column')) {
    function site_priority_ensure_column(mysqli $conn): bool
    {
        static $ready = false;
        if ($ready) return true;

        $result = $conn->query("SHOW COLUMNS FROM projectsite LIKE 'Is_Priority'");
        if (!$result) return false;
        $exists = $result->num_rows > 0;
        $result->free();

        if (!$exists && !$conn->query('ALTER TABLE projectsite ADD COLUMN Is_Priority TINYINT(1) NOT NULL DEFAULT 0')) {
            return false;
        }

        $ready = true;
        return true;
    }
}

if (!function_exists('site_priority_ensure_user_table')) {
    function site_priority_ensure_user_table(mysqli $conn): bool
    {
        static $ready = false;
        if ($ready) return true;

        $sql = "CREATE TABLE IF NOT EXISTS user_site_priorities (
            UserID INT NOT NULL,
            SiteID INT NOT NULL,
            Created_At TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (UserID, SiteID),
            INDEX idx_user_site_priority_site (SiteID)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $ready = (bool) $conn->query($sql);
        return $ready;
    }
}
