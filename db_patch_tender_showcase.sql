SET @db_name := DATABASE();

SET @sql := IF (
    EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name
          AND TABLE_NAME = 'tenders'
          AND COLUMN_NAME = 'submission_deadline'
    ),
    'SELECT 1',
    'ALTER TABLE `tenders` ADD COLUMN `submission_deadline` DATE NULL AFTER `date`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF (
    EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name
          AND TABLE_NAME = 'tenders'
          AND COLUMN_NAME = 'location'
    ),
    'SELECT 1',
    'ALTER TABLE `tenders` ADD COLUMN `location` VARCHAR(255) NULL AFTER `client`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF (
    EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name
          AND TABLE_NAME = 'tenders'
          AND COLUMN_NAME = 'sector'
    ),
    'SELECT 1',
    'ALTER TABLE `tenders` ADD COLUMN `sector` VARCHAR(255) NULL AFTER `location`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF (
    EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name
          AND TABLE_NAME = 'tenders'
          AND COLUMN_NAME = 'reference_code'
    ),
    'SELECT 1',
    'ALTER TABLE `tenders` ADD COLUMN `reference_code` VARCHAR(255) NULL AFTER `sector`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF (
    EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name
          AND TABLE_NAME = 'tenders'
          AND COLUMN_NAME = 'contract_type'
    ),
    'SELECT 1',
    'ALTER TABLE `tenders` ADD COLUMN `contract_type` VARCHAR(255) NULL AFTER `reference_code`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF (
    EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name
          AND TABLE_NAME = 'tenders'
          AND COLUMN_NAME = 'budget_label'
    ),
    'SELECT 1',
    'ALTER TABLE `tenders` ADD COLUMN `budget_label` VARCHAR(255) NULL AFTER `contract_type`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF (
    EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name
          AND TABLE_NAME = 'tenders'
          AND COLUMN_NAME = 'eligibility'
    ),
    'SELECT 1',
    'ALTER TABLE `tenders` ADD COLUMN `eligibility` TEXT NULL AFTER `budget_label`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF (
    EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name
          AND TABLE_NAME = 'tenders'
          AND COLUMN_NAME = 'source_name'
    ),
    'SELECT 1',
    'ALTER TABLE `tenders` ADD COLUMN `source_name` VARCHAR(255) NULL AFTER `details_url`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF (
    EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name
          AND TABLE_NAME = 'tenders'
          AND COLUMN_NAME = 'source_url'
    ),
    'SELECT 1',
    'ALTER TABLE `tenders` ADD COLUMN `source_url` VARCHAR(255) NULL AFTER `source_name`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF (
    EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name
          AND TABLE_NAME = 'tenders'
          AND COLUMN_NAME = 'cover_image_url'
    ),
    'SELECT 1',
    'ALTER TABLE `tenders` ADD COLUMN `cover_image_url` VARCHAR(255) NULL AFTER `source_url`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF (
    EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @db_name
          AND TABLE_NAME = 'tenders'
          AND COLUMN_NAME = 'issuer_logo_url'
    ),
    'SELECT 1',
    'ALTER TABLE `tenders` ADD COLUMN `issuer_logo_url` VARCHAR(255) NULL AFTER `cover_image_url`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

DELETE FROM `tenders`
WHERE `title` IN (
    'Offshore Catering & Camp Services',
    'Drainage Rehabilitation & Civil Works',
    'Fleet Maintenance & Site Logistics Support',
    '8 lassers gezocht voor shutdown ondersteuning'
);

