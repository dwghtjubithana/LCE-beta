# LCE-beta Start Here

## What this project is
LCE-beta (Local Content Engine) is a Laravel-backed compliance and document validation app with a simple web UI. It supports:
- Client-side OCR (Tesseract.js) or direct upload of PDFs/DOCX
- Gemini-based validation and summary generation
- Scan logging + summary PDFs
- Basic data views for tenders and suppliers

Primary entry points:
- Backend (Laravel): `app/`, `routes/`, `config/`, `database/`
- Public entry: `public/`
- Legacy reference code: `_legacy/`

## What we’re doing
We are building the Sprint 1 backend in Laravel and will recreate the existing LCE flows in a structured, testable way.

## Phases (overview)
Phase 0 — Project baseline
- API style (REST), error envelope, logging with request-id
- Environments (local/staging/prod) + env var checklist
- OpenAPI spec file (`openapi.yaml`) maintained separately
- DB migrations workflow
 - Request logging context includes `request_id` + `user_id`
 - Admin health/metrics/Gemini checks available in `/admin`

Phase 1 — Auth + Company foundation (must work first)
- Auth endpoints (register/login/me) with hashing + rate limit
- Company profile CRUD with ownership checks
- Seed required docs list (4 types) for Sprint 1 dashboard logic
Auth model note:
- Use `users` table with `uuid` (external id) + `email`/`phone` for login.
- Keep legacy columns for compatibility (`username`, `password_hash`, `role`).
Tables created in Sprint 1:
- `companies`, `documents`, `compliance_rules`, `notifications`
Document note:
- `expiry_date` stored as a dedicated nullable column (also kept in `extracted_data` for traceability).
Status note:
- `PROCESSING` covers the "pending" state (no separate `PENDING` status).

Phase 2 — Profile PDF
- Server-side PDF generation with watermark “Powered by SuriCore”
- Owner-only access to `/companies/:id/profile.pdf`

Phase 3 — Document upload
- Document model + local storage in `uploads/`
- Hashing, mime/type validation, OCR confidence gate
- Status = PROCESSING on upload
- Bulk upload endpoint for multiple files
- Virus scan placeholder (logs "skipped" for now)

Phase 4 — Scanner pipeline
- Async processing (OCR → classify → validate → decision)
- Status transitions: VALID/INVALID/MANUAL_REVIEW
- Mismatch handling + AI feedback

Phase 5 — Dashboard score + UI contract
- Score formula: (valid_docs / 4) * 100
- `/companies/:id/dashboard` response for UI
- Status → label/action mapping contract
- Status color mapping: Grijs/Blauw/Groen/Rood/Oranje
- Score color mapping: 0–49 Rood, 50–99 Oranje, 100 Groen

Phase 6 — Compliance rules + gating
- Compliance rules model + small seed ruleset
- Feature gating (FREE/PRO/BUSINESS) scaffolding
- Admin CRUD for compliance rules (API + admin UI)

Phase 7 — Watchdog + Tender Radar (backlog)
- Expiry cron job + notifications
- Tender feed + gating
- Admin user/plan management panel
- Admin tender management panel
- Admin notification management panel

Phase 8 — Tests + Sprint 1 “done”
- Unit/integration/contract tests
- E2E “money shot” scenario

## Working rules for credentials
- Do not store production DB credentials in the repo.
- Use `.env` locally; keep `.env.example` with placeholders only.
- Admin enforcement defaults to ON in production unless `ADMIN_ENFORCE_API` is explicitly set.

## Legacy reference
- Previous plain-PHP implementation is preserved in `_legacy/` for reference.

## Rules of engagement
Do:
- Read this document first, then review the reference documents before making changes:
  - `PROJECT_STATE.md`
  - `ROADMAP.md`
  - `PROJECT_LOG.md`
- Keep changes small, focused, and documented.
- Keep secrets out of code (use environment variables).
- Ensure uploads and summaries remain within `uploads/` directories.

Do not:
- Commit API keys, DB passwords, or other secrets.
- Change folder structure without updating this doc and the roadmap.
- Modify `vendor/` directly (use Composer instead).
- Delete logs or summaries unless explicitly requested.

## Required workflow after changes
After making any change:
1) Update `PROJECT_STATE.md` with:
   - The newest change
   - The previous two changes (keep only the last 2 + the new one)
2) Update `ROADMAP.md` if plans or phases are affected.
3) Append the change to `PROJECT_LOG.md`.

## Reference documents
- Project state (last 2 changes + newest): `PROJECT_STATE.md`
- Roadmap (phase planning): `ROADMAP.md`
- Project log (chronological history): `PROJECT_LOG.md`
- Deployment checklist: `DEPLOYMENT.md`
