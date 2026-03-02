-- Patch: Company type requirements for Bedrijfsvergunning
-- Date: 2026-02-16
-- Safe to re-run.

SET @schema_name = DATABASE();

-- 1) Lookup table for company types and whether a bedrijfsvergunning is mandatory
CREATE TABLE IF NOT EXISTS `company_type_requirements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_type_key` VARCHAR(120) NOT NULL,
  `company_type_label` VARCHAR(255) NOT NULL,
  `requires_bedrijfsvergunning` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_company_type_key` (`company_type_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Optional link field on companies (so each company can be mapped to a controlled type)
SET @tbl = 'companies';
SET @sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME=@tbl AND COLUMN_NAME='company_type_key'
  ),
  'SELECT 1;',
  "ALTER TABLE `companies` ADD COLUMN `company_type_key` VARCHAR(120) NULL AFTER `sector`;"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA=@schema_name AND TABLE_NAME='companies' AND INDEX_NAME='idx_companies_company_type_key'
  ),
  'SELECT 1;',
  'CREATE INDEX `idx_companies_company_type_key` ON `companies` (`company_type_key`);'
);
PREPARE stmt FROM @idx_sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) Seed known company types that require a bedrijfsvergunning
INSERT INTO `company_type_requirements` (`company_type_key`, `company_type_label`, `requires_bedrijfsvergunning`) VALUES
('bouw_schildersbedrijven', 'Bouw- en schildersbedrijven (aannemers en schilders)', 1),
('detailhandel_retail', 'Detailhandel (retail-winkels)', 1),
('test_keuringsinstanties', 'Operators van test- of keuringsinstanties', 1),
('goud_zilversmeden', 'Goud- en zilversmeden', 1),
('molen_maalbedrijven', 'Molen- en maalbedrijven (millers)', 1),
('slagers', 'Slagers', 1),
('architectenbureaus', 'Architectenbureaus', 1),
('banken', 'Banken', 1),
('wissel_valutadiensten', 'Wissel- en valutadiensten (exchange offices)', 1),
('verzekeringsmaatschappijen', 'Verzekeringsmaatschappijen', 1),
('kappers_schoonheidszalen', 'Kappers- en schoonheidszalen', 1),
('technische_engineeringbedrijven', 'Technische en engineeringbedrijven', 1),
('luchtvaartmaatschappijen', 'Luchtvaartmaatschappijen', 1),
('scheepvaartmaatschappijen', 'Scheepvaartmaatschappijen (shipping)', 1),
('hotels_motels_guesthouses', 'Hotels, motels en guesthouses', 1),
('gasstations_tankstations', 'Gasstations / tankstations (pompinstallaties)', 1),
('spuit_bestrijdingsbedrijven_lucht', 'Spuit- en bestrijdingsbedrijven met vliegtuigen', 1),
('entertainmentbedrijven', 'Entertainmentbedrijven (spel- en amusementservices)', 1),
('ongediertebestrijdingsbedrijven', 'Ongediertebestrijdingsbedrijven', 1),
('fabrikanten_dranken', 'Fabrikanten van dranken (alcoholisch en niet-alcoholisch)', 1),
('fabrikanten_industriele_gassen', 'Fabrikanten van industriële gassen', 1),
('fabrikanten_voedsel_consumptie_industrie', 'Voedsel/consumptie/industrie-fabrikanten (o.a. cement, soep, vetten, olie, veevoer, pesticiden, meststoffen)', 1),
('reparatie_constructie_vaartuigen', 'Reparatie- en constructiebedrijven voor metalen en kunststof vaartuigen', 1),
('productie_verf_meel_vlees_farma_huishoud', 'Productiebedrijven voor verf, lak, meel, vleesproducten, farmaceutica en huishoudartikelen', 1)
ON DUPLICATE KEY UPDATE
  `company_type_label` = VALUES(`company_type_label`),
  `requires_bedrijfsvergunning` = VALUES(`requires_bedrijfsvergunning`),
  `updated_at` = CURRENT_TIMESTAMP;
