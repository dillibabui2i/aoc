## 1. Shift Type discriminator & filtering

- [x] 1.1 Add `CCC Request` case to `app/Enums/PrcShiftType.php`
- [x] 1.2 Rewrite `CCRCalendarReadRepository::applyShiftType()` to branch on `tracker_id`/`coverage_id`/`source` per design.md (Training = `tracker_id` NOT NULL, Coverage = `coverage_id` NOT NULL, CCC Request = both NULL AND `source = 'ccc'`)
- [x] 1.3 Change `applyShiftType()` (and its call sites) to accept a collection/array of `PrcShiftType` instead of a single value; empty/null selection = no filter applied (show all)
- [x] 1.4 Update `app/DTO/PrcCalendarFilterDTO.php` to hold an array of shift types instead of one
- [x] 1.5 Update request validation that builds `PrcCalendarFilterDTO` (currently split between `CCRCalendarController::getWeekEvents` inline construction and `GetCoverageCalendarWeekEventsRequest::toFilterDTO`) to accept and validate an array of shift type values

## 2. Merge Coverage data into the surviving Recruiting calendar

- [x] 2.1 Remove the `PrcShiftType::Coverage`-only construction in `CCRCalendarController::getWeekEvents` so it queries all types by default, filtered only by the new Shift Type filter parameter
- [ ] 2.2 Verify `CCRCalendarReadRepository::getScheduleDetailsForDate` and `getWeekEvents` return Coverage- and CCC-originated rows correctly under the new discriminator (manual query check against real data) — **not done**: no DB available in this environment; needs a manual check against real data before/at deploy
- [x] 2.3 Confirm `ccr/eventDetails.blade.php` and `ccr/manageSchedule.blade.php` render correctly for Coverage- and CCC-originated records (no controller-specific assumptions leak in) — confirmed by inspection: both branch on `coverage_id`/`tracker_id` presence (data-driven), not on which controller/route was used

## 3. Shift Type filter UI

- [x] 3.1 Add a multi-select Shift Type filter control (Training / Coverage / CCC Request) to `resources/views/ccr/calendar.blade.php`
- [x] 3.2 Wire the filter control's selection into the existing `getWeekEvents()` jQuery AJAX call as an array parameter
- [x] 3.3 Ensure default (nothing selected) state requests/display all Shift Types
- [x] 3.4 Ensure clearing the filter re-requests all Shift Types

## 4. Grid clamp consolidation

- [x] 4.1 Extract the duplicated 8am/5pm hour-clamping logic (currently in `CCRCalendarController`, `CCRCoverageCalendarController`, and `CCRAvailabilityRepository::bucketBlocksByHourAndDay`) into a single shared helper — added `Calendar::clampToHourBucket()`
- [x] 4.2 Update `CCRCalendarController` and `CCRAvailabilityRepository` to use the shared helper
- [ ] 4.3 Confirm no behavior change: 7:30am record still renders in the 8am row, records at/after 5pm still render in the 5pm row — **not done**: requires manual/browser verification against real data, not performed in this session

## 5. Remove the Coverage calendar

- [x] 5.1 Delete `app/Http/Controllers/CCRCoverageCalendarController.php`
- [x] 5.2 Delete `resources/views/ccrcoverage/` (calendar.blade.php and any partials unique to it)
- [x] 5.3 Remove `ccr-coverage/*` route definitions from `routes/web.php` (`ccrCoverageCalendar.calendar`, `ccrCoverageCalendar.getWeekEvents`, `ccrCoverageCalendar.getEventDetails`)
- [x] 5.4 Remove `app/Http/Requests/CCRCalendar/GetCoverageCalendarWeekEventsRequest.php` — fully superseded by task 1.5's unified request validation
- [x] 5.5 Remove the "PRC Calendar – Coverage" entry from `resources/views/layouts/newSidebar.blade.php`
- [x] 5.6 Search for any remaining references to `ccrCoverageCalendar.*` routes/permission key (views, JS, seeders, tests) and remove/update them — also removed the three `ccrCoverageCalendar.*` entries from the `CCR Calendar > View Calendar` permission's `related` array in `config/permissions.php`; **requires `php artisan process:permissions` post-deploy** to sync into the `permissions` table (same requirement noted when these were originally added)

## 6. Verification

- [ ] 6.1 Manually verify: navigating to the PRC Calendar shows Training, Coverage, and CCC Request records together with no filter applied — **not done**: requires running app/browser, not available in this session
- [ ] 6.2 Manually verify: selecting one Shift Type shows only matching records; selecting multiple shows their union; clearing shows all again — **not done**: same reason
- [ ] 6.3 Manually verify: a record at 7:30am renders in the 8am row; a record at/after 5pm renders in the 5pm row, for both PRCs and Blocked Availability cards — **not done**: same reason
- [ ] 6.4 Manually verify: Manage Schedule Plus entry point and modal still work unchanged from the merged calendar — **not done**: same reason
- [ ] 6.5 Run relevant test suite / static analysis per repo commands and confirm clean — **partially done**: `php -l` syntax-checked all changed PHP files (clean); `vendor/bin/phpstan analyse` could not run in this environment (larastan bootstrap requires a live DB connection to a host unreachable here)
