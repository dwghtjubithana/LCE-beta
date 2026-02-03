-- Patch bundle: Tender direct work + public profile + payment proofs
-- Run on production DB

-- 1) Tender direct work flag
ALTER TABLE tenders
  ADD COLUMN is_direct_work TINYINT(1) NOT NULL DEFAULT 0;

UPDATE tenders
SET is_direct_work = 1
WHERE LOWER(title) LIKE '%direct werk%'
   OR LOWER(project) LIKE '%direct werk%'
   OR LOWER(title) LIKE '%lassers%'
   OR LOWER(description) LIKE '%direct werk%'
   OR LOWER(description) LIKE '%lassers%';

-- 2) Public profile fields (companies)
ALTER TABLE companies
  ADD COLUMN public_slug VARCHAR(120) NULL,
  ADD COLUMN display_name VARCHAR(160) NULL,
  ADD COLUMN profile_photo_path VARCHAR(255) NULL,
  ADD COLUMN address VARCHAR(255) NULL,
  ADD COLUMN lat DECIMAL(10,7) NULL,
  ADD COLUMN lng DECIMAL(10,7) NULL,
  ADD COLUMN verification_status VARCHAR(20) NOT NULL DEFAULT 'GRAY';

CREATE UNIQUE INDEX companies_public_slug_unique ON companies (public_slug);

-- 3) Payment proofs table
CREATE TABLE payment_proofs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  company_id BIGINT UNSIGNED NULL,
  file_path VARCHAR(255) NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'PENDING',
  notes TEXT NULL,
  reviewed_by BIGINT UNSIGNED NULL,
  submitted_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT payment_proofs_user_id_fk FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT payment_proofs_company_id_fk FOREIGN KEY (company_id) REFERENCES companies(id)
);

CREATE INDEX payment_proofs_status_idx ON payment_proofs (status);
