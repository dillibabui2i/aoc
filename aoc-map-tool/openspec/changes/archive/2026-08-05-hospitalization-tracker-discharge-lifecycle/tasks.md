## 1. Known deferrals / decisions (not blockers)

- [ ] 1.1 Note-text and outcome-option discrepancies between the Figma frames and the written ACs (line 2 of the Discharge note, "<discharge date and time>" wording, and per-status extra Close/Transfer options) are resolved in favor of the written ACs, per design.md Decision 8. Flagged for requester confirmation, not a blocker.
- [ ] 1.2 `Transfer` (Reason, Closed outcome) remains deferred to AOC-3482, unchanged from AOC-4472 — no action needed here.

## 2. Service layer: generic outcome fields (mocked, swappable)

- [x] 2.1 In `hospitalizationTracker.service.js`, add a `STATUS_BY_OUTCOME` map covering `Discharge → Discharged`, `Ready for Review → Ready for Review`, `Ready for Next Steps → Ready for Next Steps`, `Completed → Completed`.
- [x] 2.2 Extend `updateOutcome(trackerId, { outcome, reason, fields = {}, notesToAppend = [] })`: keep the existing `In Progress`/`Closed` branches untouched; for any other outcome, `Object.assign(tracker, fields)` then set `tracker.status` from `STATUS_BY_OUTCOME`.
- [x] 2.3 Write/extend `hospitalizationTracker.service.spec.js` covering `updateOutcome` for each of the four new outcomes: fields merged onto the tracker, status set correctly, notes appended, and `getTrackerById` reflects it afterward.

## 3. Store: four new outcome actions

- [x] 3.1 `submitDischarge(fields)`: builds the note (`header: "Discharged by <user> at <now>"`, `detail: "Status changed to 'Discharged' by <user>"` per design.md Decision 8), calls `updateOutcome(trackerId, { outcome: 'Discharge', fields, notesToAppend })`.
- [x] 3.2 `submitReadyForReview({ reviewer, notes })`: captures `whoSentToReview: tracker.value.assignedTo` (current assignee, read before the call) and `assignedTo: reviewer` into `fields`; note `detail` is the entered `notes` verbatim (AC12).
- [x] 3.3 `submitReadyForNextSteps({ nextStepsBy, notes })`: `fields: { assignedTo: nextStepsBy }`; note `detail` is the entered `notes` verbatim (AC21).
- [x] 3.4 `submitCompleted({ completedBy, notes })`: `fields: { completedBy }` (no reassignment); note `detail` is the entered `notes` verbatim (AC29).
- [x] 3.5 Export all four new actions from the store's returned surface.
- [x] 3.6 Write/extend `hospitalizationTracker.store.spec.js` covering each action: correct `updateOutcome` call shape, resulting `tracker.status`/`assignedTo`/`whoSentToReview`/`completedBy`, and note content.

## 4. Fix: `Assigned To` dropdown staleness (design.md Decision 6)

- [x] 4.1 In `TrackerInfoGrid.vue`, add `watch(() => store.tracker?.assignedTo, (val) => { if (val !== undefined) draft.assignedTo = val })` so externally-driven reassignment (Ready for Review/Ready for Next Steps) is reflected in the dropdown.
- [x] 4.2 Write/extend `TrackerInfoGrid.spec.js` covering: when `store.tracker.assignedTo` changes programmatically (not via the dropdown itself), the rendered `Assigned To` select's value updates to match.

## 5. New step-field components

- [x] 5.1 Build `DischargeFields.vue`: Expected Discharge Date (date, required), Discharge Date (date, required), a toggle ("First billable shift completed within 48 hours following hospital discharge?") with a conditional First billable shift date (date, required only when the toggle is on), Discharge Notes (textarea, required). Local validation sets a `has-error` class (date fields, via the `:deep()` override per design.md Decision 3) or the native `error` prop (textarea) on invalid fields; valid submit calls `store.submitDischarge(...)`.
- [x] 5.2 Build `ReadyForReviewFields.vue`: Reviewer (select, `ASSIGNED_TO_OPTIONS`, required, labeled "Review to be made by" per the Figma reference), Notes (textarea, required). Valid submit calls `store.submitReadyForReview(...)`.
- [x] 5.3 Build `ReadyForNextStepsFields.vue`: "Next Steps to be made by" (select, required, defaults to `store.tracker.whoSentToReview` per AC18), Notes (textarea, required). Valid submit calls `store.submitReadyForNextSteps(...)`.
- [x] 5.4 Build `CompletedFields.vue`: "Completed by" (select, required, defaults to `store.tracker.assignedTo` per AC26), Notes (textarea, required). Valid submit calls `store.submitCompleted(...)`.
- [x] 5.5 Write specs for all four: required-field validation prevents submit and marks the right field(s) invalid (AC4/AC10/AC19/AC27), the conditional field's visibility (Discharge only), the two default-value cases (AC18/AC26), and a successful submit calling the right store action with the right payload.

## 6. `OutcomeSubmitBar.vue`: wire in the new steps

