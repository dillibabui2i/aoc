## Why

Recruiters currently schedule a PRC (Patient Review Call — internally still coded as "CCR") as a disconnected step: either by hand-picking "CCR" from the Interview Type dropdown on the Schedule Next Step form, or by letting the system auto-create a placeholder CCR record using a heuristic date ("next step date minus one day") with no real recruiter input. The recruiting process has changed so that a PRC must always be tied to, and scheduled alongside, the clinical next step it precedes (PEPI, WEPI, Training Shift, Candidate Start) — except IPEA, where the PRC happens in person during the IPEA visit itself. Recruiters need to enter the real PRC date/time in the same action as scheduling the next step, and the system must stop offering CCR as a standalone, separately-scheduled option.

## What Changes

- **No feature flag.** An earlier draft of this change gated everything behind a new
  `pcr-recruiting-dashboard` flag, with the legacy CCR-dropdown flow kept as a
  flag-off fallback. Per explicit correction from the team lead (who had earlier
  asked for the flag, then reversed that), this is now unconditional: all tasks
  tied to migrating the CCR process to PRC ship without any flag, and the legacy
  CCR-dropdown code paths are deleted outright rather than kept as a fallback.
- Remove "CCR" as a selectable, standalone Interview Type option from both Schedule
  Next Step forms (`common/schedule_next_step.blade.php`,
  `recruiterTracker/nextstep.blade.php`).
- Add a new "PRC" scheduling block, visually matching the existing "Schedule" block
  (same heading/fieldset structure), directly below the next-step scheduling block.
  It is shown only when the selected next step is PEPI, WEPI, Training Shift, or
  Candidate Start, and stays hidden for IPEA and all other types.
- The PRC Date/Time inputs are disabled until the Next Step Date/Time are filled in,
  and are constrained (client-side picker limits + server-side validation) to be
  strictly before the Next Step Date/Time — same instant or later is rejected.
- PRC Date/Time become required fields (client + server validation) whenever the
  PRC block is shown; saving is blocked until both are filled with a valid value.
- Replace the existing heuristic auto-creation of the CCR/PRC schedule record
  (`Dashboard::saveScheduleNext()`, "next step date minus one day") with real-data
  creation from the submitted PRC Date/Time, for all four qualifying next steps.
- Extract the CCM auto-assignment side effect (`assigned_ccr`, plus its "PRC
  assigned" notification) into a shared method,
  `CcrAssignmentService::assignCcrToTracker()`
  (`app/Services/CCR/CcrAssignmentService.php`, injected into `saveScheduleNext()`),
  so it fires whenever a PRC record is created. The service takes explicit
  `trackerId`/`aocPatientId`/proposed-next-step values rather than the `Request`
  object, so it has no HTTP-layer dependency — the caller reads
  `schedule_type`/`appointment_date`/`appointment_time` off `$request` itself.
- The legacy code paths tied to manually selecting "CCR" as the schedule type (both
  blade views' CCR-specific markup/JS — dropdown injection, the CCR time-slot
  select + double-booking AJAX check, the CCR notes textarea — and the
  `elseif ($request->schedule_type == 'CCR')` branches in
  `Dashboard::saveScheduleNext()`) are deleted outright, not kept as a fallback.
- On successful save, the Recruiter Tracker status displays as "Patient Review
  Call" (unchanged mechanism — already stored as `Clinical Competency Review` and
  rendered via the existing display helper from the PRC terminology rename).

## Capabilities

### New Capabilities
- `prc-scheduling`: Defines the functional/business-rule contract for scheduling a PRC alongside a clinical next step — which next steps require it, the inline form behavior, the before-next-step time constraint, validation, and the resulting records/status/bucket/calendar visibility.

### Modified Capabilities
(none — `prc-terminology` governs display text only and is not changing; this change does not alter what that spec requires)

## Impact

- `resources/views/common/schedule_next_step.blade.php` — CCR dropdown injection and
  CCR-primary-type UI/JS deleted outright (dropdown injection on `#branch_location`
  change, the CCR time-slot select + `checkingCCRTimeSlotAndSetting()` AJAX
  double-booking check, the CCR notes textarea, the `if (val=='CCR')` branches);
  `#prc_schedule_block` renders unconditionally, `needsPrc()` has no flag check.
  Also fixes: added the missing `bootstrap-datepicker.min.css`/`.min.js` includes
  (this form never had its own copy, unlike `nextstep.blade.php` — relied on luck/
  other pages having already loaded them), loaded via a synchronous `$.ajax` call
  to avoid racing against this same response's inline `.datepicker()` init calls.
- `resources/views/recruiterTracker/nextstep.blade.php` — `#prc_schedule_block`
  renders unconditionally (previously behind its own flag check), `needsPrc()` has
  no flag check. Also fixes: `.ccrbefornext` moved into its own full-width row (was
  sharing a row with an often-empty `.col-md-6` after the Interview Type/Date/Time
  layout change, causing it to render lopsided); a `bootstrap-editable.css` rule
  (`.datepicker>div{display:none}`, written for an older bootstrap-datepicker DOM
  convention this app's vendored JS no longer uses) was hiding the calendar
  popup's active view — restored with `display:block`; `bootstrap-datepicker.min.js`
  is now loaded via a synchronous `$.ajax` call instead of a plain `<script src>`,
  for the same race-condition reason as above.
- `resources/views/layouts/myaccount.blade.php` (`showScheduleForm()`) — destroys
  any live `bootstrap-datepicker` instances inside `#tempModalCnt` before removing
  it, since the plugin appends its popup to `<body>` (its default `container`
  option) rather than inside the modal, so a plain `.remove()` orphaned it instead
  of tearing it down.
- `app/Http/Controllers/Dashboard.php` (`saveScheduleNext()`) — new server-side
  validation for PRC Date/Time (unconditional); replace heuristic CCR
  auto-creation with real-data creation for PEPI/WEPI/Training Shift/Candidate
  Start (unconditional); relocate `assigned_ccr` side effect into
  `CcrAssignmentService`; delete the `elseif ($request->schedule_type == 'CCR')`
  branches (Edit and Add mode) and the now-unreachable
  `if ($scheduleTypes_id==2) {...}` block outright. Also fixes a bug (found while
  tracing a real payload) where the inline-PRC path's `assigned_ccr` update passed
  no `proposed_next_step`/`proposed_ns_date`/`proposed_ns_time` values at all,
  leaving the PRC bucket's "Proposed Next Step" column blank — the caller now
  passes `schedule_type`/`appointment_date`/`appointment_time` explicitly.
- `app/Services/CCR/CcrAssignmentService.php` (new) — houses `assignCcrToTracker()`, injected into `Dashboard::saveScheduleNext()`.
- `config/featureflags.php` — `pcr-recruiting-dashboard` entry removed (the flag
  never shipped to production and is deleted, not just unused).
- No changes to `ccr/grid.blade.php`, `ccr/calendar.blade.php`, `ccr/nextstepNew.blade.php`, `CCRController`, or any enum/table/route names — these already consume `recruiting_schedule` rows with `type_id=2` and need no changes to keep showing PRC records created by the new path.
