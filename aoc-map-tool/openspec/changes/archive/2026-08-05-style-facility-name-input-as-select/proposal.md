## Why

On the Hospitalization Tracker "Current Hospitalization Info" tab, the `Facility Name` field renders as a plain `ui-input` text box that visually clashes with the surrounding `ui-select` dropdowns (`Hospitalization Type`, `Admission Type`, etc.) — it has no border chevron, different padding/background, and reads as inconsistent in the form grid. The requester wants it visually aligned to the `UiSelect` look (border, radius, padding, dropdown-arrow affordance) without changing its behavior (it stays a free-text input, not an actual dropdown), and wants the CSS scoped to a single component first so it can be reviewed and manually ported into the shared `@angels-of-care/ui` library afterward.

## What Changes

- Add a scoped CSS override (in `LoggedField.vue`, applied only to the `facilityName` field) that visually restyles the `Facility Name` `ui-input` to match `UiSelect`'s appearance: border color/radius, padding, font, and a trailing chevron icon — using the same design-token values (`$color-gray-200`, `$radius-md`, `$space-3`, `$text-sm`, etc.) already hard-coded as raw values in `UiSelect.vue`.
- No change to the field's underlying type, validation, or save/auto-save behavior — `facilityName` remains `type: 'text'` and keeps its current auto-save-on-change flow.
- No changes to `@angels-of-care/ui` itself; the CSS lives entirely in the app-side component so the requester can review it and port it into the library manually later.

## Capabilities

### New Capabilities
(none — purely a visual/CSS change to an existing field)

### Modified Capabilities
(none — no existing `openspec/specs/` capability describes Hospitalization Tracker field styling)

## Impact

- Frontend: `resources/js/vue/modules/vbc/components/HospitalizationTracker/LoggedField.vue` (add a scoped class + style block, applied conditionally based on a new field-config flag).
- Frontend: `resources/js/vue/modules/vbc/config/hospitalizationTrackerFields.js` (`facilityName` field entry gains a flag, e.g. `styleAsSelect: true`, to opt into the new look without hard-coding the field key in the component).
- No backend changes, no API contract changes, no changes to `@angels-of-care/ui` package.
