## 1. Database

- [x] 1.1 ~~Create migration for `ccr_availability_schedule`~~ — removed: no recurring weekly schedule in final scope (rescoped to branch-based, blocks-only)
- [x] 1.2 Add a new migration (`rescope_ccr_availability_to_facility`) rather than editing the original `ccr_availability_block`/`ccr_availability_schedule` migrations — they had already run against a test environment, so rewriting them in place would leave that environment's schema out of sync. The new migration drops `ccr_availability_schedule` and renames `ccr_availability_block.user_id` → `aoc_facility_id` (with a matching index swap and a `down()` that reverses both).
- [x] 1.3 Delete `App\AvailabilitySchedule`; update `App\AvailabilityBlock` fillable (`user_id` → `aoc_facility_id`)

## 2. Permission

- [x] 2.1 Add "Manage PRC Availability" entry under the `CCR Calendar` group in `config/permissions.php`, gating both `ccrAvailability.get` and `ccrAvailability.save` (no separate "self" tier)
- [x] 2.2 Document the required `php artisan process:permissions` post-deploy step in the PR/deploy notes

## 3. Backend: read/write availability

- [x] 3.1 `CCRAvailabilityController`: `get` (branch-scoped) and `save` (branch-scoped); removed `assignees` action (no assignee concept anymore)
- [x] 3.2 `CCRAvailabilityRepository`: `getBlocks(facilityId)`, `getBlocksForFacilityInRange(facilityId, start, end)`, `saveForFacility(facilityId, blocks, actingUserId)` (full-replace); removed schedule-related methods
- [x] 3.3 Removed `RolesEnum`/`UserReadRepository` assignee-role lookup — no longer applicable to a branch-scoped model
- [x] 3.4 Enforce permission check server-side via `AvailabilityRequest::authorize()` (single `H::canPermissions('ccrAvailability.manageOthers')` check, no per-assignee ownership logic)
- [x] 3.5 Routes in `routes/web.php`: `ccrAvailability.get`, `ccrAvailability.save` (removed `ccrAvailability.assignees`)

## 4. Validation

- [x] 4.1 ~~Weekly schedule validation~~ — removed, no weekly schedule
- [x] 4.2 Server-side: per blocked entry, require date (not in the past) + start + end, and require end > start
- [x] 4.3 Return validation errors in a shape the existing jQuery form-handling convention in `ccr/*` views can render inline/as a list

## 5. Manage Schedule modal (frontend)

- [x] 5.1 "+" icon with "Manage Schedule" tooltip on `resources/views/ccr/calendar.blade.php` (single merged PRC calendar, per the later AOC-5373 calendar-merge change), gated behind the Manage PRC Availability permission
- [x] 5.2 ~~Add the same icon to `ccrcoverage/calendar.blade.php`~~ — superseded: that view no longer exists after AOC-5373 merged both calendars into one
- [x] 5.3 Rewrite `manageSchedule.blade.php`: Branch dropdown (no default selection) + Blocked Availability repeatable rows + Save/Cancel; removed Assignee field and PRC Schedule section entirely
- [x] 5.4 ~~Assignee field self/others behavior~~ — removed, no assignee concept
- [x] 5.5 ~~PRC Schedule section~~ — removed, no weekly schedule
- [x] 5.6 Blocked Availability section: repeatable date/start/end/notes rows using `bootstrap-datepicker` (date) + `jquery.timepicker` (start/end, End-after-Start gating), "+ Add More", remove-row action, empty-state message — kept from original implementation, now scoped to the selected branch
- [x] 5.7 Wire Save to submit `facility_id` + full current Blocked Availability list; show returned validation errors inline; on success, close modal and refresh the calendar week view
- [x] 5.8 Wire modal open to show an empty/no-selection state until a branch is picked, then fetch that branch's data on `change`

## 6. Calendar rendering

- [x] 6.1 In `resources/views/ccr/weekEvents.blade.php`, render blocked-availability rows keyed to the calendar's existing branch filter as a red card labeled "BLOCKED - [Note Text]" (or without note text if blank) — no longer labeled with an assignee name
- [x] 6.2 Confirm existing scheduled PRC appointments continue to render unchanged in the same time slot alongside a BLOCKED card
- [x] 6.3 `CCRCalendarController::getWeekEvents()` now calls `getBlocksForFacilityInRange($branch, ...)` keyed off the existing branch filter, instead of the previous assignee-array lookup

## 7. Verification

- [ ] 7.1 Manual test: user without the Manage PRC Availability permission does not see the "+" icon at all
- [ ] 7.2 Manual test: user with the permission can select any branch, see/edit/save its blocked times independent of other branches
- [ ] 7.3 Manual test: setting the calendar's branch filter to a branch with saved blocks shows red BLOCKED cards; a different/no branch filter hides them
- [ ] 7.4 Manual test: validation errors block save for bad per-block time ranges or past dates
- [ ] 7.5 Manual test: removing a blocked entry and saving removes its red card
- [ ] 7.6 Manual test: existing scheduled PRC appointments are unaffected by newly saved blocks (both display in the same slot)
- [ ] 7.7 Confirm "Manage PRC Availability" appears and is assignable in Admin > Permissions/Role Management after running `php artisan process:permissions`
- [ ] 7.8 Confirm no changes to `recruiting_schedule`, existing PRC scheduling behavior, or the dormant `type_id=996` block-time feature
- [ ] 7.9 Confirm `ccr_availability_schedule` table no longer exists after running the new `rescope_ccr_availability_to_facility` migration (original migration file is left in place, superseded by the drop) and `ccr_availability_block` has `aoc_facility_id` (not `user_id`)
