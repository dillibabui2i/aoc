# Test Cases: Hospitalization Tracker — Diagnosis

Manual QA test cases. This functionality is frontend + mock data only — verify in the browser.

## Setup

1. `npm run watch` (or build via `npx mix`).
2. Log in, navigate to `/vbc/hospitalization`.
3. Open tracker `#10087` (client John Doe, ID 99999-PDN) — it already has `wasDiagnosisMade: true` and 3 demo diagnoses (R94.5 Transaminitis, I10: Essential (primary) hypertension, R00.0 Tachycardia) and status "In Progress" (or move any other tracker to In Progress manually).

---

## TC-1. Diagnosis tab shows the toggle (AC3)

**Steps:** open tracker `#10087`, go to the **Diagnosis** tab.

**Expected result:** the "Was the Diagnosis Made?" toggle is displayed, in the ON position; below it, a table with the three demo diagnoses and an **Add Diagnosis** button.

## TC-2. Toggle persistence (AC4, AC6)

**Steps:** turn the toggle OFF, then reopen the tracker.

**Expected result:** the toggle is saved OFF and remains OFF after reopening; since diagnoses already exist, the table stays visible (per TC-4) but **Add Diagnosis** disappears.

## TC-3. Toggle ON with no records (AC5)

**Steps:** open a different tracker with no diagnoses and status In Progress; turn the toggle ON.

**Expected result:** the table (empty) and the **Add Diagnosis** button both appear.

## TC-4. Toggle OFF with existing records (AC7, AC8)

**Steps:** on tracker `#10087` (has 3 diagnoses), turn the toggle OFF.

**Expected result:** the table remains visible with its 3 rows; **Add Diagnosis** is hidden.

## TC-5. Toggle OFF with no records (AC7)

**Steps:** on a tracker with zero diagnoses and the toggle OFF.

**Expected result:** neither the table nor **Add Diagnosis** is displayed.

## TC-6. Opening the create-diagnosis form (AC9)

**Steps:** with the toggle ON and tracker In Progress, click **Add Diagnosis**.

**Expected result:** the **Diagnosis Details** modal opens with empty fields: Diagnosis Code, Rank, System, Clinical Status, Verification Status, Diagnosis Notes; Creation Date and Author pre-filled (now / current user) and not editable; **Submit** and **Close** buttons.

## TC-7. Validation on create (AC10)

**Steps:** in the open create form, click **Submit** without filling in any fields.

**Expected result:** submission is blocked; Diagnosis Code, Rank, System, Clinical Status, Verification Status are highlighted with a red border (Diagnosis Notes is optional and isn't highlighted); the modal stays open.

## TC-8. Successfully creating a diagnosis (AC11, AC20)

**Steps:**
1. Open the form (Add Diagnosis).
2. Fill in: Diagnosis Code — any option, Rank — `1 - Primary`, System — any option, Clinical Status — `Active`, Verification Status — `Confirmed`. Diagnosis Notes — optional.
3. Click **Submit**.

**Expected result:** the modal closes; a new row immediately appears in the diagnosis table; the other diagnoses remain unchanged — the new diagnosis doesn't affect other trackers/records.

## TC-9. Opening the edit form (AC12)

**Steps:** on the Diagnosis tab, click the calendar icon in the **Action** column for diagnosis **R94.5 Transaminitis**.

**Expected result:** the Diagnosis Details modal opens, prefilled with that diagnosis's current values.

## TC-10. Editable while In Progress (AC13)

**Steps:** with tracker `#10087` still In Progress, confirm the opened modal from TC-9 has editable selects and a visible **Submit** button.

## TC-11. Validation on edit (AC14)

**Steps:** in the open edit form, clear the **Diagnosis Code** field and click **Submit**.

**Expected result:** submission is blocked; the Diagnosis Code field is highlighted red; other filled fields don't lose their values; the modal stays open.

## TC-12. Successfully editing a diagnosis (AC15)

**Steps:**
1. Open the edit form for diagnosis I10: Essential (primary) hypertension.
2. Change Clinical Status to `Resolved`.
3. Click **Submit**.

**Expected result:** the modal closes; that row in the diagnosis table now shows the updated Clinical Status; no other fields/diagnoses are affected.

## TC-13. Read-only mode once the tracker leaves In Progress (AC16, AC17, AC18)

**Steps:**
1. Move tracker `#10087` to a status other than In Progress — e.g. Discharged.
2. Open the **Diagnosis** tab.
3. Click the calendar icon for any diagnosis.

**Expected result:**
- the **Add Diagnosis** button is not displayed, regardless of the toggle;
- the diagnosis table is still visible, with all previously saved values;
- the Diagnosis Details modal opens in read-only mode: values are shown as plain text (no editable inputs), there is no **Submit** button, only **Close** is available.

Repeat for statuses: **Ready for Review, Ready for Next Steps, Completed, Closed** — behavior should be identical in every case.

## TC-14. Persistence across reopen (AC19)

**Steps:** close tracker `#10087`'s modal and reopen it.

**Expected result:** the same toggle value and diagnoses (with their saved values) are displayed as before closing.
