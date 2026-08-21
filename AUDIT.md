# IPNA Journalist PMS — Technical Audit Report

**Scope:** full codebase (single commit `f712c8a`, no installed config present — fresh clone state). No files were modified during the audit. No secrets were found or printed.

---

## A. Executive Summary

This is a **custom no-framework PHP 8 MVC app** (Persian/RTL, PWA) for tracking journalists' output. The codebase is well-intentioned — PDO prepared statements almost everywhere, bcrypt, rate-limit scaffolding, audit logging, `.htaccess` protection of sensitive dirs, `.gitignore` correctly excludes generated configs, and git history contains no secrets.

**However, the application as committed is completely non-functional.** Three independent fatal bugs gate every page, and a fourth breaks first-time installation:

1. Route tables reference `Admin\UserController`-style names, but classes are declared as `Admin_UserController` → every `/admin`, `/reporter`, `/photographer`, `/api` route returns 500 "Class Not Found".
2. `views/layouts/main.php` and `views/auth/login.php` call `Helper::generateCsrfToken()` — that method exists on `Auth`, not `Helper` → **even the login page fatals**.
3. Four models call `Database::lastInsertId()`, a method that doesn't exist → every create flow (user, activity, activity-type, target) fatals.
4. `includes/init.php` requires `config/config.php` *before* the installer-redirect check → fresh clones fatal instead of showing the installer.

Even after fixing those, multiple **controller↔model↔schema field-name mismatches** break admin activity creation, target setting, activity-type management, CSV export columns, pagination, and several filters. On the security side: **CSRF tokens are rendered but never verified anywhere**, there are **unescaped output sinks in the layout** (stored-XSS capable), an **IDOR in the activities API**, and **no session cookie hardening**. The README's security claims (CSRF on all forms) do not match the code. There are **zero tests**. This codebase needs a repair pass before any deployment consideration; it reads like generated code that was never executed end-to-end.

---

## B. Critical Issues (app cannot run)

