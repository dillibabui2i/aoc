# Test Cases: Hospitalization Tracker — Discharge Lifecycle (Discharge → Ready for Review → Ready for Next Steps → Completed)

Manual QA test cases. This functionality is frontend + mock data only — verify in the browser. Continuation of `hospitalization-tracker-outcome` — requires a tracker with status **In Progress** to start (see the neighboring change's test-cases.md, TC-4).

## Setup

1. `npm run watch` (or build via `npx mix`).
2. Log in, navigate to `/vbc/hospitalization`.
3. Open or create a tracker and move it to status **In Progress** (Outcome → In Progress → Submit).
4. User pool for the Reviewer / Next Steps to be made by / Completed by dropdowns: **Anna Lopatnova, Maria Ivanova, James Carter**.

---

## TC-1. Starting state In Progress (AC1)

**Steps:** open an In Progress tracker, expand Outcome.

**Expected result:** **Discharge** and **Closed** are available.

## TC-2. Discharge — form fields are displayed (AC3)

**Steps:** in Outcome select **Discharge**.

**Expected result:** a form appears below the dropdown: **Expected Discharge Date**, **Discharge Date**, a **"First billable shift completed within 48 hours following hospital discharge?"** toggle, **Discharge Notes**, and a **Submit** button.

## TC-3. Discharge — toggle reveals the extra field (additional UI check)

**Steps:** in the Discharge form, turn on the "First billable shift completed..." toggle.

**Expected result:** the **First billable shift date** field appears. Turning the toggle off hides it again.

## TC-4. Discharge — required-field validation (AC4)

**Steps:** without filling in any fields, click **Submit** in the Discharge form.

**Expected result:** submission is blocked; **Expected Discharge Date**, **Discharge Date**, and **Discharge Notes** are highlighted with a red border; the modal stays open. If the toggle is on and **First billable shift date** is left empty, it's also highlighted red.

## TC-5. Discharge — successful transition (AC5–AC6)

**Steps:**
1. Fill in Expected Discharge Date, Discharge Date, and Discharge Notes (the toggle may stay off).
2. Click **Submit**.

**Expected result:**
- the tracker status becomes **Discharged**;
- a Notes entry is added: `Discharged by <name> at <date/time>` / `Status changed to 'Discharged' by <name>`;
- the **Current Hospitalization Info** tab now shows new permanent fields at the bottom: Expected Discharge Date, Discharge Date, the toggle, and (if turned on) First billable shift date — with the values entered in the form;
- **Assigned To** remains enabled.

## TC-6. Outcome after Discharge (AC7–AC8)

**Steps:** on a tracker with status **Discharged**, expand Outcome.

**Expected result:** exactly one option is available — **Ready for Review**. **Assigned To** remains enabled.

## TC-7. Ready for Review — form and validation (AC9–AC10)

**Steps:**
1. Select Outcome **Ready for Review**.
2. Click **Submit** without filling in any fields.

**Expected result:** the **Review to be made by** dropdown and **Ready for Review notes** field are displayed; submitting empty highlights both red and blocks submission.

## TC-8. Ready for Review — successful transition (AC11–AC14)

**Steps:**
1. Select a **Review to be made by** user (e.g. Maria Ivanova).
2. Fill in notes.
3. Click **Submit**.

**Expected result:**
- the status becomes **Ready for Review**;
- the **Assigned To** field in the tracker header now shows the selected reviewer (Maria Ivanova) — **important: verify the dropdown value actually updates**, not just the underlying data;
- a Notes entry is added: `Ready for Review by <current user> at <date/time>` / `<entered notes text>` (the actual entered text, not a generic phrase);
- Assigned To remains enabled/editable.

## TC-9. Outcome after Ready for Review (AC15–AC16)

**Steps:** on a tracker with status **Ready for Review**, expand Outcome.

**Expected result:** exactly one option is available — **Ready for Next Steps**. Assigned To remains enabled.

## TC-10. Ready for Next Steps — default value and validation (AC17–AC19)

**Steps:**
1. Select Outcome **Ready for Next Steps**.
2. Look at the **Next Steps to be made by** field right after the form opens (before any edits).
3. Clear the field and click Submit.

**Expected result:**
- by default, **Next Steps to be made by** equals whoever was set as the reviewer in TC-8 (i.e. Maria Ivanova) — this is the internal "Who Sent to Review" field; verify it auto-fills correctly;
- with the required fields (Next Steps to be made by, Notes) empty, submission is blocked and both fields are highlighted red.

## TC-11. Ready for Next Steps — successful transition (AC20–AC22)

**Steps:**
1. Keep/select a user in "Next Steps to be made by" (e.g. Anna Lopatnova).
2. Fill in notes.
3. Click Submit.

**Expected result:**
- the status becomes **Ready for Next Steps**;
- **Assigned To** in the header updates to the selected user (Anna Lopatnova);
- a Notes entry is added: `Ready for Next Steps by <current user> at <date/time>` / `<entered notes text>`;
- Assigned To remains enabled.

## TC-12. Outcome after Ready for Next Steps (AC23–AC24)

**Steps:** on a tracker with status **Ready for Next Steps**, expand Outcome.

**Expected result:** exactly one option is available — **Completed**. Assigned To remains enabled.

## TC-13. Completed — default value and validation (AC25–AC27)

**Steps:**
1. Select Outcome **Completed**.
2. Look at the **Completed by** field right after the form opens.
3. Clear the field and click Submit.

**Expected result:**
- by default, **Completed by** equals the current Assigned To (the value from TC-11);
- with empty fields, submission is blocked and the fields are highlighted red.

## TC-14. Completed — successful transition and full lock-down (AC28–AC32)

**Steps:**
1. Keep/select a value in Completed by.
2. Fill in notes.
3. Click Submit.

**Expected result:**
- the tracker status becomes **Completed**;
- a Notes entry is added: `Completed by <current user> at <date/time>` / `<entered notes text>`;
- **Assigned To** becomes disabled;
- the Outcome block is fully hidden (no dropdown or Submit button);
- every field on every tab of the tracker (custom fields, Current Hospitalization Info) is disabled.

## TC-15. Completed state persists on reopen (AC33)

**Steps:** close the tracker modal, then reopen the same tracker.

**Expected result:** the status remains **Completed**, all fields are still disabled, the Outcome block stays hidden — the state doesn't "bounce back" on reopen.
