## Context

The Maps module (`app/Http/Controllers/MapAbstractController.php`) is legacy jQuery/Blade code, not part of the active-development directories called out in the project `CLAUDE.md`. Each "map" is a row in `map_configurations` (`app/Map.php`, PK `map_id`, no Eloquent casts on the JSON-ish text columns) paired with a dynamically created physical table (`map<N>`, or a custom name like `maps_bon_tx`) holding the actual rows. The currently "active" map for a browser session is tracked via `Session::get('selectedmap')` (`H::getSelectedMap()`/`H::getSelectedMapInfo()`), set whenever a user clicks a row's action icon in Manage Maps (`MapAbstractController::editmap()`, `MapAbstractController.php:1215-1228`, `Session::put('selectedmap', $id)` at line 1222).

Both the "Map Configuration" screen (`/map/configurations`) and its save endpoint (`/map/saveconfigurations` → `saveConfigurations()`) resolve "which map" purely from that session value — the configuration form itself carries no identifier for the primary map. This was confirmed to be the root cause of AOC-4409: a user editing what they believed was a newly created "BON - LA" map instead had "BON - TX" (`map_id 30`) selected in session, and the save silently renamed/reconfigured "BON - TX" instead.

Two other related defects were found during investigation (`H::getChunkedCsvData()` not halting on a mid-import field-mismatch error, and no uniqueness validation on `map_name`) but are deliberately excluded from this change — see Non-Goals.

## Goals / Non-Goals

**Goals:**
- Make `saveConfigurations()` provably operate on the map the submitted form actually corresponds to, not merely whatever the session happens to hold at request time.

**Non-Goals:**
- No change to how the "selected map" session mechanism works for read-only navigation (viewing map results, list results, import screen) — only the primary-map save path in `saveConfigurations()` is in scope.
- No change to `H::getChunkedCsvData()` / CSV import halting behavior in this change — tracked as a follow-up.
- No `map_name` uniqueness validation in this change — tracked as a follow-up.
- No retroactive migration of existing duplicate-named or already-corrupted rows — the specific "BON - TX" incident is remediated separately via a direct data fix, not by this code change.
- No change to the layer-save sub-flow (`saveConfigurations($request, true)`) beyond confirming it is unaffected — each layer already posts its own `map_id`.

## Decisions

- **Bind the primary-map save to a submitted `map_id`, not just session.** Add a hidden `<input type="hidden" name="map_id" value="{{ $mapInfo[0]->map_id }}">` to `resources/views/map/configurations.blade.php`, mirroring the existing per-layer pattern (`layers[{{$idx}}][map_id]`, line 151). In `saveConfigurations()`, before building `$mapData`, compare the submitted `$data['map_id']` against `$mapInfo[0]->map_id` (still sourced from session); if they don't match, abort with a "map selection has changed, please reload and try again" error rather than saving. This keeps session as the source of truth for *which* map's form was rendered, while using the posted value purely as a staleness guard against a session/UI mismatch — it does not require trusting a client-supplied `map_id` as authorization, since it must match what the server itself put in the form.
  - Alternative considered: switch the whole flow to a route parameter (`/map/configurations/{id}`) instead of session. Rejected as a much larger change touching every link into this screen and the layer sub-flow, for a fix that the hidden-field + match-check achieves with far less blast radius.

## Risks / Trade-offs

- [Hidden `map_id` guard rejects a legitimate save if a user has the configuration page open in two tabs for different maps and submits the stale one] → this is the intended behavior (matches "please reload" pattern used elsewhere), and is strictly safer than the current silent-overwrite behavior.
- [The CSV-import-halt and map_name-uniqueness defects remain unfixed after this change ships] → accepted for now, tracked as follow-up work; this change alone already closes the specific mechanism that caused AOC-4409 (wrong map silently overwritten via a stale session selection).

## Migration Plan

Pure application-code change, no schema migration, no data migration. Deploy behind normal release process. Rollback is a plain revert of the controller/view changes — no persisted state introduced. The AOC-4409 "BON - TX" data remediation is handled independently (direct SQL against the affected row), not gated on this change shipping.

## Open Questions

- Should `H::getChunkedCsvData()`'s halt-on-error behavior and `map_name` uniqueness validation be picked up as a follow-up change once this fix is validated in production?
