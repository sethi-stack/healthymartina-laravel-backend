# PDF Export External Service Plan

## Current Baseline (Kept Working)
- Calendar PDF export works synchronously in Laravel with chunked render + merge.
- Large exports can complete, but response time is high (40–70s+) and not scalable for concurrent users.
- Frontend uses extended request timeout for export endpoints.

## Goal
Move heavy PDF generation out of Laravel API into a dedicated export service, while keeping user-facing flow reliable and simple.

## Target Architecture
1. Laravel API receives export request and validates ownership/inputs.
2. Laravel enqueues an export job record and returns `job_id` immediately.
3. External PDF service pulls/receives job payload, generates PDF, uploads to DO Spaces.
4. Laravel exposes status and download endpoints.
5. Frontend polls status and downloads when ready.

## API Contract (Laravel)
- `POST /api/v1/calendars/export/pdf/start`
  - Input: current export payload (`calendar`, `export_param`, `template`, `hero_recipe_id`, `selected_recipes`)
  - Output: `{ success: true, job_id, status: "queued" }`
- `GET /api/v1/calendars/export/pdf/jobs/{jobId}`
  - Output: `{ status: queued|processing|completed|failed, progress?, error?, file_url? }`
- `GET /api/v1/calendars/export/pdf/jobs/{jobId}/download`
  - Streams or redirects to signed Spaces URL if completed.

## Service Contract (Laravel -> External Service)
- Auth: HMAC signature header + timestamp (or internal token).
- Payload:
  - user id
  - calendar id
  - normalized calendar data (labels/schedules/raciones/leftovers)
  - selected recipes + nutrition/lista payload
  - template + export options
- Response/callback:
  - `job_id`
  - `status`
  - `error_message` (if failed)
  - `file_path` + `file_size` (if completed)

## External PDF Service (Node.js Suggested)
- Runtime: Node + Playwright/Puppeteer.
- PDF strategy:
  - render sections (cover, recipes, calendar, nutrition, lista) as separate PDFs
  - merge sections
  - upload final PDF to DO Spaces
- Worker model:
  - controlled concurrency (e.g., 2–4 workers per instance)
  - retry policy for transient errors
  - hard timeout per job

## Deployment Plan
1. Keep existing Laravel synchronous export as fallback.
2. Implement async endpoints + job table in Laravel.
3. Build/export service MVP with one template (`bold`/`advanced`) first.
4. Enable feature flag for selected users.
5. Roll out fully after validating:
  - success rate
  - average generation time
  - concurrent run stability

## Observability
- Log per job:
  - queue wait ms
  - generation ms
  - merge ms
  - upload ms
  - output bytes
- Metrics:
  - success/failure rate
  - p50/p95 completion time
  - active concurrent jobs

## Non-Goals (Phase 1)
- No redesign of PDF layout/theme.
- No schema changes to calendar business data.
- No forced migration off current sync endpoint until async is proven.

## Suggested New Chat Kickoff Prompt
"Implement async calendar PDF export pipeline with external service integration while keeping existing sync export as fallback. Start with Laravel job endpoints (`start`, `status`, `download`) and a service adapter interface. Then scaffold Node export worker for bold/advanced template only."

