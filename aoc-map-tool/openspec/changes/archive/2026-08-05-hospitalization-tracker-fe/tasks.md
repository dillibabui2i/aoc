## 1. Known deferrals (resolved by requester, not blockers)

- [x] 1.1 "Log Updates?" flags are mocked (Admission Type, Care Requirements, 30 Days Outcome) rather than sourced from the real data dictionary — no action needed now; correcting them later is a data-only edit to `hospitalizationTrackerFields.js`.
- [x] 1.2 Permission check ships as an always-`true` stub with a `TODO` comment (no permission system exists in the codebase today) — real enforcement is explicitly deferred to a separate, future ticket.

## 2. Field metadata & mock data foundation

- [x] 2.1 Create `resources/js/vue/modules/vbc/config/hospitalizationTrackerFields.js`: ordered field descriptors (`key`, `label`, `section`, `row`, `type`, `options`, `logUpdates`) covering all header-grid fields (rows 1–5) and all Current Hospitalization Info fields (rows A–F), per the UI reference field order/grouping.
- [x] 2.2 Populate dropdown option lists that mirror existing backend enum labels exactly: `HospitalizationTypeEnum`, `HospitalizationAdmissionTypeEnum`, `HospitalizationDeclinedProgramContactEnum`, `HospitalizationSourceEnum`, `HospitalizationCareRequirementsEnum`, `HospitalizationThirtyDayOutcomeEnum`, `HospitalizationLevelEnum`.
- [x] 2.3 Add small illustrative mock option lists for fields with no backend enum today (Client Status KT, DME Provider, Assigned To).
- [x] 2.4 Create a mock client fixture list (several clients, including at least two sharing the same full name with different IDs) for the client search mock.

## 3. Service layer (mocked, swappable)

- [x] 3.1 Create `resources/js/vue/modules/vbc/services/hospitalizationTracker.service.js` exporting `HospitalizationTrackerService` with `searchClients(query)`, `checkActiveTracker(clientId)`, `createTracker(payload)`, `getTrackerById(trackerId)`, `saveField(trackerId, fieldKey, value)` — each returning a `Promise` with artificial latency and mock data, no real HTTP calls.
- [x] 3.2 Implement `searchClients` to filter the fixture list by name substring (case-insensitive) and return `{ clientId, fullName, ...autoFillFields }` per match.
- [x] 3.3 Implement `checkActiveTracker` using the shared `isActiveTracker(status)` predicate (`status !== 'Completed' && status !== 'Closed'`), returning either `{ hasActive: false }` or `{ hasActive: true, trackerId }` from mock per-client tracker state.
- [x] 3.4 Implement `createTracker` to return a full tracker object: auto-filled fields, generated Tracker ID, Creation Date, Author (current user), status `'Open'`.
- [x] 3.5 Implement `getTrackerById` to return a previously "created" tracker from an in-memory mock store, preserving field values across calls within the session.
- [x] 3.6 Implement `saveField` to update the in-memory mock tracker and resolve with the saved value (used by both the auto-save and explicit-save paths).
- [x] 3.7 Write `hospitalizationTracker.service.spec.js` covering `isActiveTracker`, client search matching/disambiguation, and each service method's resolved shape.

## 4. Store

