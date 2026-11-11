## Why

The "Diagnosis" tab introduced by AOC-4472's tab rearrangement (once a tracker has been "In Progress") currently renders `PlaceholderTab.vue`'s empty state. ICM users need to record whether a diagnosis was made for the current hospitalization and, if so, document one or more structured diagnosis records (code, rank, system, clinical/verification status, notes) tied to that tracker, editable only while it's "In Progress" and read-only afterward — matching how Contacts already behaves in this same modal.

## What Changes

- Replace the "Diagnosis" tab's `PlaceholderTab` with a real `DiagnosisTab.vue`: a "Was the Diagnosis Made?" toggle (auto-saved on every change, reusing the existing `autoSaveField` action — no new store plumbing needed for the toggle itself), a diagnosis list table, and an "Add Diagnosis" button.
- Toggle/table visibility rules: the table is shown whenever the toggle is ON, or whenever it's OFF but one or more diagnosis records already exist; "Add Diagnosis" is shown only while the toggle is ON **and** the tracker is "In Progress".
- Add a `DiagnosisDetailsModal.vue` (a new `UiModal` instance, matching the existing `ContactDetailsModal.vue` precedent) used for both creating and editing a diagnosis: Diagnosis Code, Rank, System, Clinical Status, and Verification Status are required (select fields); Creation Date and Author are system-set, read-only in both modes; Diagnosis Notes is optional free text.
- Required-field validation shows a red border on the invalid fields, same pattern as Contacts.
- On successful create or edit, the modal closes and the diagnosis table immediately reflects the change (same in-memory mocked tracker, no page navigation involved).
- Once the tracker's status is anything other than "In Progress", the Diagnosis tab's "Add Diagnosis" button is hidden and every diagnosis opens in a read-only variant of the same modal — existing diagnosis records and the toggle's saved value are always preserved and displayed regardless of status.
- Permission gating (AC1/AC2) requires no new code: the entire "Hospitalization Record" modal — Diagnosis tab included — is already gated behind `useHasPermission('Intensive Case Management')` from AOC-4402.

## Capabilities

### Modified Capabilities
- `hospitalization-tracker-creation`: the "Placeholder tabs render empty" scenario no longer covers "Diagnosis" (it gets real content); new requirements are added for the diagnosis-made toggle, diagnosis creation, editing, validation, association, and read-only behavior once the tracker leaves "In Progress".

### New Capabilities
(none — extends the same tracker capability)

## Impact

- `resources/js/vue/modules/vbc/components/HospitalizationTracker/HospitalizationTrackerModal.vue` — the `#diagnosis` slot renders `DiagnosisTab` instead of `PlaceholderTab`.
- New: `DiagnosisTab.vue` — "Was the Diagnosis Made?" `ToggleSwitch` (auto-saved via the existing `store.autoSaveField('wasDiagnosisMade', value)`), a `UiTable` of `store.tracker.diagnoses` (shown per the visibility rules above), and an "Add Diagnosis" button (toggle-ON and In-Progress gated), with an Action-column calendar icon per row opening the edit modal — mirrors `ContactsTab.vue` exactly.
- New: `DiagnosisDetailsModal.vue` — one modal for both create and edit, switching to a read-only rendering once the tracker isn't "In Progress" (same `isReadOnly` branch pattern as `ContactDetailsModal.vue`); owns its own required-field (red-border) validation.
- `resources/js/vue/modules/vbc/store/hospitalizationTracker.store.js` — new state (`diagnosisModalOpen`, `editingDiagnosis`) and actions (`openAddDiagnosis`, `openEditDiagnosis`, `closeDiagnosisModal`, `submitDiagnosis`), mirroring the existing Contacts actions exactly. The toggle itself needs no new action — `autoSaveField` already covers it.
- `resources/js/vue/modules/vbc/services/hospitalizationTracker.service.js` — new mocked `createDiagnosis(trackerId, payload)`/`updateDiagnosis(trackerId, diagnosisId, payload)` methods, mirroring `createContact`/`updateContact`; a `diagnoses: []` and `wasDiagnosisMade: false` default added to every mock tracker (fixture + newly created).
- `resources/js/vue/modules/vbc/config/hospitalizationTrackerFields.js` — new `DIAGNOSIS_CODE_OPTIONS`, `DIAGNOSIS_RANK_OPTIONS`, `DIAGNOSIS_SYSTEM_OPTIONS`, `DIAGNOSIS_CLINICAL_STATUS_OPTIONS`, `DIAGNOSIS_VERIFICATION_STATUS_OPTIONS` mock lists (no backend enum exists for these yet, same "illustrative mock list" pattern as `CONTACT_RELATIONSHIP_OPTIONS`).
- No backend (`app/VBC/**`, routes) changes — frontend + mock data only, consistent with every prior story in this tracker feature set.
