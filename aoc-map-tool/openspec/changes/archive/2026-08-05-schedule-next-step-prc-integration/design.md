## Context

Both Schedule Next Step forms (`common/schedule_next_step.blade.php` for new/"Add
Clinician" flow, `recruiterTracker/nextstep.blade.php` for existing-tracker flow)
post to the same controller action, `Dashboard::saveScheduleNext()`. Today, "CCR"
(the internal name for PRC) can be scheduled two ways:

1. **Manually**, by picking "CCR" from the `#schedule_type` dropdown (dynamically
   injected for TX-branch clients in `common/schedule_next_step.blade.php`; not
   reachable at all from `recruiterTracker/nextstep.blade.php`). This path shows
   CCR-specific markup (its own time-slot select with a double-booking AJAX check,
   a notes textarea, "Proposed Next Step/Date/Time" fields) and, on save, sets
   `assigned_ccr` (auto-assigns a Clinical Case Manager) via a dedicated code block.
2. **Automatically**, via a heuristic block in `saveScheduleNext()` (~lines 4826-4911)
   that runs whenever a tracker "needs a CCR" (`$needsCCR`/`$schedule_CCR` — no prior
   IPEA or CCR schedule exists) and the chosen next step is PEPI, WEPI, Training
   Shift, or Candidate Start. It creates a `recruiting_schedule` row (`type_id=2`)
   with a guessed date (next step date minus one day) and the next step's own time
   — no real recruiter input, and it does not run the `assigned_ccr` side effect.

The PRC rename (CCR → "PRC" in UI text only) already landed via a separate change
(`prc-terminology` spec / AOC-4231) and is merged into this epic's base branch
(`feature/AOC-4164_CCR_EPIC`). It touches no stored/functional values — this change
builds on top of it but does not depend on any further terminology work.

A parallel branch, `feature/AOC-4106_remove_ccr_auto_create_for_pepi_updated`,
partially addressed the heuristic (disabled it for PEPI only, behind a feature flag,
plus an unrelated commit widening CCR dropdown availability to all branches). That
branch is **not used as a base** — this change's server-side rule (disable the
heuristic for all four qualifying next steps, unconditionally) fully subsumes what
that branch did for PEPI, and its unrelated commit is out of scope.

## Goals / Non-Goals

**Goals:**
- Recruiter enters real PRC Date/Time in the same form submission as the next step,
  for PEPI, WEPI, Training Shift, Candidate Start.
- CCR/PRC is no longer independently selectable as a schedule type.
- The resulting `recruiting_schedule` row and CCM auto-assignment behave the same
  (or better — see below) as the old manual path, so downstream PRC bucket/calendar/
  outcome-documentation screens keep working unmodified.
- IPEA and any other next step type are unaffected beyond the removal of the CCR
  dropdown option.

**Non-Goals:**
- No changes to `ccr/grid.blade.php`, `ccr/calendar.blade.php`,
  `ccr/nextstepNew.blade.php`, `CCRController` — the downstream CCM-facing outcome
  flow that documents what happened during/after the PRC call.
- No database schema, enum, route, or table/column renames.
- No further UI terminology changes (already covered by `prc-terminology`).
- No attempt to reconcile with `feature/AOC-4106_...` or `AOC-4110` — both ignored.

## Decisions

**1. New "PRC" block is a sibling section, not fields inside the warning banner.**
The existing `.ccrbefornext` alert (`<p>` warning text) stays as-is and keeps its
current show/hide rule. A new block, structurally identical to the existing
"Schedule" section (`<h6>` + `<fieldset>` + the same `.row.pt-2 > .col-md-12` grid),
is added directly below it, containing only Date/Time inputs (no Interview Type
selector — the type is implicitly PRC). This matches the actual current UI (two
stacked bordered boxes) rather than embedding inputs inside an alert `<p>`.

**2. PRC Date/Time reuse the existing datepicker/timepicker stack.**
Both files already initialize `jquery-ui` `datepicker()` and `jonthornton`
`timepicker()` for `#datepicker`/`#appointment_time` and `#proposed_ns_date`/
`#proposed_ns_time`. The new `#prc_date`/`#prc_time` inputs get the same treatment
instead of a mockup-style `<select>` — consistent with the surrounding form, and the
picker's `maxDate`/`maxTime` options provide the client-side "before next step" guard
without hand-rolled option-disabling.

**3. Lock PRC fields until Next Step Date/Time are set; unlock/re-lock live.**
`#prc_date`/`#prc_time` start `disabled`. A shared `refreshPrcLockState()` function
(duplicated per file, matching this codebase's existing convention of independent,
near-identical handlers across these two files) enables them once both Next Step
Date and Time have values, and narrows `datepicker('option','maxDate', ...)` /
`timepicker('option','maxTime', ...)` accordingly. Clearing Next Step Date/Time
re-locks and clears the PRC fields.

