<?php

namespace Tests\Integration\Helpers;

use App\Enums\MssqlRecruiterTrackerBonusStatus;
use App\Helpers\RecruiterTrackerHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Support\Fixtures\MssqlRecruitertrackerFixture;
use Tests\TestCase;

/**
 * @coversDefaultClass \App\Helpers\RecruiterTrackerHelper
 */
class RecruiterTrackerHelperTest extends TestCase
{
    use DatabaseTransactions;

    protected RecruiterTrackerHelper $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = $this->app->make(RecruiterTrackerHelper::class);
    }

    private function createTracker(array $overrides = []): object
    {
        return (new MssqlRecruitertrackerFixture())->create($overrides);
    }

    /**
     * @test
     * @covers ::markCrossRecruiterDuplicatesIneligible
     */
    public function it_should_leave_the_first_match_eligible_when_no_prior_match_exists(): void
    {
        $tracker = $this->createTracker(['Candidate_Start_Date' => '2024-01-01 00:00:00']);

        $this->sut->markCrossRecruiterDuplicatesIneligible();

        $this->assertSame(
            MssqlRecruiterTrackerBonusStatus::PENDING_HOURS->value,
            DB::table('mssql_recruitertracker')->where('id', $tracker->id)->value('Bonus_Status')
        );
    }

    /**
     * @test
     * @covers ::markCrossRecruiterDuplicatesIneligible
     */
    public function it_should_mark_a_same_recruiter_duplicate_within_30_days_as_bonus_ineligible(): void
    {
        $recruiterId = random_int(1, 999999);
        $providerId = random_int(100000, 999999);
        $patientId = random_int(100000, 999999);

        $first = $this->createTracker([
            'user_id' => $recruiterId,
            'aoc_provider_id' => $providerId,
            'aoc_patient_id' => $patientId,
            'Candidate_Start_Date' => '2024-01-01 00:00:00',
        ]);
        $duplicate = $this->createTracker([
            'user_id' => $recruiterId,
            'aoc_provider_id' => $providerId,
            'aoc_patient_id' => $patientId,
            'Candidate_Start_Date' => '2024-01-11 00:00:00',
        ]);

        $this->sut->markCrossRecruiterDuplicatesIneligible();

        $this->assertSame(
            MssqlRecruiterTrackerBonusStatus::PENDING_HOURS->value,
            DB::table('mssql_recruitertracker')->where('id', $first->id)->value('Bonus_Status')
        );
        $this->assertSame(
            MssqlRecruiterTrackerBonusStatus::BONUS_INELIGIBLE->value,
            DB::table('mssql_recruitertracker')->where('id', $duplicate->id)->value('Bonus_Status')
        );
    }

    /**
     * @test
     * @covers ::markCrossRecruiterDuplicatesIneligible
     */
    public function it_should_mark_a_cross_recruiter_duplicate_within_30_days_as_bonus_ineligible(): void
    {
        $providerId = random_int(100000, 999999);
        $patientId = random_int(100000, 999999);

        $first = $this->createTracker([
            'user_id' => 1,
            'aoc_provider_id' => $providerId,
            'aoc_patient_id' => $patientId,
            'Candidate_Start_Date' => '2024-01-01 00:00:00',
        ]);
        $duplicate = $this->createTracker([
            'user_id' => 2,
            'aoc_provider_id' => $providerId,
            'aoc_patient_id' => $patientId,
            'Candidate_Start_Date' => '2024-01-11 00:00:00',
        ]);

        $this->sut->markCrossRecruiterDuplicatesIneligible();

        $this->assertSame(
            MssqlRecruiterTrackerBonusStatus::PENDING_HOURS->value,
            DB::table('mssql_recruitertracker')->where('id', $first->id)->value('Bonus_Status')
        );
        $this->assertSame(
            MssqlRecruiterTrackerBonusStatus::BONUS_INELIGIBLE->value,
            DB::table('mssql_recruitertracker')->where('id', $duplicate->id)->value('Bonus_Status')
        );
    }

    /**
     * @test
     * @covers ::markCrossRecruiterDuplicatesIneligible
     */
    public function it_should_not_mark_a_match_more_than_30_days_after_the_prior_one(): void
    {
        $providerId = random_int(100000, 999999);
        $patientId = random_int(100000, 999999);

        $this->createTracker([
            'aoc_provider_id' => $providerId,
            'aoc_patient_id' => $patientId,
            'Candidate_Start_Date' => '2024-01-01 00:00:00',
        ]);
        $laterMatch = $this->createTracker([
            'aoc_provider_id' => $providerId,
            'aoc_patient_id' => $patientId,
            'Candidate_Start_Date' => '2024-02-05 00:00:00', // 35 days later
        ]);

        $this->sut->markCrossRecruiterDuplicatesIneligible();

        $this->assertSame(
            MssqlRecruiterTrackerBonusStatus::PENDING_HOURS->value,
            DB::table('mssql_recruitertracker')->where('id', $laterMatch->id)->value('Bonus_Status')
        );
    }

    /**
     * @test
     * @covers ::markCrossRecruiterDuplicatesIneligible
     */
    public function it_should_not_match_a_tracker_for_a_different_client(): void
    {
        $providerId = random_int(100000, 999999);

        $this->createTracker([
            'aoc_provider_id' => $providerId,
            'aoc_patient_id' => random_int(100000, 999999),
            'Candidate_Start_Date' => '2024-01-01 00:00:00',
        ]);
        $differentClient = $this->createTracker([
            'aoc_provider_id' => $providerId,
            'aoc_patient_id' => random_int(100000, 999999),
            'Candidate_Start_Date' => '2024-01-05 00:00:00',
        ]);

        $this->sut->markCrossRecruiterDuplicatesIneligible();

        $this->assertSame(
            MssqlRecruiterTrackerBonusStatus::PENDING_HOURS->value,
            DB::table('mssql_recruitertracker')->where('id', $differentClient->id)->value('Bonus_Status')
        );
    }

    /**
     * @test
     * @covers ::markCrossRecruiterDuplicatesIneligible
     */
    public function it_should_ignore_a_soft_deleted_tracker_as_a_prior_match(): void
    {
        $providerId = random_int(100000, 999999);
        $patientId = random_int(100000, 999999);

        $this->createTracker([
            'aoc_provider_id' => $providerId,
            'aoc_patient_id' => $patientId,
            'Candidate_Start_Date' => '2024-01-01 00:00:00',
            'is_deleted' => 1,
        ]);
        $onlyLiveMatch = $this->createTracker([
            'aoc_provider_id' => $providerId,
            'aoc_patient_id' => $patientId,
            'Candidate_Start_Date' => '2024-01-11 00:00:00',
        ]);

        $this->sut->markCrossRecruiterDuplicatesIneligible();

        $this->assertSame(
            MssqlRecruiterTrackerBonusStatus::PENDING_HOURS->value,
            DB::table('mssql_recruitertracker')->where('id', $onlyLiveMatch->id)->value('Bonus_Status')
        );
    }

    /**
     * @test
     * @covers ::markCrossRecruiterDuplicatesIneligible
     */
    public function it_should_create_a_bonus_ineligible_activity_note(): void
    {
        $providerId = random_int(100000, 999999);
        $patientId = random_int(100000, 999999);

        $this->createTracker([
            'aoc_provider_id' => $providerId,
            'aoc_patient_id' => $patientId,
            'Candidate_Start_Date' => '2024-01-01 00:00:00',
        ]);
        $duplicate = $this->createTracker([
            'aoc_provider_id' => $providerId,
            'aoc_patient_id' => $patientId,
            'Candidate_Start_Date' => '2024-01-11 00:00:00',
        ]);

        $this->sut->markCrossRecruiterDuplicatesIneligible();

        $this->assertSame(
            1,
            DB::table('notes')
                ->where('entity', 'tracker-activity')
                ->where('entity_id', $duplicate->id)
                ->where('notes', 'Bonus ineligible')
                ->count()
        );
    }

    /**
     * @test
     * @covers ::markCrossRecruiterDuplicatesIneligible
     */
    public function it_should_not_create_a_duplicate_activity_note_on_repeated_runs(): void
    {
        $providerId = random_int(100000, 999999);
        $patientId = random_int(100000, 999999);

        $this->createTracker([
            'aoc_provider_id' => $providerId,
            'aoc_patient_id' => $patientId,
            'Candidate_Start_Date' => '2024-01-01 00:00:00',
        ]);
        $duplicate = $this->createTracker([
            'aoc_provider_id' => $providerId,
            'aoc_patient_id' => $patientId,
            'Candidate_Start_Date' => '2024-01-11 00:00:00',
        ]);

        $this->sut->markCrossRecruiterDuplicatesIneligible();
        $this->sut->markCrossRecruiterDuplicatesIneligible();
        $this->sut->markCrossRecruiterDuplicatesIneligible();

        $this->assertSame(
            1,
            DB::table('notes')
                ->where('entity', 'tracker-activity')
                ->where('entity_id', $duplicate->id)
                ->where('notes', 'Bonus ineligible')
                ->count()
        );
    }
}
