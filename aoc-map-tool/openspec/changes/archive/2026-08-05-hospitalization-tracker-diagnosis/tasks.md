## 1. Known assumptions (not blockers)

- [ ] 1.1 Required-field set for Diagnosis Details (Diagnosis Code, Rank, System, Clinical Status, Verification Status required; Diagnosis Notes optional; Creation Date/Author system-set) is an explicit assumption per design.md Decision 5 — flagged for requester confirmation, not a blocker.

## 2. Config: diagnosis metadata

- [x] 2.1 Add `DIAGNOSIS_CODE_OPTIONS`, `DIAGNOSIS_RANK_OPTIONS`, `DIAGNOSIS_SYSTEM_OPTIONS`, `DIAGNOSIS_CLINICAL_STATUS_OPTIONS`, `DIAGNOSIS_VERIFICATION_STATUS_OPTIONS` to `hospitalizationTrackerFields.js` — illustrative mock lists, no backend enum exists, matching the Figma example rows.

## 3. Service layer: diagnosis CRUD (mocked, swappable)

- [x] 3.1 Add `diagnoses: []` and `wasDiagnosisMade: false` to every mock tracker's default shape: `MOCK_TRACKERS_BY_ID['#10087']` and the object built in `createTracker`.
- [x] 3.2 Seed `MOCK_TRACKERS_BY_ID['#10087']` with `wasDiagnosisMade: true` and 2-3 representative diagnoses (matching the Figma reference: R94.5 Transaminitis, I10: Essential (primary) hypertension, R00.0 Tachycardia) for QA/demo fidelity.
- [x] 3.3 Implement `HospitalizationTrackerService.createDiagnosis(trackerId, payload)`: generates a mock diagnosis id, appends `{ id, ...payload }` to the tracker's `diagnoses` array, resolves the full updated tracker.
- [x] 3.4 Implement `HospitalizationTrackerService.updateDiagnosis(trackerId, diagnosisId, payload)`: merges `payload` onto the matching diagnosis in place, resolves the full updated tracker.
- [x] 3.5 Write/extend `hospitalizationTracker.service.spec.js` covering both methods: diagnosis appended/updated correctly, `getTrackerById` reflects the change afterward.

## 4. Store: diagnosis modal lifecycle and submit action

- [x] 4.1 Add `diagnosisModalOpen` (boolean) and `editingDiagnosis` (object | null) state, mirroring `contactModalOpen`/`editingContact`.
- [x] 4.2 Implement `openAddDiagnosis()`, `openEditDiagnosis(diagnosis)`, `closeDiagnosisModal()`, mirroring the Contacts actions exactly.
- [x] 4.3 Implement `submitDiagnosis(fields)`: calls `createDiagnosis` when `editingDiagnosis` is `null`, otherwise `updateDiagnosis(editingDiagnosis.id, fields)`; assigns the resolved tracker back onto `tracker.value`; calls `closeDiagnosisModal()` on success.
- [x] 4.4 Export the new state/actions from the store's returned surface.
- [x] 4.5 Write/extend `hospitalizationTracker.store.spec.js`: `openAddDiagnosis`/`openEditDiagnosis`/`closeDiagnosisModal` state transitions; `submitDiagnosis` calling `createDiagnosis` vs `updateDiagnosis` correctly depending on `editingDiagnosis`; modal closes and `tracker.diagnoses` reflects the change after a successful submit.

## 5. `DiagnosisDetailsModal.vue`

- [x] 5.1 Build the component: `UiModal` bound to `store.diagnosisModalOpen`, title "Diagnosis Details". Editable branch (Diagnosis Code, Rank, System, Clinical Status, Verification Status selects; read-only-styled Creation Date/Author; Diagnosis Notes textarea) shown when `store.tracker.status === 'In Progress'`; read-only branch (plain label/value pairs, only a "Close" action) shown otherwise.
- [x] 5.2 Prefill the editable form from `store.editingDiagnosis` when editing; blank defaults (Creation Date = now, Author = `CURRENT_USER`) when creating.
- [x] 5.3 Local required-field validation (red border) per design.md Decision 5's field list; submitting with any required field empty prevents submission and keeps the modal open.
- [x] 5.4 Valid submit calls `store.submitDiagnosis({ ...form })`.
- [x] 5.5 Write `DiagnosisDetailsModal.spec.js`: renders blank (with system-set Creation Date/Author) for create / prefilled for edit; validation blocks submission and marks the right fields (create and edit cases); valid submit calls `store.submitDiagnosis` with the right payload; read-only branch renders when the tracker isn't "In Progress" and has no Submit/editable inputs.

