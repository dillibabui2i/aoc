## Context

AOC-4472 (`hospitalization-tracker-outcome`) implemented Open → In Progress → Closed, including: `OutcomeSubmitBar.vue`'s status-driven Outcome dropdown, the note `{header, detail}` shape rendered by `NotesSidebar.vue`, `hospitalizationTracker.store.js`'s `submitOutcome`/`outcomeError`, `hospitalizationTracker.service.js`'s `updateOutcome`, and Closed-state disabling in `TrackerInfoGrid.vue`/`LoggedField.vue`/`TrackerTitleBar.vue`. That change explicitly left `Discharge` as a selectable-but-Submit-disabled placeholder because no ACs existed for it yet, and `wasInProgress` already drives the nested 9-item tab layout once a tracker has passed through "In Progress" — this story's four new statuses all occur after "In Progress", so that tab logic needs no changes (confirmed against all 8 Figma frames, which consistently show the nested layout throughout).

This story is frontend + mock data only, same as AOC-4472; no backend work.

## Goals / Non-Goals

**Goals:**
- Implement Discharge → Ready for Review → Ready for Next Steps → Completed as four small, self-contained step forms, each with its own required-field (red-border) validation, reusing the existing store/service layering (`component → store → service`) and note-format convention.
- Make the automatic reassignment (Ready for Review → reviewer, Ready for Next Steps → next-steps owner) actually visible in the "Assigned To" dropdown — fixing the underlying `draft` staleness bug rather than working around it.
- Keep `Completed` a durable, re-enterable terminal state (like `Closed` from AOC-4472): disabling must be driven by `tracker.status`, not a one-time submit side effect.

**Non-Goals:**
- No backend work.
- No content changes to the placeholder tabs (Contacts, Diagnosis, Sick Plan, Care Coordination, Education, Follow-up, Hospitalization History) — untouched.
- No change to the `Closed`/`Transfer` deferred-Submit behavior from AOC-4472 (still deferred to AOC-3482) — only `Discharge`'s deferred status is lifted, since this story is the one that defines it.
- No new design-system components — the one real gap (`UiInput`/`UiDateTimepicker` don't render a red border from an `error`-like prop) is patched locally, following the same `:deep()` override precedent already used elsewhere in this module (e.g. `LoggedField.vue`'s dropdown-style override).

## Decisions

**1. Each step is its own small form component, not one growing `OutcomeSubmitBar.vue`.**
`DischargeFields.vue`, `ReadyForReviewFields.vue`, `ReadyForNextStepsFields.vue`, `CompletedFields.vue` each read `useHospitalizationTracker()` directly (matching the codebase convention of feature components talking to the store, not props/emit chains) and own their own local `reactive` form state + validation. `OutcomeSubmitBar.vue` picks which one to render based on the locally-selected `outcome` value, replacing its generic Submit button for these four cases (it keeps the generic button only for `In Progress`/`Closed`, unchanged from AOC-4472).

**2. Required-field validation lives in each Fields component, not the store — a deliberate inconsistency with `Closed`'s `outcomeError`.**
AOC-4472's Closed/Reason validation lived in the store (`outcomeError`) because there was exactly one field. These four steps have 2–5 fields each with per-field red borders (AC4/AC10/AC19/AC27 — a different validation UX than AOC-4472's single message), so centralizing in the store would mean the store returning a field-name-keyed error map for four different shapes — more indirection for no benefit. Each component validates locally and only calls its store action once valid; the store actions (`submitDischarge`, etc.) trust their input, mirroring how `TrackerInfoGrid.vue`'s own Submit already works (no store-side validation there either).

**3. Red-border support is patched locally where the design system doesn't already provide it.**
`UiSelect` and `UITextArea` already turn their border red via an `error` prop (`.ui-select-wrap--error`/`.ui-textarea-wrap--error` in their own scoped styles) — used as-is. `UiDateTimepicker` has no `error` prop and no error styling at all; none of the four new fields need plain `UiInput` (all are select/textarea/date/toggle), so the only gap is the three date fields (Expected Discharge Date, Discharge Date, First billable shift date), all in `DischargeFields.vue`. That component passes a conditional `has-error` class straight through (default Vue attrs fallthrough applies to `UiDateTimepicker`, unlike `UiInput`/`UiSelect`/`UITextArea` which opt out via `inheritAttrs: false`) and adds one local `:deep(.has-error.ui-datepicker-wrap) { border-color: ... }` rule — the same "third-party gap, small local override" pattern already used in `LoggedField.vue`.

