## Context

`CareCoordinationActivitiesBucketSection.vue` renders `CareCoordinationFilters` + a `UiTable` fed by `careCoordinationActivities.store.js`'s `fetchAll` (100% mock — the real `careCoordinationActivitiesService.getAll` call is commented out, endpoint is a `'#'` placeholder). Its `#cell-actions` slot has calendar/eye/sticky-note icons, all inert. Row shape (`{ id, created_date, client_name, client_id, hospital, type, provider, assigned_to, due_date }`) has no link to a hospitalization tracker and no `status`/`priority`, despite the filters row already exposing Status/Priority selects.

This is the same "two disconnected mock datasets" situation resolved narrowly in the `hospitalization-tracker-notes-activity-log` story (Decision 6 there): the CC bucket's `client_id: '1234-PDN'` values don't correspond to any client in `hospitalizationTracker.service.js`'s `MOCK_CLIENTS`. This story needs a real cross-reference (to check the linked tracker's status for read-only routing, and to offer only "In Progress"-tracker clients in the create flow's patient search), so it reconciles the two datasets again, narrowly.

The Figma reference (Scenario 1) also shows a "Care Coordination" tab on the Hospitalization Tracker modal listing that tracker's own activities — **out of scope here**: none of AC1–AC14 test it, only the bucket flows are specified. See Non-Goals.

## Goals / Non-Goals

**Goals:**
- Wire the bucket's "+" and calendar icons to a real create/edit/read-only modal, gated by permission and by the linked tracker's status.
- Reuse the established "one modal, three modes" pattern from `ContactDetailsModal.vue` and the "store owns modal lifecycle" convention.
- Keep mock data local to `careCoordinationActivities.store.js`, matching this store's own existing convention (unlike the Hospitalization Tracker feature, which keeps mocks in its service).

**Non-Goals:**
- No Hospitalization Tracker modal "Care Coordination" tab content — not covered by any AC; stays a `PlaceholderTab` for now.
- No backend work, no new permission system.
- No delete action — not in the ACs or Figma.
- No general reconciliation of the CC bucket's and the tracker's mock datasets beyond linking the rows needed for this story's read-only/eligibility checks.

## Decisions

**1. The create flow is two-stage, driven by store state, not two components.**
`careCoordinationActivities.store.js` gets `modalOpen`, `editingActivity` (`null` for create), and `confirmedClient` (`null` until stage 1's client search is submitted). `CareCoordinationModal.vue` renders stage 1 (client search only) when `!editingActivity && !confirmedClient`, and stage 2 (coordination fields) once either `confirmedClient` is set (create) or `editingActivity` is set (edit — client fields are pre-populated from the activity itself, stage 1 is skipped entirely). This mirrors the existing `wizardStep` pattern in `hospitalizationTracker.store.js` without introducing a second store or a step-machine library.

**2. Read-only routing is resolved by the linked tracker's live status at open-time, not stored on the activity.**
Each CC mock row gains `hospitalization_tracker_id`. Opening an existing activity (`openActivity(activity)`) calls `HospitalizationTrackerService.getTrackerById(activity.hospitalization_tracker_id)` and stores the result's `status` as `linkedTrackerStatus`; the modal is editable only if that status is `'In Progress'` (AC9-AC13), otherwise fully read-only, reusing the same one-modal/`isReadOnly` branch pattern as `ContactDetailsModal.vue`. This also directly satisfies the Figma annotation that patient/header fields are locked even in the editable case (design intentionally never lets header fields be edited post-creation — only decides whether the *coordination* fields are editable).

**3. Patient search filters to trackers with status exactly `'In Progress'`, not the broader `isActiveTracker` set.**
The ticket's "Starting State" and AC6 are explicit: creation requires an "In Progress" tracker specifically (not merely "active", which elsewhere in this codebase also includes Discharged/Ready for Review/etc.). A new `HospitalizationTrackerService.searchActiveHospitalizedClients(query)` filters `MOCK_TRACKERS_BY_ID` to `status === 'In Progress'` entries only, returning `{ clientId, fullName, trackerId, facilityName, admissionDateTime }` (reusing the existing `facilityName`/`admissionDateTime` fields already on tracker fixtures, matching the Figma's "Hospitalized At"/"Admission Date").

**4. Three new tracker fixtures, not a change to the existing `#10087`/`99999-PDN` one.**
`#10087` defaults to status `'Open'` and other stories' tests exercise its transition to `'In Progress'`; changing its default would risk those tests and would also mean `99999-PDN` ends up with two conflicting "active tracker" entries. Instead, three `MOCK_CLIENTS` entries that currently have no tracker at all (`12345-PDN` John Taylor, `91299-PDN` John Doe, `8979-PDN` John Wayne) each get one new `'In Progress'` tracker fixture — collectively matching the Figma's own example dropdown ("John Taylor, ID 12345-PDN...", "John Doe, ID 91299-PDN...", "John Wayne, ID 8979-PDN...").

**5. Required fields (assumption, flagged, not a blocker): Coordination Type, Provider, Assigned To, and Due Date.**
Neither the ticket text nor the ACs name which fields are required. Every one of these four is populated in every Figma reference screenshot; Care Coordination Notes is free-form narrative (same reasoning as `Admission Reason`/`Contact Notes` elsewhere in this codebase, both optional); Creation Date is system-set (not user-editable, so not "required" in the validation sense); Completion Date is only meaningful once "Care Coordination Completed" is toggled on, so it's optional.

**6. `HospitalizedClientSearch.vue` is a new, self-contained component — not a reuse of `ClientSearchAutocomplete.vue`.**
`ClientSearchAutocomplete.vue` is tightly coupled to `hospitalizationTracker.store.js` (calls `store.searchClients`/`store.selectClient` directly) and its result label doesn't include a tracker id. The new component owns its own local debounce + dropdown (same UI shape) but calls `HospitalizationTrackerService.searchActiveHospitalizedClients` and renders "John Doe, ID 99999-PDN - Hospitalization Tracker ID # 023"-style results, per Figma.

**7. `createActivity`/`updateActivity` are store actions mutating the store's own local `MOCK_DATA`, not routed through `careCoordinationActivitiesService`.**
This store's own `fetchAll` already keeps its mock array locally (unlike `hospitalizationTracker.service.js`, which centralizes mocks in the service layer) — matching the sibling `hospitalization.store.js` (Dashboard) convention. Adding create/update here, rather than introducing service-layer mocked methods that don't match this store's own established pattern, is the smaller, more consistent change. `careCoordinationActivitiesService.getAll`'s already-commented-out real implementation is left untouched.

## Risks / Trade-offs

- **[Accepted]** Decision 5's required-field set is an assumption pending confirmation, same category of gap as the Contacts story.
- **[Accepted]** Decision 2 means opening a lot of activities in sequence triggers a `getTrackerById` call each time; acceptable at this mock scale (a handful of bucket rows), same as every other per-row mocked lookup in this codebase.

## Migration Plan

No data migration; additive to existing mocked stores/services. When real endpoints exist: replace `createActivity`/`updateActivity` and `searchActiveHospitalizedClients` bodies with real `client.*()` calls; component/store shapes don't need to change since they already treat the mocked calls as the source of truth.

## Open Questions

- Exact required-field set (Decision 5) is assumed, not confirmed — flagged for the requester, not a blocker.
- Whether the Hospitalization Tracker modal's own "Care Coordination" tab (Figma Scenario 1) should get real content is left for a future story, since no AC covers it.
