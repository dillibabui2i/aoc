# Writing tests

Practical, example-driven guide to adding a test in this repo. If you just need the fast
lookup version, `tests/CLAUDE.md` is the condensed cheat sheet. If you want the full reasoning
behind a rule here (why this split, why not Eloquent factories, why `schema:dump` instead of
migrations), it's in `tests/RATIONALE.md`. `tests/README.md` is just about building/refreshing
the `aoc_test` database.

## The one-question decision tree

Ask, in order:

1. **Is it an `app/Http/Requests` class?** → No dedicated test. Its `rules()` gets exercised as
   a side effect of the Feature test for the endpoint that uses it. Don't write
   `SomeRequestTest.php`.
2. **Is it a custom validation `Rule` (`app/Rules`, `app/PrivateAPI/Rules`)?** → Always
   `tests/Integration/Rules/`, no exceptions, even if the class happens to take a constructor
   argument. We deliberately don't split these by DI — see "Rules: the one deliberate
   exception" below.
3. **Does the class receive its I/O (Repository, another Service, HTTP client) through the
   constructor?** → Mock those dependencies → **Unit**. This is most of `app/Services`,
   `app/UseCases`.
4. **Does the class itself directly run a query, an artisan command, or dispatch/observe
   something, with nothing to mock?** → **Integration** (needs `aoc_test` + real app boot).
5. **Are you testing a full HTTP round-trip** (route → middleware → controller → response)?
   → **Feature**.

The line between Unit and Integration is **dependency injection, not what kind of class it
is**. A Service that internally ends up hitting the database is still Unit, as long as it gets
there through a mocked collaborator, not by calling `DB::` itself.

## Step 0: generate the skeleton

```
php artisan make:aoc-test app/Path/To/Class.php [--integration|--feature] [--bare] [--force]
```

Mirrors the path into `tests/{Unit,Integration,Feature}/...`, picks the right base class, and
for `--force`-free Unit generation reflects the constructor to wire up `Mockery::mock()` for
every typed dependency automatically. If a dependency is `final` with no interface, it can't be
mocked — the generator leaves a `// TODO` rather than faking it. Fix that at the source (drop
`final`, extract an interface) or accept the gap; don't work around it inside the test.

Default suite is Unit. Pass `--integration`/`--feature` only when step 3/4/5 above says so.

## Level 1: Unit

No app boot needed *in principle*, but plenty of Unit tests still extend `Tests\TestCase`
because they need the container for a facade (`Mockery`, `Http::fake()`, `logger()`) — that's
still Unit as long as nothing hits a real DB row or a real network call.

- `Tests\UnitTestCase` (bare `PHPUnit\Framework\TestCase`, no boot) — only for classes with
  **zero** container dependency: DTOs, Enums, pure Transformers, extracted DataMappers.
  Generator flag: `--bare`.
- `Tests\TestCase` — everything else Unit, plus all Integration/Feature.

Example — a Service with two injected Repositories, both mocked:

```php
namespace Tests\Unit\Kantime\Services;

use App\Kantime\Contracts\KantimeRepositoryInterface;
use App\Kantime\Repository\MariaDB\ProvidersRepository;
use App\Kantime\Repository\MariaDB\RenewablesRepository;
use App\Kantime\Services\RenewablesSyncService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

/**
 * @coversDefaultClass \App\Kantime\Services\RenewablesSyncService
 */
class RenewablesSyncServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @test
     * @covers ::syncOne
     */
    public function it_should_skip_when_provider_not_found(): void
    {
        $bigQuery = Mockery::mock(KantimeRepositoryInterface::class);
        $bigQuery->shouldReceive('getRecord')->andReturn(null);

        $providers = Mockery::mock(ProvidersRepository::class);
        $renewables = Mockery::mock(RenewablesRepository::class);
        $renewables->shouldNotReceive('insert');

        $sut = new RenewablesSyncService($bigQuery, $providers, $renewables, /* ... */);

        $sut->syncOne(/* ... */);

        // no exception, no insert — the mock expectations above enforce this
    }
}
```

The point: the test only proves the Service's own branching (found/not-found, insert-vs-update,
exception wrapping). Whether `RenewablesRepository::insert()` actually lands a correct row in
the database is a different test, at a different level (see next section) — don't duplicate
that assertion here.

> Current gap: `ProvidersRepository`/`RenewablesRepository` are `final` with no interface, so
> they can't be `Mockery::mock()`'d the normal way yet. Until that's fixed (drop `final` or add
> an interface), a fully-mocked version of this test isn't possible — see `tests/CLAUDE.md`
> "Known gaps".

## Level 2: Integration

Boots the app, hits real `aoc_test` via `DatabaseTransactions` (never `RefreshDatabase` — see
"Test database" below for why). Covers:

- Repository classes that themselves build/execute the query.
- All custom `Rule` classes (see next section).
- Framework-wiring smoke tests: one per process (`$this->artisan(...)`, a job dispatch), not one
  per glue class that just delegates one line into an already-Unit-tested Service.

