# Test Cases: Care Coordination Activities

Manual QA test cases. Frontend + mock data only — verify in the browser.

## Setup

1. `npm run watch` (or build via `npx mix`).
2. Navigate to the dashboard page that mounts `CareCoordinationActivitiesBucketSection`.
3. Eligible patients for creation (tracker status "In Progress"): John Taylor (ID 12345-PDN), John Doe (ID 91299-PDN), John Wayne (ID 8979-PDN). John Doe (ID 99999-PDN, tracker `#10087`) is **not** eligible by default (its tracker starts as "Open").

---

## TC-1. "+" icon and calendar icon visibility (AC1, AC2)

**Steps:** open the Care Coordination bucket.

**Expected result:** a "+" icon is displayed in the toolbar; each row's Action column shows a calendar icon (alongside the existing, still-inert eye/sticky-note icons).

## TC-2. Opening the create modal (AC3, AC4)

**Steps:** click the "+" icon.

**Expected result:** the "Care Coordination" modal opens showing only a "Hospitalized Client Name" search field (no coordination fields yet).

## TC-3. Patient search only shows In Progress trackers (AC5)

**Steps:** in the open create modal, type "John" into the patient search.

**Expected result:** results show John Taylor (ID 12345-PDN), John Doe (ID 91299-PDN), and John Wayne (ID 8979-PDN), each with their Hospitalization Tracker ID; John Doe (ID 99999-PDN) does **not** appear.

## TC-4. Selecting a patient reveals coordination fields (AC6)

**Steps:** select "John Taylor, ID 12345-PDN".

**Expected result:** the modal now shows read-only Client ID/Hospitalization ID/Hospitalized At/Admission Date (from the selected tracker), plus editable Coordination Type, Provider, Assigned To, Due Date, Completion Date, "Care Coordination Completed" toggle, and Care Coordination Notes.

## TC-5. Validation on incomplete create (AC8)

**Steps:** with a patient selected, click Save without filling in Coordination Type/Provider/Assigned To/Due Date.

**Expected result:** submission is blocked; the four fields are highlighted red; the modal stays open.

## TC-6. Successful creation (AC7, AC14)

**Steps:**
1. Select John Taylor, fill in Coordination Type = AngelLink, Provider = First Provider of Dallas, Assigned To = any option, Due Date = any date.
2. Click Save.

**Expected result:** the modal closes; the bucket refreshes; a new row for John Taylor / AngelLink appears in the table. Refresh the page (or re-open the bucket) — the new row is still present.

## TC-7. Opening an editable activity (AC9)

**Steps:** click the calendar icon for a row linked to an "In Progress" tracker (any row created in TC-6, or a seeded row linked to one of the three new fixtures).

**Expected result:** the modal opens pre-filled with that activity's values; client/header fields are shown but not editable; coordination fields are editable.

## TC-8. Validation on incomplete edit (AC11)

**Steps:** in the open edit modal, clear the Due Date field and click Save.

**Expected result:** submission is blocked; Due Date is highlighted red; other fields keep their values; the modal stays open.

## TC-9. Successful edit (AC10, AC14)

**Steps:** change the Provider field and click Save.

**Expected result:** the modal closes; the bucket refreshes; the row shows the updated Provider value; it persists across a subsequent bucket refresh.

## TC-10. Read-only mode for a non-In-Progress-tracker activity (AC12, AC13)

**Steps:** click the calendar icon for a row linked to the `#10087` tracker (status "Open", not "In Progress").

**Expected result:** the modal opens with every field disabled (including the coordination fields); no Save button is present, only Close.

## TC-11. Permission gating (AC1, AC2)

**Steps:** `useHasPermission` is currently a stub always returning `true`; temporarily edit `resources/js/vue/modules/vbc/composables/usePermissions.js` to return `false` to verify.

**Expected result:** with the stub forced to `false`, neither the "+" icon nor any row's calendar icon is rendered in the Care Coordination bucket.