**4. `updateOutcome` gains a generic `fields` merge parameter instead of a fifth outcome-specific special case.**
Rather than adding a fourth `if (outcome === 'Discharge') { ... } else if (outcome === 'Ready for Review') { ... }` branch per new status inside `updateOutcome` (mirroring how `Closed`'s `reason` was special-cased in AOC-4472), the service now accepts an optional `fields` object it `Object.assign`s onto the mock tracker before setting `status` from a small `STATUS_BY_OUTCOME` map. `In Progress`/`Closed` keep their existing dedicated branches completely unchanged (no risk to already-shipped behavior); the four new outcomes go through the generic path. This keeps `updateOutcome`'s signature backward compatible (`reason` and `fields` are both optional) while avoiding a fifth near-duplicate branch.

**5. Reassignment and "Who Sent to Review" are plain `fields` entries, not separate service calls.**
`submitReadyForReview` reads `tracker.value.assignedTo` (the *current* assignee, before reassignment) into `fields.whoSentToReview`, and sets `fields.assignedTo` to the selected reviewer, in the same `updateOutcome` call — so the reassignment and the "who sent it" bookkeeping are atomic with the status transition, not a separate follow-up write. `submitReadyForNextSteps` does the same for `fields.assignedTo` (no separate "who sent" field needed at that step, per the ACs). `submitCompleted` stores the selected `completedBy` value onto the tracker for completeness/audit, but does **not** reassign `Assigned To` (no AC asks for this — only that the control becomes *disabled*, AC30).

**6. The "Assigned To" dropdown's staleness bug is fixed, not worked around.**
`TrackerInfoGrid.vue`'s `ui-select` for editable header fields (including `Assigned To`) is bound to a local `draft` object that is only (re)populated from `store.selectedClient` at tracker-creation time (via a `watch`). Once the tracker exists, nothing re-synced `draft.assignedTo` when `store.tracker.assignedTo` changed by any means other than that same dropdown's own `onEditableChange` — meaning AC14/AC22's reassignment would silently fail to render even though the underlying data was correct. A second, narrow `watch(() => store.tracker?.assignedTo, ...)` mirrors external changes into `draft.assignedTo`. This is a pre-existing latent bug (present since AOC-4402), only now made observable because this is the first story that reassigns `Assigned To` programmatically; fixing it here is in-scope (it's required for AC14/AC22 to actually work), not scope creep.

**7. New Current Hospitalization Info fields are gated by a `postDischargeOnly` flag and a `visibleIf` predicate, not a second field-config array.**
`CURRENT_INFO_FIELDS` gains four entries (Expected Discharge Date, Discharge Date, the billable-shift toggle, First billable shift date) reusing the exact same `key`s the Discharge form writes, so the one-time Discharge submission and the ongoing generic auto-save editing (via `LoggedField.vue`, already type-aware for `date`/`toggle`) operate on the same data with zero duplication. `postDischargeOnly: true` hides all four until the tracker has passed Discharge; `firstBillableShiftDate` additionally carries `visibleIf: (tracker) => !!tracker.firstBillableShiftCompleted`. `CurrentHospitalizationInfoTab.vue`'s row list becomes a filter over `CURRENT_INFO_FIELDS` (previously a static `['A'..'F']` array) instead of gaining a second, forked field list.

**8. Outcome-note wording resolves two Figma/AC mismatches by following the written ACs, consistent with AOC-4472's precedent.**
- **Discharge note, line 2**: AC6 specifies a literal `Status changed to 'Discharged' by <user name>` (matching the exact template AOC-4472 used for In Progress/Closed) — but the Figma frame instead shows the *entered Discharge Notes text* as line 2 (`"Here are some discharge notes"`), matching the pattern the other three steps use (`<review notes>` / `<notes>` / `<notes>` as their literal line 2). This looks like a copy-paste artifact from AOC-4472's template that wasn't updated for this step. Per the established precedent of treating written ACs as the authoritative, testable contract, line 2 is implemented exactly as AC6 states; the actually-entered Discharge Notes text is preserved as its own field (`dischargeNotes`, visible in the Current Hospitalization Info tab) so the information isn't lost either way, satisfying the Figma's underlying intent (the notes are visible somewhere) without contradicting the literal AC.
- **"<discharge date and time>" wording, all four steps**: AC6/AC12/AC21/AC29 all say "at \<discharge date and time\>", even for the three steps that happen well after the actual discharge date. This is read as meaning "the date and time of that specific transition" (i.e. "now", at submit time) rather than literally reusing the original discharge date/time for Ready for Review/Next Steps/Completed notes — matching `new Date().toLocaleString('en-US')`, the same timestamp convention AOC-4472 already established for its own notes.
- **Outcome options at each intermediate status**: the Figma "Starting point" callouts list an extra `Close`/`Transfer` alongside each step's named option (e.g. "Ready for Review, Close"), but AC7/AC15/AC23 each list exactly one option. Per the same written-ACs-win precedent, only the single AC-specified option is implemented per status; re-adding a generic "Close from any status" affordance is left as a follow-up if actually intended, since no AC currently exercises it.

## Risks / Trade-offs

- **[Accepted]** The three Figma/AC discrepancies above (Decision 8) are resolved in favor of the written ACs — if the Figma is actually the more current source of truth, this is a one-config-file/one-line follow-up, not a structural rework (note text and `outcomeOptions` are both centralized).
- **[Accepted]** `Discharge`'s previous "deferred" comment/behavior from AOC-4472 is removed; this is a direct, intended supersession by this story, not a regression.
- **[Trade-off]** Per-step validation living in each Fields component (Decision 2) means four small, structurally-similar `validate()` functions instead of one shared helper — acceptable at this scale (2–5 fields each); worth revisiting only if a fifth similarly-shaped step appears later.

## Migration Plan

No data migration; additive to the existing mocked tracker/service/store. When real backend endpoints exist: (1) replace `updateOutcome`'s body with real `client.*()` calls (its `fields`-merge contract already matches a typical "PATCH tracker" shape), (2) `whoSentToReview`/`completedBy` map to whatever the real API's equivalent audit columns are, (3) the `draft` re-sync fix (Decision 6) remains correct regardless of the data source.

## Open Questions

- Whether `Close`/`Transfer` should remain selectable from every intermediate status (per the Figma "Starting point" callouts) is unresolved — implemented per the narrower, literal ACs (Decision 8); flagged for the requester to confirm, not a blocker.
