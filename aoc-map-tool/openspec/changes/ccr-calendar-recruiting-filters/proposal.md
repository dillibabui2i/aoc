## Why

The Recruiting CCR Calendar (`ccr/calendar`) currently hardcodes its scope to a single state (`mssql_facilities.State = 'TX'`) and a single hardcoded assignee (`assigned_ccr = 537`), showing every scheduled record for that hardcoded slice regardless of shift type. Recruiters, Clinical Care Managers, and Clinical Quality Nurses across all states need to locate and manage their own Training-shift Patient Review Calls (PRCs) without relying on hardcoded values that only ever worked for one team. This change replaces the hardcoded scope with a configurable State/Branch/Assignee filter bar, restricts the calendar to Training-shift PRCs only, and removes the unused time-blocking affordance. (Renaming CCR/PEPR terminology to PRC is out of scope — already covered by the existing `prc-terminology` spec.)

## What Changes

- Add a **State** dropdown to the calendar filter bar, sourced the same way as the existing "Patient Review Calls" dashboard bucket (`CommonDatatableFilters::getFilterDataSource('state')`), no default selection.
- Add a **Branch** dropdown, populated only after a State is selected (reusing the existing `getstatebranch` state→branch cascade and `getFilterDataSource('aoc_facility_id')`).
- Add a searchable **Assignee** field, sourced from a merged Clinical Care Manager + Clinical Quality Nurse user list (new: `ConstantHelper::getClinicalCareManager()` covers Care Managers only today; Quality Nurses need to be included too).
  - If the logged-in user holds the Clinical Care Manager or Clinical Quality Nurse role, the field auto-selects that user on load.
  - For all other users, the field starts empty.
- **BREAKING (behavior change)**: the calendar shows no records until at least one filter (State, Branch, or Assignee) has a value — replacing today's hardcoded always-on TX/`assigned_ccr=537` scope. Filters are independent and combine with AND when more than one is set.
- Update the calendar header/title to reflect the currently selected State, updating live when the State filter changes.
- Restrict calendar records to Training-shift PRCs only, using the existing derived shift-type expression (`coverage_id` null/0 = Training, non-zero = Coverage) — Coverage-shift PRCs are out of scope (tracked separately for a future PRC Coverage calendar).
- Confirm/preserve that calendar entries are positioned using the PRC's own `recruiting_schedule.appointment_date`/`appointment_time`, never the Recruiter Tracker's proposed next-step date/time.
- Confirm/preserve that multiple PRCs scheduled for the same date/time all render within that time slot.
- Hide the "+" block-time button on the calendar. The underlying block-time code (`addEvent`/`saveEvent` routes, controller methods, modal view) is left in place, unused.
- No feature flag — ships unconditionally.

## Capabilities

### New Capabilities
- `prc-calendar-filters`: Defines the State/Branch/Assignee filter behavior, role-based assignee defaulting, empty-until-filtered gating, Training-only scoping, and title behavior for the Recruiting PRC Calendar.

### Modified Capabilities
(none — no existing spec currently governs this calendar's filtering/display behavior; `prc-terminology` governs display text only and is unaffected)

## Impact

- `app/Http/Controllers/CCRCalendarController.php` — `getWeekEvents()`, `fetchScheduleDetailsOfCCR()`: replace hardcoded `State = 'TX'` / `assigned_ccr = 537` / `type_id IN (2, 996)` with filter-driven query logic (State/Branch/Assignee, Training-shift-only, empty-filters-yield-no-results).
- `resources/views/ccr/calendar.blade.php` — add filter bar (reusing `components/datatable-filters.blade.php`), dynamic title, hide the "+" block-time anchor.
- `resources/views/ccr/weekEvents.blade.php` — remove hardcoded "Texas" header, render title from selected State; ensure multiple same-slot PRCs still render (verify, likely no change needed).
- `app/Helpers/ConstantHelper.php` — extend/add an assignee source covering both Clinical Care Manager and Clinical Quality Nurse roles.
- No database schema, route signature, or public API changes; no feature flag.