**4. Server-side validation is independent of `$needsCCR`.**
`$needsCCR` is only computed in Edit mode partway through `saveScheduleNext()`; Add
mode never computes it. The new required/before-next-step check is a simple, early
guard keyed only on `$request->schedule_type` being one of the four qualifying types
— matching how the front-end shows/requires the fields purely off the same value.
This means the fields are validated even in the theoretical case where `$needsCCR`
later resolves to 0 (harmless over-validation, avoids restructuring the method to
compute `$needsCCR` earlier).

**5. Heuristic auto-CCR block is disabled for all 4 types, not reused/patched in place.**
Rather than trying to feed `prc_date`/`prc_time` into the existing heuristic branch
(risking subtle interaction with `$is_arizona_ccr`, the legacy `$trackerId > 8564`
cutover guard, etc.), the heuristic's `if ($schedule_CCR==1)` condition gets a new
`&& !$usesInlinePrc` guard (where `$usesInlinePrc` = next step is one of the four
types), and a separate, new, explicit creation call runs instead when
`$usesInlinePrc` is true. This keeps the legacy heuristic's behavior for any other
caller of that branch (e.g. `School Set Up`, Arizona IPEA auto-CCR) completely
unchanged and isolates the new logic from the old method's accumulated edge cases.

**6. `assigned_ccr` side effect is extracted into a shared service, not a controller helper.**
The CCM auto-assignment (today only reachable via the manual "CCR" dropdown path,
lines ~4573-4584) is moved into `CcrAssignmentService::assignCcrToTracker()`
(`app/Services/CCR/CcrAssignmentService.php`), injected into `saveScheduleNext()`
via method injection (matching this controller's existing convention for
`FirstAdvantageService`/`NotesWriteRepository`) rather than kept as a private
controller method — `Dashboard.php` is already large, and this logic has no
dependency on controller state. The service takes explicit
`trackerId`/`aocPatientId`/proposed-next-step values instead of the `Request`
object, so it stays free of any HTTP-layer dependency; the call site in
`saveScheduleNext()` reads `schedule_type`/`appointment_date`/`appointment_time`
off `$request` itself before calling in. (An earlier draft also called this from a
second, legacy manual-CCR call site — removed along with that dead code path, see
Decision 9.) This is also a deliberate behavior change: previously,
PEPI/WEPI/Training-Shift/Candidate-Start submissions that triggered the heuristic
never assigned a CCM at all (the assignment only fired from the dead manual path)
— this was a silent gap the new flow closes, per explicit user confirmation.

**7. Dead code from the manual "CCR" dropdown path is deleted, not left in place.**
Both blade views' CCR-primary-type markup/JS (time-slot select + AJAX conflict
check, notes textarea, `if (val=='CCR')` branches) and `Dashboard.php`'s
`elseif ($request->schedule_type == 'CCR')` branches are removed outright. The only
callers of `saveScheduleNext()` are these two blade forms, both changed in this same
work, so nothing else can submit `schedule_type=CCR` after this change ships — per
explicit user confirmation, no defensive dead code is kept.

**8. `.ccrScheduleFields` ("Proposed Next Step/Date/Time") inputs are kept, just hidden.**
These are unrelated to PRC scheduling — they're the pre-existing "propose what
happens after CCR" bridge fields, still read by `saveScheduleNext()`'s "needs CCR"
branches independent of this change (AC18: preserve existing next-step scheduling
behavior). They lose their only show-trigger (the deleted CCR-primary branch) and
get an explicit `display:none` so they don't orphan-render, but the inputs and their
server-side consumption are untouched.

**9. No feature flag — superseded.** An earlier draft of this change introduced a
new flag, `pcr-recruiting-dashboard`, with the legacy CCR-dropdown flow kept as a
fallback when it was off (CCR-primary-type markup/JS restored/kept rather than
deleted, `#prc_schedule_block` and the server-side validation/`$usesInlinePrc`
guarded by the flag, the `elseif ($request->schedule_type == 'CCR')` branches and
`if ($scheduleTypes_id==2) {...}` block restored as the flag-off path). The team
lead who originally asked for this later corrected himself: **all tasks tied to
migrating the CCR process to PRC ship unconditionally, no flag.** The flag was
removed from `config/featureflags.php`, and every piece of legacy CCR-dropdown code
that had been restored under it was deleted again, along with the flag checks
themselves (`needsPrc()`, `#prc_schedule_block`'s markup guard, the server-side
validation, `$usesInlinePrc`). The pre-existing, unrelated
`change-client-notes-schedule-next-steps` flag is untouched by this — it still
separately gates only the legacy `.ccrbefornext` banner in
`recruiterTracker/nextstep.blade.php`.

