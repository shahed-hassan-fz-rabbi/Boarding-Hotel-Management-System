# KNOWN_ISSUES.md — Boarding Hotel Management System

This file records open issues, limitations and refused-temptation notes.
Fixed bugs are tracked in [`PROJECT_PROGRESS.md`](PROJECT_PROGRESS.md).

## Open / In-triage

| # | Severity | Area | Issue | Notes |
|---|----------|------|-------|-------|
| 1 | Medium | Frontend | **XSS via JS template literals**: `complaints/list.php` and `leaves/list.php` inject DB-backed strings (`${...}`) into the DOM without HTML-escaping. Admin/Supervisor-controlled fields could host stored XSS payloads. | Next fix candidate: `category`, `priority`, reason text, titles. Recommend `escapeHtml()` helper or switch to PHP-rendered rows. |
| 2 | Medium | Auth | `auth/register.php` is self-service registration. Storage-backed role selection / email-renaming check needs a re-audit before any production launch. | Recommend disabling in real deployments and provisioning users from `users/add.php` only. |
| 3 | Low | Infra | `activity_logs` has no retention or pagination strategy; UI does not yet display log entries. | |
| 4 | Low | Payments | Duplicate-period prevention is app-level (SELECT before INSERT), not a DB unique index on `(student_id, year, month)`. | Race window is small but a `UNIQUE KEY` migration would be strictly safer. |
| 5 | Low | Leaves | Leave-balance mutation happens inside the approve handler workflow; corner cases (re-approval of a cancelled leave) may double-credit. | Reviewed as low-impact; recommend idempotency check. |
| 6 | Info | Infra | `gui/error` paths render `htmlspecialchar`-escaped but DB error text is only logged (error_log) not surfaced — good — yet **no visible fallback redirect** short of the browser back button for some invalid-GET paths. | Cosmetic. |
| 7 | Info | Ops | `php -S` in PowerShell background sessions may show exit code 1 noise on stderr for successful git pushes; unrelated to app. | Ignore. |

## Fixed (for reference — see PROJECT_PROGRESS.md)

- CSRF on all POST surfaces (66c820a)
- RBAC on all handlers + legacy role purge (c0aa790)
- Role-gate ordering bypass (b239098)
- `payments` bind_param 500 crashes (b239098)
- Transaction-safe room allocation + counter sync (b239098)
- Server-computed payment status (b239098)
- Dynamic BASE_URL / broken sidebar links (e332e13)
- `makepass.php` admin-only (e332e13)
- bind_param type-string crashes in meals (8dbb442)
- Complaint-assign validation against real staff list (8dbb442)
- Un-prepared activity-log writes (8dbb442)

## Refused / deliberate non-changes

- **No framework migration** kept the project native PHP by design.
- **Live data preserved**: users `3, 4, 5` and all sample students/rooms left untouched
  during the entire hardening effort; nothing destructive was run against the dump.
- **No DB schema changes** beyond what `boarding_school.sql` already contains — future
  migrations (e.g. payments unique index) are left for explicit review.
