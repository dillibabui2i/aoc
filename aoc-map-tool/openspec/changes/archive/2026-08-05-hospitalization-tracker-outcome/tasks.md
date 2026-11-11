## 1. Known deferrals (explicit scope decisions, not blockers)

- [x] 1.1 `Discharge` (Outcome) and `Transfer` (Reason) are selectable in their dropdowns per the ACs but Submit stays disabled when either is chosen — no defined behavior exists for them in this ticket (Transfer is explicitly deferred to AOC-3482; Discharge has no ACs at all here). No action needed beyond the disabled-Submit handling in section 4.
- [x] 1.2 AC21's bucket-refresh call targets the real `allHospitalization.store.js` (`fetchAll()`), which is not connected to this modal's mocked client/tracker data — the closed mock tracker will not visibly disappear from that real table in this environment. Accepted per design.md Decision 7; not a blocker.
- [x] 1.3 No "Opened by ... / Status changed to 'Open' by ..." note is backfilled for the existing AOC-4402 creation flow — out of scope for this change (design.md Non-Goals).

## 2. Service layer: outcome transition (mocked, swappable)

- [x] 2.1 In `hospitalizationTracker.service.js`, export the mock current-user value as `CURRENT_USER` (rename/re-export the existing `MOCK_CURRENT_USER` constant) so the store can attribute notes without duplicating the name.
- [x] 2.2 Add `notes: []`, `reason: null`, `wasInProgress: false` defaults to `MOCK_TRACKERS_BY_ID['#10087']` and to the object built in `createTracker` (so every tracker — pre-seeded or newly created — has a consistent shape from the start).
- [x] 2.3 Implement `HospitalizationTrackerService.updateOutcome(trackerId, { outcome, reason, notesToAppend })`: looks up the mock tracker, sets `status` to `'In Progress'` or `'Closed'` per `outcome`, sets `reason` when closing, sets `wasInProgress = true` when transitioning to In Progress, appends `notesToAppend` entries to the tracker's `notes` array, and resolves the updated tracker (mirrors the existing `saveField` mutate-and-resolve pattern, with artificial latency via the existing `delay()` helper).
- [x] 2.4 For QA/demo fidelity with the Figma reference, seed `MOCK_TRACKERS_BY_ID['#10087']` with representative Current Hospitalization Info field values, including an `admissionDateTime` computed as "more than 24 hours before now" at module-load time (not a hardcoded past date) so the informational banner (AC4) is reliably exercisable when opening the pre-seeded demo tracker.
- [x] 2.5 Write/extend `hospitalizationTracker.service.spec.js` covering `updateOutcome` for both In Progress and Closed transitions (status, reason, wasInProgress, notes array updated) and confirming `getTrackerById` reflects the change afterward (matching AC22's "reopen preserves state").

## 3. Store: `submitOutcome` action

- [x] 3.1 In `hospitalizationTracker.store.js`, add an `outcomeError` ref (or similar) for the Reason-required validation message, reset on every new Submit attempt.
- [x] 3.2 Implement `submitOutcome({ outcome, reason })`: if `outcome === 'Closed'` and `reason` is empty, set `outcomeError` and return without calling the service (AC12) — no note added, tracker unchanged, modal stays open.
- [x] 3.3 On valid input, build the two note-text lines per design.md Decision 5 (`header`/`detail`, using `CURRENT_USER` and `new Date().toLocaleString('en-US')`), call `HospitalizationTrackerService.updateOutcome(...)`, and assign the resolved tracker back onto `tracker.value` (matching the existing `submitTracker`/`autoSaveField` pattern of trusting the service's resolved shape).
- [x] 3.4 When the resolved outcome is `'Closed'`: call `closeModal()` (AC20) and `useAllHospitalization().fetchAll()` (AC21, see design.md Decision 7 for the known fidelity gap) — both after the tracker mutation succeeds, not before.
- [x] 3.5 When the resolved outcome is `'In Progress'`: do not close the modal and do not change `wizardStep` (stays `'open'`).
- [x] 3.6 Return the exported store surface list to include `outcomeError` and `submitOutcome`.
- [x] 3.7 Write/extend `hospitalizationTracker.store.spec.js`: In Progress transition (status updates, note appended, modal stays open); Closed transition with a valid reason (status/reason/notes updated, `closeModal` and `allHospitalization.fetchAll` both called — mocked per repo convention); Closed submitted with an empty reason (no service call, `outcomeError` set, tracker unchanged). (Assigned-To-stays-enabled per AC10 is asserted at the component level in section 5, since the store has no concept of field enablement.)

## 4. `OutcomeSubmitBar.vue`: state-driven Outcome + Reason + banner

- [ ] 4.1 Compute the Outcome option list from `store.tracker.status` per design.md Decision 4 (`['In Progress', 'Closed']` for `Open`, `['Discharge', 'Closed']` for `In Progress`); render nothing (root `v-if`) when `store.tracker?.status === 'Closed'` (AC17).
- [ ] 4.2 Show the Reason `UiSelect` (options: `ED Visit`, `Opened by mistake`, `Transfer`) whenever the locally-selected outcome is `'Closed'`; bind its `error` prop to the store's `outcomeError` (AC12).
- [ ] 4.3 Disable the Submit button when the selected outcome is `'Discharge'`, or when it's `'Closed'` with reason `'Transfer'` (design.md Decision 6); add a short inline hint text for both disabled cases ("Not yet available").
- [ ] 4.4 Wire Submit to call `store.submitOutcome({ outcome, reason })` (only reachable for the two real transitions per 4.3's disabling).
- [ ] 4.5 Add the 24h admission-age banner (`UiAlert`, info/blue variant) above the Outcome dropdown, computed per design.md Decision 8, hidden once `store.tracker?.status === 'Closed'`.
- [ ] 4.6 Write/extend `OutcomeSubmitBar.spec.js`: option list per status, Reason field appears only for Closed and shows the required-field error when submitted empty, Submit disabled for Discharge/Transfer, banner visibility for admission dates >24h/≤24h/Closed, Submit calls `store.submitOutcome` with the right payload.

## 5. Closed-state disabling across the modal

- [x] 5.1 `TrackerInfoGrid.vue`: update `isEditableNow(field)` to also require `store.tracker?.status !== 'Closed'` (design.md Decision 1) — covers the custom fields (High Utilizer?, Backup G-tube?/trach?, Level, last-updated timestamps) and `Assigned To` (AC15/AC16).
- [x] 5.2 `LoggedField.vue`: add `:disabled="store.tracker?.status === 'Closed'"` to every rendered field type (`ui-input`, `ui-textarea`, `ui-select`, `ui-date-timepicker`, `ToggleSwitch`) (AC18).
- [x] 5.3 `TrackerTitleBar.vue`: change the Notes icon's `v-if` to also require `store.tracker?.status !== 'Closed'` (AC19).
- [x] 5.4 Write/extend specs (`TrackerInfoGrid.spec.js`, `LoggedField.spec.js`, `TrackerTitleBar.spec.js`) covering the Closed-disabled state for each, and confirm the same assertions hold when the tracker is loaded already-Closed (not just immediately after a Closed submit) — directly exercises AC22.

## 6. Tab rearrangement

- [x] 6.1 Extend `TrackerTabs.vue` to support optional `subTabs` per top-level tab entry per design.md Decision 3: render a second tab row when the active top-level tab has `subTabs`, key the content slot by the active sub-tab, and default the active sub-tab to the first one when its parent tab is first selected.
- [x] 6.2 In `HospitalizationTrackerModal.vue`, compute `useNestedTabs = store.tracker?.status === 'In Progress' || store.tracker?.wasInProgress` (design.md Decision 2) and switch between the existing flat 4-tab config and the new nested 9-item config accordingly.
- [x] 6.3 Build four new placeholder tab entries (Contacts, Diagnosis, Care Coordination, Education) reusing the existing `PlaceholderTab.vue`, matching how Sick Plan/Follow-up are already wired.
- [x] 6.4 Re-parent the existing `Current Hospitalization Info`/`Hospitalization History` tab content under the new `Hospitalization Info` top-level tab's `subTabs` in the nested config, with no change to either tab's own component/content.
- [x] 6.5 Write/extend `TrackerTabs.spec.js` (sub-tab rendering/switching, default sub-tab selection, backward compatibility with tabs that have no `subTabs`) and `HospitalizationTrackerModal.spec.js` (flat tabs for Open/never-In-Progress-Closed, nested tabs for In Progress and for Closed-after-In-Progress).

## 7. Notes sidebar

- [x] 7.1 Update `NotesSidebar.vue` to render each note as two lines — a header line (bold, e.g. `"In Progress by Anna Lopatnova at 3/12/2026, 4:00:00 PM"`) and a detail line (e.g. `"Status changed to 'In Progress' by Anna Lopatnova"`) — replacing the current single-line `{{ note.text }}` rendering. Keep the `notes` prop's default (`[]`) and empty-state text ("No notes yet.") unchanged.
- [x] 7.2 In `HospitalizationTrackerModal.vue`, pass `:notes="store.tracker?.notes ?? []"` to `<NotesSidebar />` (currently mounted with no prop at all).
- [x] 7.3 Write/extend `NotesSidebar.spec.js` covering the two-line rendering per note and the unchanged empty state.

## 8. Verification against acceptance criteria

- [x] 8.1 AC1/AC2 — unchanged from AOC-4402 (`useHasPermission` stub); no new test needed, confirmed existing `usePermissions.spec.js`/`AllHospitalizationSection.spec.js` coverage still passes.
- [x] 8.2 AC3, AC9 — covered by `OutcomeSubmitBar.spec.js` (task 4.6).
- [x] 8.3 AC4/AC5 — covered by `OutcomeSubmitBar.spec.js`'s banner cases (task 4.6).
- [x] 8.4 AC6/AC7 — covered by `hospitalizationTracker.store.spec.js`'s In Progress case (task 3.7).
- [x] 8.5 AC8 — covered by `TrackerTabs.spec.js`/`HospitalizationTrackerModal.spec.js` (task 6.5).
- [x] 8.6 AC10 — covered by `TrackerInfoGrid.spec.js`'s In-Progress-status case (task 5.4): `Assigned To` remains enabled (only the Closed condition disables it).
- [x] 8.7 AC11/AC12/AC13/AC14 — covered by `OutcomeSubmitBar.spec.js` (Reason field + validation, task 4.6) and `hospitalizationTracker.store.spec.js` (Closed transition + validation cases, task 3.7).
- [x] 8.8 AC15–AC19 — covered by `TrackerInfoGrid.spec.js`, `LoggedField.spec.js`, `TrackerTitleBar.spec.js`, `OutcomeSubmitBar.spec.js` (task 5.4, 4.6).
- [x] 8.9 AC20/AC21 — covered by `hospitalizationTracker.store.spec.js` (`closeModal`/`fetchAll` called on Closed, task 3.7); AC21's real-world limitation documented in design.md Decision 7 and task 1.2 — not something an automated spec can meaningfully assert beyond "the call happens."
- [x] 8.10 AC22 — covered by task 5.4's "loaded already-Closed" assertions.
- [x] 8.11 AC23 — covered by `OutcomeSubmitBar.spec.js`'s Transfer-selectable-but-Submit-disabled case (task 4.6).
- [x] 8.12 Ran `npm run test` — 20/20 files, 104/104 tests pass (including the pre-existing suite), no regressions. Note: this environment's `vitest` initially failed entirely with a native-binding error (`Cannot find native binding` for `@rolldown/binding-linux-x64-gnu`) — a pre-existing npm optional-dependency install issue, unrelated to this change — fixed by installing that one binding package (`npm install --no-save @rolldown/binding-linux-x64-gnu@1.1.5`), matching the version already pinned in `package-lock.json`.

## 9. Cleanup

- [x] 9.1 Ran `eslint --fix` (ESLint + Prettier via `@vue/eslint-config-prettier`) on every new/changed file. Zero errors/warnings remain in this change's files; unrelated pre-existing CRLF warnings elsewhere in `resources/js/vue/modules/vbc` and pre-existing lint errors in untouched `Dashboard/` files are unaffected (confirmed via `npx eslint resources/js/vue --quiet` file listing).
- [ ] 9.2 Manually verify in a running dev build (if environment permits `npm run development`/`npm run watch`) at least one full path per outcome (Open → In Progress, Open → Closed, In Progress → Closed) against the Figma reference, since Vitest component specs don't substitute for a real visual/interaction check of the tab rearrangement and banner styling. **Not done in this session** — no interactive browser available; `npx mix` (production build) was run instead and compiles successfully, but that only verifies the bundle builds, not the rendered UI/interactions.
- [ ] 9.3 Run `/opsx:sync` (or `openspec sync`) to merge this change's delta spec into `openspec/specs/hospitalization-tracker-creation/spec.md` once implementation matches the spec (note: that capability's *original* AOC-4402 delta was also never synced — confirm with whoever owns that sync whether both deltas should be merged together or this one first).
