## 1. Assignee data source

- [x] 1.1 Add `UserReadRepository::getActiveByRoles(array $roleIds): Collection`, returning active users holding any of the given roles via the existing `User::roles()` `BelongsToMany` relation (`whereHas('roles', ...)`, keyed by `RolesEnum` ids) — not `ConstantHelper`, which the team doesn't want to keep growing. `CCRCalendarController` maps the result to `{value, text}` for the Assignee field, passing `[RolesEnum::CLINICAL_CARE_MANAGER->value, RolesEnum::CLINICAL_QUALITY_NURSE->value]`.
- [x] 1.2 Add `UserReadRepository::userHasAnyRole(int $userId, array $roleIds): bool`, same relation/role-id approach, for the auto-default logic.

## 2. Filter bar UI

- [x] 2.1 In `resources/views/ccr/calendar.blade.php`, add State/Branch/Assignee filters stacked in the left `col-md-2 pull-left` column, above the search box — matching the visual placement used by sibling calendars (`intake`, `ipea`, `trachventclass`, `afterhours`), rather than the shared `components.datatable-filters` full-width bar. State/Branch are plain `<select class="form-control">`s. Assignee is a plain `<input class="form-control">` backed by a native HTML5 `<datalist>` (plus a hidden input holding the resolved user id) — after a bootstrap-multiselect single-select attempt showed inconsistent width/popup-centering behavior in this layout, this native approach was used instead since it matches the other filters' exact visual weight with zero plugin behavior, while still providing type-ahead search. No default State/Branch selection.
- [x] 2.2 Wire the State→Branch cascade using the existing `getstatebranch` AJAX pattern (as used in `ccmdashboard.blade.php`) so Branch options repopulate when State changes.
- [x] 2.3 On page load, if the logged-in user holds Clinical Care Manager or Clinical Quality Nurse (task 1.2), pre-select that user in the Assignee filter and auto-submit the filter form; otherwise leave all filters unset and do not auto-submit.
- [x] 2.4 Hide the "+" block-time anchor and its trigger in `calendar.blade.php` (do not remove `addCCREvent()`, the `addEvent`/`saveEvent` routes/controller methods, or `ccr/addEvent.blade.php`).
- [x] 2.5 When neither State nor Assignee has a value, do not call the `getWeekEvents` AJAX endpoint at all — show a static guidance message ("Select a State or Assignee from the drop down box to the left", `map-intro-text` class, matching the IPEA calendar's empty-state pattern) in place of the weekly grid instead. Gate this inside the shared `getWeekEvents()` JS function so every call site (initial load, filter changes, date navigation, search) benefits automatically.
- [x] 2.6 Hide the free-text "Search for" field (`#ipeaCalendarSearch`, its label, and the "Clear" link) — it's unrelated to the State/Branch/Assignee filters and isn't part of the ticket's requirements. Hide only (`style="display:none"` on the `.search-cnt` wrapper), don't remove the markup/JS; the functional hidden `#date` input (tracks the currently viewed week) was moved outside that wrapper so it keeps working independent of the search UI's visibility.

## 3. Calendar title

- [x] 3.1 Replace the hardcoded "Texas" header in `resources/views/ccr/weekEvents.blade.php` with the currently selected State's display name.
- [x] 3.2 Update the title client-side (no full page reload) when the State filter changes.

## 4. Query filtering logic

- [x] 4.1 In `CCRCalendarController::getWeekEvents()`, replace the hardcoded `mssql_facilities.State = 'TX'` and `assigned_ccr = 537` conditions with filter-driven WHERE clauses for State, Branch, and Assignee. For Assignee, join `users` via the same single-value resolution CASE `ClinicalCaseManager::getClinicalCase()` uses for its display column (`assigned_ccr` if set, else — behind `assign_ccm_to_coverage_tracker` — `recruiting_schedule.ccm_id`, else `mssql_patients.ccm_id`), and filter with `whereIn('users.id', $assignee)` against that single resolved value rather than an independent OR across `ccm_id`/`assigned_ccr` (a record has exactly one assignee, not "any column that happens to match"). Also select `users.name as ccm_name` for the badge (task 4.6).
- [x] 4.2 Apply the same filter/join logic to `CCRCalendarController::fetchScheduleDetailsOfCCR()`.
- [x] 4.3 Change the `type_id` scope from `IN (2, 996)` to `type_id = 2` only (drop block-time entries from the query since the button is hidden and no new block-time entries should be surfaced), and add the Training-shift condition (`ifnull(recruiting_schedule.coverage_id,0) = 0`).
- [x] 4.4 Short-circuit both endpoints to return no records when State, Branch, and Assignee are all empty/unset, instead of falling through to an unfiltered or default query.
- [x] 4.5 Verify (and adjust if needed) that PRC entries are positioned using `recruiting_schedule.appointment_date`/`appointment_time`, not any Recruiter Tracker next-step field.
- [x] 4.6 In `resources/views/ccr/weekEvents.blade.php`, render a small badge (`badge badge-info`) with `$schedule->ccm_name` on each event card so the assigned CCM/CQN stays visible when filtering by State/Branch alone; no badge when the resolution yields no match.

## 5. Multi-record time slots

- [x] 5.1 Verify `weekEvents.blade.php` renders every matching PRC within a shared time slot without truncation now that block-time entries are excluded from the query; adjust the loop/markup only if a real gap is found.

## 7. Code review cleanup

- [x] 7.1 Extract the two large query-building blocks out of `CCRCalendarController` into a new `App\Repositories\CCRCalendar\CCRCalendarReadRepository` (`getWeekEvents()`, `getScheduleDetailsForDate()`, plus private `joinResolvedAssignee()`/`applyFilters()` shared helpers), following this codebase's existing `<Domain>ReadRepository` convention. Controller now only parses the request, calls the repository, and groups results for the view.
- [x] 7.2 Use `ScheduleTypes::CCR->value` instead of the literal `'2'`/`2` type_id everywhere it appears (both repository methods).

## 6. Verification

- [ ] 6.1 Manually verify: no filters set → empty calendar; State only → all Training PRCs in that state; Assignee only → all Training PRCs for that person; combined filters → intersection.
- [ ] 6.2 Manually verify: Clinical Care Manager and Clinical Quality Nurse users see themselves pre-selected with their own records on load; other roles see an empty filter bar and empty calendar.
- [ ] 6.4 Manually verify assignee matching parity: pick a CCM/CQN whose PRC is resolvable only via `ccm_id` (not `assigned_ccr`) and confirm it appears — with the correct badge — both in the "Patient Review Calls" dashboard bucket and on the calendar when filtered/defaulted to that user. Also verify the priority case: a record with `assigned_ccr = A` and client `ccm_id = B` shows badge "A" and appears only when filtering by A, not B.
- [ ] 6.3 Manually verify: Coverage-shift PRCs never appear regardless of filters; PRC scheduled date/time (not next-step date/time) determines calendar position; multiple same-slot PRCs all render; "+" button is not visible anywhere on the page.
