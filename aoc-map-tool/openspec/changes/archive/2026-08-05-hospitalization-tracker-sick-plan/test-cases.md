# Test Cases: Hospitalization Tracker — Sick Plan

Manual QA test cases. This functionality is frontend + mock data only — verify in the browser.

## Setup

1. `npm run watch` (or build via `npx mix`).
2. Log in, navigate to `/vbc/hospitalization`.
3. Open tracker `#10087` (client John Doe, ID 99999-PDN) — its client already has a seeded Sick Plan with six items (Hydration, Pain, Seizure, Respiratory, Allergy, GI), some marked Completed.

---

## TC-1. Sick Plan tab shows the table (AC4, AC6)

**Steps:** open tracker `#10087` (status Open), go to the **Sick Plan** tab.

**Expected result:** a table with six rows (Hydration, Pain, Seizure, Respiratory, Allergy, GI) is displayed, each with its Completed?/Completed by/Notes values; **Add Sick Plan** is NOT displayed (tracker is Open, not In Progress).

## TC-2. Fields read-only while Open (AC6)

**Steps:** click the calendar icon for any row.

**Expected result:** the **Sick Plan Details** modal opens in read-only mode — values shown as plain text, no editable inputs, only **Close** available.

## TC-3. Blank Sick Plan for a brand-new client (AC3)

**Steps:** create a new hospitalization tracker for a client who has never had one before (e.g. via "New Hospitalization Record").

**Expected result:** once created, opening its Sick Plan tab shows six blank rows (Hydration, Pain, Seizure, Respiratory, Allergy, GI), all Completed = No, Completed by empty, Notes empty.

## TC-4. Reusing an existing Sick Plan (AC5, AC12)

**Steps:** create a **second** hospitalization tracker for the same client used in TC-3 (close the first tracker without editing its Sick Plan, then create a new tracker for the same client — or check `checkActiveTracker`'s conflict path if applicable).

**Expected result:** the new tracker's Sick Plan tab shows the exact same six items (same ids/values) as the first tracker's — no duplicate plan, no reset to blank.

## TC-5. Fields become editable In Progress (AC7)

**Steps:** move tracker `#10087` to "In Progress" (Outcome → In Progress → Submit).

**Expected result:** the Sick Plan tab now shows an **Add Sick Plan** button; clicking any row's calendar icon opens the modal with editable Sick Plan Type/Completed?/Notes and a **Submit** button.

## TC-6. Adding a new Sick Plan item (AC8)

**Steps:**
1. With the tracker In Progress, click **Add Sick Plan**.
2. Select a Sick Plan Type, optionally toggle **Completed?** on, add Notes.
3. Click **Submit**.

**Expected result:** the modal closes; a new row appears in the table; if **Completed?** was toggled on, "Completed by" shows the current user.

## TC-7. Editing an existing item (AC8)

**Steps:**
1. Click the calendar icon for "Pain" (already Completed = Yes).
2. Change the Notes text.
3. Click **Submit**.

**Expected result:** the modal closes; the "Pain" row now shows the updated Notes; other rows are unaffected.

## TC-8. Validation (Sick Plan Type required)

**Steps:** open **Add Sick Plan**, leave Sick Plan Type unselected, click **Submit**.

**Expected result:** submission is blocked; Sick Plan Type is highlighted red; the modal stays open.

## TC-9. Fields return to read-only after leaving In Progress (AC9, AC10)

**Steps:** move tracker `#10087` to a different status (e.g. Discharged), then open the Sick Plan tab and click any row's calendar icon.

**Expected result:** **Add Sick Plan** is no longer displayed; the modal opens read-only, showing the values saved while In Progress (including the TC-6/TC-7 changes), no Submit button.

Repeat for statuses: **Ready for Review, Ready for Next Steps, Completed, Closed** — behavior should be identical in every case.

## TC-10. Persistence across reopen (AC11)

**Steps:** close tracker `#10087`'s modal and reopen it.

**Expected result:** the same Sick Plan items, with the same values, are displayed, with editability matching the tracker's current status.
