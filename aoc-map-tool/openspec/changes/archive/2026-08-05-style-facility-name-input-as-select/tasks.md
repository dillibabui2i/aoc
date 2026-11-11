## 1. Config

- [x] 1.1 In `resources/js/vue/modules/vbc/config/hospitalizationTrackerFields.js`, add `styleAsSelect: true` to the `facilityName` entry in `CURRENT_INFO_FIELDS`.

## 2. Component styling

- [x] 2.1 In `LoggedField.vue`, bind a conditional class (`logged-field__input--dropdown-style`) on the `ui-input` element when `field.styleAsSelect` is true.
- [x] 2.2 Add a `:deep()` scoped CSS rule for that class matching `UiSelect.vue`'s `.ui-select` appearance: border `1px solid #e2e8f0`, `border-radius: 6px`, `padding: 7px 12px` (with extra right padding for the chevron), `font-size: 14px`, `color: #1e293b`, `background: #fff` plus the same trailing chevron SVG background-image used by `.ui-select`.
- [x] 2.3 Add a short code comment noting these values are duplicated from `@angels-of-care/ui`'s `UiSelect.vue` for a later manual port into the library (per requester's instruction).

## 3. Verification

- [x] 3.1 Run `npx eslint` on the two touched files — 0 errors (pre-existing repo-wide CRLF/prettier warnings only, unrelated to this change).
- [ ] 3.2 Visually verify in the app that `Facility Name` now matches the border/radius/padding/chevron look of the adjacent `Hospitalization Type` / `Admission Type` selects, while still behaving as a free-text input (typing, auto-save on change) — not run in this session (no local dev server available); requester to confirm visually.
- [x] 3.3 Confirm no other `type: 'text'` field in `CURRENT_INFO_FIELDS` or elsewhere is affected (only `facilityName` has `styleAsSelect: true`).
