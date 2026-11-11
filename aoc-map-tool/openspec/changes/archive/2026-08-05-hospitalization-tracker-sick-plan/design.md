## Context

The "Sick Plan" tab exists structurally (flat and nested tab layouts both include it) but has no content — `HospitalizationTrackerModal.vue`'s `#sickPlan` slot renders `PlaceholderTab`. Every prior "tab content" story in this feature set (Contacts, Diagnosis) stored its records directly on the tracker object (`tracker.contacts`, `tracker.diagnoses`), because those records are genuinely tracker-scoped. Sick Plan is different: per AC5/AC12, it belongs to the **client** and must be reused, unchanged, across every hospitalization tracker that client ever has — creating a second Sick Plan for a client's second hospitalization would be a bug, not a feature.

No spreadsheet was actually attached to this story (the ticket references one but none was provided) — the six Sick Plan Type categories (Hydration, Pain, Seizure, Respiratory, Allergy, GI) and their descriptions are read directly off the Figma reference table/dropdown instead.

This story is frontend + mock data only, same as every prior story in this feature set; no backend work.

## Goals / Non-Goals

**Goals:**
- Auto-create a client-scoped Sick Plan exactly once, at the point a tracker is first saved as "Open" (`createTracker`), reusing it for every later tracker of the same client.
- Give the Sick Plan tab real content (table + Add button), gated by the tracker's status exactly like Contacts/Diagnosis.
- Keep `tracker.sickPlan` readable the same way `tracker.contacts`/`tracker.diagnoses` are, even though the underlying storage is client-keyed, so `SickPlanTab.vue`/`SickPlanDetailsModal.vue` don't need any different data-access pattern than their siblings.

**Non-Goals:**
- No backend work, no new permission system.
- No deletion of Sick Plan items — not in the ACs or Figma.
- No UI for viewing a client's Sick Plan history independent of a hospitalization tracker (out of scope; the Sick Plan is only ever viewed through a tracker's Sick Plan tab in this story).
- No general "predefined values" beyond the six type categories — the exact per-type default notes/behavior the ticket's (missing) spreadsheet would have specified is not implemented; flagged as an explicit gap (Decision 4).

## Decisions

**1. `tracker.sickPlan` is hydrated fresh from a client-keyed mock store on every tracker read/create — it is never itself the source of truth.**
A new `MOCK_SICK_PLANS_BY_CLIENT_ID` object (keyed by `clientId`) is the actual storage. A helper, `resolveSickPlanOnTracker(tracker)`, sets `tracker.sickPlan = MOCK_SICK_PLANS_BY_CLIENT_ID[tracker.clientId] ?? null` and is called by `getTrackerById`, `createTracker` (after ensuring the plan exists), and the new `createSickPlanItem`/`updateSickPlanItem` methods (after mutating the client-keyed store). This keeps every component-facing read (`store.tracker?.sickPlan?.items`) identical in shape to `tracker.contacts`/`tracker.diagnoses`, while guaranteeing AC12: two trackers for the same client always resolve to the *same* `MOCK_SICK_PLANS_BY_CLIENT_ID[clientId]` object, so an edit via one tracker's Sick Plan tab is visible from any other tracker for that client too (this is the intended behavior — one Sick Plan per client — not a bug).

**2. Sick Plan creation happens only in `createTracker`, not in every `updateOutcome` transition.**
A tracker's status is only ever `'Open'` for the first time at creation — no existing transition in `updateOutcome`/`submitOutcome`/`submitDischarge`/etc. ever sets status back to `'Open'`. So `createTracker` is the single, correct hook for "a hospitalization tracker is saved with status Open" (AC3/AC5/AC12); no other call site needs to know about Sick Plans at all.

**3. `ensureSickPlanForClient(clientId)` creates the plan only if missing, with six predefined blank items.**
```js
function ensureSickPlanForClient(clientId) {
    if (MOCK_SICK_PLANS_BY_CLIENT_ID[clientId]) return
    MOCK_SICK_PLANS_BY_CLIENT_ID[clientId] = {
        clientId,
        items: SICK_PLAN_TYPE_OPTIONS.map((opt) => ({
            id: `sickplan-${nextSickPlanItemSequence++}`,
            sickPlanType: opt.value,
            completed: false,
            completedBy: null,
            notes: '',
            creationDate: new Date().toLocaleString('en-US'),
            author: MOCK_CURRENT_USER,
        })),
    }
}
```
This satisfies AC3 (blank plan, six predefined type rows) and AC12 (no duplicate — the existence check is the guard) in one place.

**4. The six Sick Plan Type options and their descriptive labels are an explicit, illustrative assumption — no spreadsheet was available.**
`SICK_PLAN_TYPE_OPTIONS` (added to `hospitalizationTrackerFields.js`) uses `{ value: 'Hydration', label: 'Hydration | Encourage increased fluid intake' }`-style entries for all six categories visible in the Figma table (Hydration, Pain, Seizure, Respiratory, Allergy, GI); only "Hydration"'s description is legible in the reference, the other five descriptions are invented placeholders. Flagged for requester confirmation, not a blocker — same category of gap as every prior story's "no backend enum exists yet" mock lists.

**5. "Completed by" is system-set, not a user-editable field, mirroring "Author".**
The Figma table shows names ("Emma Watson", "Axel Hawker") in "Completed by" that correlate with whichever item is "Completed? Yes", but no field for it appears in either provided modal screenshot. `SickPlanDetailsModal.vue` sets `completedBy = CURRENT_USER` whenever the user toggles "Completed?" on (and clears it back to `null` if toggled off before saving), the same treatment as `Author`/`Creation Date` elsewhere in this tracker — avoiding a free-text "who completed this" field that isn't shown in any reference screenshot.

**6. Required fields (assumption, flagged): Sick Plan Type only; Sick Plan Notes is optional; "Completed?" always has a value (defaults false) so it's never "missing".**
Both provided modal screenshots show Sick Plan Type as a populated select and Notes as free text; nothing suggests Notes is mandatory (consistent with every other "Notes" field elsewhere in this tracker being optional).

## Risks / Trade-offs

- **[Accepted]** Decisions 4-6 are explicit assumptions pending confirmation (missing spreadsheet, unclear "Completed by" field), same category of gap as every prior story here.
- **[Accepted]** Decision 1's "shared object across trackers" is intentional per AC12, but means Sick Plan edits are visible across every tracker for a client — flagged here so it's understood as intended, not a leak.

## Migration Plan

No data migration; additive to the existing mocked tracker/service/store. When real endpoints exist: `ensureSickPlanForClient`/`createSickPlanItem`/`updateSickPlanItem` bodies get replaced with real `client.*()` calls keyed by clientId; `resolveSickPlanOnTracker`'s hydration step would instead be whatever the real tracker-detail endpoint already returns (or a follow-up request keyed by clientId).

## Open Questions

- The actual "predefined values" spreadsheet referenced by the ticket was never attached — Decision 4's six type/description pairs are a best-effort placeholder pending the real source.
- Whether "Completed by" should instead be a free-text/assignee field rather than system-set-to-current-user (Decision 5) is assumed, not confirmed.
