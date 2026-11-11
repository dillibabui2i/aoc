## Context

AOC-4402 (`hospitalization-tracker-creation`) shipped the "Hospitalization Record" modal's "Open" state with:
- `OutcomeSubmitBar.vue` — a fully inert `UiSelect` (local `ref`, hardcoded "In Progress"/"Closed" options) + `UiButton` with no click handler at all.
- `NotesSidebar.vue` — a shell that accepts a `notes` prop (`{ id, text }[]`, defaults to `[]`) but is mounted with no prop (`<NotesSidebar />` in `HospitalizationTrackerModal.vue`), so it always renders "No notes yet."
- `TrackerTabs.vue` — a flat single-level tab bar; `HospitalizationTrackerModal.vue` always renders the same 4 tabs (Current Hospitalization Info, Hospitalization History, Sick Plan, Follow-up) regardless of tracker status.
- `TrackerInfoGrid.vue` / `LoggedField.vue` — field enablement is driven entirely by `wizardStep` (`'confirmed'`/`'open'` = editable), with no concept of a tracker being permanently closed/read-only.
- `hospitalizationTracker.service.js` — five mocked methods (`searchClients`, `checkActiveTracker`, `createTracker`, `getTrackerById`, `saveField`); no outcome/status-transition method exists.
- `allHospitalization.store.js`/`.service.js` — real, backend-integrated (`GET /vbc/hospitalization/data`), rendering the "All Hospitalizations" table (`AllHospitalizationSection.vue`) that hosts the "+" button and the tracker modal. This store/service is untouched by AOC-4402 and is **not** connected to the mocked tracker/client data in any way (the table's rows don't open the tracker modal; the modal's mock clients don't appear in the table).

This change (AOC-4472) wires the Outcome control to real (mocked) status transitions and everything that depends on the tracker's status: notes, tab structure, and closed-state field/control disabling — still frontend + mock data only, per explicit scope.

## Goals / Non-Goals

**Goals:**
- Implement the full Open → In Progress → Closed state machine described in the ACs, including the 24h admission banner, the Reason field + validation, note formatting, tab rearrangement, and closed-state disabling — all driven by `tracker.status` so it's correct both immediately after submit and when a previously-transitioned tracker is reopened (AC22).
- Keep the existing component → store → service layering and the "one service module, mocked methods swappable later" pattern from AOC-4402.
- Handle the two explicitly out-of-scope branches (Discharge outcome, Transfer reason) consistently and safely (selectable, but not submittable) rather than silently mis-transitioning the tracker.

**Non-Goals:**
- No backend work: no new routes, controllers, models, enums in `app/VBC`.
- No real Activity Log entries (still AOC-3867) — the notes added here are the tracker's own `notes` array (mocked, in-memory), not a separate audit-log system.
- No content for the new placeholder tabs (Contacts, Diagnosis, Care Coordination, Education) beyond reusing the existing `PlaceholderTab.vue` empty state — building these out is separate future work, matching how Hospitalization History/Sick Plan/Follow-up were left as placeholders in AOC-4402.
- No `Discharge` outcome behavior and no `Transfer` reason behavior (see Decision 6) — both are deferred (Transfer explicitly to AOC-3482).
- No backfilling of an "Opened by ... / Status changed to 'Open' by ..." note for the original tracker-creation flow. The Figma reference shows one, but authoring it is retroactive scope creep into AOC-4402's `submitTracker`/`createTracker`; this change only adds notes for the In Progress/Closed transitions it introduces. `notes` starts as `[]` on any tracker created before this change and stays that way until a transition occurs.
- No change to `allHospitalization.store.js`/`.service.js` internals or to the real `/vbc/hospitalization/data` contract — see Decision 7 / Risks for the resulting fidelity gap.

## Decisions

