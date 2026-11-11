## 1. Investigation

- [x] 1.1 Confirm root cause of the AOC-4409 "BON - TX" corruption by diffing a pre-incident `map_configurations` row against the current production row — confirmed only `map_name`, `fields_on_map`, `fields_datatable`, `latlong_fields`, `map_preferences`, `map_status` differ; the underlying `maps_bon_tx` data table row count is unchanged.
- [x] 1.2 Trace how `/map/configurations` and `saveConfigurations()` determine which map is being edited — confirmed both rely solely on `Session::get('selectedmap')` via `H::getSelectedMapInfo()`, with no `map_id` posted for the primary map (`MapAbstractController.php:121-217`, `resources/views/map/configurations.blade.php`).

## 2. Fix: bind primary-map save to the rendered map_id

- [x] 2.1 Add a hidden `map_id` input for the primary map in `resources/views/map/configurations.blade.php` (mirroring the existing `layers[{{$idx}}][map_id]` pattern), populated from the same `$mapInfo[0]->map_id` already used to render the rest of the form — added at `configurations.blade.php:90` right after `@csrf`.
- [x] 2.2 In `MapAbstractController::saveConfigurations()` (non-layer branch), before building `$mapData`, compare the posted `map_id` against the session-derived `$mapInfo[0]->map_id`; if they differ, redirect back with an error (e.g. "Map selection has changed, please reload and try again") instead of proceeding to `update()` — added at `MapAbstractController.php:190-193`, inside the existing `else` branch, before any `$mapData` mutation for that branch.
- [x] 2.3 Verify the recursive layer-save call (`saveConfigurations($layerRequest, true)`, line ~164) is unaffected, since each layer already posts its own `map_id` and takes the `$isLayer` branch (`$tableId = $data['map_id']`) rather than the session-derived one — confirmed: the layer call always passes `$isLayer = true`, routing into the `if ($isLayer)` branch (line 183), which never reaches the new guard in the `else` branch.

## 3. Verification

- [ ] 3.1 Manually verify: opening `/map/configurations` for map A in one tab, then map B in another tab (switching session selection), then submitting the stale map-A form is rejected instead of silently overwriting map B.
- [ ] 3.2 Manually verify no regression to the normal (single-tab, no session change) create/rename flow for existing BON maps.
- [ ] 3.3 Manually verify the layer-save sub-flow (adding/editing a layer) still works unchanged.

## 4. Cleanup

- [x] 4.1 Run project linting/static analysis on changed PHP/Blade files per repo convention — `php -l` passed with no syntax errors on both `MapAbstractController.php` and `configurations.blade.php`.
- [ ] 4.2 Run `openspec sync` / `/opsx:sync` to promote the delta spec into `openspec/specs/` once implementation matches the spec.
