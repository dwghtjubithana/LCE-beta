-- Patch: plan catalog for admin-managed plans
-- Date: 2026-03-05
-- Safe to re-run.

CREATE TABLE IF NOT EXISTS `plan_catalog` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_key` VARCHAR(40) NOT NULL,
  `plan_label` VARCHAR(120) NOT NULL,
  `description` VARCHAR(255) NULL,
  `rank` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `available_for_signup` TINYINT(1) NOT NULL DEFAULT 1,
  `available_for_upgrade` TINYINT(1) NOT NULL DEFAULT 1,
  `requires_payment_proof` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_plan_catalog_plan_key` (`plan_key`),
  KEY `idx_plan_catalog_active_rank` (`is_active`, `rank`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `plan_catalog`
(`plan_key`, `plan_label`, `description`, `rank`, `is_active`, `is_default`, `available_for_signup`, `available_for_upgrade`, `requires_payment_proof`)
VALUES
('FREE', 'Free', 'Instapplan', 10, 1, 1, 1, 0, 0),
('PRO', 'Premium', 'Snelle upgrade met extra checks', 20, 1, 0, 1, 1, 1),
('BUSINESS', 'Business', 'Zakelijke verificatie en compliance', 30, 1, 0, 1, 1, 1),
('ENTERPRISE', 'Enterprise', 'Volledige enterprise readiness', 40, 1, 0, 0, 1, 1)
ON DUPLICATE KEY UPDATE
  `plan_label` = VALUES(`plan_label`),
  `description` = VALUES(`description`),
  `rank` = VALUES(`rank`),
  `is_active` = VALUES(`is_active`),
  `available_for_signup` = VALUES(`available_for_signup`),
  `available_for_upgrade` = VALUES(`available_for_upgrade`),
  `requires_payment_proof` = VALUES(`requires_payment_proof`),
  `updated_at` = CURRENT_TIMESTAMP;

UPDATE `plan_catalog`
SET `is_default` = CASE WHEN `plan_key` = 'FREE' THEN 1 ELSE 0 END
WHERE `plan_key` IN ('FREE', 'PRO', 'BUSINESS', 'ENTERPRISE');
