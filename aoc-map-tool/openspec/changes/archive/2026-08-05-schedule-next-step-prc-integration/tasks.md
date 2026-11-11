## 1. Remove standalone CCR/PRC option — `common/schedule_next_step.blade.php`

- [x] 1.1 Delete the `.before()` dropdown injection that adds "CCR" as a
  `#schedule_type` option for TX-branch clients (`$('#branch_location').change(...)`).
- [x] 1.2 Delete the `if ($(this).val() == 'CCR') {...} else {...}` branch inside
  `$('#schedule_type').change(...)`.
- [x] 1.3 Delete the CCR-specific markup that only that branch showed:
  `.appointment-time-ccr`/`#appointment_time_ccr` (and its `checkingCCRTimeSlotAndSetting()`
  function + `route('ccrCalendar.fetchScheduleDetailsOfCCR')` AJAX call) and
  `.crr-notes`/`#ccrNotes`.
- [x] 1.4 Narrow the `hideforccrschool` condition to `val == 'School Set Up'` only
  (drop the `'CCR'` half).
- [x] 1.5 Keep `.ccrScheduleFields` (`#proposed_next_step`, `#proposed_ns_date`,
  `#proposed_ns_time`) in the DOM (still read server-side) but give each row
  `style="display:none;"` since their only show-trigger is being removed.

## 2. Remove dead CCR reference — `recruiterTracker/nextstep.blade.php`

- [x] 2.1 Narrow the `$('#schedule_type').change(...)` condition that currently
  checks `val()=='CCR' || val()=='School Set Up'` down to `School Set Up` only (CCR
  was never actually selectable from this form; this is a dead-code cleanup).

## 3. Add PRC scheduling block — both blade files

- [x] 3.1 In `common/schedule_next_step.blade.php`, add a new `#prc_schedule_block`
  section directly after the closing `</div>` of `#schedule_appointment`, matching
  its structure (`<h6>PRC</h6>` + `<fieldset>` + `.row.pt-2 > .col-md-12` pairs) with
  a `prc_date` input and a `prc_time` input, hidden by default.
- [x] 3.2 In `recruiterTracker/nextstep.blade.php`, add the equivalent
  `#prc_schedule_block`, inside the same `@if(LD::checkFeatureFlag('change-client-notes-schedule-next-steps'))`
  guard that already wraps the existing `.ccrbefornext` banner in this file.
- [x] 3.3 In both files, extend the `#schedule_type` change handler: show
  `#prc_schedule_block` (in addition to the existing `.ccrbefornext` banner) when the
  selected value is PEPI, WEPI, Training Shift, or Candidate Start; hide it otherwise
  (IPEA included).

## 4. PRC field lock/unlock and client-side constraint — both blade files

- [x] 4.1 Initialize `#prc_date` with `datepicker()` and `#prc_time` with
  `timepicker({step:15, forceRoundTime:true, minTime:'06:00:00', maxTime:'21:00:00'})`,
  matching the existing `#datepicker`/`#appointment_time` initialization in the same
  file. Both start `disabled`.
- [x] 4.2 Add a `refreshPrcLockState()` function (one per file): enables/disables
  `#prc_date`/`#prc_time` based on whether Next Step Date and Time both have values;
  when enabled, sets `datepicker('option','maxDate', nextStepDate)` and, when the PRC
  date equals the next step date, narrows `timepicker('option','maxTime', nextStepTime)`;
  when disabled, clears both PRC fields.
- [x] 4.3 Wire `refreshPrcLockState()` to fire on Next Step Date (`#datepicker`
  `onSelect`) and Next Step Time (`#appointment_time` `change`) changes, and once on
  `#schedule_type` change.

## 5. Client-side validation — both blade files

- [x] 5.1 Add jQuery Validate `required` rules for `prc_date`/`prc_time`, conditional
  on the selected next step being one of the four qualifying types (mirror the
  existing conditional-required pattern already used for `datepicker`/`appointment_time`
  in this file's `.validate({ rules: {...} })` block).
- [x] 5.2 Register a custom `$.validator.addMethod('prcBeforeNextStep', ...)` that
  rejects a PRC Date/Time that is equal to or later than the Next Step Date/Time, and
  apply it to `prc_time`'s rules (following this codebase's existing
  `$.validator.addMethod` convention).

## 6. Server-side validation — `Dashboard::saveScheduleNext()`

