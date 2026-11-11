## 1. Known assumptions (not blockers)

- [ ] 1.1 Required-field set for Contact Details (Contact Name, Relationship, Phone Number, Email, Contact Date, Contact Received By required; Contact Notes optional) is an explicit assumption per design.md Decision 3 — flagged for requester confirmation, not a blocker.
- [ ] 1.2 "Contact received by" is a plain text field, not sourced from `ASSIGNED_TO_OPTIONS` (design.md Decision 6) — no action needed unless this is confirmed otherwise.

## 2. Config: contact metadata

- [x] 2.1 Add `CONTACT_RELATIONSHIP_OPTIONS` to `hospitalizationTrackerFields.js` (Step Mother, Father, Relative, Spouse, Caregiver, Nurse, Physician, Facility Contact, Other) — illustrative mock list, no backend enum exists.

## 3. Service layer: contact CRUD (mocked, swappable)

- [x] 3.1 Add `contacts: []` to every mock tracker's default shape: `MOCK_TRACKERS_BY_ID['#10087']` and the object built in `createTracker`.
- [x] 3.2 Seed `MOCK_TRACKERS_BY_ID['#10087']` with 2-3 representative contacts (matching the Figma reference: Pamela Hopkins/Step Mother, Earl Jones/Father, Amana Byrne/Relative) for QA/demo fidelity.
- [x] 3.3 Implement `HospitalizationTrackerService.createContact(trackerId, payload)`: generates a mock contact id, appends `{ id, ...payload }` to the tracker's `contacts` array, resolves the full updated tracker (mirrors `updateOutcome`'s mutate-and-resolve pattern).
- [x] 3.4 Implement `HospitalizationTrackerService.updateContact(trackerId, contactId, payload)`: merges `payload` onto the matching contact in place, resolves the full updated tracker.
- [x] 3.5 Write/extend `hospitalizationTracker.service.spec.js` covering both methods: contact appended/updated correctly, `getTrackerById` reflects the change afterward (AC17).

## 4. Store: contact modal lifecycle and submit action

