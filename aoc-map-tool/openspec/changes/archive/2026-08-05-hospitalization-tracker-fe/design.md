## Context

The "All Hospitalizations" list page (`resources/js/vue/modules/vbc/sections/AllHospitalizationSection.vue`, backed by `allHospitalization.store.js` / `allHospitalization.service.js`, hitting `GET /vbc/hospitalization/data`) already exists and is fully built — this change does not touch its table, filters, or store. Its toolbar today is a single `.head-title` block with a placeholder (unwired) export link; there is no permission-gated action precedent to follow there.

Backend support for hospitalization *tracking* already exists for the "Current Hospitalization Info" domain: `app/VBC/Models/Hospitalization/HospitalizationModel.php`, `HospitalizationCollectionItemResource`, and a full set of backed enums (`HospitalizationTypeEnum`, `HospitalizationAdmissionTypeEnum`, `HospitalizationDeclinedProgramContactEnum`, `HospitalizationSourceEnum`, `HospitalizationCareRequirementsEnum`, `HospitalizationThirtyDayOutcomeEnum`, `HospitalizationLevelEnum`, `HospitalizationStatusEnum`). None of the *tracker creation workflow* fields (Author, Client Status KT, Auth hours per week, DME Provider, High Utilizer?, Backup G-tube?/trach?, their "last updated" timestamps) exist on the backend today, nor does client search/active-tracker-check/create/save. Per the ticket, this change is frontend-only: all of that is mocked behind one service module.

The `@angels-of-care/ui` design system provides `UiModal`, `UiSelect`, `UiInput`, `UITextArea`, `UiButton`, `UiDateTimepicker`, `UiTable`, `UiAlert`, `UiSpinner` — but **no Tabs component, no Toggle/Switch component, and no autocomplete/typeahead component**. This modal is also the first consumer of `UiModal` in the codebase (confirmed zero prior usages).

No client-side permission-checking pattern exists anywhere in `resources/js/vue` (no composable, no Pinia slice, no global property) — permission enforcement today is entirely server-side, and "Intensive Case Management" was found only as a legacy Blade menu-label string, not as a permission key. This is genuinely new territory for the Vue layer (see Decision 6 and Open Questions).

## Goals / Non-Goals

**Goals:**
- Implement the full "Hospitalization Record" modal UI/state machine (New → client search → auto-fill or conflict → Open) exactly as described in the Figma-derived UI reference, reusing `@angels-of-care/ui` wherever it already has the needed primitive.
- Isolate every not-yet-existing backend interaction behind one service module so swapping in real endpoints later touches only that file.
- Implement the auto-save vs. logged-field Save/Cancel editing behavior generically, driven by a single field-metadata config, so the real "Log Updates?" data-dictionary values can be dropped in later as a config-only change.
- Add the minimum viable client-side permission check needed to satisfy AC1/AC2, without inventing a permanent app-wide permission architecture.

**Non-Goals:**
- No backend work: no new routes, controllers, models, migrations, or enum changes in `app/VBC`.
- No real Activity Log entries (AOC-3867), no Outcome-dropdown submit logic (AOC-3472), no Notes sidebar content/creation logic (AOC-3490), no Hospitalization History / Sick Plan / Follow-up tab content (AOC-3482 / AOC-3502 / AOC-3506) — these render as inert placeholders only.
- No changes to `AllHospitalizationSection.vue` beyond adding the trigger button.
- No new permanent design-system components published to `@angels-of-care/ui` — new Tabs/Toggle/autocomplete pieces are built as local, feature-scoped components under `components/HospitalizationTracker/`.

## Decisions