- [x] 6.1 Add an early validation check (alongside the existing
  `response()->json(['error'=>..., 'message'=>...])` early-return checks, after
  `$appointment_date`/`$appointment_time` are computed): when `$request->schedule_type`
  is PEPI, WEPI, Training Shift, or Candidate Start, require non-empty
  `prc_date`/`prc_time` and reject (with a clear error message) if the resulting PRC
  datetime is not strictly before the Next Step datetime.

## 7. Replace heuristic auto-CCR creation with real PRC data

- [x] 7.1 In the auto-CCR block (`if ($schedule_CCR==1) {...}`, ~lines 4826-4911),
  add a condition (`$usesInlinePrc = in_array($request->schedule_type, ['PEPI', 'WEPI', 'Training Shift', 'Candidate Start'])`)
  and guard the existing heuristic date-computation branch with `&& !$usesInlinePrc`,
  leaving it unchanged for any other caller (e.g. `School Set Up`, Arizona-IPEA
  auto-CCR).
- [x] 7.2 Add a new branch for `$schedule_CCR==1 && $usesInlinePrc` that builds the
  `recruiting_schedule` insert using `$request->prc_date`/`prc_time` directly (not
  the heuristic), reusing the same `$scheduleData` field set (`tracker_id`,
  `type_id=2`, `appointment_date`, `appointment_time`, `aoc_patient_id`,
  `aoc_candidate_id`, `aoc_provider_id`, `aoc_facility_id`, `pay_rate`, `gp`,
  `gp_approved_by`, `created_at/by`, `updated_at/by`, `scheduled_date`, `outcome`,
  `ccrNotes`).
- [x] 7.3 Extract the shared row-creation code (used by both the heuristic and the
  new inline-PRC branch) into a small private helper if the duplication is
  significant, to avoid maintaining two near-identical `RecruitingSchedule::create()`
  call sites. (Kept as one branch computing `$ccr_date`/`$ccr_time` differently, then
  a single shared `$scheduleData`/`RecruitingSchedule::create()` — no duplication to
  extract.)

## 8. Relocate CCM auto-assignment (`assigned_ccr`)

- [x] 8.1 Extract the `assigned_ccr` assignment logic (currently inline in the
  now-dead `if ($scheduleTypes_id==2) {...}` block, ~lines 4573-4584: picks
  `H::getUserToAssignCcr($trackerId)` behind `change-assign-ccr-process`, else a
  hardcoded fallback ID) into a private helper method, e.g. `assignCcrToTracker(int $trackerId)`.
  (Also folded in the adjacent "PRC assigned" notification block, found during
  implementation at ~lines 4614-4639, which was likewise only reachable via the dead
  manual `schedule_type=='CCR'` path.)
- [x] 8.2 Call this helper from the new inline-PRC creation branch (task 7.2) so a
  Clinical Case Manager is assigned whenever a PRC record is created this way.
- [x] 8.3 **Bug found while tracing a real submitted payload**: the helper wrote
  `proposed_next_step`/`proposed_ns_date`/`proposed_ns_time` by reading
  `$request->proposed_next_step`/etc — fields that only exist on the legacy manual
  "CCR" dropdown path (its own "Proposed Next Step" selector). The inline-PRC path
  has no such fields, so `isset()` was always false and the helper blanked these
  columns (`''`, `'0000-00-00'`, `'00:00:00'`) on every inline-PRC save, leaving the
  PRC bucket's "Proposed Next Step" column empty. Fixed by giving the helper
  (now `CcrAssignmentService::assignCcrToTracker()`, see section 16) three
  parameters for the proposed next step's type/date/time; the legacy call site
  passes `null` (falls back to reading the request fields it always had), the
  inline-PRC call site passes `$request->schedule_type`/`$appointment_date`/
  `$appointment_time` explicitly.

## 9. Delete dead CCR-primary-type code — `Dashboard::saveScheduleNext()`

- [x] 9.1 Delete the `elseif ($request->schedule_type == 'CCR')` branch in the
  Edit-mode next-step switch (~lines 3838-3842).
- [x] 9.2 Delete the equivalent `elseif ($request->schedule_type == 'CCR')` branch
  in the Add-mode next-step switch (~lines 4337-4341).
