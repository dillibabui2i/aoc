## Why

ICM users need to document hospitalization trackers continuously — both free-form notes and an automatic audit trail of changes to a small set of "logged" fields. `NotesSidebar.vue` (added in AOC-4402) already renders the tracker's status-change notes, but its own comment marks user-created notes and field-editing activity entries as explicit future scope. Today there is no way to create a note (the sticky-note icons in `TrackerTitleBar.vue` and the bucket's Action column are both inert), and `LoggedField.vue`'s "Log Updates?" save path persists the new value without ever recording what changed, who changed it, or when.

## What Changes

- Wire the existing (inert) sticky-note icon in `TrackerTitleBar.vue` and in `HospitalizationBucketSection.vue`'s Action column to open a new `CreateNoteModal.vue` ("Create Notes"): a single required Notes textarea, Submit/Close, red-border validation on blank submit (using `UITextArea`'s native `error` prop — no workaround needed here, unlike `UiInput`). No user-tagging control is included.
- Both icons are hidden once a tracker's status is `Closed` or `Completed` (the two statuses this mocked lifecycle actually has that represent "done" — see design.md Decision 5 re: "Cancelled").
- On submit, the note is appended to the tracker (via a new mocked `HospitalizationTrackerService.addNote`) and immediately visible in `NotesSidebar.vue` without needing the full "Hospitalization Record" modal to be open first (the bucket entry point creates a note for a tracker without loading it into the modal).
- `LoggedField.vue`'s Save action (fields with `logUpdates: true`, i.e. `admissionType`, `careRequirements`, `thirtyDayOutcome` today) now generates a real Activity Log entry — field name, old value, new value, user, timestamp — displayed in the same panel using the exact 4-line wording the story specifies, instead of silently persisting the new value with no trace.
- `NotesSidebar.vue` sorts every entry (status-change notes, user notes, activity-log entries) newest-first by an explicit `createdAt` timestamp, and renders multi-line `detail` text (needed for the Activity Log's 3-line body) instead of collapsing it to one line.
- Permission gating reuses the existing `useHasPermission('Intensive Case Management')` stub (currently only checked in `AllHospitalizationSection.vue`): both new Notes icons are hidden without it, matching every other gated control in this feature set.

## Capabilities

### Modified Capabilities
- `hospitalization-tracker-creation`: adds note creation (from two entry points), field-level activity logging for `logUpdates` fields, and chronological merged display of notes + activity log in the existing right-side panel.

### New Capabilities
(none — extends the same tracker capability)

## Impact

- `resources/js/vue/modules/vbc/components/HospitalizationTracker/TrackerTitleBar.vue` — sticky-note icon becomes clickable (`store.openCreateNote()`), hidden for `Closed`/`Completed` instead of only `Closed`.
- `resources/js/vue/modules/vbc/sections/Dashboard/HospitalizationBucketSection.vue` — sticky-note icon in the Action column becomes clickable (`store.openCreateNoteForTracker(...)`), gated by `useHasPermission('Intensive Case Management')` and row status; mounts the new `CreateNoteModal`.
- New: `resources/js/vue/modules/vbc/components/HospitalizationTracker/CreateNoteModal.vue`.
- `resources/js/vue/modules/vbc/components/HospitalizationTracker/NotesSidebar.vue` — sorts by `createdAt` desc, renders multi-line `detail`.
- `resources/js/vue/modules/vbc/components/HospitalizationTracker/LoggedField.vue` — Save path now passes old/new value + field metadata to the store so an Activity Log entry can be built.
- `resources/js/vue/modules/vbc/store/hospitalizationTracker.store.js` — new state (`noteModalOpen`, `noteModalTrackerId`), new actions (`openCreateNote`, `openCreateNoteForTracker`, `closeNoteModal`, `submitNote`); `saveLoggedField` and `buildNote` extended for activity-log entries and explicit `createdAt`.
- `resources/js/vue/modules/vbc/services/hospitalizationTracker.service.js` — new mocked `addNote(trackerId, note)`; `saveField` for `logUpdates` fields is replaced by a dedicated `saveLoggedField(trackerId, fieldKey, value, note)` that persists the value and the log entry together; new `isNotesEligible(status)` helper alongside the existing `isActiveTracker`; the bucket's disconnected mock dataset (`hospitalization.store.js`'s numeric `tracker_id`s) is reconciled into `MOCK_TRACKERS_BY_ID` under `#<tracker_id>` keys so the bucket's Notes icon has a real mocked tracker to attach notes to (see design.md Decision 6 — flagged assumption, not a blocker).
- No backend (`app/VBC/**`, routes) changes — frontend + mock data only, consistent with every prior story in this feature set.
