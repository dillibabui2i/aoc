# Test Cases: Hospitalization Tracker — Outcome (Open → In Progress → Closed)

Manual QA test cases. This functionality is frontend + mock data only (no real backend) — verify in the browser.

## Setup

1. Build the frontend: `npm run watch` (or `npm run dev`).
2. Log in as any test user — access to this functionality doesn't depend on real permissions (`useHasPermission` is a stub that always returns `true`).
3. Navigate to `/vbc/hospitalization` (All Hospitalizations).

### Useful mock data

- Client **John Doe (ID 99999-PDN)** already has an open tracker `#10087` with status **Open** and Current Hospitalization Info data populated (Admission Date/Time is always set to be >24h in the past).
- Clients with no active tracker: **John Taylor (12345-PDN)**, **John Doe (91299-PDN)**, **John Wayne (8979-PDN)** — convenient starting points for a fresh Open→In Progress→Closed run.
- "Assigned To" user pool: Anna Lopatnova, Maria Ivanova, James Carter.

How to open tracker `#10087`: "+" → in the Client Name field type `John Doe` → select **ID 99999-PDN** → in the conflict modal click **"Open the active tracker"**.

---

## TC-1. Feature availability (AC1–AC2)

**Steps:**
1. Open `/vbc/hospitalization`.
2. Confirm the "+" icon (New Hospitalization Record) is visible in the page header.
3. Open tracker `#10087` (John Doe, 99999-PDN).

**Expected result:** the "+" icon is visible, the tracker modal opens, and the Outcome block is displayed.

> Note: since the permission check is a stub (always `true`), the "functionality is hidden without ICM permission" scenario cannot be reproduced in this build — this is an expected, documented mock limitation (see design.md of the earlier changes).

## TC-2. Outcome options for status Open (AC3)

**Steps:**
1. Open tracker `#10087`.
2. Expand the **Outcome** dropdown.

**Expected result:** exactly two options are available — **In Progress**, **Closed**.

## TC-3. Informational banner (>24h) (AC4–AC5)

**Steps (banner should be visible):**
1. Open tracker `#10087` (its mock Admission Date/Time is always >24h in the past).
2. Look at the area above Outcome.

**Expected result:** a blue informational banner is displayed above the Outcome dropdown.

**Steps (banner should be hidden):**
1. Create a new tracker (e.g. John Taylor) → Submit.
2. Go to the **Current Hospitalization Info** tab and leave **Admission Date/Time** empty (or set a date within the last 24h).

**Expected result:** the banner is not displayed.

## TC-4. Transition Open → In Progress (AC6–AC10)

**Steps:**
1. Open an Open-status tracker (any).
2. In Outcome select **In Progress** → **Submit**.

**Expected result:**
- the status badge in the modal header changes to **In Progress**;
- a two-line entry is added to the Notes sidebar: `In Progress by <name> at <date/time>` and `Status changed to 'In Progress' by <name>`;
- the main tab row is rearranged into the 9-item layout: **Hospitalization Info** (with Current Hospitalization Info / Hospitalization History sub-tabs), **Contacts**, **Diagnosis**, **Sick Plan**, **Care Coordination**, **Education**, **Follow-up**;
- the **Outcome** dropdown now shows **Discharge**, **Closed**;
- the **Assigned To** dropdown remains enabled (not disabled).

## TC-5. Closed validation without a Reason (AC11–AC12)

**Steps:**
1. On an In Progress tracker, select **Closed** in Outcome.
2. Without selecting a Reason, click **Submit**.

**Expected result:** submission is blocked, a required-field message is shown under the **Reason** field; the tracker status doesn't change; the modal stays open.

## TC-6. Transition → Closed with a reason (AC13–AC14, AC20–AC21)

**Steps:**
1. On an Open or In Progress tracker, select Outcome **Closed**.
2. Select Reason **ED Visit** (or **Opened by mistake**).
3. Click **Submit**.

**Expected result:**
- the modal closes automatically;
- reopening the same tracker shows status **Closed** with the reason saved;
- a Notes entry is added: `Closed by <name> at <date/time>` / `Status changed to 'Closed' by <name> - Reason: <reason>`;
- the "All Hospitalizations" list attempts to refresh (a network request fires) — the row is **not** expected to actually disappear from that table, since the mocked tracker isn't connected to that table's real backend (a documented limitation).

## TC-7. Discharge and Transfer are selectable but not submittable (AC23)

**Steps:**
1. On an In Progress tracker, select Outcome **Discharge**.
2. Check the state of the **Submit** button.
3. Switch Outcome back to **Closed**, select Reason **Transfer**.
4. Check the state of the **Submit** button.

**Expected result:** both options can be selected in their dropdowns, but the Submit button is disabled with a "Not yet available" hint — no transition occurs.

> Note: this test case reflects the state right after this change alone was implemented. Once `hospitalization-tracker-discharge-lifecycle` is implemented (see that change's test-cases.md), **Discharge** becomes fully functional — see that file for current behavior.

## TC-8. Read-only after Closed (AC15–AC19)

**Steps:**
1. Close a tracker (see TC-6).
2. Reopen the same tracker (via the conflict modal — select the same client again and click "Open the active tracker").

**Expected result:**
- all custom fields (High Utilizer?, Backup G-tube?/trach?, Level, last-updated dates) are disabled;
- **Assigned To** is disabled;
- all fields on the Current Hospitalization Info tab are disabled;
- the **Outcome** dropdown is hidden;
- the **Notes** icon (in the modal header, next to the profile icon) is hidden.

## TC-9. Data persists on reopen (AC22)

**Steps:**
1. Close a tracker with a specific reason (e.g. "ED Visit").
2. Reopen the tracker (see TC-8).

**Expected result:** the status and closure reason are preserved and displayed correctly; the Notes tab contains the full transition history.
