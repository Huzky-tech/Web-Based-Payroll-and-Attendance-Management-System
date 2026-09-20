<?php
if (!function_exists('manager_role_ensure_table')) {
    function manager_role_ensure_table(mysqli $conn): bool
    {
        static $ready = false;
        if ($ready) return true;

        $sql = "CREATE TABLE IF NOT EXISTS managers (
            ManagerID INT NOT NULL AUTO_INCREMENT,
            UserID INT NOT NULL,
            PRIMARY KEY (ManagerID),
            UNIQUE KEY uq_managers_user (UserID),
            CONSTRAINT fk_managers_user
                FOREIGN KEY (UserID) REFERENCES users (id)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        return $ready = (bool) $conn->query($sql);
    }
}
