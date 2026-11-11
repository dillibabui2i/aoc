## Context

The PRC calendar (`ccr/calendar.blade.php`, week grid rendered by shared partial `ccr/weekEvents.blade.php`) is a legacy Blade + jQuery view — a server-rendered weekly `<table>` re-fetched via AJAX. PRC appointments live in the large shared `recruiting_schedule` table. There is no Vue/SPA involved. (An earlier version of this app had separate Recruiting/Coverage PRC calendars; they were merged into one calendar by a later, unrelated change — AOC-5373 — after this ticket's initial implementation.)

An existing "block time" feature (`CCRCalendarController::addEvent`/`saveEvent`, `type_id=996` rows in `recruiting_schedule`, and a "Manage Blocked Times" permission pointing at a nonexistent route) was investigated as a possible reuse candidate. It is confirmed dormant/unreachable in production UI and has no branch/recurrence concept at all. Per explicit direction, it is left untouched.

**Rescope**: this ticket originally modeled availability per-user (CCM/Clinical Quality Nurse role), including a weekly recurring schedule. Business changed the requirement before shipping: availability is now scoped to the **branch/facility** (`mssql_facilities`, referenced elsewhere in the app as `aoc_facility_id` — e.g. `mssql_patients.aoc_facility_id`, and already used as the PRC calendar's own branch filter). There is no recurring weekly window anymore, only one-off blocked times. Because a branch has no inherent "who manages it," there's no meaningful self/others distinction — access is gated by a single permission instead of a per-assignee ownership check.

Permissions in this app are config-driven (`config/permissions.php`, synced to the DB via `php artisan process:permissions`), not Spatie — this is a hard constraint on how the new permission is registered.

## Goals / Non-Goals

**Goals:**
- Let a user holding the "Manage PRC Availability" permission record one-off blocked times for any branch.
- Persist this per branch (one record set, not per user, not per calendar) so a follow-on ticket can enforce it at scheduling time using the patient's own `aoc_facility_id`.
- Surface blocked entries as red, display-only "BLOCKED" cards on the PRC calendar week grid, keyed to the calendar's existing branch filter.
- Ship independently of enforcement — saving availability has zero effect on scheduling behavior in this change.

**Non-Goals:**
- No recurring weekly schedule (explicitly dropped from scope).
- No per-user availability of any kind.
- No enforcement of availability at the PRC date/time picker or any scheduling endpoint (follow-on ticket).
- No changes to `recruiting_schedule`, existing PRC appointment behavior, or any other calendar.
- No refactor/removal of the dormant `type_id=996` block-time feature or the orphaned "Manage Blocked Times" permission (unrelated, pre-existing).

## Decisions

**Single table, blocks only.** `ccr_availability_block`: `id`, `aoc_facility_id`, `block_date`, `start_time`, `end_time`, `note` (nullable text), `created_by`, `updated_by`, timestamps. Indexed on (`aoc_facility_id`, `block_date`). The originally-planned `ccr_availability_schedule` table is dropped entirely. Neither table had reached production, but both had already run against a test environment, so the original migrations are left untouched (already-applied migrations don't get re-run there) — the rescope is applied via a new migration instead.

**Save is a full replace per branch.** On Save, the modal submits the selected branch's entire current Blocked Availability list. The backend deletes all existing `ccr_availability_block` rows for that `aoc_facility_id` and re-inserts what was submitted, in a transaction.

**Single permission, no per-branch ownership.** `Manage PRC Availability` (`config/permissions.php`, `CCR Calendar` group) gates both the get and save endpoints outright — there is no "view/edit your own" fallback, since a branch isn't "owned" by the logged-in user the way availability used to be modeled per-CCM. The modal's plus-icon entry point is itself hidden from users without the permission (`@canPermission`), since the popup has nothing useful to do without it.

**Calendar rendering reuses the existing branch filter.** The PRC calendar already filters by branch (`mssql_patients.aoc_facility_id`, via `PrcCalendarFilterDTO`/`CCRCalendarReadRepository`). The blocked-card overlay in `weekEvents.blade.php` now keys off that same `$branch` value (`CCRCalendarController::getWeekEvents()` calls `getBlocksForFacilityInRange($branch, ...)`) instead of the previous per-assignee lookup — no new filter UI needed, and the identifier space (`aoc_facility_id`) is already shared with the calendar's own filtering.

**No branch dropdown auto-select.** Unlike the original per-user design (which defaulted the Assignee field to "self"), the Branch dropdown opens unselected; the Blocked Availability section stays hidden until a branch is chosen.

**Validation: server-authoritative, mirrored client-side for UX.** Per blocked entry: date required and not in the past, start/end both required, end > start. Server validates on save regardless of client-side checks (legacy jQuery form, no framework-level form validation to lean on).

## Risks / Trade-offs

- **[Full-replace-on-save loses row-level audit history]** → Acceptable: `created_by`/`updated_by` + timestamps on the current rows are sufficient for this ticket's scope.
- **[Blocked-card overlay only shows when a branch filter is active]** → Accepted: matches the existing calendar filter model (no filter = no filtered subset shown); consistent with how the branch filter already gates other calendar behavior.
- **[New permission requires a manual `php artisan process:permissions` run post-deploy]** → Mitigation: call this out explicitly in tasks.md and the deploy/summary notes.
- **[Modal is legacy jQuery, not Vue]** → Accepted: matches surrounding code (CLAUDE.md: follow surrounding style even if imperfect).
- **[Race between two people saving the same branch's availability concurrently]** → Accepted as out of scope; full-replace-on-save means last write wins.

## Migration Plan

1. New migration (`rescope_ccr_availability_to_facility`) that drops `ccr_availability_schedule` and renames `ccr_availability_block.user_id` → `aoc_facility_id`, with a `down()` that reverses both. The original two migrations are left as-is (already applied on a test environment).
2. Add the `Manage PRC Availability` permission entry to `config/permissions.php`.
3. Deploy code (migration + permission config + controller/routes/views).
4. Run `php artisan process:permissions` post-deploy to sync the new permission into the `permissions` table so it becomes assignable in Admin > Permissions/Role Management.
5. Rollback: standard migration `down()` drops the table; removing the config entry and re-running `process:permissions` removes the permission.
