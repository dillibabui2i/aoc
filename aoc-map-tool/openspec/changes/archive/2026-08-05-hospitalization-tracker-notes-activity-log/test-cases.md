# Test Cases: Hospitalization Tracker — Notes & Activity Log

Manual QA test cases. This functionality is frontend + mock data only — verify in the browser.

## Setup

1. `npm run watch` (or build via `npx mix`).
2. Log in, navigate to `/vbc/hospitalization` (bucket) and to the "All Hospitalizations" dashboard section that mounts `HospitalizationTrackerModal`.
3. Tracker `#10087` (client John Doe, ID 99999-PDN) is the same fixture used by prior stories.

---

## TC-1. Notes icon visibility in the bucket (AC1-AC3, AC11-AC12)

**Steps:** open the bucket table (`HospitalizationBucketSection`), look at the Action column for rows with different statuses (Open, In Progress, Discharged, Ready for Review, Ready for Next Steps).

**Expected result:** the Notes (sticky-note) icon is displayed for all of these; it is not displayed for any row whose status is Closed or Completed (none in the current fixture — add one manually if needed to confirm the hidden case).

## TC-2. Opening Create Notes from the bucket (AC5)

**Steps:** click the Notes icon for any eligible row.

**Expected result:** the "Create Notes" modal opens with a single empty "Notes" field and Submit/Close buttons; no user-tagging control is present.

## TC-3. Validation on blank submit (AC7)

**Steps:** in the open Create Notes modal, click Submit without typing anything.

**Expected result:** submission is blocked; the Notes field shows a red border/error; the modal stays open.

## TC-4. Successful note creation from the bucket (AC8, AC9)

**Steps:**
1. Open Create Notes for the row backing tracker `#10087`.
2. Type "Family called to confirm discharge plan."
3. Click Submit.

**Expected result:** the modal closes; opening tracker `#10087`'s full record and its right-side panel shows this note at the top, with a header line "Note by Anna Lopatnova at \<date/time\>".

## TC-5. Notes icon and modal from the tracker header (AC4, AC6)

**Steps:** open tracker `#10087`'s full record; click the sticky-note icon in the title bar.

**Expected result:** the same "Create Notes" modal opens; submitting a note closes the modal and the note immediately appears at the top of the right-side panel without needing to reopen the tracker.

## TC-6. Notes icon hidden once Closed/Completed (AC11, AC12)

**Steps:** move tracker `#10087` to "Closed" (Outcome → Closed → Reason → Submit) or "Completed"; observe both the title bar and, if the row is visible in a bucket view, the Action column.

**Expected result:** the Notes icon is not displayed in either location.

## TC-7. Activity Log entry on a logged field save (AC14, AC16, AC18)

**Steps:**
1. Open tracker `#10087`, ensure status allows editing (e.g. "In Progress" or earlier).
2. On the "Current Hospitalization Info" tab, change "Admission Type" (a `logUpdates` field) to a different option.
3. Click the floating "Save" button.

**Expected result:** the right-side panel shows a new entry at the top:
```
Field edited by Anna Lopatnova - <date and time>
Field Name: Admission Type
Old Value: <previous value>
New Value: <new value>
```

## TC-8. No Activity Log entry for a non-logged field (AC15)

**Steps:** change "Facility Name" (auto-save, not `logUpdates`) to a new value.

**Expected result:** the value saves immediately (no floating Save/Cancel bar), and no new entry appears in the right-side panel.

## TC-9. Multiple saves preserve history (AC19, AC20)

**Steps:** repeat TC-7 twice more with different values for "Admission Type" (or another logged field, e.g. "Care Requirements").

**Expected result:** each save adds one new entry to the top of the panel; all previously created entries (including earlier notes and status-change entries) remain visible and unchanged, just pushed further down.

## TC-10. Chronological ordering of mixed entries (AC10, AC17, AC21, AC22)

**Steps:** on a tracker with at least one status-change note, one user-created note, and one Activity Log entry (from TC-4/TC-7 above, plus any status transition), open the right-side panel.

**Expected result:** all entries are interleaved in a single list, most recent first, regardless of type.

## TC-11. Persistence across reopen (AC23)

**Steps:** close tracker `#10087`'s modal (without changing status) and reopen it via the same entry point.

**Expected result:** all notes and Activity Log entries are still present, in the same order, as before closing.

## TC-12. Permission gating (AC1, AC2)

**Steps:** `useHasPermission` is currently a stub always returning `true`, so this can only be verified by temporarily editing `resources/js/vue/modules/vbc/composables/usePermissions.js` to return `false`.

**Expected result:** with the stub forced to `false`, the Notes icon in the bucket's Action column is not rendered for any row (the header icon's gating is inherited from the whole modal already being behind `useHasPermission` per AOC-4402).
