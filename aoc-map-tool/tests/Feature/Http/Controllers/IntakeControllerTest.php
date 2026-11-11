<?php

namespace Tests\Feature\Http\Controllers;

use App\Helpers\IntakeCoordinatorStaffTransfers;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\Support\Fixtures\UserFixture;
use Tests\Support\Scenarios\IntakeCandidateStartTrackerScenario;
use Tests\TestCase;

/**
 * @coversDefaultClass \App\Http\Controllers\IntakeController
 */
class IntakeControllerTest extends TestCase
{
    use DatabaseTransactions;

    private const CANDIDATE_STARTS = 'intake_candidate_starts';
    private const PENDING_CANDIDATE_STARTS = 'intake_pending_candidate_starts';

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::find((new UserFixture())->create()->id);
        $this->actingAs($user);
    }

    /**
     * PD-5635: any global search in Candidate Starts returned 500
     * (Unknown column 'mssql_recruitertracker.PatientStatus').
     *
     * @test
     * @covers ::getIntakeStaffTransfers
     * @covers ::getDataTableCandidateStarts
     * @dataProvider searchValues
     */
    public function it_should_search_candidate_starts_without_error(string $search): void
    {
        (new IntakeCandidateStartTrackerScenario())->create();

        $this->searchBucket(self::CANDIDATE_STARTS, $search)
            ->assertOk()
            ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data'])
            ->assertJsonMissingPath('error')
            ->assertJsonPath('recordsTotal', 1);
    }

    /**
     * @test
     * @covers ::getIntakeStaffTransfers
     * @covers ::getDataTableCandidateStarts
     */
    public function it_should_find_candidate_starts_row_by_client_status(): void
    {
        $tracker = (new IntakeCandidateStartTrackerScenario())->create(['PatientStatus' => 'PD5635 Status']);

        $this->searchBucket(self::CANDIDATE_STARTS, 'PD5635')
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.id', $tracker->id);
    }

    /**
     * One request per test: Yajra keeps the DataTables request as a container singleton.
     *
     * @test
     * @covers ::getIntakeStaffTransfers
     * @covers ::getDataTableCandidateStarts
     */
    public function it_should_not_find_candidate_starts_row_by_other_client_status(): void
    {
        (new IntakeCandidateStartTrackerScenario())->create(['PatientStatus' => 'PD5635 Status']);

        $this->searchBucket(self::CANDIDATE_STARTS, 'no-such-status-xyz')
            ->assertOk()
            ->assertJsonMissingPath('error')
            ->assertJsonPath('recordsFiltered', 0);
    }

    /**
     * Pending Candidate Starts shares the same column config (incl. PatientStatus).
     *
     * @test
     * @covers ::getIntakeStaffTransfers
     * @covers ::getDataTablePendingCandidateStarts
     * @dataProvider searchValues
     */
    public function it_should_search_pending_candidate_starts_without_error(string $search): void
    {
        (new IntakeCandidateStartTrackerScenario())->create([
            'Candidate_Start_Date' => now()->addWeek()->toDateTimeString(),
        ]);

        $this->searchBucket(self::PENDING_CANDIDATE_STARTS, $search)
            ->assertOk()
            ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data'])
            ->assertJsonMissingPath('error')
            ->assertJsonPath('recordsTotal', 1);
    }

    /**
     * @test
     * @covers ::getIntakeStaffTransfers
     * @covers ::getDataTablePendingCandidateStarts
     */
    public function it_should_find_pending_candidate_starts_row_by_client_status(): void
    {
        $tracker = (new IntakeCandidateStartTrackerScenario())->create([
            'PatientStatus' => 'PD5635 Status',
            'Candidate_Start_Date' => now()->addWeek()->toDateTimeString(),
        ]);

        $this->searchBucket(self::PENDING_CANDIDATE_STARTS, 'PD5635')
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.id', $tracker->id);
    }

    public function searchValues(): array
    {
        return [
            'empty search' => [''],
            'name search' => ['LEVI SELLERS'],
            'status search' => ['Active'],
        ];
    }

    private function searchBucket(string $type, string $search): TestResponse
    {
        return $this->getJson(route('intake.getintakestafftransfers', $this->dataTableQuery($type, $search)));
    }

    /**
     * Builds the DataTables request the grid sends, with the column list
     * taken from the bucket config (same source the UI uses).
     */
    private function dataTableQuery(string $type, string $search): array
    {
        $columns = [];
        foreach ($this->bucketColumns($type) as $column) {
            $columns[] = [
                'data' => is_array($column['data']) ? $column['name'] : $column['data'],
                'name' => $column['name'],
                'searchable' => ($column['searchable'] ?? true) ? 'true' : 'false',
                'orderable' => 'true',
                'search' => ['value' => '', 'regex' => 'false'],
            ];
        }

        return [
            'type' => $type,
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'columns' => $columns,
            'search' => ['value' => $search, 'regex' => 'false'],
        ];
    }

    private function bucketColumns(string $type): array
    {
        return app(IntakeCoordinatorStaffTransfers::class)
            ->getDataTableHtml($type, 'filterForm', 0)
            ->getData()['dataTable'];
    }
}
