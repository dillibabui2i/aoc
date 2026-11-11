## Context

`resources/views/components/global-filter.blade.php` renders three plain native `<select name="state|region|branch">` elements, populated from `$stateOptions`/`$regionOptions`/`$branchOptions` supplied by `App\View\Composers\GlobalLocationFilterComposer` (registered only for `account.dashboard`). `resources/js/globalLocationFilter.js` drives the bar:
- `readSelectValues`/`getSelectValue` read each select's `.value` (single string).
- `writeSelectValues` sets `.value` on load/clear.
- Selections persist to `localStorage` (key `globalLocationFilter`) as `{state, region, branch, appliedAt}` — all scalars.
- `applyToParams(d, tableSelector, fieldNames)` is the hook every "in-scope" bucket's own DataTable `ajax.data` callback calls; it currently pushes **one `{name, value}` entry with a scalar `value`** per field into `d.filters`.
- Buckets not overridden locally get pre-populated via `prepopulateBucketLocalFilters`, which also treats each field as scalar.

Elsewhere on the same dashboard, `resources/views/components/datatable-filters.blade.php` already renders `type: multiselect` fields as `<select multiple class="selectpicker" data-live-search="true">`, initialized via `$('.selectpicker').multiselect({ includeSelectAllOption: true, ... })`. Those buckets build `d.filters` via jQuery's `$(form).serializeArray()`, which — for a multi-select — naturally emits **one `{name, value}` pair per selected option, all sharing the same `name`**. Backend code (e.g. `NeedToHireController.php`, `RecruitingController.php`, `app/Helpers/CommonDatatableFilters.php::generateQuery`, `app/Helpers/Activity.php`) already accumulates repeated same-named `filters` entries into an array and applies `whereIn`/`orWhere` loops — i.e. the backend already tolerates multi-value `state`/`region`/`aoc_facility_id` filters, as long as they arrive in this repeated-entry shape rather than as a single entry with an array `value`.

## Goals / Non-Goals

**Goals:**
- Global Location Filter's State, Region, and Branch dropdowns become multi-select, visually and behaviorally consistent with `datatable-filters.blade.php`'s `selectpicker` multiselects (search box, "Select All", same widget).
- Users can select multiple values per field, apply them, and have the selections persist (localStorage) and restore correctly (including after page reload and after Clear).
- `applyToParams()` emits filter entries in the same repeated-`{name,value}`-per-selection shape `serializeArray()` produces, so buckets whose backend already accumulates repeated entries (the common pattern) start honoring multiple selections with no backend change.

**Non-Goals (frontend-only scope):**
- No backend controller/query changes. Buckets whose backend treats `state`/`region`/`aoc_facility_id` as a pure scalar (last value wins, no `whereIn`/accumulation) are a known limitation, not fixed here.
- No change to the set of buckets that participate in the global filter (`.card[data-follows-global="true"]`), the "Local override" badge/modal mechanism, or the option lists supplied by `GlobalLocationFilterComposer`.
- No change to `datatable-filters.blade.php` itself — it is the reference pattern, not a target of this change.

## Decisions

- **Reuse `bootstrap-multiselect` exactly as `datatable-filters.blade.php` does**, rather than introducing a different widget. The plugin's JS/CSS (`bootstrap-multiselect.min.js/css`) is already loaded globally via `resources/views/layouts/myaccount.blade.php`, so no new asset/build changes are needed — only markup (`multiple`, `class="selectpicker"`, `data-placeholder`, `data-live-search="true"`) and an init call (`$('.filter-bar .selectpicker').multiselect({ includeSelectAllOption: true })`), matching `datatable-filters.blade.php`'s init block.
- **Persist selections as arrays, keep the `localStorage` key and top-level shape unchanged** (`{state, region, branch, appliedAt}`), only changing each field's value from `string` to `string[]`. This limits the blast radius to `globalLocationFilter.js` internals and keeps `isActive()`/badge logic working with a straightforward "array is non-empty" check instead of "string is non-empty".
- **`applyToParams()` switches from pushing a single scalar entry to pushing one entry per selected value** (`gf.state.forEach(v => d.filters.push({name: stateName, value: v}))`), matching the `serializeArray()` convention already used by multiselect buckets elsewhere. This is the one behavioral change with downstream effect: it changes what's sent to bucket backends, even though no backend code is touched in this change.
- **`prepopulateBucketLocalFilters`/`setLocalFilterValue`**, which writes global values into a bucket's own local filter inputs when the bucket has no override, needs to set multiple selected options (and trigger `multiselect('refresh')`/`change.select2` as appropriate) instead of a single `.val(value)`. Exact mechanism depends on whether the target local field is itself a `selectpicker` (bootstrap-multiselect) or a `select2` field — verify per existing bucket field type during implementation (do not assume one widget for all consumers).

## Risks / Trade-offs

- [`applyToParams` now sends repeated `{name, value}` entries instead of one scalar entry] → any bucket controller that reads `filters` expecting exactly one entry per field name for `state`/`region`/`aoc_facility_id` and treats a repeated entry as "value gets overwritten by the last one" will silently apply only the last selected value instead of erroring — acceptable per this change's frontend-only scope, but should be called out to reviewers/QA as a known gap for those buckets (e.g. flagged in backend research: `BonusReportController.php`, `SuppliesController.php` treat these fields as scalars).
- [Selecting many values in State/Region/Branch increases the number of `{name, value}` entries sent per bucket ajax request] → for buckets already handling multiselect fields (e.g. `aoc_facility_id`) this is the existing, accepted pattern; no new performance concern introduced.
- [Local-filter pre-population (`prepopulateBucketLocalFilters`) may target select2 or bootstrap-multiselect fields depending on the bucket] → mis-set widget refresh calls could leave a bucket's local UI visually out of sync with its actual selected value; must be verified against a representative sample of in-scope buckets during implementation/QA, not just the global filter bar itself.

## Migration Plan

Frontend-only change, no data migration. `localStorage` payload shape changes (`state`/`region`/`branch` become arrays); no explicit migration is needed since the key is transient client state — a leftover scalar value from a prior session should be tolerated (normalize on read: wrap a legacy scalar in an array, or simply treat as "no filter" if that's simpler) rather than assumed absent. Roll out behind normal deploy; rollback is a plain revert of the changed view + JS file.

## Open Questions

- Should stale `localStorage` values saved before this change (scalars) be normalized into arrays on read, or is it acceptable to just clear/ignore them (since they only affect a client's own previously-applied filter, not shared state)?
- Which in-scope buckets' backend filter handling is scalar-only for `state`/`region`/`aoc_facility_id` today (beyond the two flagged during research: `BonusReportController.php`, `SuppliesController.php`)? Worth a follow-up backend change once identified.
