-- Tracks incorrect password attempts and permanently locks an account at the configured limit.
ALTER TABLE `users`
  ADD COLUMN `failed_login_attempts` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `last_login`,
  ADD COLUMN `account_locked_at` DATETIME NULL DEFAULT NULL AFTER `failed_login_attempts`;
