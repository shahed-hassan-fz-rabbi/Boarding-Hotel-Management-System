# Test Suite — Boarding Hotel Management System

Runnable, self-cleaning regression suites for the application. Written with the
same plain-PHP style as the app itself (file_get_contents HTTP harness + mysqli),
so they have zero dependencies beyond PHP CLI and a running instance of the app.

## What is covered

| Suite | Checks | Approx. coverage |
|-------|--------|------------------|
| `e2e_test.php`   | **31** | Auth flow incl. login/logout; CSRF rejection on all POST surfaces (403 without token); RBAC allow/deny per role (Admin/Accountant); full room **allocation lifecycle** (allocate → duplicate-blocked → deallocate) with DB-side verification of bed status & room bed counters; payment creation with **server-computed** `Paid/Partial/Due` status (a forged client status is ignored), partial-payment math, duplicate-period prevention |
| `crud_test.php`  | **14** | meals create/edit/delete; complaint create + status workflow (assign validation against real staff); leave application (auto day-count) + approval; notices create/edit; system settings save; staff user provisioning / role change / suspension |
| `upload_test.php`| **7**  | Student photo upload security: valid PNG accepted and byte-verified on disk, randomised filename with whitelist extension, `.php` upload rejected with inline error (no row, no file), 3 MB upload rejected |
| `bind_params_check.php` | static | Scans the codebase for `bind_param()` calls where the type-string length does not match the number of bound variables (a class of crash that previously broke several pages). Handles both quoted and unquoted type strings |

Total: **52 checks** in three runnable suites + one static analyzer.

## Prerequisites

1. PHP 8.x CLI with the `mysqli` extension enabled (XAMPP ships with one: `C:\xampp\php\php.exe`).
2. The app's MySQL database imported from `boarding_school.sql` (see the project README).
3. The app served somewhere reachable, e.g. via XAMPP Apache at
   `http://localhost/Boarding-Hotel-Management-System` or `php -S 127.0.0.1:8123` from the project root.

## Running

From the project root:

```bash
php tests/e2e_test.php
php tests/crud_test.php
php tests/upload_test.php
php tests/bind_params_check.php
```

Every suite prints `PASS`/`FAIL` per check and a summary line plus an
**exit code of 1 on any failure** (suitable for CI). Point them at a different
server or database via environment variables:

```bash
# Linux/Git Bash
export BHM_BASE_URL=http://localhost:8123 php tests/e2e_test.php

# Windows PowerShell
$env:BHM_BASE_URL = 'http://localhost:8123'; php tests/e2e_test.php
```

| Env var | Default |
|---|---|
| `BHM_BASE_URL` | `http://localhost/Boarding-Hotel-Management-System` |
| `BHM_DB_HOST` / `BHM_DB_USER` / `BHM_DB_PASS` / `BHM_DB_NAME` | `127.0.0.1` / `root` / `''` / `boarding_school` |

## Guarantees

- **Self-cleaning**: every suite deletes only the rows it created (all test data is
  marked with recognizable emails/markers such as `e2e-…@example.com`) and the
  first thing each run does is remove leftovers from a previous aborted run.
- **CLI-only**: `bootstrap.php` refuses execution over HTTP (responds 404), so it
  is safe for these files to live inside the webroot — they are worker scripts,
  not attack surface.
- **No secrets**: credentials come from environment variables with plain XAMPP
  demo defaults; nothing sensitive is baked into the repo.

## Known test limitations

- No JavaScript-engine-accurate checking: the suites validate HTTP behavior and
  DB end-state; the frontend's rendered JS is separately validated with
  `node --check` during development.
- The suites assume one database shared with a running app instance — run them
  against a **development copy**, not a production demo database you care about.
