## 1. Known assumptions (not blockers)

- [ ] 1.1 The referenced "spreadsheet attached to the story" was never actually attached — the six Sick Plan Type categories/descriptions (design.md Decision 4) are read off the Figma reference and partly invented (only "Hydration"'s description is legible). Flagged for requester confirmation.
- [ ] 1.2 "Completed by" is treated as system-set to the current user when "Completed?" is toggled on (design.md Decision 5), not a free-text/assignee field — flagged for confirmation.
- [ ] 1.3 Required fields: Sick Plan Type only; Notes optional (design.md Decision 6).

## 2. Config: Sick Plan type metadata

- [x] 2.1 Add `SICK_PLAN_TYPE_OPTIONS` to `hospitalizationTrackerFields.js` (Hydration, Pain, Seizure, Respiratory, Allergy, GI, each with a `label` combining the type and an illustrative description) — mock list, no backend enum/spreadsheet exists.

## 3. Service layer: client-scoped Sick Plan (mocked, swappable)

- [x] 3.1 Add `MOCK_SICK_PLANS_BY_CLIENT_ID` (object keyed by `clientId`) and `nextSickPlanItemSequence` counter.
- [x] 3.2 Implement `ensureSickPlanForClient(clientId)` (design.md Decision 3): no-op if a plan already exists; otherwise creates one with six predefined blank items (one per `SICK_PLAN_TYPE_OPTIONS` entry).
- [x] 3.3 Implement `resolveSickPlanOnTracker(tracker)`: sets `tracker.sickPlan = MOCK_SICK_PLANS_BY_CLIENT_ID[tracker.clientId] ?? null`, returns the tracker (design.md Decision 1).
- [x] 3.4 Call `ensureSickPlanForClient` + `resolveSickPlanOnTracker` from `createTracker` (after the tracker object is built, before storing/returning it).
- [x] 3.5 Call `resolveSickPlanOnTracker` from `getTrackerById` before returning, so a fresh read always reflects the latest client-scoped Sick Plan state.
- [x] 3.6 Seed `MOCK_SICK_PLANS_BY_CLIENT_ID['99999-PDN']` (client of fixture `#10087`) with six items matching the Figma reference (varying `completed`/`completedBy`/`notes` per row) and set `MOCK_TRACKERS_BY_ID['#10087'].sickPlan` via `resolveSickPlanOnTracker` for demo fidelity.
- [x] 3.7 Implement `createSickPlanItem(trackerId, payload)`: resolves the tracker, appends `{ id, ...payload }` to `MOCK_SICK_PLANS_BY_CLIENT_ID[tracker.clientId].items`, re-hydrates and resolves the full updated tracker.
- [x] 3.8 Implement `updateSickPlanItem(trackerId, itemId, payload)`: merges `payload` onto the matching item in place, re-hydrates and resolves the full updated tracker.
- [x] 3.9 Write/extend `hospitalizationTracker.service.spec.js`: `ensureSickPlanForClient` creates once and is idempotent afterward; `createTracker` for a client without a plan creates one with six items; `createTracker` for a client that already has a plan (e.g. via a second `createTracker` call for the same clientId) reuses it (no duplicate, same item ids); `createSickPlanItem`/`updateSickPlanItem` persist correctly and are visible via a subsequent `getTrackerById`; editing the Sick Plan via one tracker is visible from another tracker resolved for the same clientId (AC12).

## 4. Store: Sick Plan modal lifecycle and submit action

- [x] 4.1 Add `sickPlanModalOpen` (boolean) and `editingSickPlanItem` (object | null) state, mirroring Contacts/Diagnosis.
- [x] 4.2 Implement `openAddSickPlanItem()`, `openEditSickPlanItem(item)`, `closeSickPlanModal()`, mirroring the Contacts/Diagnosis actions exactly.
- [x] 4.3 Implement `submitSickPlanItem(fields)`: calls `createSickPlanItem` when `editingSickPlanItem` is `null`, otherwise `updateSickPlanItem(editingSickPlanItem.id, fields)`; assigns the resolved tracker back onto `tracker.value`; calls `closeSickPlanModal()` on success.
- [x] 4.4 Export the new state/actions from the store's returned surface.
- [x] 4.5 Write/extend `hospitalizationTracker.store.spec.js`: modal lifecycle transitions; `submitSickPlanItem` calling `createSickPlanItem` vs `updateSickPlanItem` correctly; `tracker.sickPlan.items` reflects the change after a successful submit.

## 5. `SickPlanDetailsModal.vue`

- [x] 5.1 Build the component: `UiModal` bound to `store.sickPlanModalOpen`, title "Sick Plan Details". Editable branch (Sick Plan Type select, "Completed?" toggle, read-only-styled Creation Date/Author, Sick Plan Notes textarea) shown when `store.tracker.status === 'In Progress'`; read-only branch (plain label/value pairs, only "Close") shown otherwise.
- [x] 5.2 Prefill from `store.editingSickPlanItem` when editing; blank defaults (Creation Date = now, Author = `CURRENT_USER`, Completed = false) when creating.
- [x] 5.3 Required-field validation (red border) for Sick Plan Type only (design.md Decision 6); blocks submission and keeps the modal open when missing.
- [x] 5.4 On submit, set `completedBy` to `CURRENT_USER` if "Completed?" is on, else `null` (design.md Decision 5), before calling `store.submitSickPlanItem({ ...form })`.
- [x] 5.5 Write `SickPlanDetailsModal.spec.js`: renders blank for create / prefilled for edit; validation blocks submission when Sick Plan Type is missing; valid submit calls `store.submitSickPlanItem` with the right payload, including `completedBy` derived from the toggle; read-only branch renders when the tracker isn't "In Progress" and has no Submit/editable inputs.

## 6. `SickPlanTab.vue`

- [x] 6.1 Build the component: "Add Sick Plan" button (`v-if="store.tracker?.status === 'In Progress'"`) wired to `store.openAddSickPlanItem()`; a `UiTable` of `store.tracker?.sickPlan?.items ?? []` (columns: Sick Plan Type, Completed?, Completed by, Notes, Action).
- [x] 6.2 Action column cell renders the `fa fa-calendar` icon wired to `store.openEditSickPlanItem(row)`, available regardless of tracker status.
- [x] 6.3 Mount `SickPlanDetailsModal` from this component.
- [x] 6.4 Write `SickPlanTab.spec.js`: table renders the client's Sick Plan items; "Add Sick Plan" hidden/shown per status; clicking the calendar icon calls `store.openEditSickPlanItem` with the right row.

## 7. Wire into `HospitalizationTrackerModal.vue`

- [x] 7.1 Replace the `#sickPlan` slot's `PlaceholderTab` with `SickPlanTab`.
- [x] 7.2 Write/extend `HospitalizationTrackerModal.spec.js`: opening the Sick Plan tab renders `SickPlanTab` instead of the old placeholder text.

## 8. Verification against acceptance criteria

- [x] 8.1 AC1/AC2 — unchanged from AOC-4402 (`useHasPermission` stub); no new code, confirmed existing coverage still passes.
- [x] 8.2 AC3/AC4 — covered by `hospitalizationTracker.service.spec.js`'s `ensureSickPlanForClient`/`createTracker` case and `SickPlanDetailsModal.spec.js`'s read-only-on-Open case.
- [x] 8.3 AC5/AC12 — covered by `hospitalizationTracker.service.spec.js`'s "reuse existing plan, no duplicate" case.
- [x] 8.4 AC6 — covered by `SickPlanTab.spec.js`/`SickPlanDetailsModal.spec.js`'s Open-status read-only cases.
- [x] 8.5 AC7/AC8/AC9 — covered by `SickPlanDetailsModal.spec.js`'s In-Progress-editable and other-status-read-only cases (parametrized across all five non-In-Progress statuses).
- [x] 8.6 AC10/AC11 — covered by `hospitalizationTracker.service.spec.js`'s persistence assertions and `SickPlanTab.spec.js`'s rendering of previously saved values.
- [x] 8.7 Ran `npm run test` — full suite green, no regressions.

## 9. Cleanup

- [x] 9.1 Run `eslint --fix` on every new/changed file.
- [ ] 9.2 Manually verify in a running dev build the full auto-creation → In-Progress-edit → read-only-after-status-change path, and that a second tracker for the same client reuses the same Sick Plan, against the Figma reference — component specs don't substitute for a real visual check.
