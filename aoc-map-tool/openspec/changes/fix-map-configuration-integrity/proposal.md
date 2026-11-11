## Why

A production incident (AOC-4409) corrupted the "BON - TX" map: its `map_configurations` row was renamed to "BON - LA" and its field mappings (`fields_on_map`, `fields_datatable`, `latlong_fields`) were wiped, while a second, unrelated "BON - LA" map (`map_id 92`) was created around the same time. The row-level diff against a pre-incident copy of `map_configurations` confirmed the underlying `maps_bon_tx` data table was never touched — only the configuration row was overwritten.

Root cause: **`saveConfigurations()` resolves the map to update purely from session state, not from the submitted form** (`app/Http/Controllers/MapAbstractController.php:121-217`, specifically `$tableId = $mapInfo[0]->map_id` at line 207, where `$mapInfo = H::getSelectedMapInfo()` reads `Session::get('selectedmap')`). The `/map/configurations` form has no hidden `map_id` for the primary map being edited (only per-layer `map_id` inputs exist, e.g. `resources/views/map/configurations.blade.php:151`). If the session's "selected map" differs from what the user believes they are editing (e.g. a stale selection from a previous action), the save silently overwrites the wrong map's name and field configuration.

Two other related defects were found during investigation (CSV import not halting immediately on a field-mismatch error in `H::getChunkedCsvData()`, and no uniqueness validation on `map_name`) but are **explicitly out of scope for this change** — scoped down to the configuration table/form fix only, pending confidence in this fix before expanding further.

## What Changes

- `saveConfigurations()` (non-layer/primary-map path) will require the form to submit the `map_id` of the map being configured (new hidden input in `resources/views/map/configurations.blade.php`), and will reject the save if that submitted `map_id` does not match the current session-selected map, instead of trusting session state unconditionally.
- No changes to CSV import handling, `map_name` uniqueness, CSV column structure, geocoding logic, map rendering, or any route/endpoint signature beyond the added validation and the new hidden field.

## Capabilities

### New Capabilities
- `map-configuration-integrity`: Map configuration saves are always applied to the map the user actually intends to edit.

### Modified Capabilities
(none — no existing `openspec/specs/` capabilities cover Maps yet)

## Impact

- Backend: `app/Http/Controllers/MapAbstractController.php` — `saveConfigurations()` (~121-217).
- Frontend: `resources/views/map/configurations.blade.php` — add a hidden `map_id` input for the primary map (pattern already exists for `layers[{{$idx}}][map_id]`).
- Data: no schema changes.

## Out of Scope (candidates for a follow-up change)

- `H::getChunkedCsvData()` (`app/Helpers/H.php:1470-1498`) not halting on first field-mismatch error mid-import.
- No uniqueness validation on `map_name` at creation (`savemap()`) or rename time.
