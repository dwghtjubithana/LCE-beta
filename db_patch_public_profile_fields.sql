-- Patch: add public profile fields to companies
-- Target: MySQL 8.x

SET @schema_name = DATABASE();
SET @tbl = 'companies';

SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='public_slug'),
  'SELECT 1;',
  'ALTER TABLE `companies` ADD COLUMN `public_slug` varchar(120) NULL AFTER `company_name`;' 
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='display_name'),
  'SELECT 1;',
  'ALTER TABLE `companies` ADD COLUMN `display_name` varchar(160) NULL AFTER `public_slug`;' 
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='profile_photo_path'),
  'SELECT 1;',
  'ALTER TABLE `companies` ADD COLUMN `profile_photo_path` varchar(255) NULL AFTER `display_name`;' 
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='address'),
  'SELECT 1;',
  'ALTER TABLE `companies` ADD COLUMN `address` varchar(255) NULL AFTER `profile_photo_path`;' 
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='lat'),
  'SELECT 1;',
  'ALTER TABLE `companies` ADD COLUMN `lat` decimal(10,7) NULL AFTER `address`;' 
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='lng'),
  'SELECT 1;',
  'ALTER TABLE `companies` ADD COLUMN `lng` decimal(10,7) NULL AFTER `lat`;' 
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='verification_status'),
  'SELECT 1;',
  "ALTER TABLE `companies` ADD COLUMN `verification_status` varchar(20) NOT NULL DEFAULT 'GRAY' AFTER `lng`;" 
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND INDEX_NAME='companies_public_slug_unique'),
  'SELECT 1;',
  'ALTER TABLE `companies` ADD UNIQUE KEY `companies_public_slug_unique` (`public_slug`);'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
