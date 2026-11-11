## Why

Clinical Case Managers and Clinical Quality Nurses currently have no calendar visibility into Patient Review Calls (PRCs) tied to Coverage shifts — only Training-shift PRCs are visible, via the "PRC Calendar – Recruiting" calendar delivered in the `ccr-calendar-recruiting-filters` change. A net-new "PRC Calendar – Coverage" calendar is needed so these users can locate and manage Coverage-shift PRCs the same way, without disrupting the existing Recruiting calendar.

## What Changes

- Add a net-new "PRC Calendar – Coverage" calendar, accessible from the sidebar alongside "PRC Calendar – Recruiting" (its own route and nav link — not a rename or replacement of the existing calendar). Access is gated by the existing "CCR Calendar" permission (no new permission created), so a single grant controls both calendars.
- Mirrors the Recruiting calendar exactly in filters (State/Branch/Assignee), role-based Assignee defaulting (Clinical Care Manager / Clinical Quality Nurse), empty-until-filtered gating, title behavior, and multi-PRC-per-slot display — the only functional difference is Shift Type scope: Coverage instead of Training.
- No "+" time-blocking button or functionality at all (net new, nothing to preserve).
- Generalizes the existing `CCRCalendarReadRepository` (shared by both calendars) to accept a shift-type parameter, fixing a join gap along the way: Coverage-shift `recruiting_schedule` rows have no `tracker_id`, so the patient join is corrected to go through `recruiting_schedule.aoc_patient_id` directly (matching the proven pattern already used by `ClinicalCaseManager::getClinicalCase()`), and the `candidate` display field gains the same Coverage-aware fallback (`coverage_tracker.new_clinician_name` / provider name / tracker candidate name) that method already uses. This is a **behavioral refinement** of the existing Recruiting calendar's query (should be a no-op there, converging on already-proven logic), not a new requirement for it.
- Extends the shared event-details modal (`CCRCalendarController::getEventDetails`, `resources/views/ccr/eventDetails.blade.php`) with a Coverage-specific branch (clinician name, coverage shift date/time) instead of duplicating a second modal.

## Capabilities

### New Capabilities
- `prc-coverage-calendar-filters`: Defines the State/Branch/Assignee filter behavior, role-based assignee defaulting, empty-until-filtered gating, Coverage-only scoping, and title behavior for the new Recruiting Coverage PRC calendar — structurally mirrors the `prc-calendar-filters` capability but scoped to Coverage shift type.

### Modified Capabilities
(none — `prc-calendar-filters` (Recruiting) is not gaining or losing requirements; its underlying query implementation is being shared/refactored, but its documented behavior is unchanged)

## Impact

- `app/Enums/PrcShiftType.php` — new enum (Training/Coverage).
- `app/Repositories/CCRCalendar/CCRCalendarReadRepository.php` — generalized to accept `PrcShiftType`, shared by both calendars; patient-join and candidate-display fixes applied.
- `app/Http/Controllers/CCRCalendarController.php` — updated call sites to pass `PrcShiftType::Training`; `getEventDetails()` extended (additive) with coverage_tracker join/fields.
- `app/Http/Controllers/CCRCoverageCalendarController.php` — new controller (`index`, `getWeekEvents`), reuses `CCRCalendarReadRepository` and `UserReadRepository`.
- `resources/views/ccrcoverage/calendar.blade.php` — new view.
- `resources/views/ccr/weekEvents.blade.php` — reused unmodified by both calendars.
- `resources/views/ccr/eventDetails.blade.php` — new Coverage-branch added, shared by both calendars.
- `routes/web.php` — new `ccrCoverageCalendar.*` route group.
- `resources/views/layouts/newSidebar.blade.php` — new nav link.
- `config/permissions.php` — no new permission group; `ccrCoverageCalendar.*` routes added to the existing `CCR Calendar` → `View Calendar` entry's `related` array. Requires `php artisan process:permissions` post-deploy to sync.
- No database schema changes, no feature flag.
