## Why

Care Coordination/ICM teams need a structured way to schedule, track, and document follow-up outreach for hospitalized patients and High Utilizers. `CommunicationBucketSection.vue` already lists rows shaped exactly like a Follow-up ("Patient Name, Patient ID, Tracker ID, Level, Hospitalized?, Status, Facility, Assignee, Due Date, Action" — a perfect match for the ticket's "Hospitalization Follow-ups bucket"), but has no create action and all its Action-column icons are inert. The Hospitalization Tracker modal's "Follow-up" tab is still a `PlaceholderTab`.

## What Changes

- Add a "+" icon to `CommunicationBucketSection.vue`'s toolbar (gated by `useHasPermission('Intensive Case Management')`) that opens a new multi-stage "Follow-up" modal in create mode.
- The modal is a single, long-lived session that transitions through statuses without closing on every submit (distinct from every other tab modal in this codebase, which close-on-submit): New (patient search) → Open (auto-populated header + informational message + Outcome dropdown) → Scheduled/Completed/Attempted/Canceled (outcome-specific fields), finalizing to a read-only view once Completed/Attempted/Canceled. Only an explicit Close (✕ or the read-only "Close" button) actually closes it.
- "Hospitalized Client Name" search only offers clients with at least one hospitalization tracker whose status is not "Closed" (broader than the Care Coordination Activities story's "In Progress"-only filter). Selecting one auto-populates Client ID, Hospitalization ID, Facility Name, and Admission Date from that tracker.
- An informational, non-blocking message is shown once a patient is selected, based on the selected tracker's Patient Level and how many days have passed since Admission Date (Level I/III → 30 days, Level II/III → 60 days, Level III → 90 days; every other level shows nothing).
- Saving for the first time creates the follow-up with status "Open" (no page navigation needed — the same modal continues). From "Open" (and again from "Scheduled"), an Outcome dropdown drives the rest of the workflow: Scheduled (date + assignee), Completed (documentation fields + optional auto-chained next Scheduled follow-up), Attempted (same shape as Completed, with attempt-specific fields), Canceled (no extra fields). Completed/Attempted/Canceled are terminal and read-only from then on.
- Add a `FollowUpTab.vue` to the Hospitalization Tracker modal's existing "Follow-up" tab (`#followUp` slot, currently `PlaceholderTab`): a table of every follow-up tied to that tracker (including Completed/Attempted, unlike the Communications bucket which excludes them), sorted newest-created-first, with a calendar icon per row opening the same Follow-up modal, editability driven purely by the follow-up's own status (not the tracker's).
- Permission gating requires no new code beyond the bucket's "+"/calendar icons: the Hospitalization Record modal is already gated behind `useHasPermission('Intensive Case Management')` from AOC-4402; the new bucket icons get the same explicit check (`CommunicationBucketSection.vue` has no existing gating to inherit, unlike the tracker modal).

## Capabilities

### New Capabilities
- `hospitalization-follow-ups`: create/manage Follow-up records from the Communications bucket, with the full New → Open → outcome workflow, next-follow-up auto-chaining for qualifying patient levels, and permission gating.

### Modified Capabilities
- `hospitalization-tracker-creation`: the "Follow-up" tab renders real content (a table of the tracker's own follow-ups) instead of an empty placeholder.

## Impact

- `resources/js/vue/modules/vbc/sections/Dashboard/CommunicationBucketSection.vue` — adds the "+" icon and wires the calendar icon; mounts the new `FollowUpModal`.
- New: `resources/js/vue/modules/vbc/components/FollowUp/FollowUpModal.vue`, `HospitalizedClientSearch.vue`.
- New: `resources/js/vue/modules/vbc/components/HospitalizationTracker/FollowUpTab.vue`.
- New: `resources/js/vue/modules/vbc/store/followUp.store.js` — owns the modal's multi-stage lifecycle (distinct from every other tab's "one modal, three modes, closes on submit" store actions, since Follow-up's modal stays open across outcome transitions).
- `resources/js/vue/modules/vbc/store/hospitalizationTracker.store.js` — small addition: `refreshTracker()`, re-fetching and reassigning `tracker.value` without touching `wizardStep`/`isModalOpen`, so `FollowUpTab.vue`'s table reflects follow-up changes made through the shared `followUp.store.js` without a full modal reopen.
- `resources/js/vue/modules/vbc/services/hospitalizationTracker.service.js` — new mocked `searchHospitalizedClientsForFollowUp(query)`, `createFollowUp(trackerId, payload)`, `updateFollowUp(trackerId, followUpId, payload)`, and `getFollowUpsBucket()` (aggregates non-Closed trackers' follow-ups, excluding Completed/Attempted, for the Communications bucket); `followUps: []` default added to every mock tracker; three existing "In Progress" fixtures (from the Care Coordination Activities story) gain `Level I/III`/`Level II/III`/`Level III` and admission ages past 30/60/90 days respectively, to exercise the informational-message and next-follow-up-default logic without adding new client fixtures.
- `resources/js/vue/modules/vbc/store/Dashboard/communication.store.js` — `fetchAll` now sources its `list` from `HospitalizationTrackerService.getFollowUpsBucket()` instead of its own static `MOCK_DATA` (whose row shape already matches the bucket's columns exactly), so bucket rows reflect real create/update activity.
- No backend (`app/VBC/**`, routes) changes — frontend + mock data only, consistent with every prior story in this epic.
