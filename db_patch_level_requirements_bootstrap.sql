-- Patch: bootstrap level-based document requirements
-- Date: 2026-03-02
-- Safe to re-run.

SET @schema_name = DATABASE();

-- Add core rules if missing so level-based dashboard/progress has deterministic baseline.
-- FREE level baseline
INSERT INTO `compliance_rules` (`document_type`, `sector_applicability`, `required_keywords`, `max_age_months`, `constraints`, `created_at`, `updated_at`)
SELECT 'KKF Uittreksel', JSON_ARRAY('general'), JSON_ARRAY('KKF','KVK','Handelsregister','Uittreksel'), 12,
       JSON_OBJECT('required_document', true, 'required_levels', JSON_ARRAY('FREE','BUSINESS','ENTERPRISE'), 'expiry_required', false, 'required_fields', JSON_ARRAY('bedrijfsnaam','kvk_nummer','uitgifte_datum')),
       CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM `compliance_rules` WHERE `document_type`='KKF Uittreksel');

INSERT INTO `compliance_rules` (`document_type`, `sector_applicability`, `required_keywords`, `max_age_months`, `constraints`, `created_at`, `updated_at`)
SELECT 'CRIB', JSON_ARRAY('general'), JSON_ARRAY('CRIB','Belastingdienst','Verklaring'), 12,
       JSON_OBJECT('required_document', true, 'required_levels', JSON_ARRAY('FREE','BUSINESS','ENTERPRISE'), 'expiry_required', true, 'required_fields', JSON_ARRAY('issue_date','expiry_date')),
       CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM `compliance_rules` WHERE `document_type`='CRIB');

INSERT INTO `compliance_rules` (`document_type`, `sector_applicability`, `required_keywords`, `max_age_months`, `constraints`, `created_at`, `updated_at`)
SELECT 'UBO', JSON_ARRAY('general'), JSON_ARRAY('UBO','Beneficial Owner'), 24,
       JSON_OBJECT('required_document', true, 'required_levels', JSON_ARRAY('FREE','BUSINESS','ENTERPRISE'), 'expiry_required', false),
       CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM `compliance_rules` WHERE `document_type`='UBO');

-- BUSINESS level
INSERT INTO `compliance_rules` (`document_type`, `sector_applicability`, `required_keywords`, `max_age_months`, `constraints`, `created_at`, `updated_at`)
SELECT 'HSE', JSON_ARRAY('general'), JSON_ARRAY('HSE','Health','Safety','Environment'), 24,
       JSON_OBJECT('required_document', true, 'required_levels', JSON_ARRAY('BUSINESS','ENTERPRISE')),
       CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM `compliance_rules` WHERE `document_type`='HSE');

-- ENTERPRISE level
INSERT INTO `compliance_rules` (`document_type`, `sector_applicability`, `required_keywords`, `max_age_months`, `constraints`, `created_at`, `updated_at`)
SELECT 'ISO', JSON_ARRAY('general'), JSON_ARRAY('ISO','Certificate','Certificaat'), 36,
       JSON_OBJECT('required_document', true, 'required_levels', JSON_ARRAY('ENTERPRISE'), 'expiry_required', true, 'required_fields', JSON_ARRAY('expiry_date')),
       CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM `compliance_rules` WHERE `document_type`='ISO');

INSERT INTO `compliance_rules` (`document_type`, `sector_applicability`, `required_keywords`, `max_age_months`, `constraints`, `created_at`, `updated_at`)
SELECT 'IOGP', JSON_ARRAY('general'), JSON_ARRAY('IOGP','423','Readiness'), 24,
       JSON_OBJECT('required_document', true, 'required_levels', JSON_ARRAY('ENTERPRISE')),
       CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM `compliance_rules` WHERE `document_type`='IOGP');

-- Existing rules are intentionally left untouched.
-- Admin remains fully in control to set `required_document` and `required_levels` per rule.
