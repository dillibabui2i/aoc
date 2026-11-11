<?php

namespace Database\Seeders\VBC;

use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerCareRequirementEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerDeclinedProgramContactEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerIcmLevelEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerSourceEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Models\Encounter\EncounterModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use Illuminate\Database\Seeder;

class HospitalizationCollectionSeeder extends Seeder
{
    private array $assigneeIds = [1, 11];
    private array $statuses = [
        HospitalizationTrackerStatusEnum::OPEN,
        HospitalizationTrackerStatusEnum::IN_PROGRESS,
        HospitalizationTrackerStatusEnum::DISCHARGED,
        HospitalizationTrackerStatusEnum::READY_FOR_REVIEW,
        HospitalizationTrackerStatusEnum::COMPLETED,
    ];
    private array $levels = [
        HospitalizationTrackerIcmLevelEnum::LEVEL_I,
        HospitalizationTrackerIcmLevelEnum::LEVEL_II,
        HospitalizationTrackerIcmLevelEnum::LEVEL_III,
        HospitalizationTrackerIcmLevelEnum::LEVEL_I_III,
        HospitalizationTrackerIcmLevelEnum::LEVEL_II_III,
    ];
    private array $declinedProgramContacts = [
        HospitalizationTrackerDeclinedProgramContactEnum::ICM_AND_TRIAGE_TEAM,
        HospitalizationTrackerDeclinedProgramContactEnum::ICM_TEAM,
        HospitalizationTrackerDeclinedProgramContactEnum::TRIAGE_TEAM,
    ];
    /** Care Requirements is a multi-select, so each tracker gets a different-sized selection. */
    private array $careRequirements = [
        [HospitalizationTrackerCareRequirementEnum::GT_GB],
        [
            HospitalizationTrackerCareRequirementEnum::TRACH_AND_VENT,
            HospitalizationTrackerCareRequirementEnum::VENT,
            HospitalizationTrackerCareRequirementEnum::SUCTION,
        ],
        [
            HospitalizationTrackerCareRequirementEnum::ADLS,
            HospitalizationTrackerCareRequirementEnum::MEDICATION_ADMINISTRATION,
        ],
        [],
    ];

    public function run(): void
    {
        $encounters = EncounterModel::all();
        foreach ($encounters as $index => $encounter) {
            $assigneeId = $this->assigneeIds[$index % count($this->assigneeIds)];
            $status = $this->statuses[$index % count($this->statuses)];

            HospitalizationTrackerModel::create([
                'encounter_id' => $encounter->id,
                // Deliberately not the assignee: the form shows Author and Assigned To side by side, so
                // seeded data that differs is what makes a mix-up visible.
                'author_id' => $this->assigneeIds[($index + 1) % count($this->assigneeIds)],
                'status' => $status,
                'icm_level' => $this->levels[$index % count($this->levels)],
                'source' => HospitalizationTrackerSourceEnum::POINT_CLICK_CARE,
                'declined_program_contact' => $this->declinedProgramContacts[$index % count($this->declinedProgramContacts)],
                'aoc_care_requirements' => array_column(
                    $this->careRequirements[$index % count($this->careRequirements)],
                    'value'
                ),
                'assigned_to_id' => $assigneeId,
                'sent_to_review_by_id' => $assigneeId,
            ]);

            if ($status === HospitalizationTrackerStatusEnum::DISCHARGED) {
                $encounter->update(['discharged_at' => $encounter->admission_at->copy()->addDays(rand(3, 10))]);
            }
        }
    }
}
