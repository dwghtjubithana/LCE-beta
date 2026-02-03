-- Patch: initial dbmyu6uoo7735j.sql -> current Sprint 1 schema
-- Target: MySQL 8.x

-- Users table: add new columns used by Laravel app (idempotent)
SET @schema_name = DATABASE();
SET @tbl = 'users';

SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='uuid'),
  'SELECT 1;',
  'ALTER TABLE `users` ADD COLUMN `uuid` char(36) NULL AFTER `id`;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='phone'),
  'SELECT 1;',
  'ALTER TABLE `users` ADD COLUMN `phone` varchar(30) NULL AFTER `email`;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='app_role'),
  'SELECT 1;',
  "ALTER TABLE `users` ADD COLUMN `app_role` varchar(50) NOT NULL DEFAULT 'user' AFTER `role`;"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='plan'),
  'SELECT 1;',
  "ALTER TABLE `users` ADD COLUMN `plan` enum('FREE','PRO','BUSINESS') NOT NULL DEFAULT 'FREE' AFTER `app_role`;"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='plan_status'),
  'SELECT 1;',
  "ALTER TABLE `users` ADD COLUMN `plan_status` enum('ACTIVE','PENDING_PAYMENT','EXPIRED') NOT NULL DEFAULT 'ACTIVE' AFTER `plan`;"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='updated_at'),
  'SELECT 1;',
  'ALTER TABLE `users` ADD COLUMN `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Uniques for new identity columns
SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND INDEX_NAME='users_uuid_unique'),
  'SELECT 1;',
  'ALTER TABLE `users` ADD UNIQUE KEY `users_uuid_unique` (`uuid`);'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND INDEX_NAME='users_email_unique'),
  'SELECT 1;',
  'ALTER TABLE `users` ADD UNIQUE KEY `users_email_unique` (`email`);'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND INDEX_NAME='users_phone_unique'),
  'SELECT 1;',
  'ALTER TABLE `users` ADD UNIQUE KEY `users_phone_unique` (`phone`);'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Sessions table (Laravel)
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Companies
CREATE TABLE IF NOT EXISTS `companies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `owner_user_id` bigint unsigned NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `sector` varchar(255) NOT NULL,
  `experience` text,
  `contact` json DEFAULT NULL,
  `bluewave_status` tinyint(1) NOT NULL DEFAULT 0,
  `current_score` tinyint unsigned NOT NULL DEFAULT 0,
  `verification_level` enum('unverified','email_verified','physical_verified') NOT NULL DEFAULT 'unverified',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `companies_uuid_unique` (`uuid`),
  KEY `companies_owner_user_id_index` (`owner_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Documents
