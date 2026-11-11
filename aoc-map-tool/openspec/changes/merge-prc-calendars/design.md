## Context

Two legacy Blade/jQuery calendars exist for PRC scheduling:

- **Recruiting > PRC Calendar** — route `ccrCalendar.calendar`, `CCRCalendarController`, view `resources/views/ccr/calendar.blade.php`. Original CCR calendar, never renamed at the identifier level per `prc-terminology`.
- **Coverage > PRC Calendar** — route `ccrCoverageCalendar.calendar`, `CCRCoverageCalendarController`, view `resources/views/ccrcoverage/calendar.blade.php`. Newer, never released.

Both share the real logic: `CCRCalendarReadRepository`, `PrcCalendarFilterDTO`, the `ccr/weekEvents.blade.php` grid partial, `ccr/eventDetails.blade.php`, and `ccr/manageSchedule.blade.php`. The only difference is a single `match()` on `PrcShiftType` (`Training` vs `Coverage`) inside `CCRCalendarReadRepository::applyShiftType()`, keyed off `recruiting_schedule.coverage_id`.

Separately, `AOC-3729` added a third, currently-unlabeled category: CCC-initiated PRC requests (`ClinicalCompetencyReviewService::save()`), which set `source = 'ccc'`, `tracker_id = null`, `coverage_id = null`. Because `applyShiftType()` only checks `coverage_id`, these rows currently fall into the `Training` bucket on the Recruiting calendar — a mislabeling bug this change also fixes as a byproduct of adding the third bucket.

The grid already only renders 8am–5pm (`config('constant')['weeklyTime']`) and already clamps out-of-range event times into the 8am/5pm edge slot. This logic is duplicated in `CCRCalendarController`, `CCRCoverageCalendarController`, and `CCRAvailabilityRepository::bucketBlocksByHourAndDay()`.

The Coverage calendar was never released, so there are no external bookmarks, saved links, or production usage to preserve — it can be deleted outright rather than redirected.

## Goals / Non-Goals

**Goals:**
- One calendar, one route, one controller, showing Training + Coverage + CCC Request PRC records together by default.
- A multi-select Shift Type filter (Training / Coverage / CCC Request), default = all shown.
- Correct, non-overlapping classification of the three shift types using existing columns only (`tracker_id`, `coverage_id`, `source`) — no schema change.
- Single, de-duplicated implementation of the 8am–5pm grid clamp.
- Clean removal of the Coverage calendar's controller/routes/view/nav entry.

**Non-Goals:**
- No changes to the Manage Schedule modal, the PRC Schedule/Blocked Availability data model, or date/time-picker enforcement logic (per proposal).
- No change to `ccrCalendar.*` route names/permission keys (per `prc-terminology`'s existing constraint).
- No visual/library change to the calendar (stays server-rendered Blade + jQuery; not migrating to Vue/FullCalendar).
- Not fixing or touching the dead search box on the Recruiting shell.

## Decisions

**Discriminator logic — derive shift type from `tracker_id`/`coverage_id`/`source`, not a stored column.**
Confirmed exhaustively (all `type_id=2` `RecruitingSchedule::create` call sites) that within CCR-type rows:
- Training rows always set `tracker_id` (never `coverage_id`).
- Coverage rows always set `coverage_id` (never `tracker_id`).
- CCC rows set neither, but always set `source = 'ccc'`.

New `applyShiftType()` (or its multi-value successor) branches as:
```
Training    -> tracker_id IS NOT NULL
Coverage    -> coverage_id IS NOT NULL (and != 0)
CCC Request -> tracker_id IS NULL AND coverage_id IS NULL AND source = 'ccc'
```
Alternative considered: add a real `shift_type` enum column and backfill. Rejected — proposal explicitly forbids data model changes, and the derived signal is exhaustively correct for every existing write path.

**Filter accepts a set, not a single enum value.**
`PrcCalendarFilterDTO::$shiftType` (currently a single `PrcShiftType`) becomes a collection/array of `PrcShiftType` (or nullable = "all"). `applyShiftType()` becomes a `whereIn`-style OR of the matched branches for whichever types are selected; when the set is empty/null, no shift-type filter is applied (all three shown). This is the one behavior-bearing DTO/repository change; both the merged controller's request validation and JS filter control must pass an array.

**Surviving controller/route: the original Recruiting one (`CCRCalendarController` / `ccrCalendar.*`).**
Per `prc-terminology`'s existing requirement not to rename `ccrCalendar.*` identifiers, and because it's the older, real calendar, it becomes the merged calendar. `CCRCoverageCalendarController`, its routes, and `ccrcoverage/calendar.blade.php` are deleted outright — not redirected — since the Coverage calendar was never released.

**Clamp logic: consolidate into one place, reused by the merged flow.**
Extract the existing `hour <= 8 → '8am'`, `hour >= 17 → '5pm'`, else `date('ga', ...)` bucketing (currently copy-pasted in two controllers plus `CCRAvailabilityRepository`) into a single shared helper (e.g. a method on `CCRCalendarReadRepository` or a small trait/helper used by both the schedule-event and blocked-availability bucketing paths). No behavior change — this is a dedup enabled by deleting the Coverage controller, not a new requirement.

**Sidebar: drop the second nav entry, don't relabel.**
Remove the "PRC Calendar – Coverage" `newSidebar.blade.php` entry entirely rather than keeping a second link to the same route (which would be confusing and redundant).

## Risks / Trade-offs

- **[Risk]** The `tracker_id`/`coverage_id`/`source` discriminator is convention-enforced, not DB-constrained — a future new PRC-creation path that forgets to set one of these could silently misclassify. → **Mitigation**: none required for this change (matches existing risk level of `applyShiftType()` today); note in code comment at the new `applyShiftType()` for future maintainers.
- **[Risk]** Deleting `CCRCoverageCalendarController`/routes could break something if the "never released" assumption is wrong (e.g. hidden bookmark, QA reliance). → **Mitigation**: confirmed with product owner (per conversation) this was never released; verify no permission-seeded links or automated tests reference `ccrCoverageCalendar.*` before removing.
- **[Trade-off]** Keeping the discriminator derived (not a stored column) means every future PRC-calendar query must repeat the three-way `tracker_id`/`coverage_id`/`source` logic rather than a single indexed enum column. Accepted per proposal's "no data model changes" constraint.

## Migration Plan

1. Add `CCC Request` case to `PrcShiftType`, rewrite `applyShiftType()`/discriminator to the three-way logic and multi-select support.
2. Update `PrcCalendarFilterDTO` and the Recruiting request validation to accept an array of shift types.
3. Merge Coverage's data access into `CCRCalendarController`/`CCRCalendarReadRepository` (no separate merge needed at the DB level — it's the same `recruiting_schedule` table, just no longer split by controller).
4. Add the Shift Type filter control to `ccr/calendar.blade.php` + its `getWeekEvents()` AJAX call.
5. Consolidate the 8am/5pm clamp helper; update `CCRAvailabilityRepository` and the surviving controller to use it.
6. Delete `CCRCoverageCalendarController`, `ccr-coverage/*` routes, `resources/views/ccrcoverage/`, and the sidebar "PRC Calendar – Coverage" entry.
7. Manual verification: Training/Coverage/CCC records all render on one calendar; filter combinations show correct subsets; an out-of-hours record (e.g. 7:30am) renders in the 8am row.

No rollback beyond standard git revert — no data migration, no irreversible state change.

## Open Questions

- None outstanding; all prior ambiguities (discriminator fields, clamp behavior, redirect necessity) were resolved during exploration.
