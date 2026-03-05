-- Patch: users OAuth identity fields
-- Date: 2026-03-05
-- Safe to re-run.

SET @schema_name = DATABASE();
SET @tbl = 'users';

SET @sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='oauth_provider'
  ),
  'SELECT 1;',
  "ALTER TABLE `users` ADD COLUMN `oauth_provider` VARCHAR(30) NULL AFTER `phone`;"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='oauth_subject'
  ),
  'SELECT 1;',
  "ALTER TABLE `users` ADD COLUMN `oauth_subject` VARCHAR(191) NULL AFTER `oauth_provider`;"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND INDEX_NAME='idx_users_oauth_provider_subject'
  ),
  'SELECT 1;',
  'CREATE INDEX `idx_users_oauth_provider_subject` ON `users` (`oauth_provider`, `oauth_subject`);'
);
PREPARE stmt FROM @idx_sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

