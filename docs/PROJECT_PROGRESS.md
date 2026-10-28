# PROJECT_PROGRESS.md — Boarding Hotel Management System

This file tracks development progress in small, verified increments.
Last verified state is always at the top of the log section.

## Roadmap / Checklist

| # | Phase / Task | Status |
|---|--------------|--------|
| 1 | Phase 1: Full project audit | ✅ Done |
| 2 | Foundation: `.gitignore`, progress docs | ✅ Done |
| 3 | Security: CSRF helper + enforcement on all POST handlers and forms | ⬜ Pending |
| 4 | Auth/RBAC: fix legacy `Warden`/`Staff` role checks | ⬜ Pending |
| 5 | Auth/RBAC: add missing role checks to POST handlers (students/delete, payments, meals, allocations) | ⬜ Pending |
| 6 | Config: dynamic `BASE_URL` instead of hardcoded path | ⬜ Pending |
| 7 | Core: transactions for room allocate/deallocate | ⬜ Pending |
| 8 | Core: server-side payment status calculation | ⬜ Pending |
| 9 | UI: fix `users/list.php` double layout bug | ⬜ Pending |
| 10 | UI: add Reports link to sidebar | ⬜ Pending |
| 11 | Docs: README with install, test, deployment instructions | ⬜ Pending |
| 12 | Final: full `php -l` sweep, security review, push verification | ⬜ Pending |

## Audit Findings (Phase 1, verified 2026-10-28)

**Stack:** Native PHP 8.2 (procedural, MySQLi), Tailwind CSS via CDN, MySQL DB `boarding_school`. No framework, no Composer. Sessions-based auth with roles `Admin / Teacher / Hostel Supervisor / Accountant`.

**Modules found (functional page set exists):** Dashboard room grid, Students (+ attendance), Room allocations + beds, Meals, Complaints, Leaves, Payments, Notices, Reports/Analytics, Settings, Activity logs, Staff, Users (RBAC).

**Issues identified (to fix in order):**
1. No CSRF protection anywhere (~29 POST surfaces).
2. Legacy roles `Warden` / `Staff` still referenced in `leaves/approve.php`, `reports/analytics.php`, `complaints/list.php` — real enum is `Admin / Teacher / Hostel Supervisor / Accountant`.
3. Destructive POST handlers missing role checks (`students/delete.php`, `payments/*`, `meals/*`, allocation handlers, notices handlers).
4. `BASE_URL` hardcoded to `http://localhost/boarding-school/` — breaks for any other folder name.
5. `users/list.php` renders `components/header.php` AND its own `<html>` (double layout).
6. `payments/edit.php` trusts client-supplied `payment_status`; must compute `Paid/Partial/Due` server-side from fee totals.
7. Room allocation/deallocation updates multiple tables without a DB transaction.
8. Reports/Analytics module has no sidebar link (unreachable).
9. No `.gitignore`, README, or project docs.
10. `makepass.php` is an unauthenticated password-hash utility (restrict or remove).

## Changelog

### Session 1 — 2026-10-28
- **Commit:** (foundation) `docs: add project progress tracker and gitignore`
- **Tests:** `php -l` pass on all 47 existing PHP files.
- **Push:** pending at time of writing.
- **Next task:** CSRF protection helper (`includes/csrf.php`) + form tokens.
