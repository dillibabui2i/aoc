# Test Cases: Hospitalization Follow-ups

Manual QA test cases. This functionality is frontend + mock data only — verify in the browser.

## Setup

1. `npm run watch` (or build via `npx mix`).
2. Navigate to the dashboard page that mounts `CommunicationBucketSection` ("Communications" bucket).
3. Eligible patients for follow-up creation (any non-Closed hospitalization): John Doe (99999-PDN, Level II), John Taylor (12345-PDN, Level I/III, admitted ~35 days ago), John Doe (91299-PDN, Level II/III, admitted ~65 days ago), John Wayne (8979-PDN, Level III, admitted ~95 days ago).

---

## TC-1. "+" and calendar icon visibility (AC1, AC2)

**Steps:** open the Communications bucket.

**Expected result:** a "+" icon is displayed in the toolbar; each row's Action column shows a calendar icon.

## TC-2. Opening the create modal (AC3)

**Steps:** click the "+" icon.

**Expected result:** the "Follow-up" modal opens with status badge "New" and an empty "Hospitalized Client Name" search field.

## TC-3. Patient search (AC4)

**Steps:** type "John" into the search field.

**Expected result:** results include all four eligible patients above (all have a non-Closed hospitalization).

## TC-4. Auto-populate and no message for a non-qualifying level (AC5)

**Steps:** select "John Doe, ID 99999-PDN" (Level II).

**Expected result:** Client ID/Hospitalization ID/Facility Name/Admission Date populate; no informational message is shown.

## TC-5. Optional-follow-up message, Level I/III (AC31)

**Steps:** open a new follow-up, select "John Taylor, ID 12345-PDN".

**Expected result:** "More than 30 days have passed since the admission date, and based on this patient's level, this follow-up will be optional." is displayed.

## TC-6. Optional-follow-up message, Level II/III (AC32)

**Steps:** select "John Doe, ID 91299-PDN".

**Expected result:** the 60-day variant of the message is displayed.

## TC-7. Optional-follow-up message, Level III (AC33)

**Steps:** select "John Wayne, ID 8979-PDN".

**Expected result:** the 90-day variant of the message is displayed.

## TC-8. Saving as Open (AC6)

**Steps:** with a patient selected, click Save.

**Expected result:** the modal stays open; the status badge changes to "Open"; a "Follow-up Outcome" dropdown appears with Scheduled/Completed/Attempted/Canceled.

## TC-9. Scheduling (AC7-AC9, AC24)

**Steps:**
1. Select "Scheduled" from the Outcome dropdown — Follow-up Date and Follow-up Assigned To fields appear.
2. Click Submit without filling them in — both are highlighted red, submission blocked.
3. Fill in both, click Submit.

**Expected result:** status badge becomes "Scheduled"; the modal stays open, now showing a fresh Outcome dropdown (Completed/Attempted/Canceled only).

## TC-10. Completing without a next follow-up (AC10, AC12, AC25)

**Steps:**
1. From the Scheduled follow-up (TC-9), select "Completed".
2. Submit without filling Status/Follow-Ups/Needs — all three highlighted red, blocked.
3. Fill in Status, Follow-Ups/Updates, Needs (Additional Notes optional); leave "Next Follow-up Due" off; Submit.

**Expected result:** status badge becomes "Completed"; all fields become read-only; only a "Close" button remains; the Communications bucket (after closing) does not show this follow-up (Completed excluded); the tracker's Follow-up tab does show it.

## TC-11. Completing with a chained next follow-up (AC11, AC13, AC26, AC34)

**Steps:**
1. Create and Schedule a new follow-up for John Taylor (Level I/III).
2. Select "Completed"; observe "Next Follow-up Due" defaults ON, with a due date one week after the scheduled follow-up date/time.
3. Fill in the required completion fields, adjust or accept the next follow-up date/assignee, Submit.

**Expected result:** current follow-up becomes read-only "Completed"; a new follow-up record with status "Scheduled" and the entered date/assignee now appears in the Communications bucket and the tracker's Follow-up tab.

## TC-12. Attempting (AC14, AC16, AC27)

**Steps:** create/schedule a follow-up, select "Attempted", submit without Attempt Notes (blocked, red border), then fill it in and submit with "Next Follow-up Due" off.

**Expected result:** status becomes "Attempted", read-only, no new follow-up created.

## TC-13. Attempting with a chained next follow-up (AC15, AC17, AC28, AC35)

**Steps:** same as TC-11 but choosing "Attempted" instead of "Completed", for John Doe (91299-PDN, Level II/III).

**Expected result:** "Next Follow-up Due" defaults ON with a 60-day-level-appropriate date rule (still "current follow-up date + 1 week", per the ticket — level only affects whether it defaults ON); submitting creates the chained Scheduled follow-up.

## TC-14. Non-qualifying level does not default Next Follow-up Due (AC36)

**Steps:** complete or attempt a follow-up for John Doe (99999-PDN, Level II).

**Expected result:** "Next Follow-up Due" defaults OFF.

## TC-15. Canceling (AC18, AC19)

**Steps:** open a new Open follow-up, select "Canceled", Submit.

**Expected result:** no additional fields ever appear; status becomes "Canceled", read-only.

## TC-16. Opening an existing follow-up from the bucket (AC20-AC22)

**Steps:** click the calendar icon for a Scheduled row in the bucket, then for one you know is Completed (open it from the tracker's Follow-up tab instead, since Completed ones are excluded from the bucket).

**Expected result:** Scheduled opens editable; Completed opens fully read-only with only "Close".

## TC-17. Validation on new follow-up (AC23)

**Steps:** open the create modal, click Save without selecting a patient.

**Expected result:** submission is blocked (no patient selected — Hospitalized Client Name shown invalid).

## TC-18. Persistence (AC29, AC30)

**Steps:** close and reopen a follow-up (from either entry point).

**Expected result:** all saved values, status, and association with the original hospitalization are unchanged.

## TC-19. Follow-up tab: display, columns, ordering (AC37-AC39)

**Steps:** open tracker `#10087` (John Doe, 99999-PDN), go to the **Follow-up** tab.

**Expected result:** a table with columns Follow-up Date, Assigned To, Status, Actions; all seeded follow-ups shown (including any Completed/Attempted ones), most recently created first.

## TC-20. Opening from the tab (AC40, AC41)

**Steps:** click the calendar icon for any row in the Follow-up tab.

**Expected result:** the Follow-up modal opens with the same editability/outcome/validation/read-only behavior as from the bucket.

## TC-21. Permission gating (AC1, AC2)

**Steps:** `useHasPermission` is currently a stub always returning `true`; temporarily edit `resources/js/vue/modules/vbc/composables/usePermissions.js` to return `false`.

**Expected result:** with the stub forced to `false`, neither the "+" icon nor any row's calendar icon is rendered in the Communications bucket.
