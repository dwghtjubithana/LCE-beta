# Frontend API Guide (Sprint 1)

This document is for frontend developers. It lists the current API endpoints, required parameters, and response shapes used by the UI.

Base URL: `/api`
Auth: Bearer JWT via `Authorization: Bearer <token>`
Error shape (all endpoints):
```
{
  "code": "STRING",
  "message": "STRING",
  "fieldErrors": { ... } // optional
}
```

## Auth

POST `/api/auth/register`
- Body (JSON):
  - `email` (string, optional)
  - `phone` (string, optional)
  - `password` (string, required)
  - `username` (string, optional)
- Response 201:
```
{
  "status": "success",
  "token": "JWT",
  "expires_in": 3600,
  "user": { "id", "uuid", "email", "phone", "username", "role", "status" }
}
```

POST `/api/auth/login`
- Body (JSON):
  - `email` (string, optional)
  - `phone` (string, optional)
  - `password` (string, required)
- Response 200: same as register

GET `/api/auth/me`
- Headers: `Authorization: Bearer <token>`
- Response 200:
```
{ "status": "success", "user": { ... } }
```

POST `/api/auth/logout`
- Headers: `Authorization: Bearer <token>`
- Response 200:
```
{ "status": "success", "message": "Logged out. Please discard the token on the client." }
```

## Company

POST `/api/companies`
- Headers: Bearer
- Body (JSON):
  - `company_name` (string, required)
  - `sector` (string, required)
  - `experience` (string, optional)
  - `contact` (object, optional)
- Response 201:
```
{ "status": "success", "company": { ... } }
```

GET `/api/companies/me`
- Headers: Bearer
- Response 200:
```
{ "status": "success", "company": { ... } }
```

GET `/api/companies/{id}`
- Headers: Bearer
- Response 200:
```
{ "status": "success", "company": { ... } }
```

PATCH `/api/companies/{id}`
- Headers: Bearer
- Body (JSON):
  - `company_name` (string, optional)
  - `sector` (string, optional)
  - `experience` (string, optional)
  - `contact` (object, optional)
  - `bluewave_status` (boolean, optional)
  - `verification_level` (string, optional)
- Response 200:
```
{ "status": "success", "company": { ... } }
```

GET `/api/companies/{id}/dashboard`
- Headers: Bearer
- Response 200:
```
{
  "status": "success",
  "current_score": 0-100,
  "score_color": "Rood" | "Oranje" | "Groen",
  "required_documents": [
    { "type": "KKF Uittreksel", "status": "VALID|INVALID|PROCESSING|MISSING|..." }
  ]
}
```

GET `/api/companies/{id}/profile.pdf`
- Headers: Bearer
- Response: `application/pdf`

## Documents

POST `/api/documents/upload`
- Headers: Bearer
- Body: `multipart/form-data`
  - `file` (file, required)
  - `category_selected` (string, required)
  - `company_id` (int, optional)
  - `ocr_confidence` (number, optional)
  - `ocr_text` (string, optional)
- Response 201:
```
{ "status": "success", "document": { ... } }
```

POST `/api/documents/upload/bulk`
- Headers: Bearer
- Body: `multipart/form-data`
  - `files[]` (files, required)
  - `category_selected` (string, required)
  - `company_id` (int, optional)
- Response 201:
```
{
  "status": "success",
  "results": [
    { "filename": "...", "status": "queued|duplicate|error", "document_id": 1, "color": "Blauw|Grijs|..." }
  ]
}
```

GET `/api/documents/{id}`
- Headers: Bearer
- Response 200:
```
{
  "status": "success",
  "document": {
    "id": 1,
    "uuid": "...",
    "company_id": 1,
    "category_selected": "...",
    "detected_type": "...",
    "status": "PROCESSING|VALID|INVALID|...",
    "ui_label": "Processing|Valid|Invalid|...",
    "recommended_action": "Bekijk|Fix met AI|Vernieuw|...",
    "color": "Grijs|Blauw|Groen|Rood|Oranje",
    "expiry_date": "2026-12-31 00:00:00" // nullable
  }
}
```

GET `/api/companies/{id}/documents`
- Headers: Bearer
- Response 200:
```
{ "status": "success", "documents": [ { ...document with ui fields... } ] }
```

POST `/api/documents/{id}/reprocess`
- Headers: Bearer
- Response 200:
```
{ "status": "success", "document": { ...document with ui fields... } }
```

GET `/api/documents/{id}/summary`
- Headers: Bearer
- Response: `application/pdf`

## Tenders (User-facing)

GET `/api/tenders`
- Headers: Bearer
- Response 200:
```
{ "status": "success", "tenders": [ { ... } ] }
```
Note: Non-BUSINESS users see blurred/hidden fields.

GET `/api/tenders/{id}`
- Headers: Bearer
- Response 200:
```
{ "status": "success", "tender": { ... } }
```

## Admin (JWT + admin role enforced in production)

