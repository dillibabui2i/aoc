## 1. Known assumptions (not blockers)

- [ ] 1.1 "Cancelled" is not a real tracker status in this mocked lifecycle; icons/eligibility use "Closed"/"Completed" only (design.md Decision 5) — flagged for requester confirmation.
- [ ] 1.2 The bucket's mock dataset (`hospitalization.store.js`) is reconciled into `MOCK_TRACKERS_BY_ID` under `#<tracker_id>` keys purely so the Notes icon works end-to-end (design.md Decision 6) — not a general integration of the two mock systems.
- [ ] 1.3 Old/new Activity Log values use the raw field value, relying on every `logUpdates` field's option `value` already equaling its `label` (design.md Decision 4).

## 2. Service layer: notes and logged-field activity entries (mocked, swappable)

- [x] 2.1 Add `isNotesEligible(status)` to `hospitalizationTracker.service.js`, alongside the existing `isActiveTracker`, returning `false` for `Closed`/`Completed`.
- [x] 2.2 Implement `HospitalizationTrackerService.addNote(trackerId, note)`: appends `note` to `MOCK_TRACKERS_BY_ID[trackerId].notes`, resolves the full updated tracker (mirrors `createContact`'s mutate-and-resolve pattern). Returns `null` (via `delay(null)`) if the trackerId doesn't exist.
- [x] 2.3 Implement `HospitalizationTrackerService.saveLoggedField(trackerId, fieldKey, value, note)`: sets `tracker[fieldKey] = value`, appends `note` to `tracker.notes`, resolves the full updated tracker in one call (replaces the generic `saveField` call previously used for `logUpdates` fields).
- [x] 2.4 Seed `MOCK_TRACKERS_BY_ID` with one entry per row in `hospitalization.store.js`'s `MOCK_DATA`, keyed `` `#${tracker_id}` ``, with at minimum `{ trackerId, status, notes: [] }` copied from the row (design.md Decision 6).
- [x] 2.5 Write/extend `hospitalizationTracker.service.spec.js`: `addNote` appends and persists (`getTrackerById` reflects it afterward), returns `null` for an unknown trackerId; `saveLoggedField` updates the field value and appends the note in one call; `isNotesEligible` covers all statuses.

## 3. Store: note modal lifecycle, submit, and logged-field activity logging

- [x] 3.1 Add `noteModalOpen` (boolean) and `noteModalTrackerId` (string | null) state.
- [x] 3.2 Implement `openCreateNote()` (uses `tracker.value.trackerId`) and `openCreateNoteForTracker(trackerId)` (bucket entry point, no full tracker load), both setting `noteModalOpen = true`.
- [x] 3.3 Implement `closeNoteModal()` resetting both fields.
- [x] 3.4 Implement `submitNote(text)`: guards on blank/whitespace-only text and a missing `noteModalTrackerId` (returns `false` so the modal keeps the red border); builds a note via `buildNote` (header: `` `Note by ${CURRENT_USER} at ${when}` ``, detail: the trimmed text) with an explicit `createdAt`; calls `HospitalizationTrackerService.addNote`; if the resolved trackerId matches the currently loaded `tracker.value`, reassigns `tracker.value` so the sidebar reacts immediately; closes the modal; returns `true` on success.
- [x] 3.5 Add `createdAt: Date.now()` to `buildNote`'s returned object and to the two inline note objects built directly in `submitOutcome` (`In Progress`/`Closed` branches).
- [x] 3.6 Change `saveLoggedField(key, value)` to `saveLoggedField(field, value)`: computes `oldValue = tracker.value[field.key]`, builds the Activity Log note (header `` `Field edited by ${CURRENT_USER} - ${when}` ``, detail `` `Field Name: ${field.label}\nOld Value: ${oldValue}\nNew Value: ${value}` ``, explicit `createdAt`), calls the new `HospitalizationTrackerService.saveLoggedField(trackerId, field.key, value, note)`, assigns the resolved tracker back onto `tracker.value`.
- [x] 3.7 Export the new state/actions from the store's returned surface.
- [x] 3.8 Write/extend `hospitalizationTracker.store.spec.js`: `openCreateNote`/`openCreateNoteForTracker`/`closeNoteModal` transitions; `submitNote` rejects blank text, appends via `addNote`, updates `tracker.notes` when targeting the loaded tracker, closes the modal; `saveLoggedField` calls the new service method with the correct old/new values and the entry appears in `tracker.notes`.

## 4. `CreateNoteModal.vue`

- [x] 4.1 Build the component: `UiModal` bound to `store.noteModalOpen`, title "Create Notes". A single `UITextArea` ("Notes") with no user-tagging control, Submit/Close footer buttons.
- [x] 4.2 Blank-submit validation using `UITextArea`'s native `error` prop (no `has-error`/`:deep()` workaround needed, unlike `UiInput`); blocks submission and keeps the modal open.
- [x] 4.3 Valid submit calls `store.submitNote(text)`; clears the local textarea and error state on success (and on modal close/reopen, via a `watch` on `store.noteModalOpen` mirroring `ContactDetailsModal.vue`'s re-fill pattern).
- [x] 4.4 Write `CreateNoteModal.spec.js`: blank submit shows the error and doesn't call `store.submitNote`; valid submit calls `store.submitNote` with the entered text; textarea resets when the modal is reopened.

## 5. Wire the Notes icons

- [x] 5.1 `TrackerTitleBar.vue`: make the sticky-note icon clickable (`@click="store.openCreateNote()"`), change its `v-if` from `status !== 'Closed'` to `isNotesEligible(store.tracker?.status)` (imported from the service).
- [x] 5.2 `HospitalizationBucketSection.vue`: import `useHospitalizationTracker`, `useHasPermission`, `isNotesEligible`; wrap the sticky-note icon in `v-if="canManageNotes && isNotesEligible(row.status)"`, wire `@click="trackerStore.openCreateNoteForTracker(`#${row.tracker_id}`)"`; mount `<CreateNoteModal />` once in this section.
- [x] 5.3 Write/extend `HospitalizationBucketSection.spec.js` (or add one if none exists): Notes icon hidden without the permission and for `Closed`/`Completed` rows; clicking it (for an eligible row, permission granted) calls `openCreateNoteForTracker` with the right id.
- [x] 5.4 Write/extend `TrackerTitleBar.spec.js`: icon hidden for `Closed`/`Completed`, clickable otherwise, calls `store.openCreateNote()`.

## 6. Activity Log generation from `LoggedField.vue`

- [x] 6.1 Update `LoggedField.vue`'s `save()` to call `store.saveLoggedField(props.field, draft.value)` (passing the whole field object instead of just `props.field.key`), so the store has the label needed for the Activity Log entry.
- [x] 6.2 Write/extend `LoggedField.spec.js`: saving a `logUpdates: true` field calls `store.saveLoggedField` with the field object and new value; saving a non-`logUpdates` field still only calls `store.autoSaveField` (no Activity Log path).

## 7. `NotesSidebar.vue`: chronological, multi-line rendering

- [x] 7.1 Sort the `notes` prop by `createdAt` descending in a local computed before rendering (`[...props.notes].sort((a, b) => (b.createdAt ?? 0) - (a.createdAt ?? 0))`).
- [x] 7.2 Add `white-space: pre-line` to `.notes-sidebar__item-detail` so multi-line `detail` text (Activity Log's 3 lines, and the existing "Closed - Reason: ..." text) renders as separate lines.
- [x] 7.3 Update the component's "future scope" comment to reflect that user notes and activity entries are now implemented.
- [x] 7.4 Write/extend `NotesSidebar.spec.js`: entries render newest-first regardless of input order; multi-line `detail` renders with line breaks preserved.

## 8. Verification against acceptance criteria

- [x] 8.1 AC1/AC2 — covered by task 5.2/5.3's permission gating (bucket) and existing `TrackerTitleBar`/modal-level gating (header) via the `useHasPermission` stub.
- [x] 8.2 AC3/AC11/AC12 — covered by `HospitalizationBucketSection.spec.js` (task 5.3).
- [x] 8.3 AC4 — covered by `TrackerTitleBar.spec.js` (task 5.4).
- [x] 8.4 AC5/AC6 — covered by `CreateNoteModal.spec.js` (task 4.4) — both entry points open the same modal/store state.
- [x] 8.5 AC7/AC8 — covered by `CreateNoteModal.spec.js` (blank vs. valid submit) and `hospitalizationTracker.store.spec.js`'s `submitNote` cases.
- [x] 8.6 AC9/AC10 — covered by `NotesSidebar.spec.js` (task 7.4) and `hospitalizationTracker.store.spec.js`'s `submitNote` updating `tracker.notes`.
- [x] 8.7 AC13 — satisfied by `CreateNoteModal.vue` never including a tagging control (task 4.1); no code to remove since none existed.
- [x] 8.8 AC14/AC15/AC19/AC20 — covered by `LoggedField.spec.js` (task 6.2) and `hospitalizationTracker.service.spec.js`'s `saveLoggedField` case (task 2.5).
- [x] 8.9 AC16/AC18 — covered by `hospitalizationTracker.store.spec.js`'s `saveLoggedField` case asserting the exact header/detail wording.
- [x] 8.10 AC17/AC21/AC22 — covered by `NotesSidebar.spec.js`'s mixed-entry sort case (task 7.4).
- [x] 8.11 AC23 — covered by `hospitalizationTracker.service.spec.js`'s `getTrackerById`-reflects-the-change assertions (task 2.5), same pattern as the contacts story.
- [x] 8.12 Ran `npm run test` — full suite green, no regressions.

## 9. Cleanup

- [x] 9.1 Run `eslint --fix` on every new/changed file.
- [ ] 9.2 Manually verify in a running dev build the full bucket → Create Notes → panel path and the header → Create Notes → panel path, plus a logged-field save producing the exact 4-line Activity Log wording, against the Figma reference — component specs don't substitute for a real visual check.