CREATE TABLE IF NOT EXISTS `documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `category_selected` varchar(255) NOT NULL,
  `detected_type` varchar(255) DEFAULT NULL,
  `status` enum('MISSING','PROCESSING','VALID','INVALID','EXPIRED','EXPIRING_SOON','MANUAL_REVIEW','NEEDS_CONFIRMATION') NOT NULL DEFAULT 'PROCESSING',
  `extracted_data` json DEFAULT NULL,
  `expiry_date` timestamp NULL DEFAULT NULL,
  `ai_feedback` text,
  `source_file_url` varchar(255) DEFAULT NULL,
  `file_hash_sha256` varchar(64) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `file_size` bigint unsigned DEFAULT NULL,
  `ocr_confidence` decimal(5,2) DEFAULT NULL,
  `ai_confidence` decimal(5,2) DEFAULT NULL,
  `summary_file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `documents_uuid_unique` (`uuid`),
  UNIQUE KEY `documents_company_filehash_unique` (`company_id`, `file_hash_sha256`),
  KEY `documents_company_id_index` (`company_id`),
  KEY `documents_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Compliance rules
CREATE TABLE IF NOT EXISTS `compliance_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `document_type` varchar(255) NOT NULL,
  `sector_applicability` json DEFAULT NULL,
  `required_keywords` json DEFAULT NULL,
  `max_age_months` smallint unsigned DEFAULT NULL,
  `constraints` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `compliance_rules_document_type_index` (`document_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned DEFAULT NULL,
  `document_id` bigint unsigned DEFAULT NULL,
  `type` enum('EXPIRING_SOON') NOT NULL DEFAULT 'EXPIRING_SOON',
  `channel` enum('email','push') NOT NULL DEFAULT 'email',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_index` (`user_id`),
  KEY `notifications_company_id_index` (`company_id`),
  KEY `notifications_document_id_index` (`document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Audit logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `actor_user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `target_type` varchar(255) DEFAULT NULL,
  `target_id` bigint unsigned DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Cache tables
CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Queue tables
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tenders table enhancements (legacy table already exists)
SET @tbl = 'tenders';

SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='title'),
  'SELECT 1;',
  'ALTER TABLE `tenders` ADD COLUMN `title` varchar(255) DEFAULT NULL AFTER `id`;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='date'),
  'SELECT 1;',
  'ALTER TABLE `tenders` ADD COLUMN `date` date DEFAULT NULL AFTER `title`;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='details_url'),
  'SELECT 1;',
  'ALTER TABLE `tenders` ADD COLUMN `details_url` varchar(255) DEFAULT NULL AFTER `client`;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='attachments'),
  'SELECT 1;',
  'ALTER TABLE `tenders` ADD COLUMN `attachments` json DEFAULT NULL AFTER `details_url`;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='description'),
  'SELECT 1;',
  'ALTER TABLE `tenders` ADD COLUMN `description` text DEFAULT NULL AFTER `attachments`;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='updated_at'),
  'SELECT 1;',
  'ALTER TABLE `tenders` ADD COLUMN `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Make legacy required project column nullable to avoid insert failures
SET @sql = IF(
  EXISTS (
    SELECT 1
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='project'
      AND IS_NULLABLE='NO'
  ),
  'ALTER TABLE `tenders` MODIFY COLUMN `project` varchar(255) NULL;',
  'SELECT 1;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Admin user insert
INSERT INTO `users` (
  `uuid`, `username`, `password_hash`, `email`, `phone`, `role`, `app_role`, `plan`, `plan_status`, `status`, `created_at`, `updated_at`
)
SELECT
  '85163742-788C-41FD-BB11-93AD7F748473',
  'dwight_admin',
  '$2y$12$d9/KRpdkwvoPz1QWkD.tDuXey53frl80JGMrR8DX5f/ffahI0UWLO',
  'dwightjubi@gmail.com',
  NULL,
  'STAFF',
  'admin',
  'BUSINESS',
  'ACTIVE',
  'ACTIVE',
  '2026-01-26 00:00:00',
  '2026-01-26 00:00:00'
WHERE NOT EXISTS (
  SELECT 1 FROM `users` WHERE `email` = 'dwightjubi@gmail.com'
);

-- Ensure admin user stays in admin role if already present
UPDATE `users`
SET
  `uuid` = '85163742-788C-41FD-BB11-93AD7F748473',
  `username` = 'dwight_admin',
  `role` = 'STAFF',
  `app_role` = 'admin',
  `plan` = 'BUSINESS',
  `plan_status` = 'ACTIVE',
  `status` = 'ACTIVE'
WHERE `email` = 'dwightjubi@gmail.com';

-- Bundle patch: Direct Werk + Public Profile + Payment Proofs (2026-02-03)

-- Tenders: is_direct_work flag
SET @tbl = 'tenders';
SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='is_direct_work'),
  'SELECT 1;',
  'ALTER TABLE `tenders` ADD COLUMN `is_direct_work` tinyint(1) NOT NULL DEFAULT 0 AFTER `description`;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Backfill direct work based on keywords (best-effort)
UPDATE `tenders`
SET `is_direct_work` = 1
WHERE LOWER(`title`) LIKE '%direct werk%'
   OR LOWER(`project`) LIKE '%direct werk%'
   OR LOWER(`title`) LIKE '%lassers%'
   OR LOWER(`description`) LIKE '%direct werk%'
   OR LOWER(`description`) LIKE '%lassers%';

-- Companies: public profile fields
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

-- Unique index for public slug
SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND INDEX_NAME='companies_public_slug_unique'),
  'SELECT 1;',
  'ALTER TABLE `companies` ADD UNIQUE KEY `companies_public_slug_unique` (`public_slug`);'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Payment proofs table
SET @tbl = 'payment_proofs';
SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl),
  'SELECT 1;',
  'CREATE TABLE `payment_proofs` (
     `id` bigint unsigned NOT NULL AUTO_INCREMENT,
     `user_id` bigint unsigned NOT NULL,
     `company_id` bigint unsigned DEFAULT NULL,
     `file_path` varchar(255) NOT NULL,
     `status` varchar(30) NOT NULL DEFAULT ''PENDING'',
     `notes` text DEFAULT NULL,
     `reviewed_by` bigint unsigned DEFAULT NULL,
     `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
     `reviewed_at` timestamp NULL DEFAULT NULL,
     `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
     `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     PRIMARY KEY (`id`),
     KEY `payment_proofs_user_id_index` (`user_id`),
     KEY `payment_proofs_company_id_index` (`company_id`),
     KEY `payment_proofs_status_index` (`status`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
