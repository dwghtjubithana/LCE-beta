# Roadmap

High-level planning by phase. Update when scope or sequencing changes.

## Phase 0 — Project baseline (Sprint 1 start) ✅
- Laravel foundation, env config, OpenAPI spec file
- Error envelope + request-id logging
- Migration workflow

## Phase 1 — Auth + Company (Sprint 1 must-work) ✅
- Auth endpoints (register/login/me)
- Company CRUD + ownership
- Seed required docs list (4 types)

## Phase 2 — Profile PDF (Sprint 1) ✅
- PDF generation with watermark
- Owner-only access

## Phase 3 — Upload + Document model (Sprint 1) ✅
- Document schema + local storage
- Upload endpoint + validation
- Status = PROCESSING

## Phase 4 — Scanner pipeline (Sprint 1) ✅
- OCR → classify → validate → decision (OCR text + confidence scoring)
- Status transitions + AI feedback

## Phase 5 — Dashboard score + UI contract (Sprint 1) ✅
- Score formula (4 required docs)
- Dashboard endpoint + status mapping

## Phase 6 — Compliance rules + gating (Sprint 1+) ✅
- Compliance rule model + seed rules
- Plan gating scaffolding

## Phase 7 — Watchdog + Tender Radar (Backlog) ✅
- Expiry cron + notifications
- Tender feed + gating

## Phase 8 — Tests + Done criteria ✅
- Unit/integration/contract tests
- E2E “money shot” scenario

## Remaining TODOs (Next)
- Backlog items only (see below)

## Backlog (Next)
- Real tender scraping/ingestion adapters
- Real notification delivery (email/push provider integration)
- User confirmation flow for uncertain classification

## Maintenance
- Keep OpenAPI synced with admin endpoints