- [x] 9.3 Delete the `if ($scheduleTypes_id==2) {...}` block in the generic
  schedule-insert path (~lines 4573-4584) now that its `assigned_ccr` logic lives in
  the new helper (task 8.1) and CCR can no longer reach this path as
  `$request->schedule_type`.
- [x] 9.4 Grep the codebase for any other caller of `route('saveScheduleNext')`
  besides the two blade forms being changed. **Found a third caller**:
  `TrackersProxyController::save()` (Private API `POST /private-api/trackers`,
  route `trackers-proxy.save`), which forwards an arbitrary `$request` straight into
  `saveScheduleNext()` and could theoretically still submit `schedule_type=CCR`
  independent of the two blade dropdowns. Per explicit user decision, this endpoint
  is out of scope and left untouched/unmitigated — accepted as a known risk, not
  fixed in this change.

## 11. Feature-flag gating (added mid-implementation, per requester correction — later reverted, see section 17)

- [x] 11.1 Add new flag `pcr-recruiting-dashboard` to `config/featureflags.php`
  (end of file, no enum case, raw string usage) — do **not** reuse
  `change-client-notes-schedule-next-steps`, which is confirmed disabled in
  production/unused and stays as-is guarding only the pre-existing `.ccrbefornext`
  banner in `recruiterTracker/nextstep.blade.php`.
- [x] 11.2 Restore the CCR-primary-type markup/JS deleted in group 1
  (`common/schedule_next_step.blade.php`: dropdown injection, `.appointment-time-ccr`,
  `.crr-notes`, `checkingCCRTimeSlotAndSetting()`, the `if (val=='CCR')` branch) as
  the fallback path, gated at runtime by a `pcrRecruitingDashboardEnabled` JS var
  (`{{ (int) LD::checkFeatureFlag('pcr-recruiting-dashboard') }}`), following this
  codebase's existing convention (same pattern as `feature/AOC-4106`'s
  `isRemoveCCRAutoSchedulingEnabled`).
- [x] 11.3 Make `needsPrc()` (both blade files) check `pcrRecruitingDashboardEnabled`
  first, so `#prc_schedule_block` and its required/lock/validation logic are a no-op
  whenever the flag is off.
- [x] 11.4 In `recruiterTracker/nextstep.blade.php`, decouple `#prc_schedule_block`'s
  markup and show/hide from the pre-existing `change-client-notes-schedule-next-steps`
  guard — wrap the markup in `@if(LD::checkFeatureFlag('pcr-recruiting-dashboard'))`
  and gate its show/hide purely via `needsPrc()`, independent of the old flag's
  `.ccrbefornext` block.
- [x] 11.5 In `Dashboard::saveScheduleNext()`, guard the new PRC required/before-
  next-step server validation and `$usesInlinePrc` with
  `LD::checkFeatureFlag('pcr-recruiting-dashboard')`.
- [x] 11.6 Restore the `elseif ($request->schedule_type == 'CCR')` branches (Edit and
  Add mode, deleted in group 9) and the `if ($scheduleTypes_id==2) {...}` call site
  (now calling `assignCcrToTracker()` instead of inline code) as the flag-off
  fallback.

## 12. Bug fixes found during manual UI testing (per requester)

