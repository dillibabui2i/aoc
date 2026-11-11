## Context

`NotesSidebar.vue` (AOC-4402) already renders `tracker.notes` as a flat list of `{ id, header, detail }` entries, currently populated only by the outcome-transition actions in `hospitalizationTracker.store.js` (`submitOutcome`, `submitDischarge`, `submitReadyForReview`, `submitReadyForNextSteps`, `submitCompleted`) via a shared `buildNote(header, detail)` helper. Its own comment states: "User-created notes and field-editing activity entries remain future scope (AOC-3490 and related)" — this is exactly that follow-up.

Two inert sticky-note icons already exist in the markup: `TrackerTitleBar.vue` (title bar, hidden only when `status === 'Closed'`) and `HospitalizationBucketSection.vue`'s `#cell-actions` slot (Action column, no visibility logic, no store wired in at all). `LoggedField.vue` already distinguishes `logUpdates: true` fields (currently `admissionType`, `careRequirements`, `thirtyDayOutcome` — all `select` fields whose option `value` already equals its `label`) from auto-save fields, but its `save()` just calls `store.saveLoggedField(key, value)`, which persists the value with no record of what changed.

This story is frontend + mock data only, same as every prior story in this feature set; no backend work.

## Goals / Non-Goals

**Goals:**
- Give both existing sticky-note icons real behavior: open a "Create Notes" modal, respecting status eligibility and the `Intensive Case Management` permission.
- Make `logUpdates` field saves generate a real, permanent Activity Log entry with the exact required wording.
- Merge notes and activity-log entries into one chronological (newest-first) timeline in the existing `NotesSidebar.vue`, without introducing a second panel/component.

**Non-Goals:**
- No user tagging (explicitly out of scope per the story).
- No backend work, no new permission system — `useHasPermission` stays the existing always-`true` stub.
- No general reconciliation of the bucket's mock dataset with the tracker mock dataset beyond what's needed for the Notes icon to work end-to-end (Decision 6).
- No edit/delete of existing notes or activity-log entries (not in the ACs).

## Decisions

**1. One `CreateNoteModal.vue`, usable both "inside" an open tracker and "from" the bucket without loading the tracker.**
The store gets `noteModalOpen` (boolean) and `noteModalTrackerId` (string | null) — the trackerId the modal is currently targeting, independent of `tracker.value` (which may be `null` when opened from the bucket). `openCreateNote()` (used by `TrackerTitleBar`, tracker already loaded) sets `noteModalTrackerId = tracker.value.trackerId`; `openCreateNoteForTracker(trackerId)` (used by the bucket) sets it directly. This mirrors the existing `activeTrackerConflict`/`contactModalOpen` "store owns modal lifecycle" convention while avoiding a forced full-tracker load just to attach a note, which the Figma flow (bucket → Create Notes modal, no intermediate full record view) requires.

**2. Notes and activity-log entries are the same shape (`{ id, header, detail, createdAt }`), sorted in the component, not the store.**
No new "type" discriminant is introduced — `NotesSidebar.vue` doesn't need to distinguish a note from an activity-log entry to render it (both are a header line + detail body). Sorting by `createdAt` (a numeric timestamp, newly added to every note-building call site including the pre-existing status-change ones) happens in `NotesSidebar.vue` itself via a computed, so `tracker.notes` stays an appendable array (as today) and the store doesn't need to keep re-sorting.

**3. Activity Log entries reuse the header/detail shape with a multi-line `detail`.**
Per the story's required wording:
```
Line 1: Field edited by <user name> - <date and time>
Line 2: Field Name: <field name>
Line 3: Old Value: <old value>
Line 4: New Value: <new value>
```
`header` = `` `Field edited by ${CURRENT_USER} - ${when}` ``, `detail` = `` `Field Name: ${field.label}\nOld Value: ${old}\nNew Value: ${new}` ``. `NotesSidebar.vue`'s `.notes-sidebar__item-detail` gets `white-space: pre-line` so the embedded `\n`s render as separate lines — this also improves the existing multi-line "Closed - Reason: ..." detail text, which today silently collapses to one line.

**4. Old/new values are the raw field values, not re-mapped through `field.options`.**
For all three fields currently flagged `logUpdates: true`, every option's `value` already equals its `label` (e.g. `ADMISSION_TYPE_OPTIONS`), so the raw stored value is already the human-readable label. A label-lookup helper is deliberately not added — flagged as a known gap (task 6.1) if a future logged field's option `value`/`label` ever diverge, not a blocker today.

**5. "Cancelled" is not a real status in this mocked lifecycle — icons are hidden for `Closed` and `Completed` only.**
The story text says notes are blocked for "Closed" and "Cancelled", but the Acceptance Criteria (AC11/AC12) and every status this mocked tracker lifecycle can reach (`Open`, `In Progress`, `Discharged`, `Ready for Review`, `Ready for Next Steps`, `Completed`, `Closed`) only include "Closed" and "Completed" as terminal. A new `isNotesEligible(status)` helper (`hospitalizationTracker.service.js`, alongside the existing `isActiveTracker`) returns `false` for exactly those two, matching the ACs and the existing terminal-status concept; if "Cancelled" is introduced as a real status later, this helper is the one place to update.

**6. The bucket's `HospitalizationBucketSection.vue` and the tracker's `MOCK_TRACKERS_BY_ID` are two disconnected mock datasets — reconciled minimally so the Notes icon has something real to attach to.**
`hospitalization.store.js` (`Dashboard/`) ships its own unrelated `MOCK_DATA` with numeric `tracker_id`s (e.g. `12345`) that don't exist in `hospitalizationTracker.service.js`'s `MOCK_TRACKERS_BY_ID` (keyed `#10087`-style) — no prior story wired the bucket's rows to the tracker modal at all (its "eye"/"calendar" icons are inert too, unchanged by this story). To make AC3/AC5 ("click Notes icon in the bucket, get a real Create Notes modal, see the note persist") actually work, `MOCK_TRACKERS_BY_ID` is seeded with one entry per bucket row, keyed `` `#${tracker_id}` ``, carrying at least `{ trackerId, status, notes: [] }` copied from the bucket row. `HospitalizationBucketSection.vue` calls `store.openCreateNoteForTracker(`#${row.tracker_id}`)`. This is an explicit, narrow assumption (flagged in tasks.md) — it does not attempt to unify the two mock systems generally (no shared client fields, no cross-navigation from bucket to full record).

## Risks / Trade-offs

- **[Accepted]** Decision 6 is a minimal, narrow reconciliation of two mock datasets that were never connected before this story — broader unification (e.g. the "eye" icon actually opening the full tracker) is out of scope.
- **[Accepted]** Decision 4's "raw value is already the label" assumption holds for all fields flagged `logUpdates` today; revisit if that changes.

## Migration Plan

No data migration; additive to the existing mocked tracker/service/store. When real endpoints exist: replace `addNote`/`saveLoggedField`'s bodies with real `client.*()` calls; `NotesSidebar.vue`'s sort-by-`createdAt` and the store's `submitNote`/activity-log note shape need no changes, since they already treat the service's resolved tracker as the source of truth (same pattern as `submitContact`).

## Open Questions

- Whether "Cancelled" should become a real tracker status is not addressed here (Decision 5) — flagged for the requester, not a blocker.
