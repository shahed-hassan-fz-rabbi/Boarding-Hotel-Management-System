# PROJECT_PROGRESS.md — Boarding Hotel Management System

This file tracks development progress in small, verified increments.

## Roadmap / Checklist

| # | Phase / Task | Status |
|---|--------------|--------|
| 1 | Phase 1: Full project audit | ✅ Done (session 1) |
| 2 | Foundation: `.gitignore`, progress docs | ✅ Done |
| 3 | Security: CSRF helper + enforcement on all POST surfaces | ✅ Done (31/31 E2E) |
| 4 | Auth: fixed logout (was a broken duplicate login page) | ✅ Done |
| 5 | RBAC: legacy `Warden`/`Staff` role checks → real enum roles | ✅ Done |
| 6 | RBAC: role checks on every POST handler | ✅ Done |
| 7 | CVE-grade fix: role gates after header include (bypassed) | ✅ Done |
| 8 | Fix: bind_param crashes in payments add/edit (500 on submit) | ✅ Done |
| 9 | Core: transaction-safe room allocate/deallocate + counter sync | ✅ Done |
| 10 | Core: server-side payment status + validation | ✅ Done |
| 11 | Config: dynamic BASE_URL (any folder name / install depth) | ✅ Done |
| 12 | UI: users/list.php double-layout fixed; Reports sidebar link added | ✅ Done |
| 13 | makepass.php restricted to Admin + CSRF | ✅ Done |
| 14 | E2E CRUD sweep: meals, complaints, leaves, notices, settings, users | ✅ Done (14/14 CRUD + fix commit 8dbb442) |
| 15 | XSS/output-escape hardening pass | ✅ Done (8 pages, commit b060372) |
| 16 | README, install/deploy docs, KNOWN_ISSUES | ✅ Done |
| 17 | Final full `php -l` sweep + push verification | ✅ Done (45/45 clean) |
| 18 | Photo upload validation (admissions) + admission bind_param 500 fix | ✅ Done (f02861c, upload tests 7/7) |
| 19 | Recruiter-facing `tests/` suite in repo (52 checks) | ✅ Done (0379ffc) |
| 20 | Portfolio README (screenshots, honest stack statement) & known-issues refresh | ✅ Done |
| 21 | Hosting research + live deployment | ⬜ Next |

## Verified Bug List (fixed)

| Bug | Severity | Commit |
|-----|----------|--------|
| `auth/logout.php` was a misnamed duplicate login page; sidebar "Logout" never logged out | High | 66c820a |
| No CSRF protection on any of ~29 POST surfaces | High | 66c820a |
| Role gates ran after `header.php` include → redirect dropped, unauthorized pages visible (settings, users, staff, notices, attendance, reports) | Critical | b239098 |
| `payments/add.php`/`edit.php` `bind_param` had 12-13 types vs 11-12 vars → HTTP 500, forms never worked | High | b239098 |
| Legacy `Warden`/'Staff' role names in authorization checks after enum rename | High | c0aa790 |
| Destructive POST handlers (students delete, payments CRUD, etc.) had no role checks | High | c0aa790 |
| `leaves/apply.php` dead student-email lookup broke normal apply flow | Med | c0aa790 |
| Room allocation updated 2-3 tables with no transaction / race locks | High | b239098 |
| `hostel_rooms.allocated_beds`/status never updated by allocate/deallocate | Med | b239098 |
| Client-supplied `payment_status` trusted server-side | Med | b239098 |
| `BASE_URL` hardcoded → sidebar/footer links broken outside one folder name | Med | e332e13 |
| `users/list.php` rendered `header.php` + its own `<html>` (double document) | Med | e332e13 |
| Reports module unreachable (no sidebar link) | Low | e332e13 |
| `makepass.php` unauthenticated password-hash generator | Med | e332e13 |
| DB errors echoed to users (`$stmt->error`) | Low | b239098 |

## Changelog

### Session 1 — 2026-10-28 (main branch)

