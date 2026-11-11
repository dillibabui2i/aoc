# tests/

Full rationale and decision history for everything below lives in `tests/RATIONALE.md` — read
it if something here needs justifying. This file is the condensed, actionable reference for
actually writing a test. For a narrative walkthrough with worked examples for each level, see
`tests/CONTRIBUTING.md`.

## Before writing a test

Generate the skeleton, don't hand-write boilerplate:

```
php artisan make:aoc-test app/Path/To/Class.php [--integration|--feature] [--bare] [--force]
```

Takes a path under `app/`, mirrors it into the right `tests/{Unit,Integration,Feature}`
folder, picks the right base class, and — for Unit — reflects the constructor and generates
`Mockery::mock()` wiring for every typed dependency automatically. If a dependency is a
`final` class with no interface, it can't be mocked — the generator leaves a `// TODO` instead
of pretending. Fix that at the source (see "Known gaps" below) or drop `final`, don't fight it
in the test.

Default suite is Unit. Only pass `--integration`/`--feature` when the class itself directly
executes a query/HTTP call (see next section for why).

## Which suite: Unit vs Integration vs Feature

**The line is dependency injection, not the class's category.** Mock at the DI boundary:

- Class receives its I/O (Repository, Service, HTTP client) via constructor/method injection
  → mock those dependencies → **Unit**, always, regardless of what the mocked dependency does
  internally or ever will. This covers most Services, UseCases, and any custom Rule class
  written with an injected Repository instead of a static `DB::`/`Model::` call.
