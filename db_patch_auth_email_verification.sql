-- Patch: auth email verification fields on users
-- Date: 2026-03-05
-- Safe to re-run.

SET @schema_name = DATABASE();
SET @tbl = 'users';

SET @sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='email_verified_at'
  ),
  'SELECT 1;',
  "ALTER TABLE `users` ADD COLUMN `email_verified_at` TIMESTAMP NULL DEFAULT NULL AFTER `email`;"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='email_verification_token'
  ),
  'SELECT 1;',
  "ALTER TABLE `users` ADD COLUMN `email_verification_token` VARCHAR(128) NULL AFTER `email_verified_at`;"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='email_verification_sent_at'
  ),
  'SELECT 1;',
  "ALTER TABLE `users` ADD COLUMN `email_verification_sent_at` TIMESTAMP NULL DEFAULT NULL AFTER `email_verification_token`;"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='email_verification_expires_at'
  ),
  'SELECT 1;',
  "ALTER TABLE `users` ADD COLUMN `email_verification_expires_at` TIMESTAMP NULL DEFAULT NULL AFTER `email_verification_sent_at`;"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND INDEX_NAME='idx_users_email_verification_token'
  ),
  'SELECT 1;',
  'CREATE INDEX `idx_users_email_verification_token` ON `users` (`email_verification_token`);'
);
PREPARE stmt FROM @idx_sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

