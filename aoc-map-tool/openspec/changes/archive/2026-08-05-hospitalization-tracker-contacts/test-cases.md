# Test Cases: Hospitalization Tracker — Contacts

Manual QA test cases. This functionality is frontend + mock data only — verify in the browser.

## Setup

1. `npm run watch` (or build via `npx mix`).
2. Log in, navigate to `/vbc/hospitalization`.
3. Open tracker `#10087` (client **John Doe, ID 99999-PDN**) — it already has 3 demo contacts (Pamela Hopkins / Step Mother, Earl Jones / Father, Amana Byrne / Relative) and status **In Progress** (or move any other tracker to In Progress manually — see `hospitalization-tracker-outcome`'s test-cases.md, TC-4).
4. Relationship options: Step Mother, Father, Relative, Spouse, Caregiver, Nurse, Physician, Facility Contact, Other.

---

## TC-1. Access to and content of the Contacts tab (AC1, AC3–AC4)

**Steps:**
1. Open tracker `#10087` (status In Progress).
2. Go to the **Contacts** tab (top tab row).

**Expected result:** a contacts table is displayed (Contact Name, Relationship, Phone Number, Email, Action) with the three demo entries, and an **Add Contact** button above the table.

## TC-2. Opening the create-contact form (AC5)

**Steps:** on the Contacts tab, click **Add Contact**.

**Expected result:** the **Contact Details** modal opens with empty fields: Contact Name, Relationship, Phone Number, E-mail, Contact Date, Contact received by, Contact Notes; **Submit** and **Close** buttons at the bottom.

## TC-3. Validation on create (AC6)

**Steps:** in the open create form, click **Submit** without filling in any fields.

**Expected result:** submission is blocked; **Contact Name, Relationship, Phone Number, E-mail, Contact Date, Contact received by** are highlighted with a red border (Contact Notes is optional and isn't highlighted); the modal stays open.

## TC-4. Successfully creating a contact (AC7, AC11)

**Steps:**
1. Open the form (Add Contact).
2. Fill in: Contact Name — `Julie Winborne`, Relationship — `Nurse`, Phone Number — `(555) 000-1111`, E-mail — `julie@example.com`, Contact Date — any date/time, Contact received by — `Melinda James`. Contact Notes — optional.
3. Click **Submit**.

**Expected result:**
- the modal closes;
- a new row "Julie Winborne / Nurse / (555) 000-1111 / julie@example.com" immediately appears in the Contacts table;
- the other contacts (Pamela Hopkins, Earl Jones, Amana Byrne) remain unchanged — the new contact doesn't affect other trackers/records.

## TC-5. Opening the edit form (AC8)

**Steps:** on the Contacts tab, click the calendar icon in the **Action** column for contact **Pamela Hopkins**.

**Expected result:** the Contact Details modal opens, prefilled with Pamela Hopkins's current values (Contact Name, Relationship, Phone Number, E-mail, Contact Date, Contact received by, Contact Notes).

## TC-6. Validation on edit (AC9)

**Steps:** in the open edit form, clear the **Contact Name** field and click **Submit**.

**Expected result:** submission is blocked; the Contact Name field is highlighted red; other filled fields don't lose their values; the modal stays open.

## TC-7. Successfully editing a contact (AC10)

**Steps:**
1. Open the edit form for contact Earl Jones.
2. Change Phone Number to `(555) 999-0000`.
3. Click **Submit**.

**Expected result:** the modal closes; the Earl Jones row in the Contacts table now shows the updated Phone Number; no other fields/contacts are affected.

## TC-8. Read-only mode once the tracker leaves In Progress (AC12–AC15)

**Steps:**
1. Move tracker `#10087` (or any other tracker with contacts) to a status other than In Progress — e.g. Discharged (Outcome → Discharge → fill in the form → Submit; see `hospitalization-tracker-discharge-lifecycle`'s test-cases.md).
2. Open the **Contacts** tab.
3. Click the calendar icon for any contact.

**Expected result:**
- the **Add Contact** button is not displayed;
- the contacts table is still visible, with all previously saved values;
- the Contact Details modal opens in read-only mode: values are shown as plain text (no editable inputs), there is no **Submit** button, only **Close** is available.

Repeat for statuses: **Ready for Review, Ready for Next Steps, Completed, Closed** — behavior should be identical in every case.

## TC-9. Contacts survive status changes (AC16)

**Steps:**
1. Record the list of contacts and their values while the tracker is In Progress.
2. Move the tracker through Discharge → Ready for Review → Ready for Next Steps → Completed (see `hospitalization-tracker-discharge-lifecycle`'s test-cases.md).
3. Open the Contacts tab at each step.

**Expected result:** the list of contacts and their values doesn't change or disappear across status transitions.

## TC-10. Data persists on reopen (AC17)

**Steps:** close the tracker modal after creating/editing a contact, reopen the same tracker, go to the Contacts tab.

**Expected result:** all previously created/edited contacts are displayed with their saved values (no data loss within the current page session).
