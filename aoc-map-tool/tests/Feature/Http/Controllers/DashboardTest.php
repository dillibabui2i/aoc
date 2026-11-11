<?php

namespace Tests\Feature\Http\Controllers;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Support\Fixtures\MssqlRecruitertrackerFixture;
use Tests\Support\Fixtures\UserFixture;
use Tests\TestCase;

/**
 * @coversDefaultClass \App\Http\Controllers\Dashboard
 */
class DashboardTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::find((new UserFixture())->create()->id);
        $this->actingAs($user);
    }

    /**
     * @test
     * @covers ::saveScheduleNext
     */
    public function it_should_mark_a_duplicate_bonus_ineligible_immediately_when_first_shift_worked_is_confirmed(): void
    {
        $providerId = random_int(100000, 999999);
        $patientId = random_int(100000, 999999);

        // First match already confirmed a moment earlier — this is the tracker our
        // controller-triggered check must find as the "prior match".
        $first = (new MssqlRecruitertrackerFixture())->create([
            'aoc_provider_id' => $providerId,
            'aoc_patient_id' => $patientId,
            'Candidate_Current_Status' => 'Client Match',
            'Bonus_Status' => 'Pending Hours',
            'Candidate_Start_Date' => '2024-01-01 00:00:00',
        ]);
        $duplicate = (new MssqlRecruitertrackerFixture())->create([
            'aoc_provider_id' => $providerId,
            'aoc_patient_id' => $patientId,
            'Candidate_Current_Status' => 'Client Match',
            'Bonus_Status' => '',
            'Candidate_Start_Date' => '2024-01-11 00:00:00',
        ]);

        $this->post(route('saveScheduleNext'), [
            'mode' => 'Edit',
            'tracker_id' => $duplicate->id,
            'patient_id' => $patientId,
            'outcome' => 'First Shift Worked',
        ]);

        $this->assertSame(
            'Bonus Ineligible',
            DB::table('mssql_recruitertracker')->where('id', $duplicate->id)->value('Bonus_Status')
        );
        $this->assertSame(
            'Pending Hours',
            DB::table('mssql_recruitertracker')->where('id', $first->id)->value('Bonus_Status')
        );
    }
}
