## Context

The GP Calculator client-selection flow (`resources/js/gp/model/GpCalculator.js`) already receives both payer values for a selected client in a single response, via `App\Http\Resources\Client\ClientScheduleDropdownResource`:
- `payer` → standard terminal payer (`MssqlPatients.Payer`)
- `respiteTerminalPlayer` → respite payer (`MssqlPatients.RespitePayer`)

`initClientAutocomplete` (GpCalculator.js:610-716) stores these as `client._payerNormal` / `client._payerRespite`. The Respite toggle is a plain checkbox whose state is read by `attachRespiteToggleDelegation` (GpCalculator.js:1075-1127) and `syncPayerByRespiteSwitch` (GpCalculator.js:1153-1249), which populates the Payer dropdown either from a locally cached value or via `GET /gp-calculator/loadPayerDropdown?isRespite=...` (`GPCalculatorController::loadPayerDropdown`, lines 763-777). Today, nothing inspects `_payerNormal`/`_payerRespite` at selection time to decide the toggle's default state — the toggle only reacts to explicit user clicks, and `Client.js` shows a warning (`respiteMsg`) if respite is on but `RespitePayer` is empty, rather than fixing the default.

`isRespite` is not persisted; it is purely UI state passed as a query param.

## Goals / Non-Goals

**Goals:**
- On client selection (initial and on re-selection), automatically derive the Respite toggle state and Payer field value from the client's existing `payer` / `respiteTerminalPlayer` data, with no extra manual toggle click when the client has no standard terminal payer.
- Keep the decision derivable from data already returned by the existing client-selection endpoint (no new endpoint, no schema change).

**Non-Goals:**
- No changes to GP calculation logic, discipline/service filtering (`getService`), or payer rate resolution.
- No changes to the `MssqlPatients` schema or to `ConstantHelper::getPayers`/`getRespitePayers`.
- No change to manual toggle behavior once a user explicitly overrides the default after selection.

## Decisions

- **Frontend-only defaulting, no backend change.** `ClientScheduleDropdownResource` already exposes both `payer` and `respiteTerminalPlayer` per client, so the "does this client have a standard terminal payer" check can be done client-side (`!client._payerNormal && client._payerRespite`) inside `initClientAutocomplete`, immediately after `_payerNormal`/`_payerRespite` are set. Alternative considered: add a computed `hasStandardPayer` flag server-side — rejected as unnecessary indirection since the raw values are already present and the emptiness check is trivial.
- **Reuse `syncPayerByRespiteSwitch` instead of duplicating its dropdown-loading logic.** When the auto-detected case applies, programmatically set the Respite checkbox to `checked` and invoke the same code path `attachRespiteToggleDelegation` uses on manual toggle (triggering `loadPayerDropdown?isRespite=true` then selecting `client._payerRespite` in the populated dropdown), instead of writing a second payer-resolution routine. This keeps AC5 (re-evaluate on client change) and the existing respite-dropdown-population behavior in one place.
- **Standard terminal payer always wins when present.** Per AC3/AC4, if `_payerNormal` is non-empty, the toggle defaults OFF and the standard payer populates the field regardless of whether a respite payer also exists — this matches current behavior, so no branch changes are needed for that path, only the new branch for "no standard payer".

## Risks / Trade-offs

- [Payer dropdown is populated asynchronously via `loadPayerDropdown`] → the auto-selected respite payer must be applied only after that response resolves (existing pattern in `syncPayerByRespiteSwitch` already awaits this; reuse avoids a race).
- [`respiteMsg` warning currently fires when respite is on and `RespitePayer` is empty] → once auto-defaulting only turns the toggle on when `_payerRespite` is non-empty, this warning path becomes unreachable for the auto-triggered case; verify it still fires correctly for manual toggle misuse.
- [`V2\GpCalculatorController` exists alongside the legacy controller] → confirm during implementation whether the V2 flow shares the same frontend client-selection code path or needs a parallel change.

## Migration Plan

Frontend-only change behind normal deploy; no data migration. Verify manually against AC1–AC7 scenarios in a lower environment before release. Rollback is a plain revert of the JS change (no persisted state introduced).

## Open Questions

- Does `V2\GpCalculatorController` / any V2 frontend counterpart need the same fix, or is GP Calculator V1 the only active surface?
