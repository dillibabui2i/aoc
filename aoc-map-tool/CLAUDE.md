# aoc-map-tool
A Laravel 9 (PHP 8.2+) monolith with a mix of jQuery/Blade legacy code and newer Vue 3 modules.

## Legacy vs active development
Most of `app/` and `resources/js/` is **legacy code** (jQuery/Blade, years of accumulated business logic, often untested and without a clear architecture). Change it carefully, optimizing for safety and backward compatibility rather than refactoring for its own sake.

**Not legacy** (newer architecture, where most active development happens):
- `app/VBC` — Value-Based Care module **backend**: a pure REST API (Laravel). Has its own `CLAUDE.md` at `app/VBC/CLAUDE.md` with pattern details.
- `resources/js/vue/modules/vbc` — Value-Based Care module **frontend**: the Vue 3 (Composition API) client for `app/VBC`.
- `app/Kantime` — Kantime integration.
- `app/PrivateAPI` — private API endpoints for OS (Mobile).
- `app/Enums` — PHP 8.1 backed enums (shared across the project).
- `app/DTO` — Data Transfer Objects (shared across the project).
- `app/Contracts` — interfaces.
- `app/Entity` — entity classes.
- `app/Events` — domain events.
- `app/Factories` — model/object factories.
- `app/Jobs` — queued jobs.
- `app/Listeners` — event listeners.
- `app/Observers` — Eloquent model observers.
- `app/Repositories` — repository classes.
- `app/Rules` — custom validation rules.
- `app/Services` — business logic services.
- `app/Transformer` — data transformers.
- `app/UseCases` — use case classes.
- `resources/js/vue` — Vue 3 (Composition API) frontend modules.

Everything else in `app/` (controllers, models, services outside these directories) and the rest of `resources/js/` (jQuery, Blade-oriented scripts) is legacy.

## VBC tasks: read the module docs first
Before planning or editing anything that touches `app/VBC` or `resources/js/vue/modules/vbc`,
read BOTH `app/VBC/CLAUDE.md` and `resources/js/vue/CLAUDE.md` in full. This is the first step,
before exploring code or delegating to subagents. When delegating, tell the subagent to read the
relevant file too. Before finishing, check the diff against their rules (layering, required-field
messages, tokens, lint/format).

## Enums
Before every implementation, check whether an enum for that context already exists in `app/Enums` (subdirectories per domain) and reuse it. 
Do not scatter string/int literals for a set that is already modelled, and do not declare a second enum for it. 
If nothing fits, add a backed `*Enum.php` there.

## Backend tests: follow `tests/` rules strictly
Whenever a backend (PHPUnit) test is written, changed or fixed, first read `tests/CLAUDE.md` (and
`tests/CONTRIBUTING.md` / `tests/RATIONALE.md` when something needs detail) and follow every rule in
them strictly — suite choice (Unit/Integration/Feature), `it_should_*` names with `@test`,
`@coversDefaultClass` / `@covers ::method`, and test data only through `tests/Support` Fixtures and
Scenarios (no `HasFactory`/`factory()`, no raw `DB::table()->insert`, no reliance on rows already present
in `aoc_test` — it is a schema-only clone). This also applies to `app/VBC` tests; the testing notes in
`app/VBC/CLAUDE.md` do not override `tests/CLAUDE.md`. Before finishing, check the diff against these rules.

## Searching the codebase

Prefer `rg`/`fd` over `grep -R`/`find`.
