# Project State

Tracks the latest change plus the previous two changes.

## Latest change
- Security hardening pass: protected SMTP secret exposure, added email verification flow (token + verify + resend + login gate), made upgrade bank details admin-managed, and restricted Gemini health endpoint to admin routes.

## Previous change
- Phase 4 started: Bedrijfsprofiel is now the source for Digital ID data (slug/display/address/socials/logo), Digital ID page is sync-first preview/publish, and public profile shows social channels.

## Prior change
- Phase 3 started: added admin-managed email settings center (SMTP credentials, toggles, templates, test send, and delivery logs) with DB patch + API + admin UI.
