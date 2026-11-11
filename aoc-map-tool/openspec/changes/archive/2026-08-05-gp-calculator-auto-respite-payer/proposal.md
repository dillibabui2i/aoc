## Why

The GP Calculator currently requires users to manually enable the Respite toggle whenever a client has only a respite payer. Until the toggle is enabled, the Payer field stays blank, creating unnecessary manual steps and a risk of selecting the wrong payer for clients who have no standard terminal payer.

## What Changes

- When a client is selected in the GP Calculator, automatically evaluate whether the client has a standard terminal payer (`Payer`) or only a respite payer (`RespitePayer`).
- If the client has no standard terminal payer and has one or more respite payers, automatically set the Respite toggle to ON and populate the Payer field with the respite payer, with no additional user action.
- If the client has a standard terminal payer (with or without a respite payer), keep existing behavior unchanged: Respite toggle stays OFF by default, Payer field is populated with the standard terminal payer.
- Re-evaluate and re-apply this default logic every time a different client is selected.
- No changes to GP calculation logic, validation rules, or other existing business rules.

## Capabilities

### New Capabilities
- `gp-calculator-payer-defaulting`: Automatic default selection of the Respite toggle and Payer field based on the selected client's payer configuration (standard terminal payer vs. respite-only payer).

### Modified Capabilities
(none — no existing `openspec/specs/` capabilities are defined yet for GP Calculator)

## Impact

- Backend: `App\Http\Resources\Client\ClientScheduleDropdownResource` (fields `payer`, `respiteTerminalPlayer`) and/or `app/Http/Controllers/GPCalculatorController.php::loadPayerDropdown` — candidate place to expose a payer-configuration signal so the client doesn't need to guess.
- Frontend: `resources/js/gp/model/GpCalculator.js` (`initClientAutocomplete`, `syncPayerByRespiteSwitch`, `attachRespiteToggleDelegation`) and `resources/js/gp/model/Client.js` (`clientPayer`, `respiteSwitch`, `respiteHidden`, `respiteMsg`) — where client selection currently stores `_payerNormal`/`_payerRespite` without auto-selecting respite.
- No database schema changes; `MssqlPatients.Payer` and `MssqlPatients.RespitePayer` remain the source columns.
- No API contract removals; only additive behavior in response data or client-side defaulting logic.
