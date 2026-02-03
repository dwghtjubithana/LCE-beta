# Deployment Checklist (Production)

## Environment
- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Set `ADMIN_ENFORCE_API=true` to require admin role on admin endpoints.
- Configure `JWT_SECRET` (or ensure `APP_KEY` is set and strong).
- Set storage paths/permissions for `storage/` and `bootstrap/cache/`.
- Provide OCR/AI keys if needed (e.g., `GEMINI_API_KEY`).

## Database
- Run `php artisan migrate` on the target database.
- Backup the DB before deploying schema changes.
- Confirm `documents.expiry_date` exists (used for expiry watchdog).

## Queue & Scheduler
- Start a queue worker (`php artisan queue:work`).
- Configure cron for:
  - `php artisan schedule:run` every minute
  - `lce:expiry-watchdog` daily at 03:00 (already scheduled in app)
  - `lce:send-notifications` every 30 minutes

## Storage
- Ensure uploads directory is writable (`storage/app/uploads`).
- If using cloud storage, update filesystem config and env vars.

## Monitoring
- Confirm logs include `request_id` and `user_id` context.
- Monitor queue failures and audit log growth.

## Smoke Test
- Register → login → create company → upload doc → dashboard score.
