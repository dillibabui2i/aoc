## 1. Investigation

- [x] 1.1 Confirm the exact `multiselect()` init options `datatable-filters.blade.php` uses for `.selectpicker` (`includeSelectAllOption`, `data-live-search`, feature-flag-gated optgroup options) and decide which apply to the global filter bar's three fields — confirmed at `datatable-filters.blade.php:417-431`: `.selectpicker` gets `includeSelectAllOption: true` (+ optgroup options behind `ipea-filter-group-selection`); `.selectpickersearch` adds `enableCaseInsensitiveFiltering: true`. State/Region/Branch have no optgroups and Branch can have many options, so: use `selectpickersearch` (with search) for Branch, plain `selectpicker` (`includeSelectAllOption: true`, no optgroup flag needed) for State and Region.
- [x] 1.2 Sample a handful of in-scope buckets (`.card[data-follows-global="true"]`) and confirm whether each one's local `state`/`region`/`branch` field is itself a `selectpicker` (bootstrap-multiselect), a `select2`, or a plain select — confirmed via `intake/grid.blade.php`, `ccr/ccmdashboard.blade.php`, `ccr/candidatestarts/accordion.blade.php`, `officeEvaluation/dashboard.blade.php`, `billing/billingDashboard.blade.php`: all local fields tied to `data-global-filter-fields` are `type: multiselect` (bootstrap-multiselect), none are `select2`. `prepopulateBucketLocalFilters`/`setLocalFilterValue` should target bootstrap-multiselect (`.multiselect('refresh')`), not `change.select2` (which was already a mismatch with reality even before this change).

## 2. Markup: multi-select dropdowns

- [x] 2.1 In `resources/views/components/global-filter.blade.php`, change the `state`, `region`, and `branch` `<select>` elements to `<select multiple>`, using `selectpicker` for State/Region and `selectpickersearch data-live-search="true"` for Branch, matching the attributes used in `datatable-filters.blade.php`.
- [x] 2.2 Remove the now-unused single-select `<option value="">Select ...</option>` placeholder pattern in favor of the `data-placeholder` attribute (consistent with `datatable-filters.blade.php`'s multiselect fields).
- [x] 2.3 Initialize `bootstrap-multiselect` on the filter bar's selects (`$('.filter-bar .selectpicker').multiselect({...})` / `$('.filter-bar .selectpickersearch').multiselect({...})`), inline in the component.
- [ ] 2.4 Verify `bootstrap-multiselect.min.css`/`.js` (already loaded via `myaccount.blade.php`) render/behave correctly inside the `.filter-bar` container's existing layout/CSS (`.filter-bar__controls` flex row) — adjust widths/wrapping if the multiselect widget doesn't fit the current layout. (Needs manual visual check in a browser — flagged in section 4.)

## 3. JS: array-based state

- [x] 3.1 In `resources/js/globalLocationFilter.js`, update `getSelectValue`/`readSelectValues` to return an array of selected values (`getSelectValues`, using `select.selectedOptions`) instead of a single string.
- [x] 3.2 Update `writeSelectValues` to set multiple selected `<option>`s from an array and call the multiselect widget's refresh (`.multiselect('refresh')`) so the UI reflects the restored selection.
- [x] 3.3 Update `isActive()` to check for non-empty arrays (via new `hasValues()` helper) instead of truthy strings.
- [x] 3.4 Update `saveFilters`/`getFilters` callers and any place that assumes `filters.state`/`.region`/`.branch` are strings to work with arrays — `toArray()`/`hasValues()` helpers added; badge toggling (`isActive`) and modal text (unaffected, only lists bucket names) verified.
- [x] 3.5 Update `applyToParams(d, tableSelector, fieldNames)` to push one `{name, value}` entry per selected value for each field, looping `gf.state`/`gf.region`/`gf.branch` via `toArray().forEach(...)`.
- [x] 3.6 Update `setLocalFilterValue`/`prepopulateBucketLocalFilters` to set an array of values into a bucket's local field via `.multiselect('refresh')` (all sampled consumers in 1.2 are bootstrap-multiselect, not select2 — the old `change.select2` trigger was already a mismatch, now corrected).
- [x] 3.7 Handle legacy scalar values that may already exist in a user's `localStorage` from before this change — `toArray()` wraps a legacy scalar string into a single-element array on read (resolves the Open Question in design.md in favor of normalizing rather than discarding).

### Additional fix found during implementation
- [x] 3.8 Consolidated the filter bar's `bootstrap-multiselect` initialization into `globalLocationFilter.js`'s new `initMultiselects()`, called from `init()` before `writeSelectValues()`, instead of a separate inline `<script>` in `global-filter.blade.php`. Two independent `DOMContentLoaded`/`$(document).ready()` listeners had no guaranteed order, so `writeSelectValues()`'s `.multiselect('refresh')` could have auto-initialized the widget with default options before the component's own init call ran with `includeSelectAllOption`/`enableCaseInsensitiveFiltering`, silently dropping those options.

## 4. Verification

- [ ] 4.1 Manually verify: selecting multiple States/Regions/Branches in the global filter bar and clicking "Apply to all buckets" reloads in-scope buckets with all selected values applied (check network payload for repeated `{name, value}` filter entries).
- [ ] 4.2 Manually verify: "Clear" empties all three multiselects and blanks in-scope buckets' local fields.
- [ ] 4.3 Manually verify: reloading the page restores the previously applied multi-value selection in the filter bar (from `localStorage`).
- [ ] 4.4 Manually verify: a bucket with a "Local override" is unaffected by Apply, and switching it back to follow-global re-applies the current multi-value selection correctly.
- [ ] 4.5 Manually verify against at least one bucket flagged as scalar-only on the backend (e.g. one consuming `BonusReportController`/`SuppliesController` if it participates in the global filter) that behavior degrades gracefully (last-value-wins) rather than erroring.
- [ ] 4.6 Cross-browser/basic accessibility check of the `bootstrap-multiselect` dropdown inside `.filter-bar` (keyboard navigation, search box, "Select All").

## 5. Cleanup

- [x] 5.1 Run project linting/build on changed JS/Blade files — `node -c resources/js/globalLocationFilter.js` passes (valid syntax). Full `npm run development` (Laravel Mix/webpack) could not complete: `public/js/globalLocationFilter.js` is owned by `root` in this environment and the build fails with `EACCES` on write — this is a pre-existing environment permissions issue unrelated to this change, not a code defect. **Not verified**: full webpack build output; needs to be run in an environment with write access to `public/js/`, or via the project's normal deploy/build pipeline.
- [ ] 5.2 Run `openspec sync` or `/opsx:sync` to promote the delta spec into `openspec/specs/` once implementation matches the spec.