### B1. Router class-name mismatch — all namespaced routes 500
- **Severity:** Critical
- **File:** `public/index.php` (route tables, lines 115–219) vs. all `controllers/{Admin,Reporter,Photographer,Api}/*.php`
- **Problem:** Routes reference `'Admin\UserController'` etc.; `dispatch()` converts `\`→`/` only for the file path, then `class_exists('Admin\UserController')` fails because classes are declared as `Admin_UserController` (no namespaces).
- **Why it matters:** Every authenticated page (admin, reporter, photographer, all APIs) returns HTTP 500. Only `/login`, `/logout`, `/dashboard` dispatch.
- **Recommended fix:** Either change route keys to underscore names (`'Admin_UserController'`), or add real `namespace Admin;` declarations and a proper autoloader. Pick one convention; add a smoke test hitting every route.

### B2. `Helper::generateCsrfToken()` does not exist — login page and every layout page fatal
- **Severity:** Critical
- **File:** `views/layouts/main.php:145,180`, `views/auth/login.php:45` (method exists as `Auth::generateCsrfToken()`; global helper `csrf_token()` in `includes/init.php:52`)
- **Problem:** `Call to undefined method Helper::generateCsrfToken()` at render time.
- **Why it matters:** Nothing renders at all — including the login form. The app is 100% down.
- **Recommended fix:** Replace with `csrf_token()` (the existing global) in all three places.

### B3. `Database::lastInsertId()` undefined — all model `create()` methods fatal
- **Severity:** Critical
- **File:** `models/User.php:80`, `models/Activity.php:145`, `models/ActivityType.php:46`, `models/MonthlyTarget.php:61` (`includes/Database.php` has no such method; `Database::insert()` returns the id correctly)
- **Problem:** `Call to undefined method Database::lastInsertId()`.
- **Why it matters:** User creation, activity creation, activity-type creation, target creation, and CSV import (via `User::create`) all crash.
- **Recommended fix:** Add `public function lastInsertId(): string { return $this->pdo->lastInsertId(); }` to `Database`, or migrate callers to `Database::insert()`.

### B4. Fresh-clone bootstrap fatals before reaching the installer
- **Severity:** Critical
- **File:** `includes/init.php:10` (requires `config/config.php`) vs. install check at lines 13–36
- **Problem:** `config/config.php` is installer-generated and gitignored, so on a fresh clone `require_once` fatals before the "not installed → redirect to /install/" logic runs. README's "installer opens automatically" only works if `config.php` already exists.
- **Why it matters:** First-time deployment (the documented cPanel flow) fails with a blank/fatal page.
- **Recommended fix:** Make `init.php` tolerate a missing `config/config.php` (wrap the require, set safe defaults) and only *then* branch to the installer.

### B5. Admin activity creation is schema-incompatible
- **Severity:** Critical (core feature broken)
- **File:** `controllers/Admin/ActivityController.php:106–113` vs. `models/Activity.php:126–146` and `database/schema.sql:76–100`
- **Problem:** Controller sends `activity_type_id`, `source_url`, `created_by`; model reads `activity_type`, `title`, `link`. Result: undefined-index warnings + `NULL` into the NOT NULL `activity_type` column → PDOException.
- **Why it matters:** Admins cannot log activities; the form 500s.
- **Recommended fix:** Align field names (`activity_type`, `title` required, `link`) and drop non-existent columns.

### B6. Admin target setting silently writes zeros
- **Severity:** Critical (data integrity)
- **File:** `controllers/Admin/TargetController.php:46–66, 85–101, 146–155` vs. `models/MonthlyTarget.php:33–62` and schema `monthly_targets` (columns `news_target…score_target`)
- **Problem:** Controller sends `target_score`/`target_articles`/`target_photos`/`notes`/`created_by`; model only accepts `news_target…score_target` → every target saves as 0, and UI reads of `$target['target_score']` (e.g. `Admin/TeamController.php:34,39`) reference a non-existent column.
- **Why it matters:** The monthly-target feature — a headline feature — records garbage while reporting success.
- **Recommended fix:** Rename controller fields to the schema's (`score_target`, etc.) or remap in the model; add a round-trip test.

### B7. Activity-type management incompatible with schema
- **Severity:** Critical (admin feature broken)
- **File:** `controllers/Admin/ActivityTypeController.php:48–68, 134–170` vs. `models/ActivityType.php:34–69` and schema (categories ENUM `news|photo|video`; columns `code`,`score`)
- **Problem:** Controller validates categories `article|social_media|general` (impossible per ENUM), sends `score_per_unit`/`unit_label`/`sort_order`/`description` (non-existent columns), never sends required `code`. Create fatals on missing `code`; `updateScore()`/`toggleStatus()` pass keys the model's allowlist drops → silent no-ops reporting success.
- **Recommended fix:** Align controller↔model↔schema on `name/code/score/category/news|photo|video`.

---

## C. High-Priority Issues

### C1. CSRF is never verified on any route
- **Severity:** High (security)
- **File:** entire request path — `public/index.php` performs no CSRF check; `Auth::verifyCsrfToken()` is only called from dead code (`includes/Router.php:98`, `includes/Middleware.php:67` — grep confirms zero live callers)
- **Problem:** Forms render tokens (`csrf_token()`), JS sends `X-CSRF-TOKEN`, but no POST handler ever verifies them — login, logout, user CRUD, settings, import, activity CRUD are all cross-site requestable.
- **Why it matters:** Session-riding attacks can create admin users, change settings, delete data. README explicitly claims CSRF protection — a false assurance.
- **Recommended fix:** Add a global CSRF check for all non-GET requests in `public/index.php` before dispatch (verify `$_POST['csrf_token']` / `HTTP_X_CSRF_TOKEN`).

### C2. Unescaped output sinks in the layout → stored XSS
- **Severity:** High (security)
- **File:** `views/layouts/main.php:167` (`$currentUser['name']`), 199/206 (flash messages), 13 (`$title`); `views/auth/login.php:31,42` (flashes)
- **Problem:** These are echoed raw. `users.name` is admin- and CSV-import-controlled (`ImportController` inserts the `Name` column verbatim) → a payload like `<img onerror=…>` persists and executes on every page for affected sessions; list views otherwise escape diligently (`e()`), so the layout is the weak point.
- **Recommended fix:** Wrap all layout echoes in `e()`. Also audit `views/shared/personal-dashboard.php` (17 raw echoes, 0 escapes — currently unused dead code; escape or delete).

### C3. IDOR in activities API
- **Severity:** High (security / data exposure)
- **File:** `controllers/Api/ActivityController.php:24–28`
- **Problem:** If `?user_id=` is passed it is honored for any role; the "restrict to self" branch only runs when the param is absent. Any logged-in reporter can read any other user's activity list.
- **Recommended fix:** Force `user_id = current user` for non-admins regardless of the param.

### C4. No session cookie hardening or idle timeout
- **Severity:** High (security)
- **File:** generated `config/config.php` template in `install/process.php:186–203` (bare `session_start()`); `includes/Auth.php`
- **Problem:** No `session.cookie_httponly`, `cookie_secure`, `cookie_samesite`; the `session_timeout_minutes` setting exists in `system_settings` but is never read/enforced. XSS (C2) can steal the session cookie; sessions never expire server-side.
- **Recommended fix:** Emit `session_set_cookie_params(['httponly'=>true,'secure'=>https,'samesite'=>'Lax'])` before `session_start()`; implement idle-timeout enforcement in `Auth::check()` using the setting.

### C5. `debug.php` exposed at web root
- **Severity:** High (security, deployment-dependent)
- **File:** `debug.php`
- **Problem:** Dumps PHP version, `DOCUMENT_ROOT`, script paths, and whether DB credentials are set. (Its `dirname(__DIR__)` paths actually resolve *outside* the project, so its file listing is wrong — but the path disclosure stands.) Not `.htaccess`-protected; nothing routes around it.
- **Recommended fix:** Delete it, or gate it behind an env flag + IP allowlist; never deploy it.

### C6. Import pipeline broken in three independent ways
- **Severity:** High (feature broken + robustness)
- **File:** `controllers/Admin/ImportController.php`
- **Problem:** (1) `.xlsx/.xls` are accepted but parsed with `fgetcsv` (binary garbage) — `parseFile()` line 91–97 ignores the extension; (2) `generateUsername()` uses `transliterator_transliterate()` (line 273) requiring the **intl** extension, which the installer never checks → fatal on hosts without intl; (3) new-user creation inside import hits the `Database::lastInsertId()` fatal (B3); (4) the preview UI in `views/admin/import/index.php:214–221` expects `res.data`, but the only POST endpoint (`/admin/import/process`) returns counts, never `data` → preview is dead code.
- **Recommended fix:** Restrict to CSV (or add a real XLSX parser), add intl to installer checks, fix B3, wire or remove the preview.

---

## D. Medium/Low-Priority Issues (correctness & quality)

| # | Severity | File | Problem / Why it matters / Recommended fix |
|---|----------|------|--------------------------------------------|
| D1 | Medium | `controllers/Admin/ActivityController.php:24` + `models/Activity.php:105–111` | `$_GET['sort']` flows into `findAll()` — safe (whitelist blocks injection), **but** the default `'created_at DESC'` doesn't match whitelist entries (`'a.created_at DESC'`) → sorting is silently dead. Fix: whitelist in the controller, pass canonical keys. |
| D2 | Medium | `controllers/Admin/UserController.php:19` + `views/admin/users/index.php:15,21,63` | Offset computed but never passed to `findAll()` (all pages identical); view expects `$pagination`, `$search`, `$role_filter` — controller sends `$page`, `$filters` → broken pagination + undefined-variable warnings. Fix: pass offset; align variable names. |
| D3 | Medium | `controllers/{Reporter,Photographer}/ActivityController.php:185–206` + `models/Activity.php:152` | Update builds `activity_type_id` and `score` keys, but model's allowlist is `activity_type`, and recomputes score itself → **changing activity type on edit is silently ignored**. Fix: use `activity_type` key; drop the manual score computation. |
| D4 | Medium | `controllers/Admin/ActivityController.php:16`, `controllers/Api/ActivityController.php:30` vs. `models/Activity.php:78,190` | Filter key `activity_type_id` vs model's `activity_type` → type filter silently ignored in admin list and API. Fix: unify key name. |
| D5 | Medium | `controllers/Admin/ExportController.php:73–87, 104–117, 134–149, 164–175` | Reads `articles/photos/videos/social_media/target_score` — report queries produce `news_count/photo_count/video_count/score_target` → undefined-index warnings, empty CSV columns. Fix: map to real aliases. |
| D6 | Medium | `controllers/Admin/ExportController.php:180–198` | CSV formula injection: user-controlled names written via `fputcsv` without neutralizing leading `= + - @` → executes in Excel when admin opens export. Fix: prefix dangerous cells with `'`. |
| D7 | Medium | `includes/Database.php:insert/update/delete` | `$table` and column names interpolated directly into SQL. Currently all call sites use literals, but the API invites future injection. Fix: add identifier whitelist/quoting. |
| D8 | Medium | `controllers/Admin/UserController.php:166–172` | `update()` accepts `role`/`status` without whitelist validation (create validates roles; update doesn't). ENUM saves you in strict mode, truncates to `''` otherwise. Fix: validate on update too. |
| D9 | Medium | `models/Report.php:21+28, 95+102` | Duplicate `video_count` alias (category sum overwritten by `exclusive_video` code sum) → wrong video totals in daily/weekly reports. Fix: rename one alias. |
| D10 | Medium | `install/index.php:7,28` + `install/process.php` (`test-db`) + `install/.htaccess` | `?force=1` reinstall bypass; unauthenticated `test-db` is a DB-credential oracle; raw DB error messages reflected into the page. Post-install `.htaccess` uses Apache 2.2 syntax (`Deny from all`) while other dirs use 2.4 (`Require all denied`) → unintended 500s on 2.4. Fix: drop `force`, use `Require all denied`, consider deleting/renaming `install/` after install. |
| D11 | Low | `controllers/Admin/ExportController.php:33–35` | `weekly-` parsing reads `$parts[0..5]` after only checking `count >= 2` → undefined-index warnings on malformed input (no injection — bound params). Fix: strict regex validation. |
| D12 | Low | `controllers/BaseController.php:41–59` | `validate()`: the `numeric` rule is dead (`isset($rule['numeric'])` on a list array is never true); `min` on null triggers PHP 8.1 deprecations. Callers cast `(int)` anyway, so impact is limited. Fix: `in_array('numeric', $rule)`. |
| D13 | Low | `controllers/Reporter/ReportController.php:32` | Weekly report buckets by `created_at` instead of `activity_date` — inconsistent with every other report. Fix: use `activity_date`. |
| D14 | Low | `views/shared/pagination.php` + activity list views | Pagination partial expects `$baseUrl`/`$currentPage` nobody passes; drops active filters. Fix: standardize one pagination contract. |
| D15 | Low | `includes/Helper.php:15–21` | `sanitize()` HTML-encodes at *input* time (wrong layer; double-encoding risk). Unused in observed flows. Fix: deprecate; escape only at output via `e()`. |
| D16 | Low | `includes/Auth.php` vs `Middleware` | Two parallel authorization styles (`Middleware::admin()` vs `(new Auth())->requireRole()`); `requireRole` redirects wrong-role users to `/login` (loop-prone) vs Middleware → `/dashboard`. Fix: keep one. |
| D17 | Low | `install/process.php:84` | Password policy: minimum 6 chars. Fix: 10+ with basic complexity. |
| D18 | Low | schema `login_attempts` | Never purged → unbounded growth. Fix: periodic cleanup or windowed delete. |
| D19 | Low | `models/SystemSetting.php:21` | `json_decode` on every value — a plain string that is valid JSON scalar (e.g. `"123"`, `"true"`) silently changes type. Fix: only decode with a marker prefix. |
| D20 | Low | `public/manifest.json` + `public/` | Icons `assets/images/icon-*.png` don't exist; `404.php`/`500.php`/`offline.php` hardcode `href="/"` → broken under subdirectory `BASE_URL`. Fix: add icons or drop PWA icons; use `BASE_URL`. |

---

## E. Security Issues (consolidated view)

| ID | Severity | Issue | Status |
|----|----------|-------|--------|
| E1 | **High** | CSRF tokens never verified anywhere (C1) | ❌ Broken — README claim false |
| E2 | **High** | Stored XSS via layout: unescaped `$currentUser['name']`, flashes, `$title` (C2); DOM XSS sinks: `toast.innerHTML` with server messages (`app.js:214`), import preview `tr.innerHTML` with row fields (`views/admin/import/index.php:235–241`), installer reflecting error strings via `innerHTML` (`install/index.php:384–389, 523`) | ❌ |
| E3 | **High** | IDOR: `?user_id=` honored for non-admins (C3) | ❌ |
| E4 | **High** | Session hardening: no httponly/secure/samesite, no idle timeout despite setting existing (C4) | ❌ |
| E5 | **High** | `debug.php` path/config disclosure at web root (C5) | ❌ |
| E6 | Medium | CSV formula injection in exports (D6) | ❌ |
| E7 | Medium | Upload validation is extension-only; no size cap, no MIME check (mitigated: file never stored/executed, parsed in place) | ⚠️ |
| E8 | Medium | Installer `test-db` credential oracle + `?force=1` reinstall (D10) | ⚠️ |
| E9 | ✅ OK | SQL injection: all observed queries use bound params; only identifier interpolation is whitelisted or literal (D7) | ✅ (with caveat) |
| E10 | ✅ OK | Passwords: bcrypt via `password_hash`; `session_regenerate_id` on login; login rate-limiting (5/15 min/IP); audit logging wired into most mutations; `config/`, `database/`, `storage/` `.htaccess`-denied; generated configs gitignored; **no secrets in repo or git history** | ✅ |
| E11 | ⚠️ Note | API is session-cookie-only (no tokens) — acceptable for same-origin app, but there is no API rate limiting; team-wide stats visible to all roles by design | ⚠️ |

---

## F. Architecture Issues

1. **Two routers, one dead.** `includes/Router.php` (162 lines, with middleware + CSRF support) is loaded but never used; real routing is hardcoded arrays in `public/index.php`. This split is exactly why CSRF verification (implemented in the dead router) never runs.
2. **No autoloader / no namespaces**, yet route keys *pretend* to be namespaced — the root cause of B1. Class loading depends on `dispatch()`'s string munging.
3. **Three-way contract drift (controller ↔ model ↔ schema)** is systemic: B5, B6, B7, D3, D4, D5 all stem from it. Nothing validates that a form's fields map to model allowlists or table columns.
4. **Massive copy-paste duplication:** `Photographer/*` controllers are near-verbatim clones of `Reporter/*` (243 vs 246 lines); same for their views and report/target controllers. Every fix must be applied twice — D3 exists in both.
5. **Inconsistent authorization style** (Middleware vs Auth::requireRole, D16) and inconsistent response style (some actions JSON-only, some dual AJAX/HTML, some redirect).
6. **`BaseController` is thin; cross-cutting concerns (auth, CSRF, validation) are per-action boilerplate** — easy to forget, as demonstrated.
7. **No global exception/error handler.** Uncaught PDOExceptions (common given the mismatches) → blank 500 (display_errors=0), logged to PHP's default log; `storage/logs/` is never written to by any code — vestigial.
8. **N+1 queries:** `Admin/DashboardController` runs `getAchievement()` per target row; `Admin/TeamController` runs 2 queries per member; admin dashboard fires ~10+ queries per load. Tolerable at small scale, will degrade.
9. **Dead code:** `Router.php`, `Auth::register()`, `views/shared/personal-dashboard.php`, `requireAuth()/requireAdmin()` in `public/index.php`, `Activity::search()`, `Report::getPerformanceChart()`, installer table-prefix support (silently ignored by the app).
10. **Service worker caches all non-API GETs cache-first**, including authenticated HTML — stale UI after deploys and private pages persisted in Cache Storage on shared devices.
11. **Settings are write-only:** `session_timeout_minutes`, `max_login_attempts`, `maintenance_mode`, `score_multiplier` are stored but never read by runtime code (Auth uses hardcoded constants).
12. **Brittle installer schema parsing** (`explode(';')`) — fine for today's schema, blocks any future procedure/trigger.

---

## G. Recommended Architecture Improvements

1. **One router.** Delete `includes/Router.php` *or* promote it to the real router; keep exactly one route table. Move auth + CSRF into a dispatch-level pipeline instead of per-action boilerplate.
2. **Add a minimal autoloader + real namespaces** (`Admin\UserController`) or standardize on flat underscore names everywhere — then the B1 class of bug becomes impossible.
3. **Introduce a single source of truth for fields:** per-model `$fillable`/column maps used by both controllers and forms; consider generating admin forms from them. Kills the B5–B7/D3–D5 mismatch family.
4. **Add a tiny front-controller test harness** (even a curl/PowerShell smoke script hitting every route and asserting non-500) — would have caught B1, B2, B3 instantly.
5. **Deduplicate Reporter/Photographer** into one role-parameterized controller pair; keep role-specific views only where they differ.
6. **Global exception handler** that logs to `storage/logs/app-YYYY-MM-DD.log` and renders `public/500.php`; dev-mode toggle via config.
7. **Service layer for scoring/achievement** (currently split between model, controller, and report SQL) to keep tie-breaker and target logic in one place.
8. **Fix the service worker strategy**: network-first for HTML, cache-first only for static assets, versioned cache name, and exclude authenticated pages from persistent caches.
9. **Wire settings to runtime** or delete them from the settings UI; a `maintenance_mode` that does nothing is a trap.
10. Longer-term: Composer (autoload + PHPUnit + PHPStan), migrations instead of install-time schema dump, and a proper `.env` layer separating secrets from config.

---

## H. Recommended Security Improvements

1. **Enforce CSRF globally** in `public/index.php` for all non-GET requests (token from body or `X-CSRF-TOKEN` header) — one block fixes E1.
2. **Escape the layout sinks** (`main.php` name/flash/title, login flashes) with `e()`; switch JS toast/preview builders from `innerHTML` to `textContent`/`createElement` (E2).
3. **Fix the API IDOR** (E3) — non-admins always scoped to self.
4. **Harden sessions at creation** (installer template): httponly + secure (when HTTPS) + SameSite=Lax; enforce `session_timeout_minutes` in `Auth::check()`; consider rotating session id periodically (E4).
5. **Remove `debug.php`** from deployable code (E5); add it to deployment exclusion lists.
6. **Neutralize CSV formula injection** in exports (prefix `= + - @` cells) (E6).
7. **Upload hardening**: size cap, `finfo` MIME check, CSV-only until XLSX parsing is real (E7).
8. **Installer lockdown**: remove `?force=1`, use Apache 2.4 syntax, delete or deny `install/` after install, truncate reflected DB error details (E8/D10).
9. **Raise password minimum** to 10+; add `PASSWORD_DEFAULT` rehash checks on login.
10. **Add security headers app-wide** (CSP at least `default-src 'self' cdn.jsdelivr.net`, since Bootstrap/Chart.js come from CDN without SRI — consider `integrity` attributes), and purge `login_attempts` on a schedule.
11. **Restore README honesty**: security claims should match implementation; add a SECURITY section describing the trust model.

---

## I. Recommended Development Roadmap

**Phase 0 — Resuscitate (1–2 days).** Fix the five criticals (B1–B4, then B5–B7 contract alignment). Add the route smoke script. Goal: install → login → admin dashboard → reporter can log an activity, end to end.

**Phase 1 — Security baseline (2–3 days).** H1–H5 (CSRF, XSS sinks, IDOR, session hardening, debug.php removal). Then H6–H8.

**Phase 2 — Correctness (3–5 days).** D1–D10 (pagination, filters, export columns, report alias, import pipeline incl. intl check + preview wiring). Add integration tests per route against a throwaway MySQL schema.

**Phase 3 — Architecture (1–2 weeks).** G1–G7 (single router, autoloader/namespaces, field-map source of truth, dedup Reporter/Photographer, exception handler + real logging, service worker strategy, settings wiring). Introduce Composer + PHPStan level 3+.

**Phase 4 — Hardening & release.** Security headers + SRI, rate-limit APIs, purge jobs, deployment checklist (remove debug.php, deny install/, verify .htaccess), README/AGENTS.md reconciliation, performance pass on dashboard N+1s.

---

## J. Files That Should Be Changed First

| Priority | File | Change |
|---|---|---|
| 1 | `views/layouts/main.php` + `views/auth/login.php` | Replace `Helper::generateCsrfToken()` with `csrf_token()` (B2) + escape name/flash/title (C2) |
| 2 | `public/index.php` | Fix controller class references (B1); add global CSRF verification for non-GET (E1) |
| 3 | `includes/Database.php` | Add `lastInsertId()` (B3); quote/whitelist identifiers in `insert/update/delete` (D7) |
| 4 | `includes/init.php` | Tolerate missing `config/config.php` before installer check (B4) |
| 5 | `controllers/Admin/ActivityController.php` | Align create/update fields with `Activity` model + schema (B5); fix sort/filter keys (D1, D4) |
| 6 | `controllers/Admin/TargetController.php` | Map to `score_target`/`news_target`/… schema columns (B6) |
| 7 | `controllers/Admin/ActivityTypeController.php` | Align to `name/code/score/category` + `news|photo|video` (B7) |
| 8 | `controllers/Api/ActivityController.php` | Scope non-admins to own `user_id` (E3) |
| 9 | `debug.php` | Delete (E5) |
| 10 | `install/process.php` | Hardened session cookie params in generated config (E4); remove prefix pretense or implement it; add intl check to `install/index.php` |
| 11 | `controllers/Admin/ExportController.php` | Fix column aliases + CSV formula neutralization (D5, D6) |
| 12 | `controllers/Admin/UserController.php` + `views/admin/users/index.php` | Pass offset; reconcile `$pagination`/`$search`/`$role_filter` contract (D2) |

---

*Audit completed 2026-08-21. No files were created, modified, or deleted during the audit; no destructive commands were run; no secrets were printed (none exist in the repo — generated configs are absent and gitignored, verified against full git history).*
