<?php

/**
 * Creates one private notification for every active Admin after a user changes
 * their own password. Password values are never stored or included.
 */
if (!function_exists('notify_admins_of_password_change')) {
    function notify_admins_of_password_change(mysqli $conn, int $changedUserId, string $changedUserName, string $changeType): void
    {
        if ($changedUserId <= 0) {
            return;
        }

        $conn->query("CREATE TABLE IF NOT EXISTS admin_notifications (
            NotificationID INT AUTO_INCREMENT PRIMARY KEY,
            RecipientUserID INT DEFAULT NULL,
            NotificationType VARCHAR(50) NOT NULL,
            ReferenceID INT DEFAULT NULL,
            Title VARCHAR(255) NOT NULL,
            Message TEXT NOT NULL,
            IsRead TINYINT(1) NOT NULL DEFAULT 0,
            CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_admin_notifications_read (IsRead)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $recipientColumn = $conn->query("SHOW COLUMNS FROM admin_notifications LIKE 'RecipientUserID'");
        if ($recipientColumn && $recipientColumn->num_rows === 0) {
            $conn->query('ALTER TABLE admin_notifications ADD COLUMN RecipientUserID INT NULL AFTER NotificationID');
        }

        $name = trim($changedUserName) ?: ('User #' . $changedUserId);
        $title = 'Password changed';
        $message = $name . ' changed their password' . ($changeType !== '' ? ' (' . $changeType . ')' : '') . '.';
        $type = 'Password Changed';

        $admins = $conn->query("SELECT a.UserID FROM admin a INNER JOIN users u ON u.id = a.UserID WHERE COALESCE(u.status, 'Active') = 'Active'");
        if (!$admins) {
            return;
        }

        $insert = $conn->prepare('INSERT INTO admin_notifications (RecipientUserID, NotificationType, ReferenceID, Title, Message, IsRead, CreatedAt) VALUES (?, ?, ?, ?, ?, 0, NOW())');
        if (!$insert) {
            return;
        }

        while ($admin = $admins->fetch_assoc()) {
            $recipientId = (int) ($admin['UserID'] ?? 0);
            if ($recipientId <= 0) {
                continue;
            }
            $insert->bind_param('isiss', $recipientId, $type, $changedUserId, $title, $message);
            $insert->execute();
        }
        $insert->close();
    }
}
