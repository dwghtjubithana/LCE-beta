-- Patch: enforce unique OAuth identity mapping on users
-- Date: 2026-03-05
-- Safe to re-run.

SET @schema_name = DATABASE();
SET @tbl = 'users';

-- If duplicates exist, keep the oldest row mapped and clear duplicates.
UPDATE `users` u
JOIN (
  SELECT `oauth_provider`, `oauth_subject`, MIN(`id`) AS keep_id
  FROM `users`
  WHERE `oauth_provider` IS NOT NULL
    AND `oauth_subject` IS NOT NULL
    AND `oauth_provider` <> ''
    AND `oauth_subject` <> ''
  GROUP BY `oauth_provider`, `oauth_subject`
  HAVING COUNT(*) > 1
) d
  ON d.`oauth_provider` = u.`oauth_provider`
 AND d.`oauth_subject` = u.`oauth_subject`
SET u.`oauth_provider` = NULL,
    u.`oauth_subject` = NULL
WHERE u.`id` <> d.`keep_id`;

-- Drop old non-unique index if present.
SET @drop_old_idx_sql = IF(
  EXISTS (
    SELECT 1
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA=@schema_name
      AND TABLE_NAME=@tbl
      AND INDEX_NAME='idx_users_oauth_provider_subject'
  ),
  'ALTER TABLE `users` DROP INDEX `idx_users_oauth_provider_subject`;',
  'SELECT 1;'
);
PREPARE stmt FROM @drop_old_idx_sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Create unique composite index if missing.
SET @create_unique_idx_sql = IF(
  EXISTS (
    SELECT 1
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA=@schema_name
      AND TABLE_NAME=@tbl
      AND INDEX_NAME='ux_users_oauth_provider_subject'
      AND NON_UNIQUE=0
  ),
  'SELECT 1;',
  'ALTER TABLE `users` ADD UNIQUE INDEX `ux_users_oauth_provider_subject` (`oauth_provider`, `oauth_subject`);'
);
PREPARE stmt FROM @create_unique_idx_sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
