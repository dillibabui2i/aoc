<?php

namespace Tests\Feature\VBC;

use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFollowupStatusEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerIcmLevelEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use Tests\TestCase;

/**
 * Pins the public value => label contract of the VBC enums to literal strings.
 *
 * The backing values are the wire contract (accepted by the `statuses` filter and sent
 * by Bruno / the frontend); the labels are what the API returns. Asserting the literal
 * map here means renaming an enum case value or changing a label fails the build —
 * unlike tests that read the enum itself, which would silently move with the change.
 */
class EnumContractTest extends TestCase
{
    public function test_hospitalization_tracker_status_contract(): void
    {
        $this->assertSame([
            'NEW' => 'New',
            'OPEN' => 'Open',
            'IN_PROGRESS' => 'In Progress',
            'DISCHARGED' => 'Discharged',
            'READY_FOR_REVIEW' => 'Ready for Review',
            'READY_FOR_NEXT_STEPS' => 'Ready for Next Steps',
            'COMPLETED' => 'Completed',
            'CLOSED' => 'Closed',
            'CANCELLED' => 'Cancelled',
        ], HospitalizationTrackerStatusEnum::list());
        // PD-3972: CANCELLED is ED-only; the shared filter options (ICM buckets) must not offer it.
        $this->assertNotContains('CANCELLED', array_column(HospitalizationTrackerStatusEnum::options(), 'value'));
    }

    public function test_ed_visits_bucket_statuses_contract(): void
    {
        $this->assertSame(
            ['IN_PROGRESS', 'READY_FOR_REVIEW', 'READY_FOR_NEXT_STEPS'],
            array_column(HospitalizationTrackerStatusEnum::edVisitsBucketOptions(), 'value')
        );
    }

    public function test_followup_status_contract(): void
    {
        $this->assertSame([
            'NOT_STARTED' => 'Not Started',
            'FIRST_ATTEMPT' => 'First Attempt',
            'SECOND_ATTEMPT' => 'Second Attempt',
            'THIRD_ATTEMPT' => 'Third Attempt',
            'COMPLETED' => 'Completed',
            'NEW' => 'New',
            'OPEN' => 'Open',
            'SCHEDULED' => 'Scheduled',
            'ATTEMPTED' => 'Attempted',
            'CANCELED' => 'Canceled',
        ], HospitalizationTrackerFollowupStatusEnum::list());
    }

    public function test_icm_level_contract(): void
    {
        $this->assertSame([
            'LEVEL_I' => 'Level I',
            'LEVEL_II' => 'Level II',
            'LEVEL_III' => 'Level III',
            'LEVEL_I_III' => 'Level I/III',
            'LEVEL_II_III' => 'Level II/III',
        ], HospitalizationTrackerIcmLevelEnum::list());
    }
}