INSERT INTO `tenders` (
    `title`,
    `project`,
    `date`,
    `submission_deadline`,
    `client`,
    `location`,
    `sector`,
    `reference_code`,
    `contract_type`,
    `budget_label`,
    `eligibility`,
    `details_url`,
    `source_name`,
    `source_url`,
    `cover_image_url`,
    `issuer_logo_url`,
    `attachments`,
    `description`,
    `is_direct_work`,
    `status`,
    `created_at`,
    `updated_at`
) VALUES
(
    'Offshore Catering & Camp Services',
    'Saramacca Operations Support',
    CURDATE() - INTERVAL 2 DAY,
    CURDATE() + INTERVAL 9 DAY,
    'Staatsolie Maatschappij Suriname N.V.',
    'Saramacca / Paramaribo',
    'Olie & Gas',
    'SC-STAATSOLIE-2026-001',
    'RFP',
    'Middelgroot contract',
    'Ervaring met offshore catering, HSE-documentatie, geldige bedrijfsregistratie en lokale mobilisatiecapaciteit.',
    'https://www.staatsolie.com/procurement-opportunities/',
    'Staatsolie Procurement',
    'https://www.staatsolie.com/procurement-opportunities/',
    'https://picsum.photos/seed/staatsolie-offshore/1400/900',
    NULL,
    JSON_ARRAY(JSON_OBJECT('type', 'url', 'url', 'https://www.staatsolie.com/procurement-opportunities/')),
    'Levering van catering, housekeeping en lichte camp support voor operationele teams in de Saramacca-regio.',
    0,
    'APPROVED',
    NOW(),
    NOW()
),
(
    'Drainage Rehabilitation & Civil Works',
    'Waterafvoer Noord-Paramaribo',
    CURDATE() - INTERVAL 4 DAY,
    CURDATE() + INTERVAL 12 DAY,
    'Ministerie van Openbare Werken',
    'Paramaribo',
    'Overheid / Infra',
    'SC-GOV-OW-2026-014',
    'Openbare aanbesteding',
    'Groot infrastructureel werk',
    'Lokale aannemer of combinatie, relevante civiele referenties, actuele belasting- en registratiebewijzen.',
    'https://www.gov.sr/ministerie-van-openbare-werken/',
    'Gov.sr - Openbare Werken',
    'https://www.gov.sr/ministerie-van-openbare-werken/',
    'https://picsum.photos/seed/gov-civil-works/1400/900',
    NULL,
    JSON_ARRAY(JSON_OBJECT('type', 'url', 'url', 'https://www.gov.sr/ministerie-van-openbare-werken/')),
    'Herstel van hoofdafwatering, civiele betonwerken en verharding in kritieke zones met hoge wateroverlast.',
    0,
    'APPROVED',
    NOW(),
    NOW()
),
(
    'Fleet Maintenance & Site Logistics Support',
    'Rosebel Operational Logistics',
    CURDATE() - INTERVAL 1 DAY,
    CURDATE() + INTERVAL 16 DAY,
    'Rosebel Gold Mines N.V.',
    'Brokopondo',
    'Mijnbouw',
    'SC-ROSEBEL-2026-003',
    'RFQ',
    'Operationeel support contract',
    'Aantoonbare ervaring met fleet maintenance, transportcoordinatie, veiligheidsprocedures en remote-site support.',
    'https://www.rosebelgoldmines.com/',
    'Rosebel Gold Mines',
    'https://www.rosebelgoldmines.com/',
    'https://picsum.photos/seed/rosebel-mining/1400/900',
    NULL,
    JSON_ARRAY(JSON_OBJECT('type', 'url', 'url', 'https://www.rosebelgoldmines.com/')),
    'Ondersteuning voor site logistics, planning van materieel en eerstelijns onderhoud van operationele voertuigen.',
    0,
    'APPROVED',
    NOW(),
    NOW()
),
(
    '8 lassers gezocht voor shutdown ondersteuning',
    'Direct Werk',
    CURDATE(),
    CURDATE() + INTERVAL 2 DAY,
    'Industrial Services Partner',
    'Wanica',
    'Direct Werk',
    'SC-DW-2026-019',
    'Snelle inzet',
    'Dagprijs per team',
    'Direct inzetbaar team met geldige ID, basisveiligheid en eigen vervoer.',
    'https://wa.me/597000000',
    'Direct placement',
    'https://wa.me/597000000',
    'https://picsum.photos/seed/direct-work-welding/1400/900',
    NULL,
    JSON_ARRAY(),
    'Snelle shutdown-opdracht voor gecertificeerde lassers. Start binnen 48 uur na bevestiging.',
    1,
    'APPROVED',
    NOW(),
    NOW()
);
