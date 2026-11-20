# IPNA PMS — Audit / Fixes

## Phase 1 — Report contract stabilization

### Fixed
- Admin daily report view now consumes the actual `Report::getDailyReport()` contract (`summary`, `users`, `activities`).
- Daily report now includes the activity detail rows required by the existing view.
- Daily report summary now exposes `mission_count`.
- Daily report user filter uses `user_id` / `user_name` returned by the model.
- Weekly report now exposes a `summary` object expected by the existing view.
- Weekly report exposes `mission_count` per user and the view uses `user_name`.
- Monthly ranking view now uses the actual aliases returned by `Report::getTeamRanking()`.
- Admin dashboard 30-day chart now groups by `activity_date`, matching the date filter, instead of `created_at`.

## Important remaining item

The ZIP supplied for this audit does not contain `tests/full-e2e-test.ps1`, although that file exists in the user's local working copy. Therefore the PowerShell-specific failures (`HOME` overwrite and `Invalid JSON primitive`) cannot be safely patched from the supplied project archive yet.

No credentials were changed or included.
