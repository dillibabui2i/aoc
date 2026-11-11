## 1. Known assumptions (not blockers)

- [ ] 1.1 Scheduled follow-ups offer only Completed/Attempted/Canceled as the next Outcome (not "Scheduled" again) — design.md Decision 7.
- [ ] 1.2 Required-field sets per outcome (design.md Decision 8) are assumed, not confirmed.
- [ ] 1.3 Next Follow-up Due Date default falls back to "now + 1 week" if the current follow-up was never Scheduled (design.md Decision 9).
- [ ] 1.4 "Automated recurring follow-up generation" (epic rationale) is implemented only as the manual "Next Follow-up Due" toggle at Completed/Attempted time — no real backend scheduler exists (design.md Non-Goals).

## 2. Service layer: Follow-up CRUD, search, and bucket aggregation (mocked, swappable)

- [x] 2.1 Add `followUps: []` to every mock tracker's default shape: `MOCK_TRACKERS_BY_ID['#10087']` and the object built in `createTracker`.
- [x] 2.2 Update the three existing "In Progress" fixtures from the Care Coordination Activities story (`#12346`/12345-PDN, `#21001`/91299-PDN, `#44002`/8979-PDN): give them `level: 'Level I/III'` (admission ~35 days ago), `level: 'Level II/III'` (~65 days ago), `level: 'Level III'` (~95 days ago) respectively, to exercise the 30/60/90-day message and Next-Due-default logic.
- [x] 2.3 Seed `MOCK_TRACKERS_BY_ID['#10087']` with 3-4 representative follow-ups (Scheduled, Completed, Completed by a different assignee, Attempted — matching the Figma Follow-up tab table) for QA/demo fidelity.
- [x] 2.4 Implement `searchHospitalizedClientsForFollowUp(query)`: filters `MOCK_TRACKERS_BY_ID` to trackers with `status !== 'Closed'`, matching `clientName`; resolves `{ clientId, fullName, trackerId, facilityName, admissionDateTime, level }[]`.
- [x] 2.5 Implement `createFollowUp(trackerId, payload)` / `updateFollowUp(trackerId, followUpId, payload)`: mirror `createContact`/`updateContact` exactly, operating on `tracker.followUps`.
- [x] 2.6 Implement `getFollowUpsBucket()`: scans every tracker with `status !== 'Closed'`, flattens `followUps`, excludes `status === 'Completed' || status === 'Attempted'`, maps to `{ id, patient_name, patient_id, tracker_id, level, hospitalized, status, facility, assignee, due_date }` (plus `hospitalization_tracker_id`/`followUpId` for row-click wiring).
- [x] 2.7 Write/extend `hospitalizationTracker.service.spec.js`: search returns only non-Closed trackers; create/update persist and are visible via `getTrackerById`; bucket aggregation excludes Completed/Attempted and Closed-tracker follow-ups.

## 3. Store: `followUp.store.js` (new)

- [x] 3.1 State: `modalOpen`, `pendingClient` (search selection pre-first-save), `followUp` (persisted record once created), `onSaved` (optional refresh callback registered by the opener).
- [x] 3.2 `openCreate(onSaved)`: resets state, sets `onSaved`, opens the modal.
- [x] 3.3 `async openExisting({ trackerId, followUpId }, onSaved)`: fetches the tracker, finds the matching follow-up, sets `followUp` (embedding `trackerId`), opens the modal.
- [x] 3.4 `selectClientForCreate(client)`: sets `pendingClient`.
- [x] 3.5 `closeModal()`: resets all state.
- [x] 3.6 `async submitCreate(fields)`: requires `pendingClient`; calls `createFollowUp` with `status: 'Open'` plus the client snapshot and any entered fields; sets `followUp`; calls `onSaved?.()`.
- [x] 3.7 `async submitOutcome(outcome, fields)`: builds the payload per outcome (Scheduled/Completed/Attempted/Canceled, including system-set Completion/Attempt Date+By); calls `updateFollowUp`; sets `followUp`; if Completed/Attempted with Next Follow-up Due, also calls `createFollowUp` for the chained Scheduled record; calls `onSaved?.()` after each successful step.
- [x] 3.8 Write `followUp.store.spec.js`: full lifecycle — create, schedule, complete without/with next follow-up, attempt without/with next follow-up, cancel; `onSaved` invoked on each successful mutation; `openExisting` resolves the right follow-up from a fetched tracker.

## 4. Store: `hospitalizationTracker.store.js` addition

- [x] 4.1 Add `refreshTracker()`: re-fetches `getTrackerById(tracker.value.trackerId)` and reassigns `tracker.value`, without touching `wizardStep`/`isModalOpen` (design.md Decision 5).
- [x] 4.2 Write/extend `hospitalizationTracker.store.spec.js` for `refreshTracker`.

## 5. `HospitalizedClientSearch.vue` (FollowUp)

