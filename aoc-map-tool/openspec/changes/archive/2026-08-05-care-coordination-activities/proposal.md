## Why

ICM users need to document care coordination actions (AngelLink referrals, DME requests, transportation, etc.) tied to an active hospitalization tracker. `CareCoordinationActivitiesBucketSection.vue` already lists these activities (mocked read-only data), but has no way to create or edit one: there's no "+" button, and its three Action-column icons (calendar/eye/sticky-note) are all inert `<a href="javascript:void(0)">` links. `careCoordinationActivities.store.js` only supports listing (`fetchAll`); there's no create/update action anywhere.

## What Changes

- Add a "+" icon to the Care Coordination bucket's toolbar (gated by `useHasPermission('Intensive Case Management')`, hidden entirely without it) that opens a new "Care Coordination" modal in create mode.
- The create flow is two-stage, per the Figma reference: stage 1 shows only a "Hospitalized Client Name" autocomplete (listing only clients with a hospitalization tracker whose status is "In Progress" — the only status this story allows new activities against); submitting stage 1 locks in the client and reveals stage 2's coordination fields (Coordination Type, Provider, Assigned To, Creation Date, Due Date, Completion Date, "Care Coordination Completed" toggle, Care Coordination Notes).
- The bucket's calendar icon (also gated by the same permission) opens the same modal for an existing activity: editable if the linked tracker's status is "In Progress" (client/header fields locked, coordination fields editable), or fully read-only otherwise (matching the established `ContactDetailsModal.vue` one-modal, three-mode pattern from the Contacts story).
- Required-field validation (red border) blocks submission when incomplete, for both create and edit.
- On successful create/edit, the modal closes and the bucket refreshes (`fetchAll()`), showing the new/updated activity.
- No backend (`app/VBC/**`, routes) changes — frontend + mock data only, consistent with every prior story in the hospitalization tracker feature set this reuses conventions from.

## Capabilities

### New Capabilities
- `care-coordination-activities`: create/edit care coordination activities from the bucket, gated by permission and by the linked tracker's status.

### Modified Capabilities
(none)

## Impact

- `resources/js/vue/modules/vbc/sections/Dashboard/CareCoordinationActivitiesBucketSection.vue` — adds the "+" create icon and wires the calendar icon; mounts the new modal.
- New: `resources/js/vue/modules/vbc/components/CareCoordinationActivity/CareCoordinationModal.vue`, `HospitalizedClientSearch.vue`.
- New: `resources/js/vue/modules/vbc/config/careCoordinationActivityFields.js` (Coordination Type / Provider option lists; reuses the existing `ASSIGNED_TO_OPTIONS` from `hospitalizationTrackerFields.js`).
- `resources/js/vue/modules/vbc/store/Dashboard/careCoordinationActivities.store.js` — new modal-lifecycle state/actions and mocked `createActivity`/`updateActivity`, following this store's existing "MOCK_DATA lives in the store" convention; each mock row gains a `hospitalization_tracker_id` linking it to a real mocked tracker, so edit/read-only routing has something to check.
- `resources/js/vue/modules/vbc/services/hospitalizationTracker.service.js` — new mocked `searchActiveHospitalizedClients(query)`, plus three new "In Progress" tracker fixtures (for clients `12345-PDN`, `91299-PDN`, `8979-PDN`, which currently have no tracker at all) so the new search has realistic, non-conflicting data to return (the existing `99999-PDN` / `#10087` fixture is left untouched, since other stories' tests depend on its current default status).
