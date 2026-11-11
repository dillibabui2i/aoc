# PHPUnit test structure — rationale

Full reasoning and decision history behind everything in `tests/CLAUDE.md` (the condensed
actionable reference) and `tests/CONTRIBUTING.md` (the worked-examples guide). Organizes
`tests/` in terms of **Unit / Integration / Feature**, accounting for how this project is
actually built (legacy `app/` + newer architecture: VBC, Kantime, PrivateAPI, Services,
Repositories, UseCases, DTO, Enums).

## Three levels — why not just Unit/Feature

The project talks to several external systems (MySQL, MSSQL, BigQuery, external HTTP APIs like
FirstAdvantage/Kantime), so the usual Laravel Unit/Feature split isn't enough — "does the test
boot the app" and "does the test hit a real DB/external service" are two independent questions.
Hence three levels:

| Level | Boots the app | Hits a real DB / external service | What it verifies |
|---|---|---|---|
| **Unit** | no | no — every injected collaborator (Repository/Service/HTTP client) is mocked | A single class's own logic: branching, orchestration, error handling — including Services/UseCases with a mocked Repository |
| **Integration** | yes | yes (`aoc_test` for repositories on our own DB, `Http::fake()` where real framework wiring is needed) | The repository leaf itself (actually running the query) against `aoc_test`; framework wiring (artisan commands, job/observer dispatch) as a smoke test on the real stack |
| **Feature** | yes | yes | The full HTTP request path: route → middleware → controller → response (JSON/HTML), including Requests validation |

## Principle: the boundary is dependency injection, not class category

To avoid shuffling test files around on every refactor, and to avoid re-litigating Unit vs
Integration per namespace, the boundary is drawn on one criterion: **what the class does
itself, versus what it delegates to injected collaborators.**

- If a class's I/O dependencies arrive through the constructor (a Repository, another Service,
  an HTTP client behind an interface) — **those dependencies get mocked**, and the class is
  tested as Unit. This covers Services, UseCases, and custom Rule classes written with DI
  instead of a static `DB::`/`Model::query()` call inside them. Example:
  `RenewablesSyncService` mocks both `KantimeRepositoryInterface` (BigQuery) and
  `RenewablesRepository`/`ProvidersRepository` (MariaDB) — the test only checks the service's
  own branching logic (invalid record → delete, provider not found → skip, insert vs update),
  and that's an honest, fast Unit test.
- This test **doesn't move** when the dependency gets refactored: if `RenewablesRepository`'s
  internals change tomorrow (different DB, different query approach), the service test doesn't
  need to know or change, because it only sees the collaborator's public contract, not its
  internals.
- **Integration stays reserved for classes that themselves directly execute the query** — i.e.
  the repository leaf (`RenewablesRepository`, `MssqlFacilitiesRepository`, etc). Its
  correctness against `aoc_test` (when it's our own DB) is checked by a dedicated test of that
  repository itself, once, in one place; MSSQL/BigQuery leaves — see the permanent-gap section
  below.
- **Rules are a deliberate exception to the DI boundary: every Rule class → Integration, with
  no split based on whether a given class happens to have an injectable Repository.** Checked
  against the real inventory: `app/Rules/` has 2 classes (`IsValidPassword`, `ReCaptcha`), both
  with zero I/O; `app/PrivateAPI/Rules/` has 7 classes (`ActiveNthAccount`,
  `AllowedCandidateCustomFieldOption`, `AllowedProviderCustomFieldOption`,
  `CandidateEligibleForPreferenceUpdate`, `NoActiveTrackerExists`,
  `ProviderEligibleForPreferenceUpdate`, `ShiftsRemovableFromNth`) — all seven call
  `DB::table()`/`Model::query()` statically right inside `passes()`, none of them take a
  Repository through the constructor. In other words, splitting by DI wouldn't be "Unit by
  default, Integration as a temporary measure for a couple of legacy exceptions" — it would be
  the opposite, Integration for the majority. To avoid re-analyzing the DI shape of every new
  Rule class on every PR, the decision is not to split: a single address,
  `tests/Integration/Rules/`, for all of them, always, against real `aoc_test`.
  `DB::shouldReceive()` was considered as a way to force one into "almost-Unit" and rejected —
  mocking a query-builder chain is brittle (breaks on any non-behavioral query refactor) and
  proves nothing about the actual query, the same reasoning that already rules out a mocked
  `BigQueryConnection`/MSSQL connection for repository leaves (see below). For the 2 classes
  with no I/O, Integration costs nothing — the test simply never touches the DB, but it still
  lives in the shared folder, no exception carved out.
- If a single repository leaf physically mixes "our own DB" with an "unverifiable external
  source" (MSSQL/BigQuery), that's a reason to split the class into two (see the Kantime
  pattern: `Repository/BigQuery/*` separate from `Repository/MariaDB/*`, glued together at the
  Service level through an interface) — not a reason to waffle between folders.
