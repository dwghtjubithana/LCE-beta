-- Patch: add baseline compliance rules
-- Target: MySQL 8.x

SET @schema_name = DATABASE();
SET @tbl = 'compliance_rules';

-- KKF Uittreksel (baseline)
INSERT INTO `compliance_rules` (
  `document_type`,
  `sector_applicability`,
  `required_keywords`,
  `max_age_months`,
  `constraints`,
  `created_at`,
  `updated_at`
)
SELECT
  'KKF Uittreksel',
  NULL,
  JSON_ARRAY('KKF','KVK','Handelsregister','Uittreksel'),
  12,
  JSON_OBJECT(
    'expiry_required', false,
    'required_fields', JSON_ARRAY('bedrijfsnaam','kvk_nummer','uitgifte_datum')
  ),
  CURRENT_TIMESTAMP,
  CURRENT_TIMESTAMP
WHERE NOT EXISTS (
  SELECT 1 FROM `compliance_rules` WHERE `document_type` = 'KKF Uittreksel'
);

-- Vergunning (baseline)
INSERT INTO `compliance_rules` (
  `document_type`,
  `sector_applicability`,
  `required_keywords`,
  `max_age_months`,
  `constraints`,
  `created_at`,
  `updated_at`
)
SELECT
  'Vergunning',
  NULL,
  JSON_ARRAY('Vergunning','Permit','License'),
  12,
  JSON_OBJECT(
    'expiry_required', true,
    'required_fields', JSON_ARRAY('issue_date','expiry_date')
  ),
  CURRENT_TIMESTAMP,
  CURRENT_TIMESTAMP
WHERE NOT EXISTS (
  SELECT 1 FROM `compliance_rules` WHERE `document_type` = 'Vergunning'
);

-- Belastingverklaring (baseline)
INSERT INTO `compliance_rules` (
  `document_type`,
  `sector_applicability`,
  `required_keywords`,
  `max_age_months`,
  `constraints`,
  `created_at`,
  `updated_at`
)
SELECT
  'Belastingverklaring',
  NULL,
  JSON_ARRAY('Belasting','Tax','Aanslag','Verklaring'),
  12,
  JSON_OBJECT(
    'expiry_required', true,
    'required_fields', JSON_ARRAY('issue_date','expiry_date')
  ),
  CURRENT_TIMESTAMP,
  CURRENT_TIMESTAMP
WHERE NOT EXISTS (
  SELECT 1 FROM `compliance_rules` WHERE `document_type` = 'Belastingverklaring'
);

-- ID Bewijs (baseline)
INSERT INTO `compliance_rules` (
  `document_type`,
  `sector_applicability`,
  `required_keywords`,
  `max_age_months`,
  `constraints`,
  `created_at`,
  `updated_at`
)
SELECT
  'ID Bewijs',
  NULL,
  JSON_ARRAY('ID','Identiteit','Passport','Rijbewijs'),
  120,
  JSON_OBJECT(
    'expiry_required', true,
    'required_fields', JSON_ARRAY('expiry_date')
  ),
  CURRENT_TIMESTAMP,
  CURRENT_TIMESTAMP
WHERE NOT EXISTS (
  SELECT 1 FROM `compliance_rules` WHERE `document_type` = 'ID Bewijs'
);
