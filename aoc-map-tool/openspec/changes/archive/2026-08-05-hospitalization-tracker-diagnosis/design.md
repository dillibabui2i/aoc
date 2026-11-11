## Context

The "Diagnosis" tab exists structurally since AOC-4472 (part of the nested 9-item tab layout, rendered once a tracker has been "In Progress") but has no content — `HospitalizationTrackerModal.vue`'s `#diagnosis` slot renders `PlaceholderTab`. This story is the direct sibling of `hospitalization-tracker-contacts`: same tab-rearrangement precedent, same `UiTable`/`UiModal` reuse, same "one modal handles create/edit/read-only" convention, same required-field/red-border validation pattern. The only genuinely new piece is the "Was the Diagnosis Made?" toggle and its visibility rules over the table/button (AC3-AC8).

This story is frontend + mock data only, same as every prior story in this feature set; no backend work.

## Goals / Non-Goals

**Goals:**
- Give the Diagnosis tab real content: the toggle, a table of the tracker's diagnosis records, and a way to create/edit them, gated by the tracker's status exactly like Contacts.
- Reuse `UiTable`/`UiModal`/`ToggleSwitch` and the established store/service layering rather than introducing new patterns.
- Implement the toggle/table/button visibility matrix exactly as specified (AC5, AC7, AC8 — table visibility depends on toggle OR existing records; button depends on toggle AND status).

**Non-Goals:**
- No backend work.
- No diagnosis deletion — not in the ACs or Figma (create + edit only).
- No sorting/pagination wiring for the diagnosis table beyond what `UiTable` renders by default — same reasoning as Contacts (a tracker's diagnosis list is small).
- No permission-gating code inside the new components — the entire modal (Diagnosis tab included) is already behind `useHasPermission('Intensive Case Management')` from AOC-4402; AC1/AC2 need no new code, same as every prior story here.

## Decisions

**1. The toggle uses the existing generic `autoSaveField` action — no new store action.**
"Was the Diagnosis Made?" (`tracker.wasDiagnosisMade`) is a plain boolean field on the tracker, saved immediately on every change (AC4/AC6), which is exactly what `autoSaveField(key, value)` already does for every non-`logUpdates` field on the Current Hospitalization Info tab. `DiagnosisTab.vue` calls `store.autoSaveField('wasDiagnosisMade', value)` directly from its `ToggleSwitch`, avoiding a duplicate "toggle persistence" action.

**2. Table visibility is a local computed in `DiagnosisTab.vue`, not stored state.**
Per AC5/AC7/AC8: the table shows when `tracker.wasDiagnosisMade` is `true`, OR when it's `false` but `tracker.diagnoses.length > 0`. This is a pure derived computation (`showTable = wasDiagnosisMade || diagnoses.length > 0`) — no new store state needed, mirroring how `ContactsTab.vue`'s "Add Contact" visibility is already a local `store.tracker?.status === 'In Progress'` check rather than stored.

**3. One `DiagnosisDetailsModal.vue` handles create, edit, and read-only — not three components.**
Exact same shape as `ContactDetailsModal.vue`: the store holds `editingDiagnosis: Diagnosis | null` (`null` = creating) and `diagnosisModalOpen: boolean`. The modal reads `store.editingDiagnosis` to prefill its form (or starts blank) and reads `store.tracker.status !== 'In Progress'` to decide editable vs. read-only.

**4. Diagnoses live on `tracker.diagnoses` (an array), mutated via two new service methods and two new store actions — mirrors Contacts exactly.**
`HospitalizationTrackerService` gains `createDiagnosis(trackerId, payload)` (assigns a mock id, appends to `MOCK_TRACKERS_BY_ID[trackerId].diagnoses`) and `updateDiagnosis(trackerId, diagnosisId, payload)` (merges onto the matching entry). The store's `submitDiagnosis(fields)` action calls whichever is appropriate based on whether `editingDiagnosis` is set, then re-assigns `tracker.value` from the resolved result.

**5. Required fields are an explicit assumption: Diagnosis Code, Rank, System, Clinical Status, and Verification Status are required; Diagnosis Notes is optional; Creation Date and Author are system-set (never user-input, so not part of validation).**
All five required fields are visibly populated in the Figma reference; Diagnosis Notes is free-form narrative text, consistent with `Contact Notes`/`Admission Reason` elsewhere in this tracker (both optional). Creation Date and Author are shown as static/disabled-looking fields even in the editable Figma screenshot — set by the system (`new Date().toLocaleString('en-US')` and `CURRENT_USER`) at creation time and never edited afterward, same treatment as the tracker's own header `Creation Date`/`Author` fields (`editable: false`). Flagged here for confirmation, not a blocker (same category of assumption as Contacts' Decision 3).

**6. Diagnosis Code/Rank/System/Clinical Status/Verification Status options are new, illustrative mock lists — no backend enum exists.**
Added to `hospitalizationTrackerFields.js` alongside the existing illustrative lists (`CONTACT_RELATIONSHIP_OPTIONS`, `CLIENT_STATUS_KT_OPTIONS`), matching the Figma's example rows (R94.5 Transaminitis, I10: Essential (primary) hypertension, R00.0 Tachycardia; Rank: 1 - Primary; System: Cardiovascular; Clinical Status: Active; Verification Status: Suspected).

## Risks / Trade-offs

- **[Accepted]** Decision 5's required-field set is an assumption pending confirmation, same category of gap as the Contacts story.
- **[Accepted]** No delete action exists yet — same accepted gap as Contacts; a small, additive follow-up if needed later.

## Migration Plan

No data migration; additive to the existing mocked tracker/service/store. When real endpoints exist: replace `createDiagnosis`/`updateDiagnosis`'s bodies with real `client.*()` calls; the store's `submitDiagnosis` action already treats the service as the source of truth for the resolved diagnosis list, so no store/component changes would be needed beyond that.

## Open Questions

- Which fields are actually required (Decision 5) is assumed, not confirmed — flagged for the requester, not a blocker.