**1. Feature module layout follows the existing `resources/js/vue/modules/vbc` structure, with one new components subfolder.**
```
modules/vbc/
├── components/HospitalizationTracker/
│   ├── HospitalizationTrackerModal.vue        (root orchestrator, owns UiModal)
│   ├── ClientSearchAutocomplete.vue            (typeahead, new — no design-system equivalent)
│   ├── ActiveTrackerConflictModal.vue          (second UiModal instance)
│   ├── TrackerInfoGrid.vue                     (header info grid, rows 1–5)
│   ├── TrackerTitleBar.vue                     (title + status badge + Person/Notes icons)
│   ├── TrackerTabs.vue                         (new local tabs component)
│   ├── CurrentHospitalizationInfoTab.vue
│   ├── PlaceholderTab.vue                      (reused for History/Sick Plan/Follow-up)
│   ├── OutcomeSubmitBar.vue
│   ├── NotesSidebar.vue                        (shell/engine, empty state)
│   ├── LoggedField.vue                         (wraps one field + floating Save/Cancel)
│   └── ToggleSwitch.vue                        (new local toggle — no design-system equivalent)
├── composables/usePermissions.js               (new — see Decision 6)
├── config/hospitalizationTrackerFields.js       (field metadata, see Decision 4)
├── services/hospitalizationTracker.service.js   (mocked, see Decision 2)
└── store/hospitalizationTracker.store.js        (Pinia setup store, see Decision 3)
```
This mirrors the existing `services/`, `store/`, `components/` split and naming convention (`*.service.js`, `*.store.js`, PascalCase components) rather than introducing a nested module.

**2. All five backend interactions live in one service file, not five.**
`hospitalizationTracker.service.js` exports a single `HospitalizationTrackerService` object (matching the existing `AllHospitalizationService` pattern) with `searchClients(query)`, `checkActiveTracker(clientId)`, `createTracker(payload)`, `getTrackerById(trackerId)`, `saveField(trackerId, fieldKey, value)`. Each method today resolves a `Promise` with static/randomized mock data and an artificial `setTimeout` delay (to exercise loading states honestly), instead of calling `client` from `core/api/client.js`. Keeping all five in one file (rather than one file per concern) means the future swap is "replace the bodies of these five functions with axios calls," with the store/components layer untouched either way — splitting them wouldn't reduce that swap cost, only add file-hopping.

**3. One Pinia setup store owns the whole modal lifecycle.**
`hospitalizationTracker.store.js` holds: `isModalOpen`, `wizardStep` (`'search' | 'confirmed' | 'conflict' | 'open'`), `selectedClient`, `activeTrackerConflict`, `tracker` (the created/loaded record), `fieldState` (per-field `{ value, savedValue, dirty, saving }` for logged fields), `permissionGranted`. Actions (`openCreateModal`, `selectClient`, `confirmNoConflict`, `openExistingTracker`, `searchAnotherClient`, `submitTracker`, `loadTrackerById`, `autoSaveField`, `saveLoggedField`, `cancelLoggedField`) call the service, never axios/mocks directly — matching the codebase's strict component → store → service layering. `AllHospitalizationSection.vue`'s own store is untouched; this is a fully separate store.

**4. A single field-metadata config drives both layout and auto-save-vs-logged behavior.**
`hospitalizationTrackerFields.js` exports an ordered array of field descriptors: `{ key, label, section: 'header' | 'currentInfo', row, type: 'text' | 'select' | 'toggle' | 'date' | 'datetime' | 'textarea', options?: enumOptionsFn, logUpdates: boolean }`. `TrackerInfoGrid.vue` and `CurrentHospitalizationInfoTab.vue` render purely by iterating this config (grouped by `row`), and `LoggedField.vue` reads `logUpdates` to decide auto-save vs. floating Save/Cancel. **Per explicit user decision, the real data dictionary is not available and this change does not block on it**: `logUpdates: true` is mocked onto a representative subset of fields (Admission Type, Care Requirements, and 30 Days Outcome — clinically-consequential classification fields), with everything else defaulting to auto-save, purely to exercise both code paths end-to-end. This is deliberately isolated to one config file so correcting it against the real data dictionary later (separate ticket/task) is a data-only change — no component logic changes.

**5. Dropdown options mirror existing backend enum labels, hardcoded as mock option lists.**
Where a backend enum already exists (`HospitalizationTypeEnum`, `HospitalizationAdmissionTypeEnum`, `HospitalizationDeclinedProgramContactEnum`, `HospitalizationSourceEnum`, `HospitalizationCareRequirementsEnum`, `HospitalizationThirtyDayOutcomeEnum`, `HospitalizationLevelEnum`), the config's `options` use the exact same labels (e.g. "Admission"/"Readmission"/"Transfer", "PointClickCare"/"Bamboo Health"/"Informed by Contact") so the mocked UI already matches real values and needs no relabeling when wired to the real API. Fields with no backend enum today (Client Status KT, Terminal Payer non-enum text, DME Provider, Assigned To) use small illustrative mock lists instead.

