-- Patch: admin-managed email settings, templates, and logs
-- Date: 2026-03-05
-- Safe to re-run.

SET @schema_name = DATABASE();

-- Ensure shared key/value settings table exists.
CREATE TABLE IF NOT EXISTS `app_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(191) NOT NULL,
  `value` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reusable templates for all outgoing email types.
CREATE TABLE IF NOT EXISTS `email_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `template_key` VARCHAR(120) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body` MEDIUMTEXT NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_email_templates_template_key` (`template_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Delivery log for observability/troubleshooting.
CREATE TABLE IF NOT EXISTS `email_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `template_key` VARCHAR(120) NULL,
  `to_email` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `status` ENUM('SENT','FAILED','SKIPPED') NOT NULL DEFAULT 'SENT',
  `error_message` TEXT NULL,
  `meta` JSON NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email_logs_template_key` (`template_key`),
  KEY `idx_email_logs_status` (`status`),
  KEY `idx_email_logs_to_email` (`to_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Baseline email settings (admin can override in UI).
INSERT IGNORE INTO `app_settings` (`key`, `value`) VALUES
('email_enabled', '1'),
('email_mailer', 'smtp'),
('email_smtp_host', ''),
('email_smtp_port', '587'),
('email_smtp_encryption', 'tls'),
('email_smtp_username', ''),
('email_smtp_password', ''),
('email_from_name', 'Wapcore LCE'),
('email_from_address', 'noreply@example.com'),
('email_reply_to_name', ''),
('email_reply_to_address', ''),
('email_verification_link_base_url', ''),
('email_verification_token_ttl_minutes', '1440'),
('email_send_welcome', '1'),
('email_send_verification', '1'),
('email_send_notifications', '1'),
('payment_bank_name', 'TCB'),
('payment_bank_account', '12.34.56.789'),
('payment_bank_account_name', 'Wapcomtek NV');

-- Baseline templates.
INSERT IGNORE INTO `email_templates` (`template_key`, `name`, `subject`, `body`, `is_active`) VALUES
('welcome', 'Welcome', 'Welkom bij Wapcore LCE', 'Hallo {{name}},\n\nWelkom bij Wapcore LCE.\n\nJe account is aangemaakt en je kunt nu inloggen.\n\nGroet,\n{{app_name}}', 1),
('email_verification', 'Email Verification', 'Verifieer je e-mailadres', 'Hallo {{name}},\n\nKlik op deze link om je e-mailadres te verifiëren:\n{{verification_link}}\n\nDeze link verloopt over {{ttl_minutes}} minuten.\n\nGroet,\n{{app_name}}', 1),
('expiring_soon', 'Document Expiring Soon', 'Document verloopt binnenkort', 'Hallo {{name}},\n\nEen document verloopt binnenkort.\nType: {{document_type}}\nControleer je dashboard om dit bij te werken.\n\nGroet,\n{{app_name}}', 1),
('test_email', 'Test Email', 'Test e-mail vanaf Wapcore LCE', 'Dit is een testmail om je e-mailinstellingen te valideren.\n\nTijdstip: {{timestamp}}\n\nGroet,\n{{app_name}}', 1);