- [x] 12.1 Missing constraint message: the mockup shows a dynamic message ("PRC must
  be scheduled before {date} at {time}...") once PRC fields unlock, and a lock hint
  before that. Added `#prc_lock_hint` / `#prc_constraint_text` elements to both
  blade files' `#prc_schedule_block`, toggled and populated by
  `refreshPrcLockState()`.
- [x] 12.2 `#prc_date` datepicker not blocking dates after the Next Step date: the
  `maxDate` option was being set from a raw date string; converted to a `Date`
  object (`new Date(nsDate)`) before assigning, and added `minDate: 0` on init so
  today is always the earliest selectable/highlighted date (addresses "today not
  shown" when opening the calendar).
- [x] 12.3 Same-day PRC time not restricted to hours strictly before the Next Step
  time: added `refreshPrcTimeConstraint()`, which sets the `jquery.timepicker`
  `disableTimeRanges` option to `[[nsTime, '11:59pm']]` when `prc_date === nsDate`
  (confirmed `disableTimeRanges` is supported by the vendored
  `public/js/jquery.timepicker.min.js`), clearing it otherwise. Wired to `#prc_date`
  changes and `refreshPrcLockState()`.
- [x] 12.4 Manually-typed PRC values bypassing the calendar/dropdown constraints
  weren't visibly flagged: `validatePrcTime()` now toggles `.error`/`.valid` classes
  on `#prc_date`/`#prc_time` directly (matching this form's existing
  highlight/unhighlight convention) and both fields get `change` handlers calling
  `refreshPrcTimeConstraint()`/`validatePrcTime()` so typed input is re-checked
  live, not only on submit.

## 13. UI polish per requester screenshot feedback (round 2)

- [x] 13.1 Dynamic PRC block title, matching mockup subtitle "Required before
  {step}": `#prc_block_title` (`<h6>`) is set to `'PRC (Patient Review Call) —
  Required before ' + $(this).val()` in the `#schedule_type` change handler, both
  blade files.
- [x] 13.2 Layout: Interview Type / Date / Time now sit in one row (`.col-md-4`
  each) instead of three stacked rows, matching the mockup's `cols-3` layout — both
  blade files' `#schedule_appointment` block. IPEA Location, rehire/new-grad
  checkboxes, `ccrbefornext`, IPEA notes, etc. stay in their existing nested
  `.col-md-6` structure below, unaffected.
- [x] 13.3 Layout: PRC Date / Time now sit in one row (`.col-md-6` each) instead of
  two stacked rows, matching the mockup's `cols-2` layout — both blade files'
  `#prc_schedule_block`.
- [x] 13.4 `maxDate` bug: `refreshPrcLockState()` was passing the raw date string
  from `#datepicker` straight to `.datepicker('option','maxDate', ...)`; converted
  to a `Date` object (`new Date(nsDate)`) first. This is very likely why dates after
  the Next Step date weren't actually being blocked in the PRC calendar.
- [x] 13.5 Added `minDate: 0` to `#prc_date` init (both files) so today is never
  excluded as the earliest selectable date.

**Superseded by section 14 below** — the "datepicker doesn't show today" /
"maxDate not blocking" reports were both actually caused by a library conflict
(this app's `.datepicker()` is bootstrap-datepicker, not jQuery UI), not by
anything guessed at here. See section 14 for the real root cause and fix.

## 14. Root-cause correction: `.datepicker()` is bootstrap-datepicker, not jQuery UI

- [x] 14.1 Discovered that `myaccount.blade.php` (main authenticated layout) loads
  `jquery-ui.min.js` (line ~75) and then, later, includes `layouts.filterSidebar`
  (line ~147) which loads `bootstrap-datepicker.min.js`. Since both define
  `$.fn.datepicker`, the later-loaded bootstrap-datepicker silently overwrites
  jQuery UI's — confirmed by pre-existing code in the same file
  (`recruiterTracker/nextstep.blade.php:1929`) binding to `changeDate`, a
  bootstrap-datepicker-specific event that doesn't exist in jQuery UI.
- [x] 14.2 This invalidated all previously-written jQuery-UI-style config: `onSelect`
  callbacks never fired (bootstrap-datepicker has no such init option — it uses the
  `changeDate` DOM event instead), and `.datepicker('option', 'maxDate', ...)` was
  setting a key bootstrap-datepicker doesn't recognize (its equivalent is `endDate`,
  set via `.datepicker('setEndDate', ...)` or `option 'endDate'`). This fully
  explains both reported bugs: the max-date restriction never took effect, and any
  logic gated behind `onSelect` (lock refresh, time-range constraint) never ran when
  picking a date from the calendar UI.
- [x] 14.3 Rewrote both blade files' `#datepicker`/`#prc_date` init and event
  binding to bootstrap-datepicker's real API: `format: 'mm/dd/yyyy'` (matches this
  codebase's existing convention, e.g. `recruiterTracker/nextstep.blade.php:1062`),
  `startDate: new Date()` at init (replaces the no-op `minDate: 0`), `.on('changeDate', ...)`
  (replaces the no-op `onSelect`), and `.datepicker('setEndDate', nsDateObj)`
  (replaces the no-op `.datepicker('option','maxDate', ...)`). Confirmed
  `setEndDate(a)` accepts a `Date` object directly by reading the vendored
  `public/js/bootstrap-datepicker.min.js`.
- [x] 14.4 Left the pre-existing, out-of-scope `beforeShowDay` calls in the legacy
  CCR-primary-select branch (flag-off fallback, `common/schedule_next_step.blade.php`)
  untouched — same class of bug (jQuery-UI-only option, no-op under
  bootstrap-datepicker), but pre-dates this ticket and is not part of the PRC
  feature; flagged here for awareness, not fixed.

## 15. Datepicker plugin-loading bugs found during manual testing (round 3)

- [x] 15.1 `common/schedule_next_step.blade.php` never loaded
  `bootstrap-datepicker.min.js`/`.css` itself (unlike `nextstep.blade.php`, which
  self-includes both) — it depended entirely on some other already-loaded page
  happening to have loaded bootstrap-datepicker first, so `.datepicker()` fell back
  to jQuery UI (loaded unconditionally, synchronously, in `myaccount.blade.php`'s
  `<head>`) whenever that didn't hold, e.g. on a genuinely fresh page load before
  any other Schedule Next Step modal had been opened. Fixed by adding both the
  `<link>` and a `<script>` include to this file.
- [x] 15.2 Race condition: a plain `<script src="...bootstrap-datepicker.min.js">`
  tag inside an AJAX-injected modal doesn't block — the document is already
  "ready" by the time `.html(response)` inserts it, so the same response's inline
  `.datepicker()` init calls could run before the external file finished
  downloading, silently keeping jQuery UI as `$.fn.datepicker`. Fixed in both blade
  files by loading the script via a synchronous `$.ajax({dataType:'script', async:false})`
  call instead (matching an existing sync-AJAX pattern already used elsewhere in
  this codebase, e.g. `intake/form.blade.php`), guaranteeing it's loaded and
  executed before any later inline script in the same response runs.
- [x] 15.3 Stale/orphaned popups across modal switches: bootstrap-datepicker appends
  its popup to `<body>` by default (its `container` option), not inside the modal
  container (`#tempModalCnt`). `showScheduleForm()` (`myaccount.blade.php`) only
  removed `#tempModalCnt` between modal opens, leaving any already-created popup
  orphaned in `<body>`. Fixed by destroying live datepicker instances
  (`.datepicker('remove')`) inside `#tempModalCnt` before removing it.
- [x] 15.4 Root cause of a persistently collapsed/invisible calendar popup (confirmed
  via live DevTools inspection — correct DOM/data, but `.datepicker-days` computed
  to `display:none`): `bootstrap-editable.css` (an unrelated, unconditionally-loaded
  vendored library in `nextstep.blade.php`) ships `.datepicker>div{display:none}` +
  `.datepicker.days div.datepicker-days{display:block}`, written for an older
  bootstrap-datepicker DOM convention (a companion `.datepicker.days` class on the
  root element) that this app's actual vendored `bootstrap-datepicker.min.js`
  doesn't generate — so the "show" half of that pair never matches, and the active
  view stays hidden. This is what an earlier, pre-existing (not-ours) rule in this
  file, `.datepicker>div{display:inline}`, was attempting to work around, with the
  wrong value. Fixed with a corrective `.datepicker>div{display:block}` (left
  unscoped, since the popup lives at `<body>` level, not inside any form container);
  the other, inactive calendar views (months/years/etc.) stay hidden via their own
  inline `style="display:none"`, which always wins over any stylesheet rule
  regardless of load order.
- [x] 15.5 `.ccrbefornext` warning banner rendered lopsided (sharing a Bootstrap row
  with an often-empty `.col-md-6` after the Interview Type/Date/Time layout change
  in section 13) instead of spanning the full width. Fixed in both blade files by
  moving it into its own `.row.pt-2 > .col-md-12`.
- [x] 15.6 `#prc_schedule_block` markup in `common/schedule_next_step.blade.php` was
  only JS-gated (`needsPrc()`), not wrapped in any `@if`, so it always rendered in
  the page source regardless of the flag. Wrapped in
  `@if(LD::checkFeatureFlag('pcr-recruiting-dashboard'))` for symmetry with
  `nextstep.blade.php` (see task 11.4) — with the flag off, the form now returns
  fully to its pre-change markup.

## 16. Extract `assignCcrToTracker()` into a service

- [x] 16.1 Moved the private controller method (task 8.1) into
  `App\Services\CCR\CcrAssignmentService::assignCcrToTracker()`
  (`app/Services/CCR/CcrAssignmentService.php`), following this codebase's existing
  `app/Services/<Domain>/...Service.php` convention — `Dashboard.php` is already a
  very large controller, and this logic has no dependency on controller state.
  Injected into `saveScheduleNext()` via method injection (matching the existing
  `FirstAdvantageService`/`NotesWriteRepository` pattern on the same method).
- [x] 16.2 Removed the `Illuminate\Http\Request` parameter from the service method
  entirely (per explicit feedback that passing the whole request object into a
  service is undesirable) — it only ever read 3 specific fields as a fallback. Both
  call sites in `Dashboard.php` now read the fields they need off `$request`
  themselves and pass them as explicit `?string` parameters.

## 17. Remove the `pcr-recruiting-dashboard` feature flag entirely (reverts section 11)

The team lead who originally asked for the flag (section 11) corrected himself:
this ships unconditionally, no flag, as originally planned before section 11 was
added.

- [x] 17.1 Removed the `pcr-recruiting-dashboard` entry from
  `config/featureflags.php` outright (not just left unused).
- [x] 17.2 `Dashboard::saveScheduleNext()`: removed the
  `LD::checkFeatureFlag('pcr-recruiting-dashboard')` check from both the early
  PRC required/before-next-step validation and the `$usesInlinePrc` computation —
  both are now unconditional on `$request->schedule_type` alone.
- [x] 17.3 Re-deleted the `elseif ($request->schedule_type == 'CCR')` branches
  (Edit and Add mode, restored in 11.6) and the now-unreachable
  `if ($scheduleTypes_id==2) {...}` block (including its dead `$scheduleData['ccrNotes']`
  assignment) — `schedule_type` can never be `'CCR'` again, so this whole path is
  dead code, not just flag-gated.
- [x] 17.4 Both blade files: removed `pcrRecruitingDashboardEnabled` and the flag
  check inside `needsPrc()` — it now only checks `PRC_SCHEDULE_TYPES`.
- [x] 17.5 Both blade files: removed the `@if(LD::checkFeatureFlag('pcr-recruiting-dashboard'))`
  guard around `#prc_schedule_block`'s markup and around its show/hide JS block —
  both render/run unconditionally.
- [x] 17.6 `common/schedule_next_step.blade.php`: re-deleted the CCR-primary-type
  markup/JS restored in 11.2 (dropdown injection on `#branch_location` change,
  `.appointment-time-ccr`/`#appointment_time_ccr`, `.crr-notes`/`#ccrNotes`,
  `checkingCCRTimeSlotAndSetting()` + its `ccrCalendar.fetchScheduleDetailsOfCCR`
  AJAX call, the `if (val=='CCR' && !pcrRecruitingDashboardEnabled)` branches) and
  simplified `hideforccrschool` back down to `val == 'School Set Up'` only.
- [x] 17.7 Confirmed via repo-wide grep that no reference to
  `pcr-recruiting-dashboard`/`pcrRecruitingDashboardEnabled` or
  `schedule_type == 'CCR'` remains in any of the three changed files.

## 10. Verification

- [x] 10.1 `php -l` on every changed `.php` file. (`Dashboard.php` passes; the two
  changed files are Blade views, not checked by `php -l` — visually verified
  `@if`/`@endif` balance and confirmed no leftover references to deleted
  elements/functions.)
- [ ] 10.2 Manual run-through in `common/schedule_next_step.blade.php`: confirm CCR
  is absent from the dropdown for both TX and non-TX branches; select each of
  PEPI/WEPI/Training Shift/Candidate Start and confirm the PRC block appears locked,
  then unlocks after Next Step Date/Time are set; confirm same/after-next-step PRC
  values are blocked client-side; submit without PRC values and confirm server
  rejects; submit valid values and confirm the Recruiter Tracker status displays
  "Patient Review Call", a `recruiting_schedule` row (`type_id=2`) exists with the
  submitted date/time, `assigned_ccr` is set, and the record appears in the
  Recruiting PRC bucket and PRC Calendar.
- [ ] 10.3 Repeat 10.2 for `recruiterTracker/nextstep.blade.php` (existing tracker,
  opened from a different Recruiting bucket) to confirm parity.
- [ ] 10.4 Confirm IPEA selection shows no PRC block in either form and next-step
  fields behave as before.
- [ ] 10.5 Confirm non-qualifying schedule types (e.g. `School Set Up`, `Needs to be
  Scheduled by RA`) are unaffected — no PRC block, and (for `School Set Up`, if it
  can hit the heuristic branch) unchanged heuristic behavior.
