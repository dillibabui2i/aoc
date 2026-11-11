## 1. Known assumptions (not blockers)

- [ ] 1.1 Required fields: Coordination Type, Provider, Assigned To, Due Date (design.md Decision 5) — flagged for requester confirmation.
- [ ] 1.2 The Hospitalization Tracker modal's own "Care Coordination" tab (Figma Scenario 1) is out of scope — no AC covers it; stays a `PlaceholderTab`.
- [ ] 1.3 Three new "In Progress" tracker fixtures are added for clients that previously had none (design.md Decision 4), rather than touching the existing `#10087`/`99999-PDN` fixture.

## 2. `hospitalizationTracker.service.js`: active-hospitalized-client search

- [x] 2.1 Add three new tracker fixtures to `MOCK_TRACKERS_BY_ID`, status `'In Progress'`, for clients `12345-PDN` (John Taylor), `91299-PDN` (John Doe), `8979-PDN` (John Wayne) — each carrying `clientId`, `clientName`, `facilityName`, `admissionDateTime`, distinct `trackerId`s.
- [x] 2.2 Implement `searchActiveHospitalizedClients(query)`: filters `MOCK_TRACKERS_BY_ID` to `status === 'In Progress'` entries, optionally matching `clientName` against `query` (case-insensitive substring, empty query returns none — matching `searchClients`' existing behavior), resolves `{ clientId, fullName, trackerId, facilityName, admissionDateTime }[]`.
- [x] 2.3 Write/extend `hospitalizationTracker.service.spec.js`: returns only "In Progress" trackers; matches by name substring; empty query returns nothing; existing `99999-PDN`/`#10087` fixture (status `Open` by default) is correctly excluded.

## 3. Config: care coordination field metadata

- [x] 3.1 Create `config/careCoordinationActivityFields.js`: `COORDINATION_TYPE_OPTIONS` (AngelLink, Branch/CCM/CCC, BD, DME, MD/Pharmacy, Telephone/Text OR, Transportation, Other Community Resources — matching the bucket's existing `type` values) and `PROVIDER_OPTIONS` (illustrative mock list, e.g. First Provider of Dallas, DME Provider of Dallas). Re-export/import `ASSIGNED_TO_OPTIONS` from `hospitalizationTrackerFields.js` for the "Assigned To" field rather than duplicating it.

## 4. `careCoordinationActivities.store.js`: modal lifecycle and mocked create/update

- [x] 4.1 Add `hospitalization_tracker_id` to every `MOCK_DATA` row, linking some to the existing `#10087` fixture (status `Open` by default → read-only when opened) and others to the new "In Progress" fixtures from task 2.1 (→ editable).
- [x] 4.2 Add state: `modalOpen`, `editingActivity` (`null` => creating), `confirmedClient` (`null` until stage 1 is submitted in create mode), `linkedTrackerStatus` (`null` | string, resolved on open).
- [x] 4.3 Implement `openCreate()` (resets `editingActivity`/`confirmedClient`/`linkedTrackerStatus`, opens the modal).
- [x] 4.4 Implement `async openActivity(activity)`: sets `editingActivity = activity`, calls `HospitalizationTrackerService.getTrackerById(activity.hospitalization_tracker_id)`, stores the result's `status` as `linkedTrackerStatus`, opens the modal.
- [x] 4.5 Implement `confirmClientForCreate(client)`: sets `confirmedClient = client` and `linkedTrackerStatus = 'In Progress'` (patient search already only offers "In Progress" clients, per task 2.2).
- [x] 4.6 Implement `closeModal()` resetting all of the above.
- [x] 4.7 Implement `createActivity(fields)`: builds a new row (generated id, `client_name`/`client_id`/`hospital`/`hospitalization_tracker_id` from `confirmedClient`, plus the submitted coordination fields), pushes it onto `MOCK_DATA`, calls `fetchAll()`, then `closeModal()`.
- [x] 4.8 Implement `updateActivity(fields)`: merges `fields` onto the matching `MOCK_DATA` row (by `editingActivity.id`), calls `fetchAll()`, then `closeModal()`.
- [x] 4.9 Export the new state/actions from the store's returned surface.
- [x] 4.10 Write/extend `careCoordinationActivities.store.spec.js`: `openCreate`/`openActivity` (both branches: In-Progress-linked → editable, other-status-linked → read-only)/`confirmClientForCreate`/`closeModal` transitions; `createActivity` appends and appears in `list` after a subsequent `fetchAll`; `updateActivity` merges onto the correct row only.

## 5. `HospitalizedClientSearch.vue`

- [x] 5.1 Build the component: debounced `UiInput` + dropdown, calling `HospitalizationTrackerService.searchActiveHospitalizedClients(query)` directly, rendering "`<fullName>`, ID `<clientId>` - Hospitalization Tracker ID # `<trackerId>`" per result, click-to-select emitting the selected client.
- [x] 5.2 Write `HospitalizedClientSearch.spec.js`: debounced search calls the service; selecting a result emits it; only "In Progress" clients ever appear (covered indirectly via the service mock).

## 6. `CareCoordinationModal.vue`

- [x] 6.1 Stage 1 (create, no `confirmedClient` yet): renders only `HospitalizedClientSearch`; selecting a client calls `store.confirmClientForCreate(client)`.
- [x] 6.2 Stage 2 (create, `confirmedClient` set) and edit (`editingActivity` set, stage 1 skipped): read-only header (Hospitalized Client Name, Client ID, Hospitalization ID, Hospitalized At, Admission Date) sourced from `confirmedClient` or `editingActivity`; editable coordination fields (Coordination Type select, Provider select, Assigned To select, Creation Date — system-set/read-only, Due Date, Completion Date, "Care Coordination Completed" toggle, Care Coordination Notes textarea).
- [x] 6.3 Read-only mode (`linkedTrackerStatus !== 'In Progress'` and `editingActivity` is set): every field disabled, no Save action, only Close — same one-modal/branch pattern as `ContactDetailsModal.vue`.
- [x] 6.4 Required-field validation (red border, design.md Decision 5's field list) on Save, for both create (stage 2) and edit; blocks submission and keeps the modal open when incomplete.
- [x] 6.5 Valid Save calls `store.createActivity(fields)` (create) or `store.updateActivity(fields)` (edit).
- [x] 6.6 Write `CareCoordinationModal.spec.js`: stage 1 → stage 2 transition on client selection; validation blocks incomplete submit and marks the right fields (create and edit); valid submit calls the right store action with the right payload; read-only branch renders when the linked tracker isn't "In Progress" (no Save, all fields disabled).

## 7. Wire the bucket

- [x] 7.1 `CareCoordinationActivitiesBucketSection.vue`: import `useHasPermission`, add a "+" icon in the toolbar (`v-if` on the permission), wired to `store.openCreate()`.
- [x] 7.2 Wire the Action-column calendar icon (`v-if` on the same permission) to `store.openActivity(row)`; leave the eye/sticky-note icons untouched (out of scope, unrelated to this story).
- [x] 7.3 Mount `<CareCoordinationModal />` in this section.
- [x] 7.4 Write/extend `CareCoordinationActivitiesBucketSection.spec.js` (new, none exists yet): "+" icon hidden without the permission, calls `openCreate` when clicked; calendar icon hidden without the permission, calls `openActivity` with the right row when clicked.

## 8. Verification against acceptance criteria

- [x] 8.1 AC1/AC2 — covered by task 7.4's permission gating.
- [x] 8.2 AC3/AC4 — covered by `CareCoordinationModal.spec.js` (task 6.6, stage 1 rendering) and `CareCoordinationActivitiesBucketSection.spec.js` (task 7.4).
- [x] 8.3 AC5/AC6 — covered by `hospitalizationTracker.service.spec.js` (task 2.3) and `HospitalizedClientSearch.spec.js` (task 5.2).
- [x] 8.4 AC7/AC8 — covered by `CareCoordinationModal.spec.js` and `careCoordinationActivities.store.spec.js`'s `createActivity` case.
- [x] 8.5 AC9/AC10/AC11 — covered by `CareCoordinationModal.spec.js`'s edit-mode cases and `careCoordinationActivities.store.spec.js`'s `updateActivity` case.
- [x] 8.6 AC12/AC13 — covered by `CareCoordinationModal.spec.js`'s read-only case and `careCoordinationActivities.store.spec.js`'s `openActivity` (non-In-Progress branch).
- [x] 8.7 AC14 — covered by `careCoordinationActivities.store.spec.js`'s `createActivity`/`updateActivity` cases asserting the row persists in `list` after `fetchAll`.
- [x] 8.8 Ran `npm run test` — full suite green, no regressions.

## 9. Cleanup

- [x] 9.1 Run `eslint --fix` on every new/changed file.
- [ ] 9.2 Manually verify in a running dev build the full create (two-stage) and edit/read-only paths against the Figma reference — component specs don't substitute for a real visual check.
