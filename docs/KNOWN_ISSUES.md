# KNOWN_ISSUES.md — Boarding Hotel Management System

This file records open issues, limitations and refused-temptation notes.
Fixed bugs are tracked in [`PROJECT_PROGRESS.md`](PROJECT_PROGRESS.md).

## Open / In-triage

| # | Severity | Area | Issue | Notes |
|---|----------|------|-------|-------|
| 1 | **Fixed (b060372)** | Frontend | ~~XSS via JS template literals~~ All DB-backed `${...}` values in innerHTML templates are now escaped via an `esc()` helper across students/allocations/meals/payments/complaints/leaves/dashboard/logs; onclick attribute injection removed; json_encode hardened with JSON_HEX_* flags. | Verified: E2E 31/31 + CRUD 14/14 + node --check on all 8 pages. |
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
- JS template-literal XSS across list pages (b060372)
- **Student admission form was a guaranteed HTTP 500** — `bind_param` had 11 type
  chars for 12 variables; every form-submitted admission crashed. (f02861c)
- **Student photo upload accepted any file** (incl. `.php` into the webroot, with
  the original client filename). Now whitelisted image types only, 2 MB cap,
  `getimagesize` check, randomised names, validated before any DB write. (f02861c)

## Refused / deliberate non-changes

- **No framework migration** kept the project native PHP by design.
- **Live data preserved**: users `3, 4, 5` and all sample students/rooms left untouched
  during the entire hardening effort; nothing destructive was run against the dump.
- **No DB schema changes** beyond what `boarding_school.sql` already contains — future
  migrations (e.g. payments unique index) are left for explicit review.
