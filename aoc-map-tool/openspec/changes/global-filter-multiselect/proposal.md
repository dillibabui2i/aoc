## Why

The Global Location Filter (`resources/views/components/global-filter.blade.php`, rendered on `account/dashboard.blade.php`) only lets a user pick one State, one Region, and one Branch at a time via plain native `<select>` elements. Every other filter bar in the app (`resources/views/components/datatable-filters.blade.php`, used across dozens of buckets on the same dashboard) already supports multi-select dropdowns via `bootstrap-multiselect`. Users need to filter multiple states/regions/branches at once from the global filter the same way they already can from a bucket's own local filters.

## What Changes

- Convert the Global Location Filter's State, Region, and Branch `<select>` elements to multi-select dropdowns using the same `bootstrap-multiselect` widget already used by `datatable-filters.blade.php` (`selectpicker` styling, "Select All" option, search box), so the UI and interaction pattern match.
- Update `resources/js/globalLocationFilter.js` so reading/writing/persisting the filter bar's selections works with arrays of values instead of single scalars: `localStorage` payload (`state`/`region`/`branch`), `readSelectValues`/`writeSelectValues`, `isActive()`, and `applyToParams()` (which will emit one `{name, value}` filter entry per selected value — the same convention `serializeArray()` already produces for existing multi-select bucket filters, so it plugs into backend code that already accumulates repeated same-named entries).
- Frontend-only: no backend controller/query changes are in scope. Buckets whose backend filter handling already treats `state`/`region`/`aoc_facility_id` as accumulated repeated entries (the common pattern) will start correctly honoring multiple selections; buckets whose backend code treats these fields as pure scalars will keep working (last/only value wins) but won't gain true multi-value filtering until a separate backend change addresses them.

## Capabilities

### New Capabilities
- `global-filter-multiselect`: The Global Location Filter (State/Region/Branch) supports selecting and applying multiple values per field, with the same multi-select UI/UX as the existing datatable filter bars.

### Modified Capabilities
(none — no existing `openspec/specs/` capability currently documents the Global Location Filter)

## Impact

- Frontend view: `resources/views/components/global-filter.blade.php` — convert 3 `<select>` elements to `<select multiple>` with `selectpicker`/`data-live-search` markup matching `datatable-filters.blade.php`; add multiselect initialization (`$(...).multiselect(...)`) inline or via `globalLocationFilter.js`.
- Frontend JS: `resources/js/globalLocationFilter.js` — `readSelectValues`, `getSelectValue`, `writeSelectValues`, `isActive`, `saveFilters`/`getFilters` payload shape, `applyToParams`, and the "Local override" modal/badge logic (anything that reads `filters.state`/`filters.region`/`filters.branch` as a scalar) need to handle arrays.
- No changes to `app/View/Composers/GlobalLocationFilterComposer.php` (option sources `$stateOptions`/`$regionOptions`/`$branchOptions` are unaffected — same option lists, just multi-selectable).
- No backend controller/query changes in this change; downstream buckets with scalar-only backend handling of `state`/`region`/`aoc_facility_id` are a known, out-of-scope limitation (see design.md Non-Goals).
