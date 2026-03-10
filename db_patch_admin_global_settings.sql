-- Patch: global admin runtime settings (AI safety + operational controls)
-- Date: 2026-03-10
-- Safe to re-run.

SET @schema_name = DATABASE();

CREATE TABLE IF NOT EXISTS `app_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(191) NOT NULL,
  `value` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `app_settings` (`key`, `value`) VALUES
('ai_validation_retry_count', '1'),
('gemini_timeout_seconds', '60'),
('ai_include_internal_debug_paths', '0'),
('ai_expose_debug_meta_to_user', '0'),
('upload_malware_scan_mode', 'OFF'),
('upload_malware_scan_timeout_seconds', '20'),
('upload_malware_scan_binary', 'clamscan'),
('upload_malware_scan_block_on_error', '0');