Example — real repository test, trimmed from `tests/Integration/Repositories/MssqlPatients/MssqlPatientsReadRepositoryTest.php`:

```php
namespace Tests\Integration\Repositories\MssqlPatients;

use App\Repositories\MssqlPatients\MssqlPatientsReadRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\Fixtures\MssqlPatientsFixture;
use Tests\TestCase;

/**
 * @coversDefaultClass \App\Repositories\MssqlPatients\MssqlPatientsReadRepository
 */
class MssqlPatientsReadRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    protected MssqlPatientsReadRepository $sut;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sut = $this->app->make(MssqlPatientsReadRepository::class);
    }

    /**
     * @test
     * @covers ::getById
     */
    public function it_should_return_patient(): void
    {
        $patient = (new MssqlPatientsFixture())->create(['ccc_id' => 42]);

        $result = $this->sut->getById($patient->id);

        $this->assertSame($patient->ccc_id, $result->ccc_id);
    }
}
```

Note `$patient->ccc_id` on the assertion side, not a hardcoded `42` — `Fixture::create()`
returns the full inserted row, so you never re-type a literal you already used to set the data
up (see Fixtures below).

### Rules: the one deliberate exception

Every custom `Rule` class → `tests/Integration/Rules/`, always, even ones that could
theoretically be written with an injectable Repository. Checked against the real inventory
(2026-08-10): `app/Rules/` has 2 classes with zero I/O, `app/PrivateAPI/Rules/` has 7, and all
seven call `DB::table()`/`Model::query()` statically inside `passes()` — no constructor
injection anywhere. Splitting "Unit if DI, Integration otherwise" would mean re-litigating the
DI-shape of every new Rule on every PR, for what would mostly land on the Integration side
anyway. So: one address, no exceptions.