- [x] 4.1 Add `contactModalOpen` (boolean) and `editingContact` (object | null) state, mirroring the `activeTrackerConflict`/`wizardStep` "store owns modal lifecycle" convention.
- [x] 4.2 Implement `openAddContact()` (`editingContact = null`, `contactModalOpen = true`), `openEditContact(contact)` (`editingContact = contact`, `contactModalOpen = true`), `closeContactModal()` (`contactModalOpen = false`, `editingContact = null`).
- [x] 4.3 Implement `submitContact(fields)`: calls `createContact` when `editingContact` is `null`, otherwise `updateContact(editingContact.id, fields)`; assigns the resolved tracker back onto `tracker.value` (so the Contacts table's `v-for` reacts immediately, per design.md Decision 2); calls `closeContactModal()` on success.
- [x] 4.4 Export the new state/actions from the store's returned surface.
- [x] 4.5 Write/extend `hospitalizationTracker.store.spec.js`: `openAddContact`/`openEditContact`/`closeContactModal` state transitions; `submitContact` calling `createContact` vs `updateContact` correctly depending on `editingContact`; modal closes and `tracker.contacts` reflects the change after a successful submit.

## 5. `ContactDetailsModal.vue`

- [x] 5.1 Build the component: `UiModal` bound to `store.contactModalOpen`, title "Contact Details". Editable branch (Contact Name, Relationship select, Phone Number, Email, Contact Date, Contact Received By, Contact Notes) shown when `store.tracker.status === 'In Progress'`; read-only branch (plain label/value pairs, only a "Close" action, matching the Figma reference exactly) shown otherwise (AC14/AC15).
- [x] 5.2 Prefill the editable form from `store.editingContact` when editing; blank defaults when creating (`store.editingContact === null`).
- [x] 5.3 Local required-field validation (red border via the same pattern as `DischargeFields.vue`/siblings from AOC-4472 — `UiInput`'s missing native error-border gap patched the same way) per design.md Decision 3's field list; submitting with any required field empty prevents submission and keeps the modal open (AC6/AC9).
- [x] 5.4 Valid submit calls `store.submitContact({ ...form })`.
- [x] 5.5 Write `ContactDetailsModal.spec.js`: renders blank for create / prefilled for edit; validation blocks submission and marks the right fields (create and edit cases); valid submit calls `store.submitContact` with the right payload; read-only branch renders when the tracker isn't "In Progress" and has no Submit/editable inputs.

## 6. `ContactsTab.vue`

- [x] 6.1 Build the component: "Add Contact" button (`v-if="store.tracker?.status === 'In Progress'"`, AC13) wired to `store.openAddContact()`; a `UiTable` of `store.tracker.contacts` per design.md Decision 5 (columns: Contact Name, Relationship, Phone Number, Email, Action).
- [x] 6.2 Action column cell renders the `fa fa-calendar` icon (matching the existing inert icon already present in `AllHospitalizationSection.vue`'s own Action column) wired to `store.openEditContact(row)`, available regardless of tracker status (AC8/AC14 — clicking it always opens the modal, which itself decides editable vs. read-only).
- [x] 6.3 Mount `ContactDetailsModal` from this component (chosen over mounting it alongside `ActiveTrackerConflictModal` in `HospitalizationTrackerModal.vue`, since it's only relevant while the Contacts tab exists).
- [x] 6.4 Write `ContactsTab.spec.js`: table renders the tracker's contacts; "Add Contact" hidden/shown per status (AC4/AC13); clicking the calendar icon calls `store.openEditContact` with the right row; contacts persist across a fresh mount with the same tracker (AC16/AC17 — reactive state, no reload needed).

## 7. Wire into `HospitalizationTrackerModal.vue`

- [x] 7.1 Replace the `#contacts` slot's `PlaceholderTab` with `ContactsTab`.
- [x] 7.2 `ContactDetailsModal` mounted from within `ContactsTab.vue` (task 6.3) — no separate mount needed here.
- [x] 7.3 Write/extend `HospitalizationTrackerModal.spec.js`: opening the Contacts tab (once nested tabs apply, i.e. `In Progress` or later) renders `ContactsTab` instead of the old placeholder text.

## 8. Verification against acceptance criteria

- [x] 8.1 AC1/AC2 — unchanged from AOC-4402 (`useHasPermission` stub); no new code, confirmed existing coverage still passes.
- [x] 8.2 AC3/AC4 — covered by `ContactsTab.spec.js`.
- [x] 8.3 AC5/AC6/AC7 — covered by `ContactDetailsModal.spec.js` (create case) and `hospitalizationTracker.store.spec.js`'s `submitContact`/`createContact` case.
- [x] 8.4 AC8/AC9/AC10 — covered by `ContactDetailsModal.spec.js` (edit case, prefill) and `hospitalizationTracker.store.spec.js`'s `submitContact`/`updateContact` case.
- [x] 8.5 AC11 — covered by `createContact`/`updateContact` both operating on `MOCK_TRACKERS_BY_ID[trackerId].contacts` only (service spec, task 3.5).
- [x] 8.6 AC12/AC13/AC14/AC15 — covered by `ContactsTab.spec.js` (Add Contact hidden) and `ContactDetailsModal.spec.js`'s parametrized read-only case (all five non-"In Progress" statuses: Discharged, Ready for Review, Ready for Next Steps, Completed, Closed).
- [x] 8.7 AC16/AC17 — covered by `ContactsTab.spec.js`'s persistence case and the service spec's `getTrackerById`-reflects-the-change assertions.
- [x] 8.8 Ran `npm run test` — 26/26 files, 153/153 tests pass (128 pre-existing + 25 new/extended for this change), no regressions.

## 9. Cleanup

- [x] 9.1 Ran `eslint --fix` on every new/changed file — zero errors/warnings remain in this change's files.
- [ ] 9.2 Manually verify in a running dev build (if available) the full create → edit → read-only-after-status-change path against the Figma reference — component specs don't substitute for a real visual check of the read-only layout. **Not done in this session** — no interactive browser available; `npx mix` (production build) was run instead and compiles successfully, but that only verifies the bundle builds, not the rendered UI/interactions.
- [ ] 9.3 Run `/opsx:sync` once implementation matches the spec — note that AOC-4402/4472's own deltas targeting `hospitalization-tracker-creation` were also never synced; confirm the intended merge order before syncing this one.