- Separate case — **`app/Http/Requests` don't get a dedicated test at all.** Its `rules()` is
  verified through the Feature test of the endpoint that uses that Request
  (`assertJsonValidationErrors(...)` for invalid data, a success case separately). This isn't
  about mocking — a Request doesn't perform I/O itself, it declares rules, so there's no point
  testing it apart from how those rules are actually applied.
- Framework glue (Jobs/Observers/Listeners/Console commands) that 100% delegates into an
  already-Unit-tested Service in one line (`handle(Service $s) { $s->run(); }`) doesn't deserve
  its own test — that would just be testing "was a method called." One
  Integration/Feature smoke test (dispatch a job / `$this->artisan(...)` / trigger a real
  observer) is enough to confirm the DI chain actually wires up and the side effect happens —
  not one such test per glue class, but a single end-to-end happy-path test per process.

## Directory structure

```
tests/
├── TestCase.php                 # base for Integration/Feature — boots the app
├── UnitTestCase.php             # PHPUnit\Framework\TestCase, NO app boot
├── CreatesApplication.php
├── Support/
│   └── ...                      # shared traits/helpers
│
├── Unit/                        # ← collaborators (Repository/Service/HTTP client) mocked
│   ├── DTO/
│   ├── Enums/
│   ├── Transformer/
│   ├── DataMappers/              # pure mapping of raw MSSQL/BQ rows → objects (see below)
│   ├── Services/                 # Repository/other Service mocked (Mockery)
│   ├── UseCases/                 # same
│   └── VBC/
│       └── Services/
│
├── Integration/                 # ← the class itself directly executes a query / framework wiring
│   ├── Repositories/
│   │   └── ...                  # mirrors app/Repositories/<Entity>, INCLUDING Mssql* (these
│   │                             # are local MySQL mirror tables, not a live MSSQL — see below)
│   ├── Rules/                    # ALL Rule classes, no exceptions (see "Principle" above)
│   ├── Console/                  # smoke: $this->artisan(...), one per process
│   └── VBC/
│       └── Repositories/
│
│   # app/SyncProcesses/Mssql*.php and app/Helpers/Sync.php do NOT go here — that's raw
│   # sqlsrv_connect()/sqlsrv_query() against a live MSSQL, a permanent gap (see below)
│
└── Feature/                     # ← the full HTTP cycle, including Requests validation
    ├── Http/
    │   └── Controllers/
    │       └── V2/
    ├── VBC/
    │   └── Http/Controllers/
    └── PrivateAPI/
```

Rule: **test path and name mirror the class's path and name under `app/`.**
`App\Services\Foo\Bar` → `Tests\Unit\Services\Foo\BarTest`.

## Naming and docblock conventions

- Test methods: `it_should_...` (snake_case), not `testMethodName`. PHPUnit 9 only recognizes a
  method without the `test` prefix as a test if it carries `@test` in its docblock — for
  `it_should_...`, the `@test` tag is mandatory, otherwise the method silently never runs.
- On the class: `@coversDefaultClass \Fully\Qualified\ClassUnderTest`; on each test method:
  `@covers ::methodName` (the short form relative to `coversDefaultClass`). **The space before
  `::` is required** — PHPUnit's docblock annotation parser (`Util/Annotation/DocBlock.php`)
  only captures the tag name from letters/`_`/`-`; without the space, `@covers::methodName`
  parses as an empty, targetless `@covers` — IDE click-to-navigate might keep working (PHPStorm
  has its own, more forgiving parser), but PHPUnit's `--coverage-*` report won't link that
  annotation to the method.