- [x] 6.1 Extend `outcomeOptions` per design.md Decision 8's table: `Discharged → ['Ready for Review']`, `Ready for Review → ['Ready for Next Steps']`, `Ready for Next Steps → ['Completed']`.
- [x] 6.2 Extend the root `v-if` to also hide when `store.tracker?.status === 'Completed'` (AC31), alongside the existing `Closed` case.
- [x] 6.3 Remove `Discharge` from `submitDisabled`/the deferred-outcome comment (it now has real behavior); `Transfer` remains the only deferred case.
- [x] 6.4 Render `DischargeFields`/`ReadyForReviewFields`/`ReadyForNextStepsFields`/`CompletedFields` (each with its own embedded Submit) when `outcome` matches, in place of the generic Submit button — which remains only for `In Progress`/`Closed`.
- [x] 6.5 Update `OutcomeSubmitBar.spec.js`: replace the now-obsolete "disables Submit when Discharge is selected" case with one confirming `DischargeFields` renders instead; add cases for the three new statuses' option lists and hidden-when-`Completed`.

## 7. Closed-state disabling extended to `Completed`

- [x] 7.1 `TrackerInfoGrid.vue`: extend `isEditableNow(field)`'s status check from `!== 'Closed'` to `!== 'Closed' && !== 'Completed'` (AC30 for `Assigned To`, plus the custom fields).
- [x] 7.2 `LoggedField.vue`: extend `isDisabled` from `=== 'Closed'` to `=== 'Closed' || === 'Completed'` (AC32).
- [x] 7.3 Write/extend specs covering the `Completed` case for both components, including "loaded already-Completed" (reopen) per AC33 — mirroring the existing `Closed`-reopen coverage from AOC-4472.

## 8. Current Hospitalization Info: post-discharge fields

- [x] 8.1 Add four entries to `CURRENT_INFO_FIELDS` in `hospitalizationTrackerFields.js`: `expectedDischargeDate` (date), `dischargeDate` (date), `firstBillableShiftCompleted` (toggle), `firstBillableShiftDate` (date, `visibleIf: (tracker) => !!tracker.firstBillableShiftCompleted`) — all four flagged `postDischargeOnly: true`.
- [x] 8.2 Update `CurrentHospitalizationInfoTab.vue` to filter `CURRENT_INFO_FIELDS` by `postDischargeOnly`/`visibleIf` against `store.tracker` before grouping into rows (replacing the static `['A'..'F']` row-key array with one derived from the filtered fields).
- [x] 8.3 Write/extend `CurrentHospitalizationInfoTab.spec.js`: post-discharge fields hidden before `Discharged`, shown from `Discharged` onward; `firstBillableShiftDate` hidden/shown based on the toggle's value.

## 9. Verification against acceptance criteria

- [x] 9.1 AC1/AC2 — unchanged from AOC-4402/4472 (`useHasPermission` stub); confirmed existing coverage still passes.
- [x] 9.2 AC3/AC4/AC5/AC6 — covered by `DischargeFields.spec.js` and `hospitalizationTracker.store.spec.js`'s `submitDischarge` case.
- [x] 9.3 AC7/AC8 — covered by `OutcomeSubmitBar.spec.js` (options for `Discharged`) and `TrackerInfoGrid.spec.js` (Assigned To enabled).
- [x] 9.4 AC9–AC14 — covered by `ReadyForReviewFields.spec.js` and `hospitalizationTracker.store.spec.js`'s `submitReadyForReview` case (reviewer assignment + `whoSentToReview`).
- [x] 9.5 AC15/AC16 — covered by `OutcomeSubmitBar.spec.js` and `TrackerInfoGrid.spec.js`.
- [x] 9.6 AC17–AC22 — covered by `ReadyForNextStepsFields.spec.js` (including the AC18 default-value case) and `hospitalizationTracker.store.spec.js`'s `submitReadyForNextSteps` case.
- [x] 9.7 AC23/AC24 — covered by `OutcomeSubmitBar.spec.js` and `TrackerInfoGrid.spec.js`.
- [x] 9.8 AC25–AC29 — covered by `CompletedFields.spec.js` (including the AC26 default-value case) and `hospitalizationTracker.store.spec.js`'s `submitCompleted` case.
- [x] 9.9 AC30–AC32 — covered by `TrackerInfoGrid.spec.js`/`LoggedField.spec.js`/`OutcomeSubmitBar.spec.js`'s `Completed` cases.
- [x] 9.10 AC33 — covered by the "loaded already-Completed" (reopen) cases in task 7.3.
- [x] 9.11 Ran `npm run test` — 24/24 files, 134/134 tests pass (110 pre-existing + 25 new/extended for this change), no regressions.

## 10. Cleanup

- [x] 10.1 Ran `eslint --fix` on every new/changed file — zero errors/warnings remain in this change's files.
- [ ] 10.2 Manually verify in a running dev build (if available) at least the full Discharge → Ready for Review → Ready for Next Steps → Completed path against the Figma reference — component specs don't substitute for a real visual check of the new forms' layout/red-border styling. **Not done in this session** — no interactive browser available; `npx mix` (production build) was run instead and compiles successfully, but that only verifies the bundle builds, not the rendered UI/interactions.
- [ ] 10.3 Run `/opsx:sync` once implementation matches the spec — note that AOC-4402's and AOC-4472's deltas targeting the same `hospitalization-tracker-creation` capability were also never synced; confirm the intended merge order before syncing this one.
