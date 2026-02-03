-- Patch: add is_direct_work flag to tenders
-- Run on production DB

ALTER TABLE tenders
  ADD COLUMN is_direct_work TINYINT(1) NOT NULL DEFAULT 0;

UPDATE tenders
SET is_direct_work = 1
WHERE LOWER(title) LIKE '%direct werk%'
   OR LOWER(project) LIKE '%direct werk%'
   OR LOWER(title) LIKE '%lassers%'
   OR LOWER(description) LIKE '%direct werk%'
   OR LOWER(description) LIKE '%lassers%';