## Risks / Trade-offs

- **[Risk]** A third caller of `saveScheduleNext()` exists beyond the two blade forms:
  `TrackersProxyController::save()` (Private API `POST /private-api/trackers`,
  route `trackers-proxy.save`), which forwards an arbitrary `$request` straight into
  the controller and could theoretically still submit `schedule_type=CCR`,
  bypassing the dropdown removal entirely. → **Mitigation**: none applied — per
  explicit user decision, this Private API endpoint is out of scope for this change
  and is left as a known, accepted risk rather than guarded against.
- **[Risk]** No dedup guard exists in the heuristic block against creating a second
  `type_id=2` row if one is already open for the tracker; the new explicit-creation
  path inherits the same gap. → **Mitigation**: out of scope for this change (pre-
  existing behavior), but flagged here for a possible follow-up.
- **[Trade-off]** Extracting `assigned_ccr` into a shared helper changes behavior
  (CCM now gets assigned in a path where it silently didn't before) rather than being
  a pure refactor. Accepted per explicit product/user decision — this is the correct
  fix, not an unintended side effect.
- **[Moot — flag removed]** An earlier draft had `recruiterTracker/nextstep.blade.php`'s
  PRC block markup inherit the pre-existing `change-client-notes-schedule-next-steps`
  flag, while `common/schedule_next_step.blade.php`'s PRC block markup wasn't
  wrapped in any flag at all — an asymmetry that was corrected by giving both files
  their own `pcr-recruiting-dashboard` guard. That flag (and both files' guards) is
  now removed entirely per Decision 9; `#prc_schedule_block` renders unconditionally
  in both files, so the asymmetry this bullet described no longer applies.
- **[Found during manual testing, fixed]** This app's `.datepicker()` resolves to
  bootstrap-datepicker (see tasks.md section 14), but that alone didn't fully
  explain every reported styling issue. Two further, deeper root causes were found
  and fixed: (1) a race condition — bootstrap-datepicker's JS was loaded via a plain
  `<script src>` inside these AJAX-injected modals, which doesn't block; the
  document is already "ready" by the time the response is injected, so inline
  `.datepicker()` init calls could run before the external file finished loading and
  silently fall back to jQuery UI (loaded synchronously, unconditionally, earlier in
  `myaccount.blade.php`'s `<head>`) — fixed by loading it via a synchronous `$.ajax`
  call instead; (2) `bootstrap-editable.css` (an unrelated, vendored library loaded
  unconditionally in `nextstep.blade.php`) ships a `.datepicker>div{display:none}`
  rule written for an older bootstrap-datepicker DOM convention (a companion
  `.datepicker.days`-style class on the root element) that this app's actual
  vendored `bootstrap-datepicker.min.js` doesn't use — it hid the calendar's active
  view outright. Fixed with a corrective `display:block` override (the other,
  inactive views stay hidden via their own inline `style="display:none"`, which
  always wins over any stylesheet rule regardless of load order).

## Migration Plan

No data migration. Rollout is a single code change; once merged, CCR is simply no
longer selectable and new qualifying next-step submissions produce a real PRC
record instead of a heuristic one. No rollback beyond a standard revert — no
persisted data format changes.

## Open Questions

None outstanding for this change's own scope — base branch, scope of the heuristic
removal (all 4 types), dead code removal, and the `assigned_ccr` relocation were all
explicitly confirmed with the requester during planning.

**Pending, tracked separately (not yet decided/applied):** `feature/AOC-4106_remove_ccr_auto_create_for_pepi_updated`
(Hanna Hranenko) is an unmerged colleague branch that removes the heuristic
auto-CCR creation for PEPI specifically, behind its own flag
(`remove-ccr-auto-scheduling`). Investigation (not yet applied — no code merged or
cherry-picked) found this is a natural predecessor/subset of this change's own
heuristic removal, not a conflicting direction — and now that `pcr-recruiting-dashboard`
is gone and `$usesInlinePrc` is unconditional (Decision 9), the reconciliation is
simpler than originally planned: `$usesInlinePrc` is now *always* true for PEPI, so
the heuristic branch her flag targets is already permanently unreachable for PEPI
regardless of her flag's state. If her branch is merged as-is, `remove-ccr-auto-scheduling`
would have no observable effect for PEPI — her ticket's intent is already fully
satisfied by this change. Still awaiting a decision on whether/when to actually
cherry-pick `971891e56` (without the unrelated `AOC-4110` commit it also carries),
or whether to just let it lapse as superseded. Also surfaced during that
investigation, independent of this change: her implementation doesn't touch
`$needsCCR`, so a tracker could get permanently stuck in "proposed"/"Clinical
Competency Review" for PEPI if her flag were ever exercised on an older version of
this code without this change's PRC form — flagged for her awareness, not
something this change needs to fix.
