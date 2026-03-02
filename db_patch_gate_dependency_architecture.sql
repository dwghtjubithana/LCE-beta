-- Patch: Blocking Dependency Architecture (Gate 1 / Gate 2) + 3-tier monetization
-- Date: 2026-02-16
-- Safe to re-run.

SET @schema_name = DATABASE();

-- 1) companies: verification_status + compliance_gate_passed
SET @tbl = 'companies';
SET @sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='verification_status'
  ),
  "ALTER TABLE `companies` MODIFY COLUMN `verification_status` enum('UNVERIFIED','VERIFIED_ENTITY','OFFSHORE_READY') NOT NULL DEFAULT 'UNVERIFIED';",
  "ALTER TABLE `companies` ADD COLUMN `verification_status` enum('UNVERIFIED','VERIFIED_ENTITY','OFFSHORE_READY') NOT NULL DEFAULT 'UNVERIFIED' AFTER `verification_level`;"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='compliance_gate_passed'
  ),
  'SELECT 1;',
  "ALTER TABLE `companies` ADD COLUMN `compliance_gate_passed` tinyint(1) NOT NULL DEFAULT 0 AFTER `verification_status`;"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Backfill legacy verification values if present (GRAY/GOLD)
UPDATE `companies`
SET `verification_status` = CASE
  WHEN `verification_status` IN ('GOLD', 'VERIFIED_ENTITY', 'OFFSHORE_READY') THEN 'VERIFIED_ENTITY'
  ELSE 'UNVERIFIED'
END;

UPDATE `companies`
SET `compliance_gate_passed` = CASE
  WHEN `verification_status` IN ('VERIFIED_ENTITY', 'OFFSHORE_READY') THEN 1
  ELSE 0
END;

-- 2) documents: is_baseline
SET @tbl = 'documents';
SET @sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='is_baseline'
  ),
  'SELECT 1;',
  "ALTER TABLE `documents` ADD COLUMN `is_baseline` tinyint(1) NOT NULL DEFAULT 0 AFTER `category_selected`;"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE `documents`
SET `is_baseline` = CASE
  WHEN UPPER(REPLACE(`category_selected`, ' ', '_')) IN ('KKF_UITTREKSEL','CRIB','UBO') THEN 1
  ELSE 0
END;

-- 3) users: extend plan enum with ENTERPRISE (retain PRO for backward compatibility)
SET @tbl = 'users';
SET @sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='plan'
  ),
  "ALTER TABLE `users` MODIFY COLUMN `plan` enum('FREE','BUSINESS','ENTERPRISE','PRO') NOT NULL DEFAULT 'FREE';",
  'SELECT 1;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Optional normalization: move legacy PRO to BUSINESS
UPDATE `users`
SET `plan` = 'BUSINESS'
WHERE `plan` = 'PRO';

-- 4) payment_proofs: target_level for admin approval choice
SET @tbl = 'payment_proofs';
SET @sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='target_level'
  ),
  'SELECT 1;',
  "ALTER TABLE `payment_proofs` ADD COLUMN `target_level` enum('BUSINESS','ENTERPRISE') NOT NULL DEFAULT 'BUSINESS' AFTER `status`;"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

