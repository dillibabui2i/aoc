<?php

namespace Tests\Integration\Repositories\MssqlPatients;

use App\Repositories\MssqlPatients\MssqlPatientsWriteRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Support\Fixtures\MssqlPatientsFixture;
use Tests\TestCase;

/**
 * @coversDefaultClass \App\Repositories\MssqlPatients\MssqlPatientsWriteRepository
 */
class MssqlPatientsWriteRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    protected MssqlPatientsWriteRepository $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = $this->app->make(MssqlPatientsWriteRepository::class);
    }

    /**
     * @test
     * @covers ::updateClientCallFrequency
     */
    public function it_should_update_the_call_frequency(): void
    {
        $id = (new MssqlPatientsFixture())->create(['ccc_client_call_frequency' => 1])->id;

        $affected = $this->sut->updateClientCallFrequency($id, 5);

        $this->assertSame(1, $affected);
        $this->assertSame(
            5,
            DB::table('mssql_patients')->where('id', $id)->value('ccc_client_call_frequency')
        );
    }

    /**
     * @test
     * @covers ::updateClientCallFrequency
     */
    public function it_should_accept_an_explicit_updated_at(): void
    {
        $id = (new MssqlPatientsFixture())->create(['ccc_client_call_frequency' => 1])->id;

        $this->sut->updateClientCallFrequency($id, 3, '2024-05-01 10:00:00');

        $this->assertSame(
            '2024-05-01 10:00:00',
            DB::table('mssql_patients')->where('id', $id)->value('updated_at')
        );
    }

    /**
     * @test
     * @covers ::updateClientCallFrequency
     */
    public function it_should_return_zero_when_patient_does_not_exist(): void
    {
        $affected = $this->sut->updateClientCallFrequency(999999999, 5);

        $this->assertSame(0, $affected);
    }
}
