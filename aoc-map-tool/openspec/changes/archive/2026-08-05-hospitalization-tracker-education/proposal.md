## Why

The "Education" tab currently renders `PlaceholderTab.vue`'s empty state. Care Coordination/ICM teams need to document Training/Education actions (provided to family, nursing staff, caregivers, etc.) while a hospitalization tracker is "In Progress", with the same controlled, auditable, status-driven editability already established for Contacts, Diagnosis, and Sick Plan in this same modal.

## What Changes

- Replace the "Education" tab's `PlaceholderTab` with a real `EducationTab.vue`: a "Staff / Family Training/Education required?" toggle (auto-saved via the existing generic `autoSaveField` action — no new store plumbing needed for the toggle itself), an Education records table, and an "Add Education" button — both the table and the button shown only while the toggle is ON.
- Add a `EducationDetailsModal.vue` (a new `UiModal` instance, matching the existing `ContactDetailsModal.vue`/`DiagnosisDetailsModal.vue` precedent) used for both creating and editing an Education record: Training Type and Education Source are required select fields; Education Notes is optional free text; "Training Completed" is a toggle. Unlike Contacts/Diagnosis, this modal has no system-set Creation Date/Author fields — none appear in the Figma reference.
- Required-field validation shows a red border on the invalid fields, same pattern as Contacts/Diagnosis.
- On successful create or edit, the modal closes and the Education table immediately reflects the change.
- Editability follows the tracker's current status exactly like every other tab in this modal: editable only while "In Progress", read-only for any other status (the ticket calls out "Discharged" specifically, but every other status this tracker can reach that isn't "In Progress" gets the same read-only treatment, for consistency with Contacts/Diagnosis/Sick Plan — see design.md Decision 2).
- Permission gating (AC15) requires no new code: the entire "Hospitalization Record" modal — Education tab included — is already gated behind `useHasPermission('Intensive Case Management')` from AOC-4402.

## Capabilities

### Modified Capabilities
- `hospitalization-tracker-creation`: the "Placeholder tabs render empty" scenario no longer covers "Education"; new requirements are added for the training-required toggle, Education record creation, editing, validation, association, and read-only behavior once the tracker leaves "In Progress".

### New Capabilities
(none — extends the same tracker capability)

## Impact

- `resources/js/vue/modules/vbc/components/HospitalizationTracker/HospitalizationTrackerModal.vue` — the `#education` slot renders `EducationTab` instead of `PlaceholderTab`.
- New: `EducationTab.vue` — "Staff / Family Training/Education required?" `ToggleSwitch` (auto-saved via `store.autoSaveField('staffFamilyTrainingRequired', value)`), a `UiTable` of `store.tracker.educationRecords` (shown only while the toggle is ON), and an "Add Education" button (toggle-ON and In-Progress gated), with an Action-column calendar icon per row opening the edit modal — mirrors `DiagnosisTab.vue`.
- New: `EducationDetailsModal.vue` — one modal for both create and edit, switching to a read-only rendering once the tracker isn't "In Progress" (same `isReadOnly` branch pattern as `ContactDetailsModal.vue`/`DiagnosisDetailsModal.vue`), with no system-set fields (none appear in the Figma reference for this modal).
- `resources/js/vue/modules/vbc/store/hospitalizationTracker.store.js` — new state (`educationModalOpen`, `editingEducationRecord`) and actions (`openAddEducationRecord`, `openEditEducationRecord`, `closeEducationModal`, `submitEducationRecord`), mirroring the existing Contacts/Diagnosis actions exactly.
- `resources/js/vue/modules/vbc/services/hospitalizationTracker.service.js` — new mocked `createEducationRecord(trackerId, payload)`/`updateEducationRecord(trackerId, recordId, payload)` methods; `educationRecords: []` and `staffFamilyTrainingRequired: false` defaults added to every mock tracker (fixture + newly created).
- `resources/js/vue/modules/vbc/config/hospitalizationTrackerFields.js` — new `TRAINING_TYPE_OPTIONS`, `EDUCATION_SOURCE_OPTIONS` mock lists (no backend enum exists for these yet, matching the Figma example rows).
- No backend (`app/VBC/**`, routes) changes — frontend + mock data only, consistent with every prior story in this tracker feature set.
