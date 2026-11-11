## 1. Investigation

- [x] 1.1 Confirm whether `App\Http\Controllers\V2\GpCalculatorController` and its frontend counterpart share `resources/js/gp/model/GpCalculator.js` client-selection code, or need a separate fix (resolves Open Question in design.md) — confirmed: V2 (`indexV2.blade.php`) is the sole consumer of `resources/js/gp/gp-calculator.js` / `GpCalculator.js`. V1 (`index.blade.php`) has its own separate inline jQuery script with the same underlying issue, but per user decision this change is scoped to V2 only.
- [x] 1.2 Confirm the exact shape of `client._payerNormal` / `client._payerRespite` when a value is absent (empty string vs `null` vs `undefined`) to write a correct emptiness check — confirmed via `GpCalculator.js:680-681`: both are normalized to `''` via `?? ''`, and `ui.item.payer`/`ui.item.respiteTerminalPlayer` can independently be `''`, `null`, or `undefined` from the API; `Boolean(...)` treats all of these as absent.

## 2. Frontend: auto-defaulting logic

- [x] 2.1 In `resources/js/gp/model/GpCalculator.js::initClientAutocomplete`, after `_payerNormal`/`_payerRespite` are set, add a check: no standard terminal payer AND one or more respite payers present — implemented at `GpCalculator.js:653-656` using `ui.item.payer`/`ui.item.respiteTerminalPlayer` directly (evaluated before the `_payerNormal`/`_payerRespite` assignment, same source data).
- [x] 2.2 When the check in 2.1 is true, programmatically set the Respite toggle checkbox to checked — `GpCalculator.js:658`; the checkbox is also explicitly reset to unchecked when the check is false, since `isRespite` now always reflects the freshly evaluated default rather than leftover checkbox state.
- [x] 2.3 Trigger the same code path used by `attachRespiteToggleDelegation`/`syncPayerByRespiteSwitch` so `GET /gp-calculator/loadPayerDropdown?isRespite=true` is called and the client's respite payer is selected in the populated dropdown once it resolves — **correction after manual QA**: an earlier version of this task incorrectly assumed the Payer `<select>` already contained respite payer options and skipped the AJAX reload, which caused the toggle to switch ON but the Payer field to stay blank (respite payer values have no matching `<option>` in the page-rendered standard-payer-only list). Fixed by making the `select` callback `async` and calling `await this.syncPayerByRespiteSwitch({ row, client, isRespite })` (`GpCalculator.js:692-696`) instead of setting `.val()` directly.
- [x] 2.4 When the client has a standard terminal payer (with or without a respite payer), verify no behavior change: Respite toggle stays OFF, standard payer populates the Payer field — `isRespite = !hasStandardPayer && hasRespitePayer` is `false` whenever a standard payer exists, so the existing `payerVal = isRespite ? respiteTerminalPlayer : payer` branch resolves to the standard payer, matching prior behavior.
- [x] 2.5 Ensure re-selecting a different client re-runs the evaluation in 2.1-2.4 (no stale toggle/payer state carried over from the previous client) — the `select` callback recomputes `isRespite` from scratch on every selection (no dependency on the previous checkbox state), so switching clients always re-evaluates.
- [x] 2.6 Review `resources/js/gp/model/Client.js` `respiteMsg` warning logic to confirm it no longer fires for the auto-selected case (respite payer is guaranteed non-empty when auto-enabling) — confirmed: the warning in `GpCalculator.js:708-710` only shows when `isRespite && !payerVal`; auto-enabled `isRespite` is only `true` when `respiteTerminalPlayer` is truthy, so `payerVal` is never empty in that case.

## 3. Verification against acceptance criteria

- [ ] 3.1 Manually verify AC1/AC2: client with only a respite payer auto-enables toggle and populates Payer field with no manual action
- [ ] 3.2 Manually verify AC3: client with both standard and respite payers keeps toggle OFF and standard payer in Payer field
- [ ] 3.3 Manually verify AC4: client with only a standard terminal payer keeps toggle OFF and standard payer in Payer field
- [ ] 3.4 Manually verify AC5: switching between clients with different payer configurations re-applies correct defaults each time
- [ ] 3.5 Manually verify AC6: Payer field is populated immediately once client data finishes loading, no flash of blank/incorrect value
- [ ] 3.6 Manually verify AC7: existing GP calculations, validations, and discipline/service filtering (`getService`) are unaffected

## 4. Cleanup

- [x] 4.1 Run project linting on changed JS files (per repo convention, e.g. `make npm` if applicable) — `make npm` (webpack/Laravel Mix build) compiled `js/gp/gp-calculator.js` successfully with no errors
- [ ] 4.2 Run `openspec sync` or `/opsx:sync` to promote the delta spec into `openspec/specs/` once implementation matches the spec
