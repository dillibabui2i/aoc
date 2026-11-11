<?php

namespace Tests\Feature\Kantime;

use App\Helpers\Sync;
use App\Kantime\Common\BigQueryConnection;
use App\Kantime\Common\KantimeSyncConfig;
use App\Kantime\DataMappers\RenewablesDataMapper;
use App\Kantime\Domain\ProviderId;
use App\Kantime\Exception\KantimeSyncException;
use App\Kantime\Repository\BigQuery\RenewablesRepository as BigQueryRenewablesRepository;
use App\Kantime\Repository\MariaDB\ProvidersRepository;
use App\Kantime\Repository\MariaDB\RenewablesRepository as MariaDbRenewablesRepository;
use App\Kantime\Services\RenewablesSyncService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use RuntimeException;
use Tests\Support\Fixtures\MssqlProvidersFixture;
use Tests\Support\Fixtures\RenewablesFixture;
use Tests\TestCase;

/**
 * Integration test for RenewablesSyncService::syncOne().
 *
 * Everything below the BigQuery boundary (RenewablesRepository@BigQuery,
 * RenewablesDataMapper, the two MariaDB repositories, real DB tables) runs for
 * real. Only `BigQueryConnection::cursor()` is faked, using rows shaped exactly
 * like the associative arrays real BigQuery rows are flattened into.
 *
 * `BigQueryConnection` is a `final readonly class`, so it cannot be mocked via
 * a normal subclassing double. Instead we use Mockery's `overload:` instance
 * mock, which replaces the class definition itself for the current process -
 * hence running every test method in its own process (`@runTestsInSeparateProcesses`)
 * so the fake definition never leaks into, or collides with, other test files.
 *
 * @coversDefaultClass \App\Kantime\Services\RenewablesSyncService
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class RenewablesSyncServiceTest extends TestCase
{
    use DatabaseTransactions;
    use MockeryPHPUnitIntegration;

    private const FIXTURE = __DIR__ . '/fixtures/daas_renewable_item.json';

    private function fakeRow(array $overrides = []): array
    {
        $row = json_decode(file_get_contents(self::FIXTURE), true, 512, JSON_THROW_ON_ERROR);

        return array_merge($row, $overrides);
    }

    private function buildSyncService(array $cursorRows): RenewablesSyncService
    {
        $connection = Mockery::mock('overload:' . BigQueryConnection::class);
        $connection->shouldReceive('tableRef')->once()->andReturn('test-project.test_dataset.daas_dw_renewableitems_vw');
        $connection->shouldReceive('cursor')->once()->andReturn($cursorRows);

        return $this->syncServiceFor($connection);
    }

    private function buildSyncServiceThrowing(\Throwable $exception): RenewablesSyncService
    {
        $connection = Mockery::mock('overload:' . BigQueryConnection::class);
        $connection->shouldReceive('tableRef')->once()->andReturn('test-project.test_dataset.daas_dw_renewableitems_vw');
        $connection->shouldReceive('cursor')->once()->andThrow($exception);

        return $this->syncServiceFor($connection);
    }

    private function syncServiceFor($connection): RenewablesSyncService
    {
        $mapper = new RenewablesDataMapper();

        return new RenewablesSyncService(
            new BigQueryRenewablesRepository($connection, $mapper),
            new ProvidersRepository(),
            new MariaDbRenewablesRepository(),
            $mapper,
            new Sync(),
            new KantimeSyncConfig(debugSync: false)
        );
    }

    private function createProvider(int $billingProviderId, array $attributes = []): object
    {
        return (new MssqlProvidersFixture())->create(array_merge([
            'BillingProviderID' => $billingProviderId,
            'ProviderID' => $billingProviderId,
            'ClinicianID' => 'CLN-' . $billingProviderId,
        ], $attributes));
    }

    private function insertRenewable(array $attributes = []): int
    {
        return (new RenewablesFixture())->create($attributes)->id;
    }

    /**
     * @test
     * @covers ::syncOne
     * @covers ::processOne
     */
    public function it_should_insert_a_new_renewable_record_when_none_exists(): void
    {
        $provider = $this->createProvider(5077);

        $service = $this->buildSyncService([$this->fakeRow()]);

        $service->syncOne(new ProviderId(5077));

        $row = DB::table('renewables')->where('renewable_item_id', 9101)->first();

        $this->assertNotNull($row);
        $this->assertSame($provider->id, (int) $row->aoc_provider_id);
        $this->assertSame(5, (int) $row->renewables_type);
        $this->assertSame('2027-03-01', $row->expiration_date);
        $this->assertSame('2023-03-01', $row->issued_date);
        $this->assertSame('RN-778899', $row->license_no);
        $this->assertSame('TX', $row->license_state);
        $this->assertSame(0, (int) $row->isMissing);
        $this->assertStringStartsWith('2000-01-01', $row->last_nursys_sync);
        $this->assertNotNull($row->created_at);
    }

    /**
     * @test
     * @covers ::syncOne
     * @covers ::processOne
     */
    public function it_should_mark_the_record_missing_when_expiration_date_is_blank(): void
    {
        $this->createProvider(5078);

        $service = $this->buildSyncService([$this->fakeRow([
            'ProviderID' => '5078',
            'renewable_item_id' => 9102,
            'expiration_date' => '',
        ])]);

        $service->syncOne(new ProviderId(5078));

        $row = DB::table('renewables')->where('renewable_item_id', 9102)->first();

        $this->assertNotNull($row);
        $this->assertSame(1, (int) $row->isMissing);
    }

    /**
     * @test
     * @covers ::syncOne
     * @covers ::processOne
     */
    public function it_should_update_an_existing_renewable_record(): void
    {
        // A provider can have one `renewables` row per renewables_type (up to ~8 types).
        // getRecord() returns a single BigQuery row for the provider, and a single DAAS
        // sync should refresh every renewables row belonging to that provider.
        $provider = $this->createProvider(5090);

        $staleExpiration = '2025-01-01';
        $existingTypes = range(1, 8);

        foreach ($existingTypes as $type) {
            $this->insertRenewable([
                'UniqueKey' => $type . '-key',
                'aoc_provider_id' => $provider->id,
                'renewables_type' => $type,
                'renewable_item_id' => 9200 + $type,
                'expiration_date' => $staleExpiration,
                'license_no' => 'OLD-TYPE-' . $type,
            ]);
        }

        $updatedType = 5;

        $service = $this->buildSyncService([$this->fakeRow([
            'ProviderID' => '5090',
            'renewables_type' => $updatedType,
            'renewable_item_id' => 9200 + $updatedType,
            'expiration_date' => '2028-06-15',
            'license_no' => 'RN-FRESH',
        ])]);

        $service->syncOne(new ProviderId(5090));

        $rowsByType = DB::table('renewables')
            ->where('aoc_provider_id', $provider->id)
            ->get()
            ->keyBy('renewables_type');

        $this->assertCount(8, $rowsByType, 'no rows should have been inserted or removed, only updated');

        // Expected behavior: a single DAAS sync for a provider refreshes every
        // renewables_type row belonging to that provider, not just the one whose type
        // happens to match the BigQuery row.
        foreach ($existingTypes as $type) {
            $this->assertSame($type . '-key', $rowsByType[$type]->UniqueKey);
            $this->assertSame($type, $rowsByType[$type]->renewables_type, "renewables_type $type was not refreshed by the sync");
            $this->assertSame($type === 5 ? '2028-06-15' : $staleExpiration, $rowsByType[$type]->expiration_date, "renewables_type $type was not refreshed by the sync");
            $this->assertSame($type === 5 ? 'RN-FRESH' : 'OLD-TYPE-' . $type, $rowsByType[$type]->license_no, "renewables_type $type was not refreshed by the sync");
        }
    }

    /**
     * @test
     * @covers ::syncOne
     * @covers ::processOne
     */
    public function it_should_not_insert_a_duplicate_row_when_updating_an_existing_record(): void
    {
        $provider = $this->createProvider(5091);

        $existingId = $this->insertRenewable([
            'aoc_provider_id' => $provider->id,
            'renewables_type' => 5,
            'renewable_item_id' => 9205,
            'expiration_date' => '2025-01-01',
            'license_no' => 'OLD-LICENSE',
        ]);

        $service = $this->buildSyncService([$this->fakeRow([
            'ProviderID' => '5091',
            'renewables_type' => 5,
            'renewable_item_id' => 9205,
            'expiration_date' => '2028-06-15',
            'license_no' => 'RN-FRESH',
        ])]);

        $service->syncOne(new ProviderId(5091));

        $rows = DB::table('renewables')->where('aoc_provider_id', $provider->id)->get();

        $this->assertCount(1, $rows, 'updating an existing record must not also insert a duplicate row');
        $this->assertSame($existingId, $rows->first()->id);
        $this->assertSame('2028-06-15', $rows->first()->expiration_date);
        $this->assertSame('RN-FRESH', $rows->first()->license_no);
    }

    /**
     * @test
     * @covers ::syncOne
     * @covers ::processOne
     */
    public function it_should_never_update_renewables_type_seventeen_records(): void
    {
        $provider = $this->createProvider(5080);

        $existingId = $this->insertRenewable([
            'aoc_provider_id' => $provider->id,
            'renewables_type' => 17,
            'renewable_item_id' => 9104,
            'expiration_date' => '2025-01-01',
            'license_no' => 'OLD-TYPE17',
        ]);

        $service = $this->buildSyncService([$this->fakeRow([
            'ProviderID' => '5080',
            'renewables_type' => 17,
            'renewable_item_id' => 9104,
            'expiration_date' => '2028-06-15',
            'license_no' => 'SHOULD-NOT-APPLY',
        ])]);

        $service->syncOne(new ProviderId(5080));

        $row = DB::table('renewables')->where('id', $existingId)->first();

        // processOne() explicitly skips updateById() for renewables_type 17.
        $this->assertSame('2025-01-01', $row->expiration_date);
        $this->assertSame('OLD-TYPE17', $row->license_no);
    }

    /**
     * @test
     * @covers ::syncOne
     * @covers ::processOne
     */
    public function it_should_do_nothing_when_no_matching_provider_exists(): void
    {
        $service = $this->buildSyncService([$this->fakeRow(['ProviderID' => '999999'])]);

        $service->syncOne(new ProviderId(999999));

        $this->assertSame(0, DB::table('renewables')->count());
    }

    /**
     * @test
     * @covers ::syncOne
     * @covers ::processOne
     */
    public function it_should_do_nothing_when_big_query_returns_no_rows(): void
    {
        $this->createProvider(5081);

        $service = $this->buildSyncService([]);

        $service->syncOne(new ProviderId(5081));

        $this->assertSame(0, DB::table('renewables')->count());
    }

    /**
     * @test
     * @covers ::syncOne
     * @covers ::processOne
     */
    public function it_should_delete_instead_of_upsert_records_flagged_invalid_by_big_query(): void
    {
        // ISVALIDRECORD is `CAST(... AS STRING)` in BigQuery; the mapper reads it with
        // FILTER_VALIDATE_BOOLEAN, so the string "false" becomes bool false and processOne()
        // takes its delete branch instead of upserting (AOC-4465).
        $provider = $this->createProvider(5082);
        $this->insertRenewable(['renewable_item_id' => 9105, 'aoc_provider_id' => $provider->id]);

        $service = $this->buildSyncService([$this->fakeRow([
            'ProviderID' => '5082',
            'renewable_item_id' => 9105,
            'ISVALIDRECORD' => 'false',
        ])]);

        $service->syncOne(new ProviderId(5082));

        $this->assertNull(DB::table('renewables')->where('renewable_item_id', 9105)->first());
    }

    /**
     * @test
     * @covers ::syncOne
     * @covers ::processOne
     */
    public function it_should_wrap_unexpected_failures_into_kantime_sync_exception(): void
    {
        $service = $this->buildSyncServiceThrowing(new RuntimeException('bigquery unavailable'));

        $this->expectException(KantimeSyncException::class);
        $this->expectExceptionMessage('bigquery unavailable');

        $service->syncOne(new ProviderId(5083));
    }
}