**1. `tracker.status` (not `wizardStep`) is the single source of truth for all closed-state UI behavior.**
`wizardStep` stays `'open'` throughout Open → In Progress → Closed and remains `'open'` when a closed tracker is reopened via `openExistingTracker`/`loadTrackerById` (AC22 requires the same disabled/hidden rendering on reopen as immediately after closing — a `wizardStep`-only rule couldn't distinguish "freshly submitted" from "reopened", but `tracker.status === 'Closed'` naturally covers both). `TrackerInfoGrid.vue`'s `isEditableNow(field)` becomes `field.editable && (wizardStep === 'confirmed' || wizardStep === 'open') && tracker?.status !== 'Closed'`; `LoggedField.vue` adds `:disabled="store.tracker?.status === 'Closed'"` to every rendered field type; `TrackerTitleBar.vue`'s Notes icon becomes `v-if="isOpen && store.tracker?.status !== 'Closed'"`; `OutcomeSubmitBar.vue` becomes `v-if="store.tracker?.status !== 'Closed'"` at its root.

**2. A `wasInProgress` boolean (not just current `status`) decides flat vs. nested tabs.**
Per the ACs, tab rearrangement (AC8) is only specified under "In Progress Outcome," and the Figma "Alternate Path - Closed" walkthrough (Open → Closed directly) shows the tab bar staying flat. But a tracker closed *from* In Progress should logically keep the nested layout it already had — and both paths end at the same `status === 'Closed'`, so `status` alone can't disambiguate them. `tracker.wasInProgress` is set to `true` the moment the In Progress transition succeeds and never reset; `HospitalizationTrackerModal.vue` computes `useNestedTabs = tracker.status === 'In Progress' || tracker.wasInProgress`, and picks the flat 4-tab list or the nested 9-item list accordingly. This is a mock-only bookkeeping field (`wasInProgress`), analogous to how `wizardStep`/`activeTrackerConflict` already track transient workflow state that has no real backend column yet.

**3. `TrackerTabs.vue` gains one level of optional sub-tabs, backward compatible.**
Rather than building a second parallel tabs component, `tabs` entries gain an optional `subTabs: [{ key, label }]`. When the active top-level tab has `subTabs`, a second tab row renders below the first, and the content slot is keyed by the active *sub*-tab (falling back to the top-level tab's own key when it has no sub-tabs, exactly as today). Existing usage (`HospitalizationTrackerModal.vue`'s current flat 4-tab list, and any test coverage) is unaffected since none of those tabs declare `subTabs`. The nested layout's tab set is:
```js
[
  { key: 'hospitalizationInfo', label: 'Hospitalization Info', subTabs: [
      { key: 'currentInfo', label: 'Current Hospitalization Info' },
      { key: 'history', label: 'Hospitalization History' },
    ] },
  { key: 'contacts', label: 'Contacts' },
  { key: 'diagnosis', label: 'Diagnosis' },
  { key: 'sickPlan', label: 'Sick Plan' },
  { key: 'careCoordination', label: 'Care Coordination' },
  { key: 'education', label: 'Education' },
  { key: 'followUp', label: 'Follow-up' },
]
```
`Contacts`, `Diagnosis`, `Care Coordination`, `Education` are new `PlaceholderTab` instances (matching `Sick Plan`/`Follow-up`'s existing pattern); `Current Hospitalization Info`/`Hospitalization History` keep their existing components, just re-parented under the new `hospitalizationInfo` tab.

**4. Outcome options and the Reason dropdown are pure functions of `tracker.status`, defined next to `OutcomeSubmitBar.vue`.**
```js
function outcomeOptionsFor(status) {
  if (status === 'Open') return ['In Progress', 'Closed']
  if (status === 'In Progress') return ['Discharge', 'Closed']
  return []
}
```
matching AC3/AC9 exactly (and the discrepancy with the Figma "Final Outcome" annotation text, which lists "Discharge, Transfer, Cancelled" — the written ACs are treated as the authoritative, testable contract per this ticket; see Risks). The Reason dropdown (`ED Visit` / `Opened by mistake` / `Transfer`) renders whenever the selected outcome is `Closed`, regardless of current status (AC11 says "Open" or "In Progress"), and is required — submitting with it empty sets a local `error` string passed to `UiSelect`'s existing `error` prop (`"This field is required."`), matching AC12's "standard required-field validation" without inventing a new validation component.

**5. `hospitalizationTracker.store.js` gains one `submitOutcome({ outcome, reason })` action; the service gains one matching `updateOutcome` method.**
The store validates (Closed + no reason → returns a validation failure, no service call, no note, modal stays open — AC12) and constructs the note text (business rule, kept in the store per the existing `// Auto-save path (ACxx)` comment convention already used there for other business rules), then calls `HospitalizationTrackerService.updateOutcome(trackerId, { outcome, reason })`, which mutates the mock tracker's `status`/`reason`/`wasInProgress` and returns the updated record — mirroring the existing `saveField` mutate-and-resolve pattern exactly. Note text:
```js
const user = HospitalizationTrackerService.CURRENT_USER // exported const, same value already used for `author`
const when = new Date().toLocaleString('en-US') // matches existing `creationDate` formatting
// In Progress:
header: `In Progress by ${user} at ${when}`
detail: `Status changed to 'In Progress' by ${user}`
// Closed:
header: `Closed by ${user} at ${when}`
detail: `Status changed to 'Closed' by ${user} - Reason: ${reason}`
```
On a successful Closed transition, the action also calls `closeModal()` (AC20) and `useAllHospitalization().fetchAll()` (AC21 — see Decision 7). On a successful In Progress transition, the action does **not** close the modal (no AC requires it, and the user needs to keep working the tracker) and does not otherwise change `wizardStep`.

**6. `Discharge` and `Transfer` are selectable but not submittable — Submit is disabled, not silently a no-op.**
Both appear in their respective dropdowns per AC9/AC11/AC23, but `OutcomeSubmitBar.vue`'s Submit button is disabled whenever `outcome === 'Discharge'` or (`outcome === 'Closed'` and `reason === 'Transfer'`), with a short inline hint ("Not yet available"). This is safer than letting Submit silently do nothing (which would look like a bug) and is consistent with AC23's framing that Transfer's *workflow* — not just its dropdown presence — is out of scope. `Discharge` has no ACs at all in this ticket (unlike Transfer, it isn't even assigned a future ticket number in the source material), so it's treated identically pending a future story that defines it.

**7. AC21 ("remove the closed tracker from the bucket list") is satisfied by calling the real `allHospitalization.store.js`'s `fetchAll()`, with the resulting mock/real disconnect flagged as an accepted limitation, not hidden.**
The "All Hospitalizations" bucket table (`AllHospitalizationSection.vue` → `allHospitalization.store.js` → real `GET /vbc/hospitalization/data`) has never been connected to this modal's mocked client/tracker data (confirmed: no table row opens the tracker modal, and no mocked client appears in the real table) — that disconnect predates this change and is out of scope to fix here. Calling `fetchAll()` after a successful Closed transition is the architecturally correct action (it's exactly what a real integration would do, and is a one-line, safe, idempotent call), but because the closed tracker in this change is mock-only, it will not visibly disappear from that real table in this environment — there is no real row to remove. This is called out explicitly here rather than either skipping the call (which would leave AC21 completely unaddressed) or inventing a fake client-side row-filtering mechanism against data this store doesn't actually hold.

