## Why

ICM (Intensive Case Management) users currently have no structured, guided workflow to create a hospitalization tracker for a client. Trackers are effectively created ad hoc, with no duplicate-active-tracker protection, no auto-fill from existing client data, and no consistent field/logging structure. This creates risk of duplicate records, inconsistent data, and misalignment with the client's hospitalization lifecycle (AOC-4402).

## What Changes

- Add a single new "+" action to the existing "All Hospitalizations" list page toolbar (`AllHospitalizationSection.vue`) that opens a new "Hospitalization Record" modal. The list/grid page itself is unchanged otherwise.
- Add a new "Hospitalization Record" modal (Vue 3, `@angels-of-care/ui` design system) with four states:
  1. **New** — only Client Name enabled, autocomplete search over existing clients only, disambiguated by client ID (two clients can share a name).
  2. **Client selected, no active tracker** — remaining fields auto-fill (read-only display) from the client record; Assigned To and the custom fields (High Utilizer?, Backup G-tube?, Backup trach?, Level, last-updated timestamps) become editable; Submit enabled.
  3. **Client selected, active tracker exists** (status not in `Completed`/`Closed`) — a conflict modal offers "Open the active tracker" (cancels creation, opens existing tracker) or "Search another client" (returns to client search).
  4. **Saved/Open** — title becomes `Hospitalization Record \ {Patient Name} \ {Patient Level}` with Person/Notes icons, status badge shows "Open", tracker header (Tracker ID, Creation Date, Author, Assigned To) is populated, an Outcome dropdown + Submit (no wired logic) is shown, and a 4-tab body renders: "Current Hospitalization Info" (editable, this story) and three empty placeholder tabs ("Hospitalization History" / "Sick Plan" / "Follow-up", built out by AOC-3482/3502/3506).
- "Current Hospitalization Info" tab fields auto-save on change by default; fields flagged as requiring logging (per the data dictionary's "Log Updates?" column) instead show floating Save/Cancel buttons and stay unsaved until the user chooses one. Activity Log entry creation itself is explicitly out of scope (AOC-3867).
- Add a Notes sidebar shell/engine (empty state, no note content/creation logic) on the right side of the modal, ready to be populated by future stories.
- Gate the new "+" button and (defensively) the modal's contents behind a client-side "Intensive Case Management" permission check — implemented as an always-`true` stub with a `TODO` comment, since no permission system exists in this codebase yet; real enforcement is a separate, future ticket (explicit decision).
- All backend interactions (client search, active-tracker check, tracker create, tracker fetch-by-id, field save) are stubbed behind a single service module (`hospitalizationTracker.service.js`) with realistic mock data and artificial latency, so the real endpoints can be swapped in later without touching any component, store, or composable.

## Capabilities

### New Capabilities
- `hospitalization-tracker-creation`: Defines the end-to-end frontend contract for creating and reopening a hospitalization tracker via the "Hospitalization Record" modal — permission gating, client search/selection, active-tracker conflict handling, auto-fill, tracker header/tabs/outcome/notes-shell rendering, and the auto-save vs. logged-field Save/Cancel editing behavior.

### Modified Capabilities
(none — no existing `openspec/specs/` capability currently governs the "All Hospitalizations" page or hospitalization tracker creation)

## Impact

- `resources/js/vue/modules/vbc/sections/AllHospitalizationSection.vue` — add one permission-gated "+" button to the toolbar area (`.head-title` block) that opens the new modal. No other change to this file; its store/service/table logic is untouched.
- New: `resources/js/vue/modules/vbc/components/HospitalizationTracker/` — modal and sub-components (client search autocomplete, active-tracker conflict modal, tracker info grid, tabs, current-hospitalization-info tab fields, floating save/cancel wrapper, toggle switch, notes sidebar shell). Two components (tabs, toggle switch) are net-new local components because `@angels-of-care/ui` does not currently provide them (confirmed absent from the design system).
- New: `resources/js/vue/modules/vbc/store/hospitalizationTracker.store.js` — Pinia setup store owning modal/wizard state, the in-progress/loaded tracker, per-field dirty/save state.
- New: `resources/js/vue/modules/vbc/services/hospitalizationTracker.service.js` — mocked service layer for client search, active-tracker check, tracker create, tracker fetch-by-id, and field save (auto-save + explicit save paths). No real HTTP calls; isolated behind this one module so it is the only file touched when real endpoints exist.
- New: `resources/js/vue/modules/vbc/config/hospitalizationTrackerFields.js` — single source of truth for tracker field metadata (key, label, section/row, input type, dropdown options, whether the field requires the logged Save/Cancel flow). Centralizing this here means the "Log Updates?" assumptions called out in `design.md` are a one-file change once the real data dictionary is confirmed.
- New: `resources/js/vue/modules/vbc/composables/usePermissions.js` — client-side permission check composable. No permission system exists anywhere in this codebase today (confirmed by repo-wide search); per explicit decision, this ships as an always-`true` stub with a `TODO` comment, and real enforcement is deferred to a separate future ticket.
- No backend (`app/VBC/**`, routes) changes. Existing `app/VBC/Enum/Hospitalization/*` enums and `HospitalizationModel`/`HospitalizationCollectionItemResource` fields are read (for mock data shape/values only) but not modified.
- No changes to `app/VBC/CLAUDE.md` conventions; new frontend code follows `resources/js/vue/CLAUDE.md` (Composition API, `<script setup>`, setup-store Pinia, strict component→store→service layering, Vitest specs alongside code).
