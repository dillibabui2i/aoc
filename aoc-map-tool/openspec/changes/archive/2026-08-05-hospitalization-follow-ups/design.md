## Context

`CommunicationBucketSection.vue` renders `CommunicationFilters` + a `UiTable` fed by `communication.store.js`'s `fetchAll` (100% mock — the real `CommunicationService.getAll` call is commented out, same dead-code shape as `careCoordinationActivities.service.js`/`hospitalization.store.js`). Its row shape (`{ id, patient_name, patient_id, tracker_id, level, hospitalized, status, facility, assignee, due_date }`) already matches the Figma-described "Hospitalization Follow-ups bucket" columns exactly — no schema change needed there, just a real data source. All three of its Action-column icons (calendar/eye/sticky-note) are inert, and there's no "+" icon at all.

The Hospitalization Tracker modal's `#followUp` tab slot still renders `PlaceholderTab`.

Every prior tab-content story in this epic (Contacts, Diagnosis, Sick Plan, Education) used a "one modal, closes on every successful submit" pattern. Follow-up is different: per the Figma reference, the *same* modal instance stays open across multiple outcome transitions (New → Open → Scheduled → Completed, for example), only closing on an explicit ✕ or "Close". This is the central new interaction pattern this story introduces.

This story is frontend + mock data only; no backend work.

## Goals / Non-Goals

**Goals:**
- Wire `CommunicationBucketSection.vue`'s "+" and calendar icons to a real Follow-up modal, gated by permission.
- Implement the full New → Open → {Scheduled | Completed | Attempted | Canceled} workflow, including the auto-chained "next follow-up" record for Completed/Attempted when requested.
- Give the Hospitalization Tracker's own "Follow-up" tab real content, sharing the exact same modal/store as the bucket.
- Reuse the existing "search hospitalized clients" pattern from `hospitalization-follow-ups`' sibling story (Care Coordination Activities), broadened to "any non-Closed hospitalization" instead of "In Progress only".

**Non-Goals:**
- No backend work.
- No actual automated/scheduled generation of recurring follow-ups for High Utilizers outside of a user's own Completed/Attempted submission (the ticket's "Automated recurring follow-up generation" is described at the epic level; this story's concrete, testable mechanism is the manual "Next Follow-up Due" toggle at Completed/Attempted time, per AC11-17/34-36 — a real cron-style auto-generator has no frontend-only equivalent and isn't asked for by any AC).
- No Follow-up deletion — not in the ACs or Figma.
- No changes to the Communications bucket's filters/columns beyond swapping its data source — the row shape was already correct.

## Decisions

**1. The Follow-up modal is one long-lived session per open, not a create-then-close-then-reopen-to-edit flow.**
`followUp.store.js` holds `modalOpen`, `pendingClient` (search selection, before the first save), and `followUp` (the persisted record once it exists). Every successful action (`submitCreate`, `submitOutcome`) updates `followUp.value` in place and leaves `modalOpen` true; `FollowUpModal.vue` re-renders whichever stage the follow-up's current `status` implies. Only `closeModal()` (bound to ✕ and the read-only "Close" button) resets state and hides the modal. This mirrors the Figma sequences exactly (e.g. "Schedule a follow-up": four screenshots of the *same* modal, badge progressing New → Open → Scheduled).

**2. Follow-up records are tracker-scoped in storage (`tracker.followUps`), read cross-tracker for the bucket.**
Like Contacts/Diagnosis/Education, a follow-up belongs to the hospitalization tracker it was created against — `HospitalizationTrackerService.createFollowUp`/`updateFollowUp` mutate `MOCK_TRACKERS_BY_ID[trackerId].followUps` and mirror `createContact`/`updateContact` exactly. The Communications bucket needs a cross-tracker read, so a new `getFollowUpsBucket()` scans every tracker with status other than "Closed", flattens their `followUps`, excludes any with status "Completed" or "Attempted" (per the Figma annotation "All follow-ups must be displayed, except 'Completed' and 'Attempted'"), and maps each to the bucket's existing row shape. This is the same "narrow, service-owned aggregation" approach used for `searchActiveHospitalizedClients` in the Care Coordination story, rather than introducing a second, disconnected mock dataset.

**3. Editability is a function of the follow-up's own status, not the tracker's.**
Per Requirement 4 of the ticket: New/Open/Scheduled are editable; Completed/Attempted/Canceled are read-only — regardless of what the *tracker's* status happens to be. This is simpler than Contacts/Diagnosis/Sick Plan/Education (which all key off the tracker's status) and is implemented as a single `isReadOnly = ['Completed', 'Attempted', 'Canceled'].includes(followUp.status)` check inside `FollowUpModal.vue`.

**4. Opening an existing follow-up always re-fetches the tracker, whether triggered from the bucket or the tab.**
`followUp.store.js`'s `openExisting({ trackerId, followUpId })` calls `HospitalizationTrackerService.getTrackerById(trackerId)` and finds the matching entry in `tracker.followUps`, exactly like Care Coordination Activities' `openActivity`. One code path serves both entry points (the bucket only ever had a flattened summary row; the tab already has the full tracker loaded, but re-fetching anyway keeps the logic single-sourced and guarantees freshness) — the minor redundant fetch for the tab case is an accepted, deliberate simplification.

