-- Patch: add document_files table for multi-side uploads (ID bewijs)
-- Target: MySQL 8.x

SET @schema_name = DATABASE();
SET @tbl = 'document_files';

SET @sql = IF(
  EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl),
  'SELECT 1;',
  'CREATE TABLE `document_files` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `document_id` bigint unsigned NOT NULL,
    `side` enum(''FRONT'',''BACK'') NOT NULL DEFAULT ''FRONT'',
    `file_path` varchar(255) NOT NULL,
    `original_filename` varchar(255) DEFAULT NULL,
    `mime_type` varchar(100) DEFAULT NULL,
    `file_size` bigint unsigned DEFAULT NULL,
    `file_hash_sha256` varchar(64) DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `document_files_document_side_unique` (`document_id`,`side`),
    KEY `document_files_document_id_index` (`document_id`),
    KEY `document_files_hash_index` (`file_hash_sha256`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
