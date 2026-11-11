## Context

`CurrentHospitalizationInfoTab.vue` renders `CURRENT_INFO_FIELDS` (from `hospitalizationTrackerFields.js`) through `LoggedField.vue`, which switch-renders one of `ui-input` / `ui-select` / `ui-date-timepicker` / etc. from `@angels-of-care/ui` based on `field.type`. `facilityName` is `type: 'text'`, so it renders as a bare `ui-input` with no label-adjacent styling to match the `ui-select` dropdowns next to it (`hospitalizationType`, `admissionType`, `declinedProgramContact`, `source`).

`UiInput.vue` has `inheritAttrs: false` and forwards `$attrs` (including `class`) directly onto the native `<input>` element, not onto its wrapper `div.ui-input-wrap`. This mirrors how `UiSelect.vue` puts its `.ui-select` class directly on the native `<select>`. That means a `class` passed from `LoggedField.vue` to `<ui-input>` lands on the actual `<input>` DOM node, so it can be targeted with a scoped `:deep()` selector.

`UiSelect.vue`'s `.ui-select` class (for reference, not imported) uses these raw values: `padding: 7px 12px` with `padding-right: 32px`, `border: 1px solid #e2e8f0`, `border-radius: 6px`, `font-size: 14px`, `color: #1e293b`, `background: #fff` plus a chevron SVG data-URI positioned at `right 12px center`. These are hard-coded in the library component (no exported SCSS tokens available at the app layer), so this change duplicates the same raw values locally with a comment noting the library source, to keep the visual match exact.

## Goals / Non-Goals

**Goals:**
- Make the `Facility Name` input visually indistinguishable in chrome (border/radius/padding/font/chevron) from the `ui-select` fields on the same tab, while remaining a real text `<input>`.
- Keep the change local to `LoggedField.vue` (+ a config flag), so it can be reviewed and ported into `@angels-of-care/ui` by hand later, per the requester's explicit ask.

**Non-Goals:**
- Not converting `facilityName` into an actual `select`/dropdown, or changing its data/options/validation.
- Not modifying `@angels-of-care/ui` (`UiInput.vue`/`UiSelect.vue`) in this change.
- Not restyling any other text fields on the form (e.g. Client Name, Branch) — only the one field explicitly called out.

## Decisions

- **Config-driven opt-in flag over hard-coded field key.** Add `styleAsSelect: true` to the `facilityName` entry in `CURRENT_INFO_FIELDS` (`hospitalizationTrackerFields.js`) rather than checking `field.key === 'facilityName'` inside `LoggedField.vue`. This follows the existing pattern in that file (layout/type driven entirely by config) and lets any future field opt into the same look without touching the component again.
- **`:deep()` scoped CSS class passed via `class` binding, not a wrapper `<div>`.** Since `UiInput` forwards `class` from `$attrs` straight onto the native `<input>` (confirmed in `UiInput.vue`), `LoggedField.vue` can bind `:class="{ 'logged-field__input--dropdown-style': field.styleAsSelect }"` directly on `<ui-input>` and style it with `:deep(.logged-field__input--dropdown-style)` in its own `<style scoped>` block — no wrapper markup or JS changes needed.
- **Duplicate the exact raw CSS values from `UiSelect.vue` instead of importing library SCSS tokens.** No other component in `resources/js/vue/modules/vbc` imports `@angels-of-care/ui`'s SCSS token partial, and pulling in `@use '.../styles/tokens'` into an app-side `<style scoped>` (plain CSS, not SCSS) would be a bigger footprint than this cosmetic change needs. Values are copied as literals with a short comment pointing at `UiSelect.vue` as the source of truth, so a manual porter can trivially replace them with token references once this lands in the library.

## Risks / Trade-offs

- [Raw values duplicated instead of importing tokens] → if `UiSelect`'s tokens ever change, this override will silently drift out of sync until someone notices; acceptable since the requester's explicit intent is a temporary, single-component styling pass ahead of a manual library port.
- [`:deep()` targets a DOM node owned by a third-party component] → if a future `@angels-of-care/ui` upgrade changes `UiInput`'s internal structure (e.g. stops forwarding `class` to the native `<input>`), the override could silently stop applying; low risk since `inheritAttrs: false` + `v-bind="$attrs"` on the `<input>` is a stable, deliberate pattern already used by `UiSelect` too.
- [Visual-only change, no behavior change] → no functional risk to auto-save/save-cancel flows since `field.type` stays `'text'`.

## Migration Plan

Frontend-only, no data/schema/API impact. Ship behind normal deploy; verify visually on the Hospitalization Tracker modal's "Current Hospitalization Info" tab. Rollback is a plain revert of the two touched files.

## Open Questions

None — scope and target field were confirmed directly from the screenshot and existing field config.