| Commit | Description | Tests | Push |
|--------|-------------|-------|------|
| `75b7189` | (pre-existing) Boarding Management System baseline | — | — |
| `77a44eb` | docs: progress tracker + .gitignore | php -l all 44 files clean | ✅ origin/main |
| `66c820a` | feat: CSRF protection on all POST forms/handlers; real logout page | CSRF helper unit tests 7/7; php -l 22 files | ✅ origin/main |
| `c0aa790` | fix: RBAC enforcement on all state-changing handlers; legacy roles purged | php -l 20 files; legacy-role grep clean | ✅ origin/main |
| `e332e13` | fix: dynamic BASE_URL, reports nav, users layout, admin-only hash tool | BASE_URL unit tests 5/5 contexts; php -l clean | ✅ origin/main |
| `b239098` | fix: guard order bypass, payment 500s, atomic allocations | **E2E 31/31 PASS** (live server + live DB) | ✅ origin/main |
| `1fef35f` | docs: progress tracker + .gitignore | php -l all 44 files clean | ✅ origin/main |
| `8dbb442` | fix: bind_param type strings (meals), complaint update validation (status whitelist + staff existence), prepared activity-log writes everywhere | **CRUD 14/14 + E2E 31/31 PASS**; bind_param checker consistent; php -l clean | ✅ origin/main |
| `368f8d8` | docs: README.md (install/config/security), docs/KNOWN_ISSUES.md, progress update | php -l 45/45 clean | ✅ origin/main |
| `b060372` | fix: XSS hardening — esc() on every DB value in JS innerHTML templates (8 pages), no more names inside onclick attributes, JSON_HEX_* embedding | E2E 31/31 + CRUD 14/14 PASS; node --check valid JS on all 8 pages | ✅ origin/main |
| `7af9e74` | docs: XSS hardening + CRUD sweep results logged; roadmap complete | — | ✅ origin/main |
| `f02861c` | fix: student admission 500 (bind_param 11/12 types/vars) + photo-upload validation (whitelist/size/getimagesize/random name, pre-DB-write); DB error no longer echoed; uploads/ git-ignored | upload suite 7/7 vs XAMPP Apache; improved bind_param checker proved it catches the bug class | ✅ origin/main |
| `0379ffc` | test: repo `tests/` suite — e2e 31 + crud 14 + upload 7 + static bind_param check; CLI-only guard, env-configurable, self-cleaning | all suites green against XAMPP Apache (52/52) | local until push approval |

### CRUD sweep coverage (8dbb442, PHP built-in server + live MySQL)
- Meals: create → edit (Dinner, qty 2) → delete, marker-verified in DB
- Complaints: create with whitelist priority → update to Resolved, assigned to a real active staff id
- Leaves: apply (3-day date range → days computed = 3) → approve by logged-in admin id
- Notices: create → edit persistence
- Settings: save system settings
- Users: provision as Teacher → change role to Accountant → suspend (status 0)
- Test cleanup verified: zero residual E2E-CRUD rows; live data (users 3,4,5, 5 students) intact

### E2E test coverage (b239098, PHP built-in server + live MySQL)
- Login page load, CSRF rejection without token, login with token, dashboard render
- Module POST without token rejected (403)
- Full room allocation lifecycle incl. duplicate allocation and counter sync
- Payment creation with forged status → computed Due; partial math; duplicate period prevented
- Accountant blocked from settings & users, allowed on payments
- Logout invalidates session

## Next task (exact steps)
1. Consider payment-duplicate unique index migration (KNOWN_ISSUES.md #4) — schema change, needs explicit approval.
2. Re-audit `auth/register.php` role selection before any real deployment (KNOWN_ISSUES.md #2).
3. Fresh-clone smoke test: clone repo into a temp dir, import SQL, `php -S`, verify BASE_URL auto-detect works from a clean clone.
4. Optional: pagination/retention plan for activity_logs (KNOWN_ISSUES.md #3).

Known environmental note: project is NOT under `htdocs`; for local serving either copy to htdocs, create an Apache alias, or run `php -S localhost:8080 -t .` (BASE_URL auto-adapts).