- [x] 5.1 Build the component (mirrors Care Coordination's, but calls `searchHospitalizedClientsForFollowUp`): debounced search + dropdown, emits the selected client.
- [x] 5.2 Write a spec covering debounced search and selection emission.

## 6. `FollowUpModal.vue`

- [x] 6.1 Stage "New" (no `followUp` yet): renders `HospitalizedClientSearch`; selecting a client calls `store.selectClientForCreate`; Save calls `store.submitCreate`.
- [x] 6.2 Stage header (once `pendingClient` or `followUp` exists): read-only Client ID/Hospitalization ID/Facility Name/Admission Date; informational 30/60/90-day message computed from `pendingClient` only (design.md Decision 11).
- [x] 6.3 Stage "Open"/"Scheduled" (editable): Follow-up Outcome dropdown with status-dependent options (design.md Decision 7); outcome-specific field sections (Scheduled/Completed/Attempted/Canceled) rendered once an outcome is locally selected; required-field validation (red border) per outcome.
- [x] 6.4 "Next Follow-up Due" toggle (Completed/Attempted only): defaults per design.md Decision 9's level rule; reveals/validates Next Follow-up Due Date + Next Follow-up Assigned To when on.
- [x] 6.5 Read-only stage (Completed/Attempted/Canceled): all fields as plain text, only a "Close" action.
- [x] 6.6 Submitting any outcome calls `store.submitOutcome(outcome, fields)`; the modal never auto-closes on submit — only the ✕/Close button calls `store.closeModal()`.
- [x] 6.7 Write `FollowUpModal.spec.js`: New→Open transition; each outcome's field visibility and validation; Scheduled→Completed/Attempted/Canceled re-transition; Next Follow-up Due defaulting per level; chained-follow-up creation call; read-only rendering for finalized statuses.

## 7. `FollowUpTab.vue`

- [x] 7.1 Build the component: `UiTable` of `store.tracker.followUps` sorted by `createdAt` descending (columns: Follow-up Date, Assigned To, Status, Actions), calendar icon per row calling `followUpStore.openExisting({ trackerId, followUpId: row.id }, () => hospitalizationTrackerStore.refreshTracker())`.
- [x] 7.2 Mount `FollowUpModal` from this component.
- [x] 7.3 Write `FollowUpTab.spec.js`: renders all follow-ups (including Completed/Attempted) sorted newest-first; clicking the calendar icon calls `openExisting` with the right ids and a working refresh callback.

## 8. Wire the bucket

- [x] 8.1 `CommunicationBucketSection.vue`: import `useHasPermission`, add a "+" icon (permission-gated) wired to `followUpStore.openCreate(() => communicationStore.fetchAll())`.
- [x] 8.2 Wire the Action-column calendar icon (permission-gated) to `followUpStore.openExisting({ trackerId: row.hospitalization_tracker_id, followUpId: row.followUpId }, () => communicationStore.fetchAll())`; leave the eye/sticky-note icons untouched (out of scope).
- [x] 8.3 Mount `<FollowUpModal />` in this section.
- [x] 8.4 `communication.store.js`: `fetchAll` now calls `HospitalizationTrackerService.getFollowUpsBucket()` instead of returning the local `MOCK_DATA`/`MOCK_META` (design.md Decision 6); remove the now-unused constants.
- [x] 8.5 Write/extend `CommunicationBucketSection.spec.js` (new, none exists yet): "+"/calendar icons hidden without the permission; clicking each calls the right store action.

## 9. Wire into `HospitalizationTrackerModal.vue`

- [x] 9.1 Replace the `#followUp` slot's `PlaceholderTab` with `FollowUpTab`.
- [x] 9.2 Write/extend `HospitalizationTrackerModal.spec.js`: opening the Follow-up tab renders `FollowUpTab` instead of the old placeholder text.

## 10. Verification against acceptance criteria

- [x] 10.1 AC1/AC2 — covered by task 8.5's permission gating.
- [x] 10.2 AC3-AC5 — covered by `FollowUpModal.spec.js` (New stage, search, auto-populate).
- [x] 10.3 AC6 — covered by `followUp.store.spec.js`'s `submitCreate` case.
- [x] 10.4 AC7-AC9, AC24 — covered by `FollowUpModal.spec.js`'s Scheduled outcome cases.
- [x] 10.5 AC10-AC13, AC25/AC26 — covered by `FollowUpModal.spec.js`'s Completed outcome cases (with/without next follow-up) and `followUp.store.spec.js`'s chained-creation case.
- [x] 10.6 AC14-AC17, AC27/AC28 — covered by `FollowUpModal.spec.js`'s Attempted outcome cases.
- [x] 10.7 AC18/AC19 — covered by `FollowUpModal.spec.js`'s Canceled outcome case.
- [x] 10.8 AC20 — covered by `CommunicationBucketSection.spec.js`.
- [x] 10.9 AC21/AC22 — covered by `FollowUpModal.spec.js`'s parametrized editable/read-only cases.
- [x] 10.10 AC23 — covered by `FollowUpModal.spec.js`'s New-stage validation case.
- [x] 10.11 AC29/AC30 — covered by `hospitalizationTracker.service.spec.js`'s association/persistence assertions.
- [x] 10.12 AC31-AC36 — covered by `FollowUpModal.spec.js`'s informational-message and Next-Due-defaulting cases.
- [x] 10.13 AC37-AC41 — covered by `FollowUpTab.spec.js`.
- [x] 10.14 Ran `npm run test` — full suite green, no regressions.

## 11. Cleanup

- [x] 11.1 Run `eslint --fix` on every new/changed file.
- [ ] 11.2 Manually verify in a running dev build the full New→Open→Scheduled→Completed(+chained)/Attempted/Canceled workflow, both from the Communications bucket and the tracker's Follow-up tab, against the Figma reference.
