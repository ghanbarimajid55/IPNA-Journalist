# IPNA Journalist PMS — Current Code Audit & Verification

Date: 2026-08-23

## What was verified locally against the supplied source archive

- PHP syntax: **80/80 PHP files pass `php -l`**.
- `debug.php`: absent.
- Legacy `includes/Router.php`: absent; routing is centralized in `public/index.php`.
- Controller route class naming uses the flat underscore convention (`Admin_UserController`, etc.).
- Global CSRF verification exists in `public/index.php` for POST/PUT/PATCH/DELETE.
- Session cookie parameters are configured before generated `config.php` starts the session.
- Non-admin `/api/activities` requests are forced to the authenticated user's own `user_id`.
- Activity create/update field names are aligned with the schema (`activity_type`, `activity_date`, `mission`, etc.).
- Monthly target fields match the schema (`*_target`, `score_target`).
- Activity type fields/categories match the schema.
- User pagination and filter variables are supplied to the view.
- CSV export uses the report aliases and neutralizes spreadsheet formulas.
- Reporter/photographer controllers use the shared role base classes.

## Fixes applied in this review archive

1. Session idle timeout is now enforced from `session_timeout_minutes`.
2. Login rate limiting now reads `max_login_attempts` and `lockout_duration_minutes` settings, with safe defaults.
3. Admin/team creation and editing now use the real `position` schema field instead of nonexistent `department`/`phone` fields.
4. Team dashboard now includes both reporters and photographers unless a role filter is explicitly selected.
5. Team target achievement reads the real `score_target` database column.
6. Admin/user and admin/team password validation is raised to 10 characters; corresponding UI validation was aligned.
7. Installer validates database/prefix identifiers before interpolation into SQL and no longer exposes raw MySQL connection errors.
8. Installer administrator password minimum is 10 characters.
9. Import currently accepts **CSV only** rather than falsely claiming XLS/XLSX support.
10. Removed an unused `/admin/activity-types/{id}/edit` route because the UI uses an inline modal and the controller has no separate edit page.
11. Corrected the test-seed activity count message (actual total is 311 rows).
12. Added `tests/full-e2e-test.ps1` for installed-environment end-to-end testing.

## Still requiring execution on the user's Windows/XAMPP machine

The supplied archive does not include access to the user's running Apache/MySQL instance, so HTTP/session/database execution against `http://ipna.local` cannot be performed from this analysis environment.

The supplied E2E script therefore must be run on the Windows machine. It exercises:

- admin login and all admin GET pages
- dashboard APIs
- activity CRUD
- activity-type CRUD
- monthly target save
- settings update
- reporter login and pages
- photographer login and pages
- API activity scoping / IDOR regression
- invalid CSRF rejection

Command:

```powershell
cd C:\Users\mhossein\Projects\IPNA-Journalist-CLONE
& "C:\xampp\php\php.exe" database\seed_test_data.php
powershell -ExecutionPolicy Bypass -File tests\full-e2e-test.ps1 -AdminUser "YOUR_ADMIN_USERNAME" -AdminPass "YOUR_ADMIN_PASSWORD"
```

## Test accounts created by the seed

- 10 reporters
- 5 photographers
- Password: `Test@12345`
- Username prefix: `ipna_test_`
- Activities span the current and previous month and the last 42 days.

## Important limitation

This is a **source-level repair + automated test harness**, not a claim that the running `ipna.local` instance has been modified. The archive can be copied over the project after a backup, or individual changed files can be applied.