**6. Client-side permission check: an always-true stub, per explicit user decision.**
No permission system (by that name or any other) was found anywhere in this codebase (`app/`, config, or views) — confirmed by exhaustive search. Per explicit direction, this change does **not** attempt to invent a plumbing mechanism (Blade data attributes, a permissions store, etc.) for a system that doesn't exist yet. `usePermissions.js` exports `useHasPermission(name)`, which unconditionally returns `true`, with a `// TODO(AOC-????): wire to real "Intensive Case Management" permission once a permission system exists` comment. All consumers (the "+" button visibility, the defensive modal-open guard) call this composable normally, so the day a real permission system lands, only this one function's body changes — no consumer is touched. Building the real check is explicitly deferred to a separate ticket.

**7. Client search disambiguates same-name clients by carrying `clientId` as the selection's real value.**
`ClientSearchAutocomplete.vue` renders each match as `"{fullName}, ID {clientId}"` (per the UI reference) and emits the full match object (`{ clientId, fullName, ... }`) on selection, never just the display string — so two "John Doe" entries with different IDs are never conflated. Free-text submission is impossible: the field only accepts a value once an item has been explicitly selected from the list (component-internal `selectedClient` ref, not the raw input's `v-model`), directly satisfying AC8/AC9.

**8. Active-tracker "active" rule is a pure function against the mocked status, matching the real enum's semantics.**
`isActiveTracker(status)` = `status !== 'Completed' && status !== 'Closed'`, implemented once in the service/store layer and unit-tested directly, so the same predicate can be pointed at real `HospitalizationStatusEnum` values (`COMPLETED`, `CLOSED`) later with no logic change — only the input's shape changes from a mock string to the real enum value.

**9. New Tabs and Toggle components are local, not contributed to `@angels-of-care/ui`.**
Publishing to the shared design system is out of scope for a single-story frontend change and would require design-system-team review/versioning. `TrackerTabs.vue` and `ToggleSwitch.vue` are built minimally (enough for this modal's 4-tab / 3-toggle needs) inside `components/HospitalizationTracker/`. If a second consumer needs them later, promoting them to `@angels-of-care/ui` is a natural follow-up, not blocked by anything done here.

## Risks / Trade-offs

- **[Accepted]** The "Log Updates?" field flags (Decision 4) are mocked, not sourced from the real data dictionary — per explicit user decision, this is not a blocker for this change; correcting them is a follow-up data-only edit to `hospitalizationTrackerFields.js`.
- **[Accepted]** The permission check (Decision 6) always returns `true` — per explicit user decision, real enforcement is deferred to a separate ticket once a permission system exists in the codebase. Until then, this feature is effectively visible to all users in any environment where it ships.
- **[Trade-off]** All backend calls are mocked with client-side randomized/static data and artificial latency; there is no real client database to search against, so "search existing clients" returns from a small in-memory fixture list. This is sufficient to demonstrate and test all ACs but is not representative of real search performance/relevance.
- **[Trade-off]** Building local Tabs/Toggle components (Decision 9) duplicates effort if another team builds equivalents in `@angels-of-care/ui` concurrently. Accepted to avoid scope creep into shared design-system changes for a single-story ticket.

## Migration Plan

No data migration; this is additive, frontend-only code behind a new "+" entry point. Nothing existing is removed or altered in a way that needs a rollback plan beyond a standard revert. When real backend endpoints exist, the swap is: (1) replace the five method bodies in `hospitalizationTracker.service.js` with real `client.*()` calls, (2) replace `usePermissions`'s `return true` stub with a real permission check (separate ticket, once a permission system exists), (3) correct any `logUpdates` flags in `hospitalizationTrackerFields.js` against the confirmed data dictionary — no other file needs to change.

## Open Questions

None outstanding for this change's own scope. Two items were raised during planning and explicitly resolved by the requester rather than left open:
- Permission enforcement: ships as an always-`true` stub with a `TODO` comment; real enforcement is a separate, future ticket once a permission system exists in the codebase.
- "Log Updates?" field flags: shipped as a mocked, representative subset (not sourced from the real data dictionary); correcting the actual set is a follow-up data-only change, not a blocker here.

**Custom/new fields with no backend precedent** (Author, Client Status KT, Auth hours per week, DME Provider, High Utilizer?, Backup G-tube?/trach? + their "last updated" timestamps) still have no confirmed expected data types/validation/source system for a future real backend — flagged for awareness, not a blocker for this frontend-only change since all values are mocked.
