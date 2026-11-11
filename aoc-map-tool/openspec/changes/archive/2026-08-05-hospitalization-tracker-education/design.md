## Context

The "Education" tab exists structurally (part of the nested 9-item tab layout) but has no content — `HospitalizationTrackerModal.vue`'s `#education` slot renders `PlaceholderTab`. This story is the closest sibling to `hospitalization-tracker-diagnosis`: a toggle gating a table + "Add" button, one modal for create/edit/read-only, same required-field/red-border validation pattern. Unlike Diagnosis, the Education records are simpler (no system-set Creation Date/Author fields appear in the Figma reference), and the toggle's visibility rule is simpler too (no "keep table visible if records already exist while toggle is off" nuance — the ticket doesn't ask for it).

This story is frontend + mock data only, same as every prior story in this feature set; no backend work.

## Goals / Non-Goals

**Goals:**
- Give the Education tab real content: the toggle, a table of the tracker's Education records, and a way to create/edit them, gated by the tracker's status exactly like Contacts/Diagnosis.
- Reuse `UiTable`/`UiModal`/`ToggleSwitch` and the established store/service layering rather than introducing new patterns.
- Keep the toggle/table/button visibility simple and literal to the ACs: table + button shown only when the toggle is ON (no "existing records keep it visible" carve-out, unlike Diagnosis — not asked for here).

**Non-Goals:**
- No backend work.
- No Education record deletion — not in the ACs or Figma.
- No sorting/pagination wiring beyond `UiTable`'s defaults — same reasoning as every prior tab-table in this tracker.
- No permission-gating code inside the new components — the entire modal is already behind `useHasPermission('Intensive Case Management')` from AOC-4402; AC15 needs no new code.

## Decisions

**1. The toggle uses the existing generic `autoSaveField` action — no new store action, mirroring the Diagnosis story's Decision 1 exactly.**
"Staff / Family Training/Education required?" (`tracker.staffFamilyTrainingRequired`) is a plain boolean on the tracker, saved immediately on every change, which is exactly what `autoSaveField(key, value)` already does.

**2. Editability is `status !== 'In Progress'`, generalized from the ticket's "Discharged" example to match every sibling tab's rule.**
The ticket's Requirements/AC11-13 only call out "Discharged" explicitly as the read-only trigger, but its own "Starting State" section requires "In Progress" for the tab to be usable at all, and the story's rationale explicitly asks for "consistency with hospitalization activity logging standards" — i.e. consistency with Contacts/Diagnosis/Sick Plan, all of which use `status !== 'In Progress'` (not "status === 'Discharged'" specifically) as the read-only rule. Treating "Discharged" as the ticket's illustrative example rather than an exhaustive rule avoids an inconsistent one-off (e.g. Education would otherwise stay "editable" while Closed or Completed, unlike every other tab). Flagged as an explicit interpretation, not a blocker.

**3. Table and "Add Education" visibility: both shown only while the toggle is ON — no exception for existing records.**
Unlike Diagnosis's AC7/AC8 (table stays visible with existing records even when its toggle is off), this ticket's AC3 only specifies "toggle enabled → table + button shown"; there's no AC asking for the table to remain visible when the toggle is off. Implemented literally: `showTable = showAddButton = staffFamilyTrainingRequired && status === 'In Progress'`... except AC11 requires **existing** Education data to still display read-only after Discharge even if the toggle happens to be off at that point — so display visibility is `wasEducationRequired` alone (independent of status), while only the "Add" button is additionally gated by status. This mirrors Diagnosis's pattern for the *button* (status-gated) while keeping the *table* visibility rule intentionally simpler than Diagnosis's (no "or has existing records" carve-out was requested, but AC11's persistence requirement means the table can't disappear the instant status leaves "In Progress" either — it still depends on the toggle's last saved value, which persists).

**4. One `EducationDetailsModal.vue` handles create, edit, and read-only — not three components, mirroring `DiagnosisDetailsModal.vue` exactly.**
The store holds `editingEducationRecord: Record | null` (`null` = creating) and `educationModalOpen: boolean`. No Creation Date/Author fields are added — neither provided modal screenshot (editable or read-only) shows them, unlike Diagnosis/Sick Plan.

**5. Education records live on `tracker.educationRecords` (an array), mutated via two new service methods and two new store actions — mirrors Contacts/Diagnosis exactly.**
`HospitalizationTrackerService` gains `createEducationRecord(trackerId, payload)`/`updateEducationRecord(trackerId, recordId, payload)`; the store's `submitEducationRecord(fields)` action calls whichever is appropriate based on whether `editingEducationRecord` is set.

**6. Required fields are an explicit assumption: Training Type and Education Source are required; Education Notes is optional; "Training Completed" always has a value (defaults false).**
Both provided modal screenshots show Training Type and Education Source as populated selects; Education Notes is free-form narrative text, consistent with every other "Notes" field elsewhere in this tracker being optional.

**7. Training Type / Education Source options are new, illustrative mock lists — no backend enum exists.**
`TRAINING_TYPE_OPTIONS` (Emergency Trach Replacement, Head Injury, Respiratory Education for Clinical Staff, Seasonal Illness Guide — the four rows visible in the Figma table) and `EDUCATION_SOURCE_OPTIONS` (AOC Clinical Excellence, plus a couple of illustrative additions since only one is legible in the reference) are added to `hospitalizationTrackerFields.js`.

## Risks / Trade-offs

- **[Accepted]** Decision 2's generalization of "Discharged" to "any non-In-Progress status" is an interpretation choice favoring consistency across tabs; flagged, not a blocker.
- **[Accepted]** Decision 6's required-field set is an assumption pending confirmation, same category of gap as every prior story here.

## Migration Plan

No data migration; additive to the existing mocked tracker/service/store. When real endpoints exist: replace `createEducationRecord`/`updateEducationRecord`'s bodies with real `client.*()` calls; no store/component changes needed beyond that.

## Open Questions

- Whether read-only should trigger specifically on "Discharged" (as literally written) or on any non-"In Progress" status (Decision 2, the interpretation implemented here) is assumed, not confirmed.
- Which fields are actually required (Decision 6) is assumed, not confirmed.
