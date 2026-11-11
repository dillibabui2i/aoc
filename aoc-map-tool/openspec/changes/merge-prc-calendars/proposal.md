## Why

PRC availability and scheduled PRCs are currently split across two separate, never-released calendars (Recruiting > PRC Calendar and Coverage > PRC Calendar), with no way to see both together and no way to tell CCC-requested PRCs apart from either. Merging them into one calendar with a Shift Type filter gives Recruiting, Coverage, and CCC a single unified view, and fixes a latent bug where CCC-created PRC requests are silently mislabeled as Training records today.

## What Changes

- **BREAKING**: Delete the Coverage PRC Calendar entirely — `CCRCoverageCalendarController`, its routes (`ccr-coverage/*`), and `resources/views/ccrcoverage/calendar.blade.php`. It was never released, so no redirect/back-compat shim is needed.
- The original Recruiting PRC Calendar (`ccrCalendar.calendar` route, `CCRCalendarController`, `resources/views/ccr/calendar.blade.php`) becomes the single merged "PRC Calendar", now showing Training, Coverage, and CCC Request records together by default.
- Remove the "PRC Calendar – Coverage" sidebar entry; the remaining "PRC Calendar" entry is the only calendar nav item.
- Add a `CCC Request` case to `app/Enums/PrcShiftType.php` (previously only `Training`/`Coverage`).
- Rework `PrcShiftType` discrimination to use existing columns with no schema change: `Training` = `tracker_id` NOT NULL, `Coverage` = `coverage_id` NOT NULL, `CCC Request` = both NULL and `source = 'ccc'`. This corrects today's bug where CCC-created rows (always `coverage_id` NULL) fall into the `Training` bucket.
- Add a Shift Type filter control (Training / Coverage / CCC Request) to the merged calendar view, supporting multi-select; default (no selection) shows all types. Requires `PrcCalendarFilterDTO`/`applyShiftType()` to accept a set of shift types instead of one.
- Consolidate the duplicated 8am–5pm grid-hour clamping logic (currently duplicated in `CCRCalendarController`, `CCRCoverageCalendarController`, and `CCRAvailabilityRepository`) into one shared implementation used by the merged flow. No behavior change — the grid already renders only 8am–5pm (`config/constant.php`) and already clamps early/late times into the edge slot.
- Retain the Recruiting calendar's dead search box as-is; not in scope.

## Capabilities

### New Capabilities
- `prc-calendar`: The merged PRC Calendar's own behavior — single entry point, Shift Type filter (multi-select, default-all), and the 8am–5pm grid display window with edge-clamping for out-of-range times.

### Modified Capabilities
(none — `prc-scheduling` and `prc-terminology` requirements are unaffected; PRC records still end up visible on "the PRC Calendar" as those specs require, just on the merged one)

## Impact

- **Code removed**: `app/Http/Controllers/CCRCoverageCalendarController.php`, `resources/views/ccrcoverage/calendar.blade.php`, `ccr-coverage/*` routes in `routes/web.php`, the "PRC Calendar – Coverage" entry in `resources/views/layouts/newSidebar.blade.php`.
- **Code modified**: `app/Enums/PrcShiftType.php`, `app/Repositories/CCRCalendar/CCRCalendarReadRepository.php` (`applyShiftType`), `app/DTO/PrcCalendarFilterDTO.php`, `app/Http/Controllers/CCRCalendarController.php`, `resources/views/ccr/calendar.blade.php`, `resources/views/ccr/weekEvents.blade.php` (filter UI), `app/Repositories/CCRAvailability/CCRAvailabilityRepository.php` (dedup clamping).
- **Routes removed**: `ccrCoverageCalendar.calendar`, `ccrCoverageCalendar.getWeekEvents`, `ccrCoverageCalendar.getEventDetails`. `ccrCalendar.*` routes are unchanged at the identifier level.
- **Permissions**: The `ccrCoverageCalendar.calendar` permission gate becomes unused; no new permission needed since `ccrCalendar.calendar` already gates the merged view. Flagging per repo convention since this touches permission-gated routes, even though no permission schema change is required.
- **No data model changes**: `source`, `tracker_id`, `coverage_id` columns already exist; only query/enum logic changes.