**5. `hospitalizationTracker.store.js` gets one small addition — `refreshTracker()` — so `FollowUpTab.vue` reflects changes made through the separate `followUp.store.js`.**
`followUp.store.js`'s mutations happen against `MOCK_TRACKERS_BY_ID` directly via the service, independent of whatever `hospitalizationTracker.store.js.tracker` currently holds (a snapshot from an earlier `getTrackerById` call). Rather than merging the two stores or having `followUp.store.js` reach into `hospitalizationTracker.store.js` internals, a minimal `refreshTracker()` action (re-fetch by the currently-loaded `trackerId`, reassign `tracker.value`, touch nothing else) is added to `hospitalizationTracker.store.js`; `FollowUpTab.vue` passes it as the `onSaved` callback when opening a follow-up, so its table updates immediately after a submit without reopening the whole "Hospitalization Record" modal.

**6. The Communications bucket's own `fetchAll` now calls `HospitalizationTrackerService.getFollowUpsBucket()` instead of returning its local static `MOCK_DATA`.**
Unlike Care Coordination Activities (which kept mock data local to its own Dashboard store, since its records were never meant to be cross-referenced against `hospitalizationTracker.service.js`'s trackers), Follow-ups are fundamentally tracker-scoped data that also needs bucket-level visibility — so the bucket becomes a thin read-through to the tracker service's aggregation, and its own `MOCK_DATA`/`MOCK_META` constants are no longer used for `list`/`total` (removed as dead code once the swap is in place).

**7. Outcome dropdown options depend on the follow-up's current status: `Open` offers all four (Scheduled/Completed/Attempted/Canceled); `Scheduled` offers only the three finalizing options (Completed/Attempted/Canceled) — re-selecting "Scheduled" from an already-Scheduled follow-up isn't offered.**
Not explicitly stated in the ACs, but implied by every Figma sequence: once a follow-up is Scheduled, the next dropdown never shows "Scheduled" as an option again — re-submitting the same schedule isn't a modeled scenario. Flagged as an explicit, reasonable interpretation.

**8. Required fields per outcome (assumption, flagged): Scheduled needs Follow-up Date and Follow-up Assigned To. Completed needs Status, Follow-Ups/Updates, and Needs (Additional Notes optional). Attempted needs Attempt Notes only. Both Completed and Attempted, when "Next Follow-up Due" is on, additionally require Next Follow-up Due Date and Next Follow-up Assigned To. Canceled has no fields.**
Read directly off which fields the Figma reference shows with a `*` or as clearly populated in every example screenshot; the fields shown without emphasis (Additional Notes) are treated as optional, consistent with every other "Notes" field in this codebase.

**9. Next Follow-up Due Date defaults to the *current* follow-up's own scheduled date/time + 1 week; if the follow-up was never Scheduled (e.g. completed directly from Open), it falls back to "now" + 1 week.**
The ticket says "Last Follow-up Date/Time + 1 week" — "last follow-up date/time" is read as the record's own `followUpDate` (set when it was Scheduled), not the completion/attempt timestamp being recorded right now. The fallback for a never-Scheduled record is an explicit, flagged gap the ACs don't cover.

**10. Completion Date/Completed By and Attempt Date/Attempted By are system-set (now / `CURRENT_USER`), computed the moment the user selects that outcome — not editable, matching the Figma's grayed-out treatment of these fields.**
Same treatment as Creation Date/Author elsewhere in this tracker (Diagnosis, Sick Plan).

**11. The informational 30/60/90-day message is computed from the *selected tracker's* Level and Admission Date at patient-selection time (New/Open stage only) — it is not re-shown or recomputed once the follow-up has been saved and reopened later.**
The ACs describe it strictly "during follow-up creation"; no AC asks for it to reappear on a later, already-Open/Scheduled follow-up. Implemented as a computed value derived from `pendingClient` (only populated pre-first-save) — naturally disappears once the record exists, without extra state.

## Risks / Trade-offs

- **[Accepted]** Decisions 7-9 are interpretations of gaps the ACs leave open; flagged, not blockers.
- **[Accepted]** Decision 6 changes `communication.store.js`'s behavior (previously fully self-contained mock data) to depend on `hospitalizationTracker.service.js` — an intentional coupling, since Follow-ups are the reason this bucket exists; its own `MOCK_DATA` becomes unused once the swap lands.

## Migration Plan

No data migration; additive to the existing mocked tracker/service/store, plus one narrow behavior change to `communication.store.js`'s data source (Decision 6). When real endpoints exist: `getFollowUpsBucket`/`createFollowUp`/`updateFollowUp`/`searchHospitalizedClientsForFollowUp` bodies get replaced with real `client.*()` calls; no store/component shape changes needed beyond that.

## Open Questions

- Exact required-field sets (Decision 8) and the Scheduled-dropdown-options rule (Decision 7) are assumptions pending confirmation.
- Whether "automated recurring follow-up generation" (epic-level rationale) implies a real backend scheduler is explicitly out of scope here (Non-Goals) — flagged for a future, backend-inclusive story if needed.
