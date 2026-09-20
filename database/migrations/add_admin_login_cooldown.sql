-- Admins are never manually locked. Excess failed attempts use this temporary
-- cooldown, while account_locked_at remains available for other roles.
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `admin_login_cooldown_until` DATETIME NULL DEFAULT NULL
  AFTER `account_locked_at`;

UPDATE `users` u
INNER JOIN `admin` a ON a.UserID = u.id
SET u.admin_login_cooldown_until = DATE_ADD(NOW(), INTERVAL 5 MINUTE),
    u.account_locked_at = NULL
WHERE u.account_locked_at IS NOT NULL;