## Admin Web UI Routes
- `GET /admin/login` (admin login screen)
- `GET /admin` (dashboard; requires admin JWT in localStorage)
- `GET /admin/users`
- `GET /admin/users/{id}`
- `GET /admin/users/{id}/edit`
- `GET /admin/users/create`
- `GET /admin/companies`
- `GET /admin/companies/{id}`
- `GET /admin/companies/{id}/edit`
- `GET /admin/companies/create`
- `GET /admin/documents`
- `GET /admin/documents/{id}`
- `GET /admin/compliance-rules`
- `GET /admin/compliance-rules/{id}`
- `GET /admin/compliance-rules/{id}/edit`
- `GET /admin/compliance-rules/create`
- `GET /admin/tenders`
- `GET /admin/tenders/{id}`
- `GET /admin/tenders/{id}/edit`
- `GET /admin/tenders/create`
- `GET /admin/notifications`
- `GET /admin/notifications/{id}`
- `GET /admin/logs`
- `GET /admin/system`

GET `/api/admin/health`
- Response:
```
{ "status": "success", "health": { "app_env", "app_version", "queue_connection", "last_tender_import_at", "last_notifications_sent_at" } }
```

GET `/api/admin/metrics`
- Response:
```
{ "status": "success", "metrics": { "total_users", "total_companies", "total_documents", "documents_by_status", "avg_processing_seconds" } }
```

GET `/api/admin/audit-logs?search=&limit=&page=`
- Response:
```
{ "status": "success", "logs": [ ... ], "meta": { "page", "per_page", "total", "total_pages" } }
```

GET `/api/admin/users?search=&limit=&page=`
GET `/api/admin/users/{id}`
POST `/api/admin/users`
PATCH `/api/admin/users/{id}`
- Body (JSON):
  - `email` (string, required for create unless phone is provided)
  - `phone` (string, required for create unless email is provided)
  - `password` (string, required for create)
  - `username` (string, required for create)
  - `app_role` (string: user|admin)
  - `role` (string: USER|STAFF)
  - `status` (string: ACTIVE|SUSPENDED)
  - `plan` (string: FREE|PRO|BUSINESS)
  - `plan_status` (string: ACTIVE|PENDING_PAYMENT|EXPIRED)
- Response: `{ "status": "success", "user": { ... } }`

GET `/api/admin/companies?search=&limit=&page=`
GET `/api/admin/companies/{id}`
POST `/api/admin/companies`
- Body (JSON):
  - `owner_user_id` (int, required)
  - `company_name` (string, required)
  - `sector` (string, required)
  - `experience` (string, optional)
  - `contact_email` (string, optional)
  - `contact_phone` (string, optional)
  - `contact_address` (string, optional)
  - `contact_city` (string, optional)
  - `contact_country` (string, optional)
- Response:
```
{ "status": "success", "companies": [ { ... } ], "meta": { "page", "per_page", "total", "total_pages" } }
```

GET `/api/admin/documents?search=&status=&category=&limit=&page=`
GET `/api/admin/documents/{id}`
- Response 200:
```
{ "status": "success", "document": { "id", "uuid", "company_id", "category_selected", "status", "detected_type", "expiry_date", "color", "ui_label", "recommended_action" } }
```

GET `/api/admin/compliance-rules?search=&limit=&page=`
GET `/api/admin/compliance-rules/{id}`
POST `/api/admin/compliance-rules`
PATCH `/api/admin/compliance-rules/{id}`
DELETE `/api/admin/compliance-rules/{id}`
- Body (JSON):
  - `document_type` (string, required)
  - `sector_applicability` (array of strings, optional)
  - `required_keywords` (array of strings, optional)
  - `max_age_months` (int, optional)
  - `constraints` (object, optional)

GET `/api/admin/tenders?search=&limit=&page=`
POST `/api/admin/tenders`
PATCH `/api/admin/tenders/{id}`
DELETE `/api/admin/tenders/{id}`
- Body (JSON):
  - `title` (string, required)
  - `client` (string, required)
  - `date` (string date, optional)
  - `details_url` (string URL, optional)
  - `attachments` (array of strings, optional; filenames/URLs)
  - `description` (string, optional)

GET `/api/admin/notifications?search=&status=&limit=&page=`
GET `/api/admin/notifications/{id}`
POST `/api/admin/notifications/{id}/resend`
POST `/api/admin/notifications/{id}/mark-sent`
POST `/api/admin/notifications/mark-sent` (bulk; body: `{ "status":"pending" }` or `{ "ids":[...] }`)

## Status Colors
- `Grijs` = Missing/Unknown
- `Blauw` = Processing
- `Groen` = Valid
- `Rood` = Invalid/Expired
- `Oranje` = Expiring Soon/Manual Review/Needs Confirmation

## Notes
- `PROCESSING` is used for the "pending/scanning in progress" state.
- For demo flow: register → create company → upload 4 required docs → dashboard score to 100.
