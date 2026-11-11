# Test Cases: Hospitalization Tracker — Education

Manual QA test cases. This functionality is frontend + mock data only — verify in the browser.

## Setup

1. `npm run watch` (or build via `npx mix`).
2. Log in, navigate to `/vbc/hospitalization`.
3. Open tracker `#10087` (client John Doe, ID 99999-PDN) — it already has `staffFamilyTrainingRequired: true` and 4 demo Education records (Emergency Trach Replacement, Head Injury, Respiratory Education for Clinical Staff, Seasonal Illness Guide) and status "In Progress" (or move any other tracker to In Progress manually).

---

## TC-1. Education tab shows the toggle (AC1, AC2)

**Steps:** open tracker `#10087`, go to the **Education** tab.

**Expected result:** the "Staff / Family Training/Education required?" toggle is displayed, in the ON position; below it, a table with the four demo records and an **Add Education** button.

## TC-2. Toggle OFF hides the table and button (AC3)

**Steps:** turn the toggle OFF.

**Expected result:** the toggle is saved OFF; the Education table and **Add Education** button disappear.

## TC-3. Toggle ON shows the table and button (AC3)

**Steps:** turn the toggle back ON.

**Expected result:** the table (with the same 4 records) and **Add Education** reappear.

## TC-4. Opening the create form (AC4)

**Steps:** with the toggle ON and tracker In Progress, click **Add Education**.

**Expected result:** the **Training/Education** modal opens with empty fields: Training Type, Education Source, Education Notes; "Training Completed" toggle OFF; **Submit** and **Close** buttons.

## TC-5. Validation on create (AC6)

**Steps:** in the open create form, click **Submit** without filling in any fields.

**Expected result:** submission is blocked; Training Type and Education Source are highlighted with a red border (Education Notes is optional and isn't highlighted); the modal stays open.

## TC-6. Successfully creating a record (AC5)

**Steps:**
1. Open the form (Add Education).
2. Fill in: Training Type — any option, Education Source — any option. Education Notes — optional. Toggle "Training Completed" if desired.
3. Click **Submit**.

**Expected result:** the modal closes; a new row immediately appears in the Education table; the other records remain unchanged.

## TC-7. Opening the edit form (AC7)

**Steps:** on the Education tab, click the calendar icon in the **Action** column for record **Head Injury**.

**Expected result:** the Training/Education modal opens, prefilled with that record's current values.

## TC-8. Validation on edit (AC9)

**Steps:** in the open edit form, clear the **Training Type** field and click **Submit**.

**Expected result:** submission is blocked; the Training Type field is highlighted red; other filled fields don't lose their values; the modal stays open.

## TC-9. Successfully editing a record (AC8)

**Steps:**
1. Open the edit form for **Respiratory Education for Clinical Staff**.
2. Toggle "Training Completed" ON.
3. Click **Submit**.

**Expected result:** the modal closes; that row in the Education table now shows Completed = Yes; no other fields/records are affected.

## TC-10. Read-only mode after leaving In Progress (AC11, AC12, AC13)

**Steps:**
1. Move tracker `#10087` to "Discharged" (or any other non-In-Progress status).
2. Open the **Education** tab.
3. Click the calendar icon for any record.

**Expected result:**
- the **Add Education** button is not displayed, regardless of the toggle;
- the Education table is still visible, with all previously saved values;
- the Training/Education modal opens in read-only mode: values are shown as plain text (no editable inputs), there is no **Submit** button, only **Close** is available.

## TC-11. Persistence across reopen (AC14)

**Steps:** close tracker `#10087`'s modal and reopen it.

**Expected result:** the same toggle value and Education records (with their saved values) are displayed as before closing.

## TC-12. Permission gating (AC15)

**Steps:** `useHasPermission` is currently a stub always returning `true`; temporarily edit `resources/js/vue/modules/vbc/composables/usePermissions.js` to return `false` to verify.

**Expected result:** with the stub forced to `false`, the entire "Hospitalization Record" modal (Education tab included) is not accessible, per the existing AOC-4402 gating.
