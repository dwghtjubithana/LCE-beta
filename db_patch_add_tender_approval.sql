-- Patch: add tender approval fields
-- Target: MySQL 8.x

SET @schema_name = DATABASE();
SET @tbl = 'tenders';

-- status
SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='status'),
  'SELECT 1;',
  "ALTER TABLE `tenders` ADD COLUMN `status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'APPROVED' AFTER `is_direct_work`;"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- submitted_by_user_id
SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='submitted_by_user_id'),
  'SELECT 1;',
  'ALTER TABLE `tenders` ADD COLUMN `submitted_by_user_id` bigint unsigned NULL AFTER `status`;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- submitted_at
SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='submitted_at'),
  'SELECT 1;',
  'ALTER TABLE `tenders` ADD COLUMN `submitted_at` timestamp NULL DEFAULT NULL AFTER `submitted_by_user_id`;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- approved_by_user_id
SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='approved_by_user_id'),
  'SELECT 1;',
  'ALTER TABLE `tenders` ADD COLUMN `approved_by_user_id` bigint unsigned NULL AFTER `submitted_at`;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- approved_at
SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='approved_at'),
  'SELECT 1;',
  'ALTER TABLE `tenders` ADD COLUMN `approved_at` timestamp NULL DEFAULT NULL AFTER `approved_by_user_id`;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Indexes
SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND INDEX_NAME='tenders_status_index'),
  'SELECT 1;',
  'ALTER TABLE `tenders` ADD KEY `tenders_status_index` (`status`);'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND INDEX_NAME='tenders_submitted_by_user_id_index'),
  'SELECT 1;',
  'ALTER TABLE `tenders` ADD KEY `tenders_submitted_by_user_id_index` (`submitted_by_user_id`);'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Backfill status for existing rows
UPDATE `tenders`
SET `status` = 'APPROVED'
WHERE `status` IS NULL;