## 6. `DiagnosisTab.vue`

- [x] 6.1 Build the component: "Was the Diagnosis Made?" `ToggleSwitch` bound to `store.tracker.wasDiagnosisMade`, calling `store.autoSaveField('wasDiagnosisMade', value)` on change.
- [x] 6.2 Compute table visibility (`wasDiagnosisMade || diagnoses.length > 0`) and "Add Diagnosis" visibility (`wasDiagnosisMade && status === 'In Progress'`) per design.md Decision 2.
- [x] 6.3 Render the `UiTable` of `store.tracker.diagnoses` (columns: Diagnosis Code, Rank, System, Clinical Status, Verification Status, Action) when visible, mirroring `ContactsTab.vue`'s table setup.
- [x] 6.4 Action column cell renders the `fa fa-calendar` icon wired to `store.openEditDiagnosis(row)`, available regardless of tracker status (the modal itself decides editable vs. read-only).
- [x] 6.5 Mount `DiagnosisDetailsModal` from this component.
- [x] 6.6 Write `DiagnosisTab.spec.js`: toggle reflects and persists `wasDiagnosisMade`; table/button visibility matrix (all four combinations of toggle value × existing records / status); clicking the calendar icon calls `store.openEditDiagnosis` with the right row.

## 7. Wire into `HospitalizationTrackerModal.vue`

- [x] 7.1 Replace the `#diagnosis` slot's `PlaceholderTab` with `DiagnosisTab`.
- [x] 7.2 `DiagnosisDetailsModal` mounted from within `DiagnosisTab.vue` (task 6.5) — no separate mount needed here.
- [x] 7.3 Write/extend `HospitalizationTrackerModal.spec.js`: opening the Diagnosis tab (once nested tabs apply) renders `DiagnosisTab` instead of the old placeholder text.

## 8. Verification against acceptance criteria

- [x] 8.1 AC1/AC2 — unchanged from AOC-4402 (`useHasPermission` stub); no new code, confirmed existing coverage still passes.
- [x] 8.2 AC3 — covered by `DiagnosisTab.spec.js`.
- [x] 8.3 AC4/AC6 — covered by `DiagnosisTab.spec.js`'s toggle persistence case (asserting `store.autoSaveField` is called).
- [x] 8.4 AC5/AC7/AC8 — covered by `DiagnosisTab.spec.js`'s visibility-matrix cases.
- [x] 8.5 AC9/AC10/AC11 — covered by `DiagnosisDetailsModal.spec.js` (create case) and `hospitalizationTracker.store.spec.js`'s `submitDiagnosis`/`createDiagnosis` case.
- [x] 8.6 AC12/AC13/AC14/AC15 — covered by `DiagnosisDetailsModal.spec.js` (edit case, prefill, In-Progress editability) and `hospitalizationTracker.store.spec.js`'s `submitDiagnosis`/`updateDiagnosis` case.
- [x] 8.7 AC16/AC17/AC18 — covered by `DiagnosisDetailsModal.spec.js`'s read-only case and `DiagnosisTab.spec.js`'s "Add Diagnosis hidden when not In Progress" case.
- [x] 8.8 AC19 — covered by `hospitalizationTracker.service.spec.js`'s `getTrackerById`-reflects-the-change assertions, same pattern as Contacts.
- [x] 8.9 AC20 — covered by `createDiagnosis`/`updateDiagnosis` both operating on `MOCK_TRACKERS_BY_ID[trackerId].diagnoses` only (service spec).
- [x] 8.10 Ran `npm run test` — full suite green, no regressions.

## 9. Cleanup

- [x] 9.1 Run `eslint --fix` on every new/changed file.
- [ ] 9.2 Manually verify in a running dev build the full toggle/table/button visibility matrix, create → edit → read-only-after-status-change path against the Figma reference — component specs don't substitute for a real visual check.
