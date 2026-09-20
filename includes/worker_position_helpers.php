<?php
if (!function_exists('worker_position_ensure_column')) {
    function worker_position_ensure_column(mysqli $conn): bool
    {
        static $ready = false;
        if ($ready) return true;
        $result = $conn->query("SHOW COLUMNS FROM worker LIKE 'Position'");
        if (!$result) return false;
        $exists = $result->num_rows > 0;
        $result->free();
        if (!$exists && !$conn->query("ALTER TABLE worker ADD COLUMN Position VARCHAR(100) NULL AFTER Last_Name")) return false;
        return $ready = true;
    }
}