- Class itself directly builds/executes a query or HTTP call (it's the leaf) → **Integration**
  if it hits our own DB (`aoc_test`), via `Tests\TestCase` + `DatabaseTransactions` — never
  `RefreshDatabase`/migrations (`aoc_test` is cloned from the dev schema, not built from
  migrations).
- `app/Http/Requests` don't get a dedicated test at all — validation is asserted through the
  Feature test of the endpoint that uses the request (`assertJsonValidationErrors(...)`).
- Custom `Rule` classes (`app/Rules`, `app/PrivateAPI/Rules`) → always **Integration**
  (`tests/Integration/Rules/`), regardless of whether the class happens to take an injected
  Repository. Deliberate exception to the DI-boundary rule above: 7 of the 9 Rule classes that
  exist today call `DB::table()`/`Model::query()` statically inside `passes()` with no
  injectable seam, so splitting by DI would mean re-litigating every new Rule class on a
  case-by-case basis. Don't reach for `DB::shouldReceive()` to force one into Unit — mocking a
  query-builder chain is brittle (breaks on any non-behavioral query refactor) and proves
  nothing about the actual query, same reasoning as the mocked-connection rejection below.
- Framework glue that 100% delegates to an already-tested Service in one line (Jobs,
  Observers, Listeners, thin Console commands) doesn't get its own test — one
  Integration/Feature smoke test per process (dispatch/`$this->artisan(...)`) is enough to
  prove the wiring, not one test per glue class.
- If a class needs to talk to BOTH our own DB and an external source, don't waffle on which
  folder — split the class (inject the external source behind an interface, like
  `App\Kantime\Repository\BigQuery\*` vs `Repository\MariaDB\*`, wired together in the
  Service). The Service's test then mocks both collaborators and stays Unit; each repository
  gets its own test at whatever level it belongs to.

### Permanently untestable (no staging, ever)

- `app/Kantime/Repository/BigQuery/*` (real BigQuery) and `app/SyncProcesses/Mssql*.php` +
  `app/Helpers/Sync.php` (raw `sqlsrv_connect()`/`sqlsrv_query()` against live MSSQL) — there
  is no test instance for either and there won't be. Don't write an Integration test against
  them; don't fake coverage with a mocked-connection Unit test either — that proves nothing
  about the actual query. The only honest thing to test here is pure logic extracted out of
  the I/O (a `DataMapper::fromRow()`-style class) — Unit test that in isolation.
- **`App\Repositories\Mssql*` is NOT in this category** despite the name — those query
  `mssql_patients`/`mssql_facilities`/etc., which are ordinary tables in **our own** MySQL
  schema (populated by the `SyncProcesses` classes above), included in `aoc_test`. Test them
  as normal Integration repositories.

## Naming and docblocks

- Test methods: `it_should_...` (snake_case), not `testXxx`. PHPUnit 9 only picks up a method
  as a test if it's prefixed `test` OR has `@test` in its docblock — since these don't have
  the prefix, **`@test` is mandatory** or the method silently never runs.
- Class docblock: `@coversDefaultClass \Fully\Qualified\ClassUnderTest`.
- Each test method: `@covers ::methodName` — **the space after `@covers` is required**.
  PHPUnit's docblock parser only captures a value after whitespace; `@covers::methodName`
  parses as a bare, targetless `@covers` and won't be linked in `--coverage-*` reports (IDE
  click-navigation may still work by luck, actual coverage attribution won't).
- If the covered method calls a **private** helper internally, list that too:
  `@covers ::publicMethod` + `@covers ::privateHelper` on the same test. `@covers` doesn't
  just tag intent — it restricts which executed lines get credited to this test
  (`CodeCoverage::applyCoversAnnotationFilter`). A private method invoked only indirectly,
  through a method-scoped `@covers` that doesn't name it, will show as uncovered in the
  report even though it ran.

## Test data: Fixtures and Scenarios, never `HasFactory`

No Eloquent factories anywhere in `tests/`, even for the handful of tables that have a model
(`User`, `MssqlPatients`, `MssqlFacilities`, `LOB`). One mechanism for all ~270 tables, not two
— and `Fixture::create()` skipping Eloquent events/observers is the correct default for bare
precondition data anyway.

- **`tests/Support/Fixtures/<Name>Fixture.php`** — one table, `extends Fixture`, implements
  `table()` + `defaults()` only. `create($overrides)` inserts and returns the **full row**
  (`object`, same shape as `DB::table()->find()`) — pull `->id` or any other field from it,
  don't hardcode literals a second time in assertions.
  - Written lazily, one per table, only when a test first needs that table — not upfront.
  - Business-key tables (e.g. `lob.LOBID` referenced by `mssql_patients.LOBID`, not `lob.id`)
    need no special handling — the field is just on the returned record.
  - Reusable attribute presets → a named state method calling `$this->state([...])` (protected,
    clone-based, mirrors Laravel factory states), e.g.
    `MssqlPatientsFixture::eligibleForCccCalls()`.
- **`tests/Support/Scenarios/<Name>Scenario.php`** — multiple tables/Fixtures wired together
  into one business concept (e.g. `EligibleCccCallPatientScenario` = patient + facility + lob
  wired so every eligibility rule passes by default). At this project's scale, write these
  immediately when a composite is needed — don't wait for a second consumer the way single
  Fixtures do. No shared base class (scenario shapes vary too much to force one); usually just
  `use ResolvesOverrides` + `create(array $overrides = []): object`.
  **Mandatory class docblock**, fully describing the scenario: what it represents, which real
  method/rule it backs, every condition it guarantees by default (one per line), and how to
  break a specific one via `$overrides`. This docblock is the only place the business rule
  actually gets written down — treat it as documentation, not boilerplate. See
  `EligibleCccCallPatientScenario` for the template.
- **`Tests\Support\ResolvesOverrides::resolveOverrides(defaults, overrides)`** — merges, then
  calls any default still left as a `Closure` (i.e. not overridden) — for defaults that need a
  side effect (create a related Fixture) only when the caller didn't supply the value.
  `Fixture::make()` already uses this; use it directly in Scenarios too.

## Base classes

- `Tests\TestCase` — boots the app. Default for everything except pure-logic classes.
  Add `DatabaseTransactions` for Integration/Feature. A Unit test that only needs the
  container for `Mockery`/`Http::fake()`/global helpers like `logger()` still uses this — it's
  still Unit as long as no real DB row or network call happens.
- `Tests\UnitTestCase` — bare `PHPUnit\Framework\TestCase`, no boot at all. Only for classes
  with zero container dependency: DTO, Enum, pure Transformer, extracted DataMapper. Generator
  flag: `--bare`.

## Running

```
composer test              # everything
composer test:unit         # fast, no DB
composer test:integration  # needs aoc_test — container only
composer test:feature      # needs aoc_test — container only
./vendor/bin/phpunit path/to/OneTest.php                    # one file
./vendor/bin/phpunit --filter it_should_do_x path/to/File   # one method
```
Host PHP is incompatible with this Laravel version — Integration/Feature must run inside the
app container. `phpunit.xml` pins `DB_DATABASE=aoc_test` so this holds regardless of how the
suite is invoked — don't remove that override, without it tests silently fall back to `.env`'s
`DB_DATABASE` (the real dev database).

If `aoc_test` needs building or rebuilding: `php artisan test:build-db`. Full explanation of
why this exists (migrating from scratch doesn't work — incomplete migration history) and the
one-time setup step for a brand new Docker volume is in `tests/README.md`.

## Known gaps (don't silently work around, flag instead)

- `App\Kantime\Repository\MariaDB\ProvidersRepository` / `RenewablesRepository` are `final`
  with no interface → Mockery can't mock them, blocking a proper Unit test of
  `RenewablesSyncService`. Not fixed yet — resolve by dropping `final` or extracting an
  interface (symmetric with `KantimeRepositoryInterface` on the BigQuery side) when that test
  is actually written.
- `tests/Feature/Kantime/RenewablesSyncServiceTest.php` is misplaced: the service mocks its
  BigQuery collaborator (via `Mockery::mock('overload:' . BigQueryConnection::class)`) but
  calls the real (unmocked) MariaDB repositories against `aoc_test` — real I/O, so it currently
  behaves as Integration despite living in `Feature/` and despite the service's own DI shape
  (all collaborators injected) putting it in Unit territory once `ProvidersRepository`/
  `RenewablesRepository` stop being `final`. Move to `tests/Unit/Kantime/Services/` and mock
  both repositories once that's fixed (see the `final` gap above).
- `tests/Integration/VBC/Http/Requests/**/*RequestTest.php` stub files should be deleted, not
  filled in — Requests don't get a dedicated test (see "Which suite" above); their coverage
  belongs in the corresponding `*ControllerTest.php`.