- Example:
  ```php
  /**
   * @coversDefaultClass \App\Repositories\MssqlPatients\MssqlPatientsReadRepository
   */
  class MssqlPatientsReadRepositoryTest extends TestCase
  {
      /**
       * @test
       * @covers ::getById
       */
      public function it_should_return_patient(): void { ... }
  }
  ```
- The `make:aoc-test` generator already stamps `@coversDefaultClass`/`@test`/
  `@covers ::TODO_...` into the skeleton — you only need to rename the method and fill in the
  real method name in `@covers`.

## Base classes

The Unit/Integration criterion is whether the test touches a **real** external resource (a real
DB, a real network call), not whether the Laravel container is booted at all. Hence two Unit
base-class options, by what the class actually needs:

- `Tests\UnitTestCase` — extends `PHPUnit\Framework\TestCase` directly, no
  `CreatesApplication`. For classes with zero dependency on the container at all — DTO, Enum,
  Transformer, DataMappers. The fastest option, used wherever possible.
- `Tests\TestCase` (existing, boots the app) — for Unit tests that still need the container for
  facades (`Http::fake()`, `Log::shouldReceive()`, `Mockery::mock()` on bound interfaces) or for
  method injection (`$this->app->make(Service::class)`), but where no call ever reaches a real
  DB/network — every collaborator is mocked. Such a test still physically lives under
  `tests/Unit/...` and runs in `testsuite=Unit`: the app is booted, but there's no real I/O, and
  that's what determines the level.
- `Tests\TestCase` (existing) — for Integration and Feature, boots the app. Add the
  **`DatabaseTransactions`** trait, not `RefreshDatabase`/`migrate` — because `aoc_test` is
  cloned from the dev schema, not built by migrations from scratch.

## What to test first (legacy vs new architecture)

Per the project's `CLAUDE.md`: most of `app/` is legacy, change it carefully, don't refactor
for its own sake. From that:

1. **New code** (`app/Services`, `app/Repositories`, `app/UseCases`, `app/DTO`, `app/Enums`,
   `app/Kantime`, `app/PrivateAPI`) — tests are mandatory for everything new.
2. **Legacy bugfixes** — a targeted regression test for the specific bug, nothing more.
3. **Backfilling legacy coverage "for the future"** — not the goal of this document; can be
   considered separately, on request.

## Decided: VBC

The line "No tests are written for this module" in `app/VBC/CLAUDE.md` was a statement of fact
(no tests yet), not a prohibition. Reworded to "no tests yet, new code is covered per this
document." VBC is tested under the same three-level scheme, directories already laid out above.

## Decided: MSSQL/BigQuery — we don't close that integration gap, and say so honestly

**Important correction (verified while writing a real test for `MssqlPatients*Repository`):
not everything with an "Mssql" prefix in the code hits a live MSSQL.** The only real,
unverifiable-without-a-staging-instance connection is the raw `sqlsrv_connect()`/
`sqlsrv_query()` in `app/SyncProcesses/Mssql*.php` and `app/Helpers/Sync.php`.
`App\Repositories\MssqlFacilities`, `App\Repositories\MssqlPatients\*`,
`App\Repositories\MssqlProviders`, etc. work with tables `mssql_patients`/`mssql_facilities`/
`mssql_providers` — ordinary tables in **our own** MySQL schema (created by regular Laravel
migrations, included in `aoc_test`), which the `SyncProcesses` classes above mirror in from
live MSSQL. The "Mssql" prefix is a trace of data origin, not a sign of a live connection. So
these repositories are ordinary repository leaves: a full **Integration** test through
`aoc_test` + `DatabaseTransactions`, no exceptions. Example —
`MssqlPatientsReadRepositoryTest`/`MssqlPatientsWriteRepositoryTest` (already written, see
`tests/Integration/Repositories/MssqlPatients/`): insert real rows into
`mssql_patients`/`mssql_facilities`/`lob`, verify every business rule of
`getEligibleForCccCalls()` (line of business, facility-id blacklist, PRN, Search_Name,
Colorado exception, date, status, `IsLatestAdmit`, PatientID) for real.

