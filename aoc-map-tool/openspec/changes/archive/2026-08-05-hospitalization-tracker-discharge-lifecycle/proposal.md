## Why

Once a hospitalization tracker reaches "In Progress" (AOC-4472), there is no way to finalize it: the Outcome dropdown only offers `Discharge`/`Closed`, and `Discharge` is currently a deliberately inert, disabled-Submit placeholder (no ACs existed for it when AOC-4472 shipped). ICM users need a controlled, auditable multi-step path — Discharge → Ready for Review → Ready for Next Steps → Completed — that captures discharge details, supports reassignment for review and follow-up, and locks the tracker down once finalized.

## What Changes

- Wire up the `Discharge` outcome (previously a disabled placeholder): selecting it shows a dedicated form (Expected Discharge Date, Discharge Date, a "first billable shift within 48h" toggle with a conditional date, Discharge Notes). Submitting validates required fields (red border on the missing ones), sets status to `Discharged`, appends a note, and persists the entered values as new, permanently-editable fields on the "Current Hospitalization Info" tab.
- **Ready for Review**: from `Discharged`, shows a Reviewer + Notes form. Submitting records the current assignee into a new internal `whoSentToReview` field, reassigns the tracker to the selected reviewer, sets status to `Ready for Review`, and appends a note (using the entered notes verbatim as the note's second line, per the ACs and Figma).
- **Ready for Next Steps**: from `Ready for Review`, shows a "Next Steps to be made by" (defaulting to `whoSentToReview`) + Notes form. Submitting reassigns the tracker to the selected user, sets status to `Ready for Next Steps`, and appends a note.
- **Completed**: from `Ready for Next Steps`, shows a "Completed by" (defaulting to the current assignee) + Notes form. Submitting sets status to `Completed`, appends a note, disables `Assigned To`, hides the Outcome control entirely, and disables every hospitalization field — durably, including on reopen.
- All four steps' required-field validation shows a **red border** on the invalid field (a different, more field-level pattern than AOC-4472's single error-message convention), matching the ACs' explicit wording.
- `Discharge` is removed from the "deferred, Submit-disabled" list introduced in AOC-4472 (it now has real, defined behavior). `Transfer` (Reason option, Closed outcome) remains deferred to AOC-3482, unchanged.
- Fixes a latent bug exposed by this story: the "Assigned To" dropdown was bound to a one-time `draft` copy of the tracker that never re-synced after the tracker's `assignedTo` changed programmatically (only user edits flowed back). Ready for Review/Ready for Next Steps's automatic reassignment would otherwise not visibly update the dropdown.

## Capabilities

### Modified Capabilities
- `hospitalization-tracker-creation`: adds the Discharge → Ready for Review → Ready for Next Steps → Completed lifecycle requirements on top of the Outcome/notes/tab-rearrangement/disabling behavior already added by AOC-4472's delta.

### New Capabilities
(none — extends the same tracker capability)

## Impact

- `resources/js/vue/modules/vbc/components/HospitalizationTracker/OutcomeSubmitBar.vue` — `outcomeOptions` extended per status (`Discharged` → `Ready for Review`, `Ready for Review` → `Ready for Next Steps`, `Ready for Next Steps` → `Completed`); root visibility extended to also hide for `Completed`; renders one of four new step-field components instead of the generic Submit button when those outcomes are selected; `Discharge` no longer disables Submit.
- New: `DischargeFields.vue`, `ReadyForReviewFields.vue`, `ReadyForNextStepsFields.vue`, `CompletedFields.vue` — one small form component per step, each reading/writing the store directly (matching the existing convention), owning its own required-field (red-border) validation before calling the corresponding store action.
- `resources/js/vue/modules/vbc/store/hospitalizationTracker.store.js` — four new actions (`submitDischarge`, `submitReadyForReview`, `submitReadyForNextSteps`, `submitCompleted`), each building the note text and calling the (extended) service, mirroring `submitOutcome`'s existing style.
- `resources/js/vue/modules/vbc/services/hospitalizationTracker.service.js` — `updateOutcome` extended (backward compatibly) to accept a generic `fields` object merged onto the mock tracker, plus a status-per-outcome map covering the four new outcomes.
- `resources/js/vue/modules/vbc/config/hospitalizationTrackerFields.js` — four new `CURRENT_INFO_FIELDS` entries (Expected Discharge Date, Discharge Date, the billable-shift toggle, First billable shift date), flagged so they only render once the tracker has passed through Discharge.
- `resources/js/vue/modules/vbc/components/HospitalizationTracker/CurrentHospitalizationInfoTab.vue` — row/field list becomes filtered (post-discharge fields hidden before Discharge; the conditional date hidden unless its toggle is on) instead of a static row list.
- `resources/js/vue/modules/vbc/components/HospitalizationTracker/TrackerInfoGrid.vue` — `isEditableNow` also disables the custom fields and `Assigned To` for `Completed` (previously only `Closed`); the `draft.assignedTo` re-sync bugfix.
- `resources/js/vue/modules/vbc/components/HospitalizationTracker/LoggedField.vue` — `isDisabled` also true for `Completed` (previously only `Closed`).
- No change to `HospitalizationTrackerModal.vue`'s tab structure: Discharge only happens from `In Progress`, so `wasInProgress` is already `true` and the nested 9-item tab layout (AOC-4472) already applies through every subsequent step — confirmed against all eight Figma frames.
- No backend (`app/VBC/**`, routes) changes — frontend + mock data only, per explicit scope; real endpoints are a future integration.
