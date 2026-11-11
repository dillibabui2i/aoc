## Why

The "Hospitalization Record" modal built in AOC-4402 (`hospitalization-tracker-creation`) renders a saved tracker in an "Open" state with a fully inert Outcome control (`OutcomeSubmitBar.vue`: local `ref`, static option list, `Submit` does nothing) and an empty Notes sidebar shell. ICM users have no way to actually progress a tracker through its lifecycle ("Open" → "In Progress" → "Closed"), so trackers never reflect real-world status, no audit trail (notes) is produced for status changes, and closed trackers keep behaving as if fully open/editable. This blocks the standard AOC hospitalization workflow (AOC-4472).

## What Changes

- Wire `OutcomeSubmitBar.vue` to the tracker's actual status: the Outcome dropdown's option list is now driven by `tracker.status` (Open → `In Progress`/`Closed`; In Progress → `Discharge`/`Closed`), and Submit calls a new store action that performs the transition instead of doing nothing.
- Add a blue informational banner (reusing `@angels-of-care/ui`'s `UiAlert`) above the Outcome dropdown when `admissionDateTime` is more than 24 hours in the past and the tracker isn't yet `Closed`.
- **"In Progress" transition**: updates `tracker.status`, appends a two-line note, keeps `Assigned To` enabled, and rearranges the tab bar from the current flat 4-tab layout into the nested layout required by AC8 (a new "Hospitalization Info" parent tab containing "Current Hospitalization Info"/"Hospitalization History" as sub-tabs, alongside five new top-level placeholder tabs: Contacts, Diagnosis, Sick Plan, Care Coordination, Education, Follow-up).
- **"Closed" transition**: shows a required "Reason" dropdown (ED Visit / Opened by mistake / Transfer) with standard required-field validation; on submit, updates `tracker.status` and `tracker.reason`, appends a two-line note, disables the custom fields, `Assigned To`, and every Current Hospitalization Info field, hides the Outcome dropdown and the Notes icon, closes the modal, and refreshes the "All Hospitalizations" bucket table.
- Reopening a previously closed tracker (`loadTrackerById`/`openExistingTracker`) preserves and re-renders this same disabled/hidden state — closed is a durable, re-enterable state, not just a one-time submit side effect.
- `Discharge` (Outcome option) and `Transfer` (Reason option) are both selectable per the ACs but Submit stays disabled when either is chosen — no defined behavior exists for them yet (`Transfer` is explicitly deferred to AOC-3482; `Discharge` has no ACs in this ticket at all).
- Extend `NotesSidebar.vue` to render structured two-line note entries (header/timestamp line + detail line) instead of the current single-line-only shell, and wire it to the tracker's real `notes` array (currently always empty/unused).
- Gating: all of the above is only reachable once already past the existing `useHasPermission('Intensive Case Management')` check (AC1/AC2 are already satisfied by the existing stub from AOC-4402; no new gating code needed).

## Capabilities

### Modified Capabilities
- `hospitalization-tracker-creation`: The "Outcome control is inert" scenario becomes "Outcome control drives status transitions," and new requirements are added for the informational banner, In Progress/Closed transitions, note formatting, tab rearrangement, closed-state field/control disabling and persistence, and the deferred Discharge/Transfer branches.

### New Capabilities
(none — this extends the existing tracker capability rather than introducing a new one)

## Impact

- `resources/js/vue/modules/vbc/components/HospitalizationTracker/OutcomeSubmitBar.vue` — becomes state-driven (options per `tracker.status`), adds the Reason dropdown + validation, adds the 24h admission banner, calls the new store action on Submit.
- `resources/js/vue/modules/vbc/components/HospitalizationTracker/NotesSidebar.vue` — renders structured two-line note entries; receives the tracker's real `notes` array from `HospitalizationTrackerModal.vue` instead of no prop at all.
- `resources/js/vue/modules/vbc/components/HospitalizationTracker/TrackerTitleBar.vue` — hides the Notes icon when `tracker.status === 'Closed'`.
- `resources/js/vue/modules/vbc/components/HospitalizationTracker/TrackerInfoGrid.vue` — custom fields and `Assigned To` become disabled once `tracker.status === 'Closed'` (previously governed only by `wizardStep`).
- `resources/js/vue/modules/vbc/components/HospitalizationTracker/LoggedField.vue` — every rendered field type gains a `disabled` state driven by `tracker.status === 'Closed'`.
- `resources/js/vue/modules/vbc/components/HospitalizationTracker/TrackerTabs.vue` — gains optional one-level-deep sub-tab support (backward compatible: a tab with no `subTabs` behaves exactly as today).
- `resources/js/vue/modules/vbc/components/HospitalizationTracker/HospitalizationTrackerModal.vue` — computes the flat-vs-nested tab structure from `tracker.status`/`tracker.wasInProgress`, adds five new placeholder tabs (Contacts, Diagnosis, Care Coordination, Education — "Sick Plan" and "Follow-up" already exist and are reused), passes `tracker.notes` into `NotesSidebar`.
- `resources/js/vue/modules/vbc/store/hospitalizationTracker.store.js` — new `submitOutcome({ outcome, reason })` action owning validation, the mocked service call, note-text construction, and (on Closed) closing the modal and triggering `allHospitalization.store.js`'s `fetchAll()`.
- `resources/js/vue/modules/vbc/services/hospitalizationTracker.service.js` — new mocked `updateOutcome(trackerId, { outcome, reason })` method; existing mock tracker fixture(s) gain `notes: []`, `reason: null`, `wasInProgress: false` defaults; exports the mock "current user" name so the store can attribute notes without duplicating it.
- No changes to `allHospitalization.store.js`/`.service.js` beyond calling the existing `fetchAll()` — that store/service already hit the real `/vbc/hospitalization/data` endpoint and are untouched otherwise (see design.md's Risks section for the resulting fidelity gap between this mocked tracker and that real list).
- No backend (`app/VBC/**`, routes) changes — this is a frontend + mock-data-only change, per explicit scope.