Don't reach for `DB::shouldReceive()` to force one into Unit — mocking a query-builder chain
means stubbing every method in the chain (`table()`, `where()`, `exists()`, ...) individually,
which breaks on any non-behavioral query refactor and proves nothing about whether the real
query is correct. Same reasoning as not mocking a BigQuery/MSSQL connection (see "Permanently
untestable" in `tests/CLAUDE.md`).

Example, for `App\PrivateAPI\Rules\ProviderEligibleForPreferenceUpdate`
(`return DB::table('mssql_providers')->where('id', $value)->where('is_deleted', 0)->exists();`):

```php
namespace Tests\Integration\PrivateAPI\Rules;

use App\PrivateAPI\Rules\ProviderEligibleForPreferenceUpdate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\Fixtures\MssqlFacilitiesFixture; // stand-in — use a provider fixture once one exists
use Tests\TestCase;

/**
 * @coversDefaultClass \App\PrivateAPI\Rules\ProviderEligibleForPreferenceUpdate
 */
class ProviderEligibleForPreferenceUpdateTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     * @covers ::passes
     */
    public function it_should_pass_for_an_active_provider(): void
    {
        $provider = /* insert a real mssql_providers row via a Fixture */;

        $sut = new ProviderEligibleForPreferenceUpdate();

        $this->assertTrue($sut->passes('provider_id', $provider->id));
    }

    /**
     * @test
     * @covers ::passes
     */
    public function it_should_fail_for_a_deleted_provider(): void
    {
        $provider = /* insert with is_deleted = 1 */;

        $sut = new ProviderEligibleForPreferenceUpdate();

        $this->assertFalse($sut->passes('provider_id', $provider->id));
    }
}
```

Write the exhaustive edge cases (found/not-found/deleted/wrong-state) *here*, on the Rule's own
test. The Feature test of whatever endpoint uses this Rule (next section) only needs one
"invalid input → 422" case to prove wiring — it doesn't re-enumerate these branches.

## Level 3: Feature

Full HTTP cycle — the only level that boots real routing/middleware and therefore the only
level where `app/Http/Requests` validation actually gets exercised. One test class per
controller, covering **both** the controller's own behavior and the Request's validation as
part of the same file — not two separate files.

```php
namespace Tests\Feature\VBC\Http\Controllers\CareCoordination;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CareCoordinationControllerTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_should_reject_invalid_facility_id(): void
    {
        $this->postJson('/api/vbc/care-coordination', ['facility_id' => 'not-an-int'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['facility_id']);
    }

    /** @test */
    public function it_should_return_care_coordination_for_valid_payload(): void
    {
        $this->postJson('/api/vbc/care-coordination', [/* valid payload */])
            ->assertStatus(200)
            ->assertJsonStructure([/* ... */]);
    }
}
```

Don't write one validation case per field in `rules()` — cover what's actually load-bearing
(required fields, cross-field rules, custom Rule classes attached to that field), skip
mechanical `required|integer` fields with no business meaning behind them.

## Test data: Fixtures and Scenarios — never `HasFactory`

No Eloquent factories anywhere in `tests/`, even for the ~4 tables that do have a model. One
mechanism for all ~270 tables:

```php
class MssqlFacilitiesFixture extends Fixture
{
    protected function table(): string { return 'mssql_facilities'; }
    protected function defaults(): array { return ['State' => 'TX']; }
}
```

`create($overrides)` inserts and returns the **full row** — pull `->id` or any other field from
it rather than hardcoding the same literal twice:

```php
$facility = (new MssqlFacilitiesFixture())->create(['State' => 'CO']);
// $facility->id, $facility->State, and every column default are all right there
```

Named states (reusable attribute presets) are a method on the Fixture calling
`$this->state([...])`:

```php
class MssqlPatientsFixture extends Fixture
{
    public function eligibleForCccCalls(): static
    {
        return $this->state(['isPRN' => 0, 'Patient_CurrentStatusID' => 1, /* ... */]);
    }
}
```

When a test needs **several tables wired together** into one business concept, that's a
Scenario, in `tests/Support/Scenarios/`, not a bigger Fixture:

```php
/**
 * A patient eligible for CCC (Client Care Coordinator) outreach calls.
 *
 * Backs App\Repositories\MssqlPatients\MssqlPatientsReadRepository::getEligibleForCccCalls().
 * Wires mssql_patients + a linked mssql_facilities row + a linked lob row so that, by default,
 * every one of the repository's filters passes:
 *
 * - Line of business (lob.LOBName) is not PT/OT/ST
 * - aoc_facility_id is not in the hard-coded exclusion list (14, 43, 44)
 * - ...
 *
 * Pass $overrides to violate exactly one of the rules above and get an *ineligible* patient.
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

The class docblock is **mandatory** and is the one place the business rule the scenario
represents actually gets written down: what it represents, which real method it backs, every
condition it guarantees by default (one per line), and how `$overrides` breaks a specific one.

Write Fixtures/Scenarios lazily — one per table, only when a test first needs it. Already
available: `Fixture` (base), `MssqlPatientsFixture`, `MssqlFacilitiesFixture`, `LobFixture`,
`ResolvesOverrides` (trait), `EligibleCccCallPatientScenario`.

## Naming and docblocks

- Test methods: `it_should_...` (snake_case), **not** `testXxx`. PHPUnit 9 only picks up a
  method without the `test` prefix if it carries `@test` in the docblock — forget the tag and
  the method silently never runs (no error, no warning, it's just absent from the run).
- Class docblock: `@coversDefaultClass \Fully\Qualified\ClassUnderTest`.
- Method docblock: `@covers ::methodName` — **the space goes before `::`, not after.** PHPUnit's
  docblock parser is picky about this; `@covers:: methodName` (space after) parses as an empty,
  targetless `@covers` and silently drops out of `--coverage-*` attribution, even though
  IDE-click-to-navigate might still work by luck (PHPStorm's own parser is more forgiving). This
  exact mistake was live in `MssqlPatientsReadRepositoryTest.php` until it got fixed
  2026-08-10 — worth a second look before you copy-paste an existing test as a template.
- A public method that calls a **private** helper needs both listed: `@covers ::publicMethod`
  plus `@covers ::privateHelper` on the same test method. `@covers` doesn't just document
  intent, it restricts which executed lines count toward this test's coverage — an unlisted
  private helper shows up red in the coverage report even though it genuinely ran.
- `make:aoc-test` already stamps `@coversDefaultClass`/`@test`/`@covers ::TODO_...` into the
  generated skeleton — you only need to rename the method and fill in the real target.

## Running tests

```
composer test              # everything
composer test:unit         # fast, no DB, no app container needed
composer test:integration  # needs aoc_test — run inside the app container
composer test:feature      # needs aoc_test — run inside the app container
./vendor/bin/phpunit path/to/OneTest.php
./vendor/bin/phpunit --filter it_should_do_x path/to/File
```

`phpunit.xml` pins `DB_DATABASE=aoc_test` — this holds regardless of how the suite is invoked,
so tests never silently fall back to `.env`'s `DB_DATABASE` (the real dev database). Don't
remove that override.

If `aoc_test` needs building/rebuilding: `php artisan test:build-db` — full explanation in
`tests/README.md`.

## Don't fake coverage of what's genuinely untestable

`app/Kantime/Repository/BigQuery/*` (real BigQuery) and `app/SyncProcesses/Mssql*.php` +
`app/Helpers/Sync.php` (raw `sqlsrv_connect()`/`sqlsrv_query()` against live MSSQL) have no test
instance and won't get one. Don't write an Integration test against them, and don't write a
Unit test with a mocked connection either — a mocked connection doesn't prove the actual query
is correct, it only proves your mock returns what you told it to. The honest thing to test here
is pure logic pulled out of the I/O — a `DataMapper::fromRow()`-style class, Unit-tested in
isolation (see `App\Kantime\DataMappers\RenewablesDataMapper::fromDaasRow()`).

Note that **not everything named "Mssql" falls in this bucket** —
`App\Repositories\MssqlFacilities`, `App\Repositories\MssqlPatients\*`, etc. query
`mssql_patients`/`mssql_facilities`, which are ordinary tables in **our own** MySQL schema
(populated by the `SyncProcesses` classes above, included in `aoc_test`). Those are normal
Integration repository tests, no exception — the `Mssql` prefix is about data origin, not about
a live connection.