**8. The 24h admission banner is a pure computed off `tracker.admissionDateTime`, rendered by `OutcomeSubmitBar.vue`.**
`admissionDateTime` is stored as `MM/DD/YYYY HH:mm` (24h clock — matches `UiDateTimepicker`'s existing `datetime` mode format string already used for this exact field). The banner computed is:
```js
const admissionAgeHours = computed(() => {
  if (!tracker.value?.admissionDateTime) return null
  const parsed = parseMmDdYyyyHm(tracker.value.admissionDateTime) // small local parser, no new dependency
  return (Date.now() - parsed.getTime()) / 36e5
})
const showAdmissionBanner = computed(
  () => admissionAgeHours.value !== null && admissionAgeHours.value > 24 && tracker.value?.status !== 'Closed',
)
```
using `UiAlert` (already in `@angels-of-care/ui`, unused everywhere in this codebase today) with its default/info variant for the "blue informational" styling, avoiding a new component. Suppressing it once `Closed` avoids showing a stale "please review and select an outcome" prompt on a tracker that no longer accepts outcome changes (not explicitly required by the ACs, but directly implied by AC17's "Outcome dropdown should be hidden" intent — the banner exists solely to prompt an outcome decision).

## Risks / Trade-offs

- **[Accepted]** AC9's written option list ("Discharge, Closed") conflicts with the Figma "Final Outcome" annotation text ("Discharge, Transfer, Cancelled"). The written, testable ACs are treated as authoritative per Decision 4; if the Figma annotation reflects an updated design intent, reconciling it is a follow-up, not a blocker here.
- **[Accepted]** `Discharge` and `Transfer` remain selectable-but-not-submittable (Decision 6) rather than fully implemented — both are undefined/deferred in this ticket's own scope.
- **[Accepted]** AC21's bucket-refresh call is real but the mocked tracker it's meant to remove is not represented in that real backend list, so nothing will visibly change in the table in this environment (Decision 7) — this is a pre-existing fidelity gap inherited from AOC-4402's "frontend + mock" scope, not something this change can close without backend work.
- **[Trade-off]** `wasInProgress` (Decision 2) is a mock-only bookkeeping field with no corresponding real backend concept yet; when real endpoints exist, the real API's status-history (or a dedicated "has ever been In Progress" flag) will need to supply the same signal.

## Migration Plan

No data migration; purely additive to the existing mocked tracker/service/store from AOC-4402. When real backend endpoints exist: (1) replace `updateOutcome`'s body with a real `client.*()` call, (2) source `wasInProgress` from whatever the real API represents (status history, or a dedicated flag) instead of a client-tracked boolean, (3) once the tracker modal is wired to real bucket rows (separate future work), AC21's `fetchAll()` call starts having a visible, correct effect with no code change needed here.

## Open Questions

- Whether `Discharge` will get its own future ticket (analogous to `Transfer` → AOC-3482) is unknown from the source material — flagged for awareness, not a blocker (Decision 6 handles it safely either way).
- Whether the "Opened by ... / Status changed to 'Open' by ..." note shown throughout the Figma reference should be retroactively added to AOC-4402's creation flow is explicitly out of scope for this change (see Non-Goals) — raised here so it isn't lost, not because it blocks this ticket.
