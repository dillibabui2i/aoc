## Context

The "Contacts" tab exists structurally since AOC-4472 (part of the nested 9-item tab layout, rendered once a tracker has been "In Progress") but has no content — `HospitalizationTrackerModal.vue`'s `#contacts` slot renders `PlaceholderTab`. `@angels-of-care/ui` provides `UiTable` (already used for the real, paginated "All Hospitalizations" list) and `UiModal` (already used twice: the main tracker modal and `ActiveTrackerConflictModal.vue`, the precedent for "a second modal instance living alongside the main one"). The required-field/red-border validation pattern (component-local `reactive` errors object, checked before calling a store action) was established in AOC-4472's `DischargeFields.vue` and siblings and is reused as-is here.

This story is frontend + mock data only, same as every prior story in this feature set; no backend work.

## Goals / Non-Goals

**Goals:**
- Give the Contacts tab real content: a table of the tracker's contacts and a way to create/edit them, gated by the tracker's status exactly like every other editable surface in this modal.
- Reuse `UiTable`/`UiModal` and the established store/service layering rather than introducing new patterns.
- Make the read-only rendering (once the tracker isn't "In Progress") match the Figma reference's distinct, simpler layout — not just disabled inputs.

**Non-Goals:**
- No backend work.
- No contact deletion — not in the ACs or Figma (create + edit only).
- No sorting/pagination wiring for the Contacts table beyond what `UiTable` renders by default — a tracker's contact list is small (a handful of entries), unlike the real, server-paginated "All Hospitalizations" table.
- No permission-gating code inside the new components — the entire modal (Contacts tab included) is already behind `useHasPermission('Intensive Case Management')` from AOC-4402; AC1/AC2 need no new code, same as every prior story here.

## Decisions

**1. One `ContactDetailsModal.vue` handles create, edit, and read-only — not three components.**
The store holds `editingContact: Contact | null` (`null` = creating a new contact) and `contactModalOpen: boolean`, mirroring the existing `activeTrackerConflict`/`wizardStep` "the store owns modal lifecycle state" convention. The modal reads `store.editingContact` to prefill its form (or starts blank) and reads `store.tracker.status !== 'In Progress'` to decide whether to render the editable form or the Figma's separate read-only layout (Decision 4). One component avoids duplicating the field list three times.

**2. Contacts live on `tracker.contacts` (an array), mutated via two new service methods and two new store actions, not merged through the existing generic `updateOutcome`/`fields` mechanism.**
`updateOutcome`'s `fields` merge (AOC-4472) is for scalar tracker properties; a contacts *list* needs create/update-by-id semantics instead. `HospitalizationTrackerService` gains `createContact(trackerId, payload)` (assigns a mock id, appends to `MOCK_TRACKERS_BY_ID[trackerId].contacts`) and `updateContact(trackerId, contactId, payload)` (merges onto the matching entry). The store's `submitContact(fields)` action calls whichever is appropriate based on whether `editingContact` is set, then re-assigns `tracker.value.contacts` from the resolved result so the table's `v-for` reacts immediately — no "refetch" step exists or is needed, since this is the same in-memory mock object the table already reads from (AC7/AC10's "the Contacts tab should refresh" is satisfied by ordinary Vue reactivity, not a network round-trip).

**3. Required fields are an explicit assumption: Contact Name, Relationship, Phone Number, Email, Contact Date, and Contact Received By are required; Contact Notes is optional.**
None of AC6/AC9's "required fields" are named. All six non-notes fields are visibly populated in both Figma states, and Contact Notes is free-form narrative text — consistent with how `Admission Reason` (a similarly free-form field elsewhere in this tracker) is not required either. Flagged here for confirmation, not a blocker (same category of assumption as AOC-4472's "Discharge Notes" note-text resolution).

**4. Read-only mode is a second, separate template branch — not the same form with every input disabled.**
The Figma's "Read Only" frame is visually distinct from the editable one (plain label/value pairs stacked vertically, no input borders, only a "Close" button — no Submit at all), unlike how `LoggedField.vue`/discharge-lifecycle fields render read-only as "the same input, just disabled." `ContactDetailsModal.vue` branches its whole body on `isReadOnly` rather than threading a `disabled` prop through six different field types, matching what the Figma actually shows rather than approximating it with disabled form controls.

**5. The Contacts table reuses `UiTable` with a static (non-paginated) dataset.**
`UiTable` is already the established table component (used by the real "All Hospitalizations" list); passing `rows: tracker.contacts`, `total: contacts.length`, `page: 1`, `perPage: contacts.length || 1` (so its "Showing X to Y of Z" footer stays sensible) reuses the component and its styling without wiring real sort/pagination logic that a handful of per-tracker contacts doesn't need. The Action column's cell slot renders the existing `fa fa-calendar` icon already present (but inert) in `AllHospitalizationSection.vue`'s own Action column, now wired to `store.openEditContact(row)`.

**6. "Contact received by" is a plain text field, not tied to `ASSIGNED_TO_OPTIONS`.**
The Figma's example value ("Melinda James") isn't one of the mocked `ASSIGNED_TO_OPTIONS` users, and nothing in the ACs implies it must be a system user — it plausibly represents whoever received the contact, which may not be an ICM staff member at all (e.g. a front-desk contact). Implemented as a `UiInput`, matching the Figma control exactly rather than assuming it maps onto the existing mock user list.

**7. `Relationship` options are a new, illustrative mock list — no backend enum exists.**
`CONTACT_RELATIONSHIP_OPTIONS` (Step Mother, Father, Relative, Spouse, Caregiver, Nurse, Physician, Facility Contact, Other) is added to `hospitalizationTrackerFields.js` alongside the existing illustrative lists (`CLIENT_STATUS_KT_OPTIONS`, `DME_PROVIDER_OPTIONS`) for fields with no confirmed backend source yet.

## Risks / Trade-offs

- **[Accepted]** Decision 3's required-field set is an assumption pending confirmation, same category of gap as prior stories in this feature.
- **[Accepted]** No delete action exists yet — if the real workflow needs it, it's a small, additive follow-up (one more store action + service method + table action icon), not a rework of anything built here.

## Migration Plan

No data migration; additive to the existing mocked tracker/service/store. When real endpoints exist: replace `createContact`/`updateContact`'s bodies with real `client.*()` calls; the store's `submitContact` action already treats the service as the source of truth for the resolved contact list, so no store/component changes would be needed beyond that.

## Open Questions

- Which fields are actually required (Decision 3) is assumed, not confirmed — flagged for the requester, not a blocker.