- [x] 4.1 Create `resources/js/vue/modules/vbc/store/hospitalizationTracker.store.js` (Pinia setup store): state for `isModalOpen`, `wizardStep`, `selectedClient`, `activeTrackerConflict`, `tracker`, `clientSearchResults`/`clientSearchLoading`. (`permissionGranted` was dropped from store state — the permission stub is read directly via `useHasPermission()` where needed, e.g. the "+" button, since it's a pure function with no async/store-worthy state.)
- [x] 4.2 Implement actions `openCreateModal`, `closeModal`, `selectClient` (calls `checkActiveTracker`, sets `wizardStep` to `'conflict'` or `'confirmed'`), `openExistingTracker` (calls `getTrackerById`, sets `wizardStep: 'open'`), `searchAnotherClient` (resets to `'search'`), `submitTracker` (calls `createTracker`, sets `wizardStep: 'open'`), `loadTrackerById`.
- [x] 4.3 Implement `autoSaveField(key, value)` and `saveLoggedField(key, value)` (both call `saveField` immediately when invoked; the difference is *when* each is called — see `LoggedField.vue` in section 11). `cancelLoggedField` was **not** added to the store: canceling a logged-field edit only discards an uncommitted local draft value that never reached the store, so it's handled entirely inside `LoggedField.vue` with no store round-trip needed.
- [x] 4.4 Write `hospitalizationTracker.store.spec.js` (mocking the service per repo convention) covering: client selection → conflict vs. confirmed branching, submit → Open transition, auto-save vs. logged-field save persistence.

## 5. Permission composable (stub)

- [x] 5.1 Create `resources/js/vue/modules/vbc/composables/usePermissions.js` exporting `useHasPermission(name)` that unconditionally returns `true`, with a `// TODO(AOC-????): wire to real "Intensive Case Management" permission once a permission system exists` comment, per design.md Decision 6.
- [x] 5.2 Write `usePermissions.spec.js` asserting it currently always returns `true` regardless of the `name` argument (documents current stub behavior so a future real implementation consciously updates this spec).

## 6. Entry point on the existing list page

- [x] 6.1 In `AllHospitalizationSection.vue`, add a permission-gated "+" button inside the existing `.head-title` toolbar block, wired to `hospitalizationTrackerStore.openCreateModal()`. No other change to this file.
- [x] 6.2 Mount `HospitalizationTrackerModal` alongside the existing section content, gated on the same permission check (the modal internally also gates on `store.isModalOpen`).

## 7. Local components: Tabs & Toggle (net-new, no design-system equivalent)

- [x] 7.1 Build `components/HospitalizationTracker/TrackerTabs.vue`: minimal 4-tab bar (active-tab styling matching Figma), named-slot content per tab key.
- [x] 7.2 Build `components/HospitalizationTracker/ToggleSwitch.vue`: on/off switch matching the Figma toggle style, `v-model` boolean, `disabled` prop.
- [x] 7.3 Write component specs (`TrackerTabs.spec.js`, `ToggleSwitch.spec.js`) covering active-tab switching and toggle disabled/enabled + v-model behavior.

## 8. Client search autocomplete

- [x] 8.1 Build `components/HospitalizationTracker/ClientSearchAutocomplete.vue`: text input (via `UiInput`) + dropdown result list rendered as `"{fullName}, ID {clientId}"`; debounced calls to the store's search; emits the full matched object on selection only (never raw text).
- [x] 8.2 Disable the field once a client is selected in this wizard step (per Figma state 1/2/3), showing a disabled read-only display of the selection; going back through `searchAnotherClient` returns it to the interactive search state.
- [x] 8.3 Write `ClientSearchAutocomplete.spec.js` covering: typing triggers search, same-name clients render as distinct selectable entries, selecting resolves the correct `clientId`, free text cannot be "submitted" without a selection.

## 9. Header info grid & title bar

- [x] 9.1 Build `components/HospitalizationTracker/TrackerInfoGrid.vue`: renders rows 1–5 from `hospitalizationTrackerFields.js` (`HEADER_FIELDS`), respecting per-state enablement (all disabled except Client Name in New state; auto-filled read-only + Assigned To/custom fields enabled once a client is confirmed). Also owns the pre-submit "Submit" button and the staged custom-field draft sent as the `createTracker` payload.
- [x] 9.2 Build `components/HospitalizationTracker/TrackerTitleBar.vue`: plain title + "New" badge before save; `Hospitalization Record \ {name} \ {level}` + Notes/Person icons + status badge after save. Person icon opens `/client/profile?id={clientId}` (`ClientController::getClientDetails`, confirmed via `routes/web.php` — real route, not a guess) in a new tab; Notes icon is inert.
- [x] 9.3 Write specs for both components covering the enablement-by-state matrix and the title-format switch.

## 10. Active-tracker conflict modal

- [x] 10.1 Build `components/HospitalizationTracker/ActiveTrackerConflictModal.vue` (second `UiModal` instance): "Open the active tracker" and "Search another client" actions wired to the corresponding store actions.
- [x] 10.2 Write a spec covering both button actions dispatch the correct store action and the modal visibility follows `wizardStep === 'conflict'`.

## 11. Current Hospitalization Info tab & logged-field editing

- [x] 11.1 Build `components/HospitalizationTracker/CurrentHospitalizationInfoTab.vue`: renders rows A–F from `hospitalizationTrackerFields.js` (`CURRENT_INFO_FIELDS`) via `LoggedField`.
- [x] 11.2 Build `components/HospitalizationTracker/LoggedField.vue`: renders the matching `@angels-of-care/ui` primitive per `type` (`UiInput`, `UiSelect`, `UITextArea`, `UiDateTimepicker`, local `ToggleSwitch`); if `logUpdates` is false, calls `store.autoSaveField` on every change; if true, holds the change in a local `draft` ref and shows floating Save/Cancel (Save calls `store.saveLoggedField`, Cancel reverts `draft` locally with no store call).
- [x] 11.3 Build `components/HospitalizationTracker/PlaceholderTab.vue` (reused for Hospitalization History / Sick Plan / Follow-up) rendering an empty-state message.
- [x] 11.4 Write specs: `CurrentHospitalizationInfoTab.spec.js` (one field per configured row/field) and `LoggedField.spec.js` (auto-save path fires immediately with no floating buttons; logged path shows Save/Cancel, Cancel reverts to original value with no store call, Save persists via `store.saveLoggedField`).

## 12. Outcome bar & Notes sidebar shell

- [x] 12.1 Build `components/HospitalizationTracker/OutcomeSubmitBar.vue`: `UiSelect` with "In Progress"/"Closed" placeholder "Select an outcome" + a Submit `UiButton`, no wired logic (both inert per scope).
- [x] 12.2 Build `components/HospitalizationTracker/NotesSidebar.vue`: right-side panel shell, empty state (`notes` prop defaults to `[]`), structured so a future story can drop in a list of note/activity entries without restructuring this component.
- [x] 12.3 Write minimal render specs for both (presence of controls; empty vs. populated notes list).

## 13. Root modal orchestrator

- [x] 13.1 Build `components/HospitalizationTracker/HospitalizationTrackerModal.vue`: single `UiModal` (title text rendered via the `extraHeader` slot / `TrackerTitleBar`, not the modal's own `title` prop, to fit the icon/status-badge layout), switches between the search/conflict/confirmed layout and the Open layout based on `wizardStep`, composing `TrackerTitleBar`, `TrackerInfoGrid`, `ClientSearchAutocomplete` (nested inside `TrackerInfoGrid`), `ActiveTrackerConflictModal`, `TrackerTabs` (+ `CurrentHospitalizationInfoTab` / `PlaceholderTab`), `OutcomeSubmitBar`, `NotesSidebar`.
- [x] 13.2 Submit-button enablement ("valid client selected AND no unresolved conflict", AC9/AC12) lives in `TrackerInfoGrid.vue`'s `canSubmit` (`wizardStep === 'confirmed' && !!selectedClient`) — the button doesn't render at all in `'search'`/`'conflict'`, satisfying "prevent the operation" without a separate disabled-state check.
- [x] 13.3 Write `HospitalizationTrackerModal.spec.js` covering the full happy path (select → confirmed → submit → Open state renders header/tabs/outcome/notes) and the conflict path (select → conflict modal visible → each of the two resolutions).

## 14. Verification against acceptance criteria

Automated coverage (75 Vitest specs, all passing) exercises every AC below at the component/store/service level. A real browser walkthrough was **not** done: this environment's `npm run development` (Laravel Mix/webpack) fails with `EACCES` writing to `public/js/vue-app.js`, which is root-owned here — a pre-existing environment/filesystem permission issue unrelated to this change, not something fixed as part of it. Flagging this explicitly rather than claiming a browser check that didn't happen.

- [x] 14.1 AC1/AC2 covered by `AllHospitalizationSection.spec.js` (button renders; permission stub is always granted) and `usePermissions.spec.js`.
- [x] 14.2 AC3–AC9 covered by `TrackerInfoGrid.spec.js` (New-state disablement) and `ClientSearchAutocomplete.spec.js` (search, disambiguation, selection-only).
- [x] 14.3 AC10–AC14 covered by `hospitalizationTracker.store.spec.js` (`selectClient` branching, `submitTracker` → Open) and `hospitalizationTracker.service.spec.js` (`isActiveTracker`, `createTracker`).
- [x] 14.4 AC15–AC18 covered by `ActiveTrackerConflictModal.spec.js` and `HospitalizationTrackerModal.spec.js`'s conflict-path tests.
- [x] 14.5 AC19–AC30 covered by `TrackerTitleBar.spec.js` (title/status/icons), `HospitalizationTrackerModal.spec.js` (tabs/outcome present in Open state), `OutcomeSubmitBar.spec.js`.
- [x] 14.6 AC31–AC35 covered by `LoggedField.spec.js` (auto-save vs. floating Save/Cancel, Cancel revert, Save persistence).
- [ ] 14.7 AC36 (reload/reopen persistence) is covered at the service/store level (`getTrackerById` returns a previously created tracker with all field values intact) but not via an actual page-reload browser check — not possible without a running dev server in this environment.

## 15. Cleanup

- [x] 15.1 Ran `eslint --fix` (equivalent to `npm run lint:fix`, which also runs prettier via `@vue/eslint-config-prettier`) on every new/changed file in this change. Zero lint errors remain; the only warnings left anywhere in `resources/js/vue/modules/vbc` are pre-existing CRLF/formatting warnings in files this change didn't touch (or didn't touch beyond appending a few LF-only lines) — left as-is per "avoid formatting-only changes outside modified code."
- [x] 15.2 Ran `npm run test` — 20/20 files, 75/75 tests pass, including the pre-existing suite (no regressions).
- [ ] 15.3 Run `/opsx:sync` (or `openspec sync`) to promote the `hospitalization-tracker-creation` delta spec into `openspec/specs/` once implementation matches the spec — left for a follow-up step, not part of `/opsx:apply` itself.
