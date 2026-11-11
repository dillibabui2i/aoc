## Why

The "Sick Plan" tab currently renders `PlaceholderTab.vue`'s empty state. ICM users need Sick Plan documentation available from the moment a hospitalization tracker is first saved ("Open"), editable only while the tracker is "In Progress", and preserved read-only afterward — but unlike Contacts/Diagnosis, the Sick Plan itself belongs to the **client**, not the tracker: it's created once per client and reused across every subsequent hospitalization for that same client (AC5/AC12), giving ICM users a durable, auditable history rather than a fresh blank plan every time.

## What Changes

- When a tracker is first saved with status "Open" (i.e. at `createTracker` time — the only point a tracker's status is ever "Open" for the first time), the system ensures the client has a Sick Plan: if none exists yet, a blank one is created with six predefined Sick Plan Type rows (Hydration, Pain, Seizure, Respiratory, Allergy, GI — the categories shown in the Figma reference; exact default text beyond the type name is an explicit assumption, see design.md Decision 4, since no spreadsheet was attached to this story); if one already exists for that client, it's reused as-is — no duplicate is ever created.
- Replace the "Sick Plan" tab's `PlaceholderTab` with a real `SickPlanTab.vue`: a table of the client's Sick Plan items (Sick Plan Type, Completed?, Completed by, Notes, Action) and an "Add Sick Plan" button, visible only while the tracker is "In Progress".
- Add a `SickPlanDetailsModal.vue` (mirroring `ContactDetailsModal.vue`/`DiagnosisDetailsModal.vue`'s one-modal, three-mode pattern) for both creating a new Sick Plan item and editing an existing one: Sick Plan Type (select) and a "Completed?" toggle are required inputs; Sick Plan Notes is optional; Creation Date and Author are system-set; "Completed by" auto-populates to the current user when "Completed?" is toggled on.
- Editability follows the tracker's current status exactly like every other tab in this modal: editable only while "In Progress" (AC7-AC9), read-only otherwise (AC4/AC6/AC10), regardless of which specific non-"In Progress" status it is.
- Permission gating (AC1/AC2) requires no new code — the entire "Hospitalization Record" modal is already gated behind `useHasPermission('Intensive Case Management')` from AOC-4402.

## Capabilities

### Modified Capabilities
- `hospitalization-tracker-creation`: the "Placeholder tabs render empty" scenario no longer covers "Sick Plan"; new requirements are added for client-scoped Sick Plan creation/reuse, item creation/editing, and status-driven editability.

### New Capabilities
(none — extends the same tracker capability)

## Impact

- `resources/js/vue/modules/vbc/components/HospitalizationTracker/HospitalizationTrackerModal.vue` — the `#sickPlan` slot renders `SickPlanTab` instead of `PlaceholderTab`.
- New: `SickPlanTab.vue` — "Add Sick Plan" button (status-gated) + `UiTable` of the resolved Sick Plan's items, with an Action-column calendar icon per row opening the edit modal.
- New: `SickPlanDetailsModal.vue` — one modal for create/edit/read-only, switching on `store.tracker.status !== 'In Progress'`.
- `resources/js/vue/modules/vbc/store/hospitalizationTracker.store.js` — new state (`sickPlanModalOpen`, `editingSickPlanItem`) and actions (`openAddSickPlanItem`, `openEditSickPlanItem`, `closeSickPlanModal`, `submitSickPlanItem`), mirroring the Contacts/Diagnosis actions.
- `resources/js/vue/modules/vbc/services/hospitalizationTracker.service.js` — a new client-keyed mock store (`MOCK_SICK_PLANS_BY_CLIENT_ID`), an `ensureSickPlanForClient(clientId)` helper invoked from `createTracker`, and new `createSickPlanItem(trackerId, payload)`/`updateSickPlanItem(trackerId, itemId, payload)` methods; every tracker resolved by `getTrackerById`/`createTracker` gets a fresh `sickPlan` property hydrated from the client-keyed store (design.md Decision 1) so components keep reading `tracker.sickPlan` the same way they read `tracker.contacts`/`tracker.diagnoses`, without the Sick Plan actually living on the tracker object in storage.
- `resources/js/vue/modules/vbc/config/hospitalizationTrackerFields.js` — new `SICK_PLAN_TYPE_OPTIONS` mock list (no backend enum/spreadsheet values were available — illustrative, flagged assumption).
- No backend (`app/VBC/**`, routes) changes — frontend + mock data only, consistent with every prior story in this tracker feature set.
