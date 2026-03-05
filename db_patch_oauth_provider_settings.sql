-- Patch: admin-managed OAuth provider settings (Google + Microsoft)
-- Date: 2026-03-05
-- Safe to re-run.

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
('auth_google_enabled', '0'),
('auth_google_client_id', ''),
('auth_google_client_secret', ''),
('auth_google_redirect_uri', ''),
('auth_google_prompt', 'select_account'),
('auth_microsoft_enabled', '0'),
('auth_microsoft_client_id', ''),
('auth_microsoft_client_secret', ''),
('auth_microsoft_redirect_uri', ''),
('auth_microsoft_tenant', 'common');

