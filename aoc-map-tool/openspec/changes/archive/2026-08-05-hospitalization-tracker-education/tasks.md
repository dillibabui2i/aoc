## 1. Known assumptions (not blockers)

- [ ] 1.1 Read-only is triggered by `status !== 'In Progress'`, generalizing the ticket's "Discharged" example to match the Contacts/Diagnosis/Sick Plan convention (design.md Decision 2) — flagged for requester confirmation.
- [ ] 1.2 Required-field set for the Training/Education modal (Training Type, Education Source required; Education Notes optional) is an explicit assumption per design.md Decision 6.

## 2. Config: Education metadata

- [x] 2.1 Add `TRAINING_TYPE_OPTIONS` (Emergency Trach Replacement, Head Injury, Respiratory Education for Clinical Staff, Seasonal Illness Guide) and `EDUCATION_SOURCE_OPTIONS` (AOC Clinical Excellence, plus illustrative additions) to `hospitalizationTrackerFields.js` — mock lists, no backend enum exists.

## 3. Service layer: Education CRUD (mocked, swappable)

- [x] 3.1 Add `educationRecords: []` and `staffFamilyTrainingRequired: false` to every mock tracker's default shape: `MOCK_TRACKERS_BY_ID['#10087']` and the object built in `createTracker`.
- [x] 3.2 Seed `MOCK_TRACKERS_BY_ID['#10087']` with `staffFamilyTrainingRequired: true` and 4 representative Education records (matching the Figma reference: Emergency Trach Replacement (Completed), Head Injury (Completed), Respiratory Education for Clinical Staff (not Completed), Seasonal Illness Guide (not Completed)) for QA/demo fidelity.
- [x] 3.3 Implement `HospitalizationTrackerService.createEducationRecord(trackerId, payload)`: generates a mock record id, appends `{ id, ...payload }` to the tracker's `educationRecords` array, resolves the full updated tracker.
- [x] 3.4 Implement `HospitalizationTrackerService.updateEducationRecord(trackerId, recordId, payload)`: merges `payload` onto the matching record in place, resolves the full updated tracker.
- [x] 3.5 Write/extend `hospitalizationTracker.service.spec.js`: record appended/updated correctly, `getTrackerById` reflects the change afterward.

## 4. Store: Education modal lifecycle and submit action

- [x] 4.1 Add `educationModalOpen` (boolean) and `editingEducationRecord` (object | null) state, mirroring Contacts/Diagnosis.
- [x] 4.2 Implement `openAddEducationRecord()`, `openEditEducationRecord(record)`, `closeEducationModal()`, mirroring the Contacts/Diagnosis actions exactly.
- [x] 4.3 Implement `submitEducationRecord(fields)`: calls `createEducationRecord` when `editingEducationRecord` is `null`, otherwise `updateEducationRecord(editingEducationRecord.id, fields)`; assigns the resolved tracker back onto `tracker.value`; calls `closeEducationModal()` on success.
- [x] 4.4 Export the new state/actions from the store's returned surface.
- [x] 4.5 Write/extend `hospitalizationTracker.store.spec.js`: modal lifecycle transitions; `submitEducationRecord` calling `createEducationRecord` vs `updateEducationRecord` correctly; `tracker.educationRecords` reflects the change after a successful submit.

## 5. `EducationDetailsModal.vue`

- [x] 5.1 Build the component: `UiModal` bound to `store.educationModalOpen`, title "Training / Education". Editable branch (Training Type select, Education Source select, Education Notes textarea, "Training Completed" toggle) shown when `store.tracker.status === 'In Progress'`; read-only branch (plain label/value pairs, only "Close") shown otherwise. No Creation Date/Author fields (design.md Decision 4 — none in the Figma reference).
- [x] 5.2 Prefill from `store.editingEducationRecord` when editing; blank defaults when creating.
- [x] 5.3 Required-field validation (red border) for Training Type and Education Source (design.md Decision 6); blocks submission and keeps the modal open when missing.
- [x] 5.4 Valid submit calls `store.submitEducationRecord({ ...form })`.
- [x] 5.5 Write `EducationDetailsModal.spec.js`: renders blank for create / prefilled for edit; validation blocks submission and marks the right fields; valid submit calls `store.submitEducationRecord` with the right payload; read-only branch renders when the tracker isn't "In Progress" and has no Submit/editable inputs.

## 6. `EducationTab.vue`

- [x] 6.1 Build the component: "Staff / Family Training/Education required?" `ToggleSwitch` bound to `store.tracker.staffFamilyTrainingRequired`, calling `store.autoSaveField('staffFamilyTrainingRequired', value)` on change.
- [x] 6.2 Compute table visibility (`staffFamilyTrainingRequired`) and "Add Education" visibility (`staffFamilyTrainingRequired && status === 'In Progress'`) per design.md Decision 3.
- [x] 6.3 Render the `UiTable` of `store.tracker.educationRecords` (columns: Completed?, Training Type, "Training/Education to be prepared for staff and family" [notes], Action) when visible.
- [x] 6.4 Action column cell renders the `fa fa-calendar` icon wired to `store.openEditEducationRecord(row)`, available regardless of tracker status (the modal itself decides editable vs. read-only).
- [x] 6.5 Mount `EducationDetailsModal` from this component.
- [x] 6.6 Write `EducationTab.spec.js`: toggle reflects and persists `staffFamilyTrainingRequired`; table/button visibility per the toggle/status combinations; clicking the calendar icon calls `store.openEditEducationRecord` with the right row.

## 7. Wire into `HospitalizationTrackerModal.vue`

- [x] 7.1 Replace the `#education` slot's `PlaceholderTab` with `EducationTab`.
- [x] 7.2 Write/extend `HospitalizationTrackerModal.spec.js`: opening the Education tab renders `EducationTab` instead of the old placeholder text.

## 8. Verification against acceptance criteria

- [x] 8.1 AC1/AC2 — covered by `EducationTab.spec.js`.
- [x] 8.2 AC3 — covered by `EducationTab.spec.js`'s visibility cases.
- [x] 8.3 AC4/AC5/AC6 — covered by `EducationDetailsModal.spec.js` (create case) and `hospitalizationTracker.store.spec.js`'s `submitEducationRecord`/`createEducationRecord` case.
- [x] 8.4 AC7/AC8/AC9 — covered by `EducationDetailsModal.spec.js` (edit case, prefill) and `hospitalizationTracker.store.spec.js`'s `submitEducationRecord`/`updateEducationRecord` case.
- [x] 8.5 AC10 — covered by `EducationTab.spec.js`'s table-rendering case.
- [x] 8.6 AC11/AC12/AC13 — covered by `EducationDetailsModal.spec.js`'s read-only case and `EducationTab.spec.js`'s "Add Education hidden when not In Progress" case.
- [x] 8.7 AC14 — covered by `hospitalizationTracker.service.spec.js`'s `getTrackerById`-reflects-the-change assertions.
- [x] 8.8 AC15 — unchanged from AOC-4402 (`useHasPermission` stub); no new code, confirmed existing coverage still passes.
- [x] 8.9 Ran `npm run test` — full suite green, no regressions.

## 9. Cleanup

- [x] 9.1 Run `eslint --fix` on every new/changed file.
- [ ] 9.2 Manually verify in a running dev build the toggle/table/button visibility, create → edit → read-only-after-discharge path against the Figma reference — component specs don't substitute for a real visual check.
