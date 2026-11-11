## Why

The "Contacts" tab introduced by AOC-4472's tab rearrangement (once a tracker has been "In Progress") currently renders `PlaceholderTab.vue`'s empty state. ICM/Care Coordination teams need a centralized, per-tracker list of hospitalization-related contacts (family, caregivers, nurses, physicians, facility contacts) they can create and edit while the tracker is active, with the list becoming read-only once the tracker moves past "In Progress" — matching how the rest of the tracker's fields already lock down on later statuses.

## What Changes

- Replace the "Contacts" tab's `PlaceholderTab` with a real `ContactsTab.vue`: an "Add Contact" button (hidden once the tracker isn't "In Progress") and a table of the tracker's contacts (Contact Name, Relationship, Phone Number, Email, Action), reusing `@angels-of-care/ui`'s `UiTable`.
- Add a `ContactDetailsModal.vue` (a second `UiModal` instance, matching the existing `ActiveTrackerConflictModal.vue` precedent) used for both creating and editing a contact: Contact Name, Relationship, Phone Number, Email, Contact Date, and Contact Received By are required; Contact Notes is optional (the ACs describe "required fields" generically without naming them — see design.md Decision 3 for this explicit assumption).
- Required-field validation shows a red border on the invalid fields (same UX and same `UiDateTimepicker` red-border gap/workaround already established in AOC-4472's discharge lifecycle work).
- On successful create or edit, the modal closes and the Contacts table immediately reflects the change (both back onto the same in-memory mocked tracker, no real page refresh needed since there's no page navigation involved).
- Once the tracker's status is anything other than "In Progress" (Discharged, Ready for Review, Ready for Next Steps, Completed, Closed), the Contacts tab hides "Add Contact" and opens existing contacts in a read-only variant of the same modal (no Submit action, no editable inputs) — contacts themselves are always preserved and displayed regardless of status.
- Permission gating (AC1/AC2) requires no new code: the entire "Hospitalization Record" modal — Contacts tab included — is already gated behind `useHasPermission('Intensive Case Management')` from AOC-4402.

## Capabilities

### Modified Capabilities
- `hospitalization-tracker-creation`: the "Placeholder tabs render empty" scenario no longer covers "Contacts" (it gets real content); new requirements are added for contact creation, editing, validation, association, and read-only behavior once the tracker leaves "In Progress".

### New Capabilities
(none — extends the same tracker capability)

## Impact

- `resources/js/vue/modules/vbc/components/HospitalizationTracker/HospitalizationTrackerModal.vue` — the `#contacts` slot renders `ContactsTab` instead of `PlaceholderTab`; mounts `ContactDetailsModal` alongside `ActiveTrackerConflictModal`.
- New: `ContactsTab.vue` — "Add Contact" button (permission/status-gated) + `UiTable` of `store.tracker.contacts`, with an Action-column calendar icon per row opening the edit modal.
- New: `ContactDetailsModal.vue` — one modal used for both create and edit, switching to a read-only rendering (per Decision in design.md) once the tracker isn't "In Progress"; owns its own required-field (red-border) validation, matching the pattern established in `DischargeFields.vue`/`ReadyForReviewFields.vue` etc. from AOC-4472.
- `resources/js/vue/modules/vbc/store/hospitalizationTracker.store.js` — new state (`contactModalOpen`, `editingContact`) and actions (`openAddContact`, `openEditContact`, `closeContactModal`, `submitContact`) following the existing "one store owns the whole modal lifecycle" convention (mirrors `activeTrackerConflict`/`wizardStep`).
- `resources/js/vue/modules/vbc/services/hospitalizationTracker.service.js` — new mocked `createContact(trackerId, payload)`/`updateContact(trackerId, contactId, payload)` methods, and a `contacts: []` default added to every mock tracker (fixture + newly created).
- `resources/js/vue/modules/vbc/config/hospitalizationTrackerFields.js` — new `CONTACT_RELATIONSHIP_OPTIONS` mock list (no backend enum exists for this yet, same "illustrative mock list" pattern as `CLIENT_STATUS_KT_OPTIONS`/`DME_PROVIDER_OPTIONS`).
- No backend (`app/VBC/**`, routes) changes — frontend + mock data only, per explicit scope (consistent with every prior story in this tracker feature set).