What genuinely remains untestable (a repository is, by definition, integration code — its job
*is* the trip to external storage; a "unit test of a repository" with a mocked connection
doesn't verify the thing the repository exists to do):

- **BigQuery** (`app/Kantime/Repository/BigQuery/*`, the real `BigQueryConnection`) and **the
  fact of the trip to a live MSSQL itself** (`app/SyncProcesses/Mssql*.php`,
  `app/Helpers/Sync.php`, raw `sqlsrv_*`) — there's no staging instance and there won't be, a
  permanent, consciously accepted gap.
- What's genuinely worth Unit-testing in these two places is **the pure logic separated from
  the trip to the external source**: mapping a raw row into a domain object/DTO, building
  parameters across branches. Example: `App\Kantime\DataMappers\RenewablesDataMapper::
  fromDaasRow()` — a pure function, zero I/O, a cheap and honest Unit test. Lives in
  `tests/Unit/DataMappers/...`.
- Practical consequence for new classes of this shape: if the logic goes beyond a trivial
  query (joins, conditional criteria building, result processing), pull that part out into its
  own class (like Kantime's `DataMapper`) so a testable piece exists.

This breakdown is about the repository/data source itself. The module that uses it can be cut
differently — see the Kantime case below.

## Case study: `app/Kantime` (BigQuery source + writes to our own DB)

Kantime isn't a separate, fourth category; it's cut along the boundary already baked into the
module's architecture. Example — `RenewablesSyncService`:

```php
public function __construct(
    private readonly KantimeRepositoryInterface $repository,       // ← BigQuery, interface
    private readonly ProvidersRepository $providersRepository,     // ← MariaDB (our DB)
    private readonly RenewablesRepository $renewablesRepository,   // ← MariaDB (our DB)
    ...
) {}
```

`KantimeAppServiceProvider` resolves `KantimeRepositoryInterface` via contextual binding
(`->needs(KantimeRepositoryInterface::class)->give(...)`) into a concrete
`Repository\BigQuery\*Repository`. `ProvidersRepository`/`RenewablesRepository` are already
`Repository\MariaDB\*` — operations against **our own** DB (`aoc_test`), like any other
Repository in the project.

Split:

- **`app/Kantime/Repository/BigQuery/*`** — a source with no staging instance, same case as
  MSSQL above: never tested against real BigQuery. The honestly Unit-testable part is
  `App\Kantime\DataMappers\*DataMapper::fromDaasRow()` (already extracted as its own class), not
  the repository itself with a mocked `BigQueryConnection`.
- **`app/Kantime/Repository/MariaDB/*`** — write to our own DB, themselves directly execute the
  query → **Integration** test through `aoc_test` + `DatabaseTransactions`, like any other
  repository leaf. This is the only place that actually verifies `insert`/`updateById` land
  correctly against the schema.
- **`RenewablesSyncService` and any `*SyncService`/`Jobs/Sync*`** — every dependency arrives
  through the constructor/method (`KantimeRepositoryInterface`, `ProvidersRepository`,
  `RenewablesRepository`) → by the general "mock at the DI boundary" principle, this is **Unit**:
  both the BigQuery interface and the MariaDB repositories get mocked (Mockery). The service
  internally calls the global `logger()` helper, so the test still needs `Tests\TestCase` (see
  "Base classes" above) — that doesn't make the test Integration, there's still no real I/O.
  The test only verifies the service's own logic — provider not found → skip, invalid record →
  delete, existing record found → update, otherwise → insert, exceptions wrapped in
  `KantimeSyncException`. Whether `RenewablesRepository::insert()` actually lands data in
  `aoc_test` without errors is a separate concern of that repository's own Integration test —
  don't duplicate it in the service test.

This is the general pattern for any future Sync module with a similar shape (external
read-only source behind an interface + writes to our own DB): mock both collaborators in the
service's test, verify the real write separately, in the repository's own test.

## Test data: Fixture classes instead of Eloquent factories

The project has 200+ tables and only a handful of Eloquent models (most tables intentionally
live on `DB::table()` — normal for legacy, see the root `CLAUDE.md`). Two options were
considered:

- Eloquent `HasFactory` + `database/factories/*Factory.php` — but only for the ~4 tables that
  already have a model (`User`, `MssqlPatients`, `MssqlFacilities`, `LOB`), and a separate
  Fixture mechanism for the remaining ~200. Rejected: two parallel ways to build test data is a
  permanent source of confusion ("which one do I use for this table?") that outlives any
  specific task. Adding Eloquent models for tables that don't need them in production was also
  rejected — a model should exist because business logic needs it, not because tests do (see
  the "don't refactor legacy for its own sake" principle).
- **Chosen: a single Fixture mechanism on top of `DB::table()`, regardless of whether a model
  exists.** This is also more correct by default: `Model::create()` fires Eloquent
  events/Observers, which is usually unwanted noise for bare precondition seeding — a Fixture
  doesn't touch them.

Mechanism — `tests/Support/Fixtures/`:

```php
abstract class Fixture
{
    abstract protected function table(): string;
    abstract protected function defaults(): array;

    public function make(array $overrides = []): array
    {
        return array_merge($this->defaults(), $overrides);
    }

    public function create(array $overrides = []): object
    {
        $id = DB::table($this->table())->insertGetId($this->make($overrides));

        return DB::table($this->table())->find($id);
    }
}
```

`create()` returns the **entire inserted row** (`object`, the same shape as
`DB::table()->find()`/what `getById()` returns in the repositories themselves), not a bare id —
so a test gets both the id and any other field (including DB-computed defaults, e.g.
`created_at` via `useCurrent()`) in one shot, without re-typing a literal between the insert and
the assertion:

```php
$patient = (new MssqlPatientsFixture())->create(['ccc_id' => 42]);

$result = $this->sut->getById($patient->id);

$this->assertSame($patient->ccc_id, $result->ccc_id); // don't hardcode 42 a second time
```

A concrete fixture is just `table()` + `defaults()`:

```php
class MssqlFacilitiesFixture extends Fixture
{
    protected function table(): string { return 'mssql_facilities'; }
    protected function defaults(): array { return ['State' => 'TX']; }
}
```

**Named states — like `state()` on Laravel factories.** `Fixture::state(array $attributes)`
clones the fixture with extra attributes (immutable — the original instance isn't mutated); a
concrete fixture wraps that in a meaningfully named method:

```php
class MssqlPatientsFixture extends Fixture
{
    // ...

    public function eligibleForCccCalls(): static
    {
        return $this->state([
            'isPRN' => 0,
            'Patient_CurrentStatusID' => 1,
            'IsLatestAdmit' => 1,
            // ...
        ]);
    }
}
```

Usage: `(new MssqlPatientsFixture())->eligibleForCccCalls()->create(['LOBID' => $lobId])`. A
named state is added only when that exact attribute set is genuinely reused (like
`eligibleForCccCalls()`, extracted out of `createEligiblePatient()` — the patient-field pattern
is shared, while the facility/lob linkage across other tables stays in the scenario). One-off
field variations are just `create(['field' => value])`, no named state needed.

Rules:
- **Written lazily, one per table, only when a test first needs that table** — not trying to
  cover all 200 tables upfront (same principle as the test generator: infrastructure grows on
  demand, not in batches).
- If a table has an atypical "business key" instead of an auto-increment id for external
  references (like `lob.LOBID`, which `mssql_patients.LOBID` references, not `lob.id`) — that's
  not a problem: since `create()` returns the whole row, the needed field is just there on it
  (`(new LobFixture())->create()->LOBID`), no need to override `create()`.
- **Composite domain scenarios** (e.g. an "eligible-for-CCC-calls patient" — patient + facility
  + lob wired together) don't belong inside a single table's generic Fixture — that's no longer
  about one table. At this project's scale (hundreds of tables), scenarios aren't a rare
  exception but the norm, so they're written as separate classes in
  `tests/Support/Scenarios/` right away, rather than waiting for a second consumer (unlike
  single Fixtures, where laziness is justified — there are 200 tables and most don't need a
  scenario).

  **A scenario class has no shared abstract parent** (unlike `Fixture`, where the "one table →
  one row" contract is genuinely the same for all of them). Scenario shapes vary (one linked set
  of rows, several entities, a collection) — forcing a common interface now would be premature.
  Usually just a class with `use ResolvesOverrides` (if lazy defaults are needed) and a
  `create(array $overrides = []): object` method.

  **A class docblock is mandatory** and must fully describe the scenario — this is the one
  place the business rule the tests verify actually gets written down:
  - what the scenario represents (a domain concept, not a technical one);
  - which real method/business rule it backs (`Class::method`);
  - what's guaranteed by default — one condition per line;
  - how to break one specific condition via `$overrides` for an exclusion test.

  Example — `Tests\Support\Scenarios\EligibleCccCallPatientScenario`:
  ```php
  /**
   * A patient eligible for CCC (Client Care Coordinator) outreach calls.
   *
   * Backs App\Repositories\MssqlPatients\MssqlPatientsReadRepository::getEligibleForCccCalls().
   * Wires mssql_patients + a linked mssql_facilities row + a linked lob row so that, by
   * default, every one of the repository's filters passes:
   *
   * - Line of business (lob.LOBName) is not PT/OT/ST
   * - aoc_facility_id is not in the hard-coded exclusion list (14, 43, 44)
   * - ... (remaining conditions, one per line)
   *
   * Pass $overrides to violate exactly one of the rules above and get an *ineligible*
   * patient for a specific exclusion test.
   */
  class EligibleCccCallPatientScenario
  {
      use ResolvesOverrides;

      public function create(array $overrides = []): object
      {
          $attributes = $this->resolveOverrides([
              'aoc_facility_id' => fn () => (new MssqlFacilitiesFixture())->create()->id,
              'LOBID' => fn () => (new LobFixture())->create()->LOBID,
          ], $overrides);

          return (new MssqlPatientsFixture())->eligibleForCccCalls()->create($attributes);
      }
  }
  ```
  Usage from a test: `(new EligibleCccCallPatientScenario())->create($overrides)` — same
  ergonomics as a Fixture (returns the whole row, not a bare id).

  Naming — by domain concept, not by table/repository (`EligibleCccCallPatientScenario`, not
  `MssqlPatientsScenario`), `Scenario` suffix to visually distinguish from `*Fixture` in file
  listings. While scenarios in one area are few — flat in `tests/Support/Scenarios/`; if many
  accumulate (by actual need, not preemptively) — group into per-domain subfolders
  (`tests/Support/Scenarios/Ccc/`, `.../Kantime/`, etc.), mirroring how the rest of the test
  directories mirror `app/`.
- **A default that requires a side effect** (e.g. "if facility isn't in overrides, create one
  via `MssqlFacilitiesFixture`") — don't mix two different mechanisms (`??` for some fields,
  `array_merge` for others) in one method. The shared trait
  `Tests\Support\ResolvesOverrides::resolveOverrides(array $defaults, array $overrides)` merges
  `$defaults` with `$overrides`, then resolves whichever defaults remain a `Closure` (i.e.
  weren't overridden) — so the side effect (creating a related fixture) only runs when the value
  is actually needed:
  ```php
  $attributes = $this->resolveOverrides([
      'aoc_facility_id' => fn () => (new MssqlFacilitiesFixture())->create()->id,
      'isPRN' => 0,
      // ...
  ], $overrides);
  ```
  `Fixture::make()` itself uses this same trait — one mechanism for both single fixtures and
  composite scenarios.
- Already available: `Fixture` (base), `MssqlPatientsFixture`, `MssqlFacilitiesFixture`,
  `LobFixture`, `ResolvesOverrides` (trait).

## Test database: `aoc_test` via `schema:dump`, not `migrate` from scratch (decided 2026-08-01)

`php artisan migrate` on an empty database doesn't work — the migration history is incomplete
(some tables were never created by any migration; some migrations `ALTER TABLE` on such
tables). Solution: Laravel's own `schema:dump` mechanism —
`database/schema/mysql-schema.sql` stores a full schema dump (all 253 tables + the current
`migrations` table content) taken from the dev database. `migrate` on an empty database already
knows how to load such a dump first, then apply only what's missing from it — but its loader
shells out to the external `mysql` binary, which isn't available in the app container.
Hence — `php artisan test:build-db [--database=aoc_test]`: loads the same dump via PDO
(`DB::unprepared()`, no external binaries), then calls `migrate` as usual. The full procedure
(including how to re-take the dump and the one-time step of creating the `aoc_test` database
itself as root) is in `tests/README.md`.

A real bug was found and fixed along the way:
`database/migrations/2022_05_21_035157_add-application-table.php` declared the `race` column
twice (100 and 50 characters) — broke `migrate` from scratch independent of the issue above.
Kept the 100-character version (matches the real column in the dev database).

