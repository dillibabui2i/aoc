<?php

namespace Database\Seeders\VBC;

use App\MssqlPatients;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFollowupStatusEnum;
use App\VBC\Models\Encounter\EncounterModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerFollowupModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use Illuminate\Database\Seeder;

class FollowupCollectionSeeder extends Seeder
{
    private const PER_TRACKER = 3;
    private array $assigneeIds = [1, 11];
    private array $statuses = [
        HospitalizationTrackerFollowupStatusEnum::SCHEDULED,
        HospitalizationTrackerFollowupStatusEnum::FIRST_ATTEMPT,
        HospitalizationTrackerFollowupStatusEnum::COMPLETED,
    ];

    public function run(): void
    {
        $trackers = HospitalizationTrackerModel::with('encounter')->get();

        foreach ($trackers as $index => $tracker) {
            $assigneeId = $this->assigneeIds[$index % count($this->assigneeIds)];
            foreach (range(0, self::PER_TRACKER - 1) as $i) {
                $status = $this->statuses[$i];
                $isCompleted = $status === HospitalizationTrackerFollowupStatusEnum::COMPLETED;
                $isFirstAttempt = $status === HospitalizationTrackerFollowupStatusEnum::FIRST_ATTEMPT;
                HospitalizationTrackerFollowupModel::create([
                    'hospitalization_tracker_id' => $tracker->id,
                    'patient_id' => $tracker->encounter->patient_id,
                    'assigned_to_id' => $assigneeId,
                    'requested_by_id' => $assigneeId,
                    'status' => $status,
                    'scheduled_at' => now()->addDays(rand(-3, 7)),
                    'attempted_at' => $isFirstAttempt ? now()->subDays(rand(1, 5)) : null,
                    'attempted_by_id' => $isFirstAttempt ? $assigneeId : null,
                    'completed_at' => $isCompleted ? now()->subDays(rand(0, 3)) : null,
                    'completed_by_id' => $isCompleted ? $assigneeId : null,
                    'status_note' => $isCompleted ? "Seed status note for tracker {$tracker->id}" : null,
                    'follow_ups_updates' => $isCompleted ? "Seed follow-up update for tracker {$tracker->id}" : null,
                    'needs' => $isCompleted ? "Seed needs for tracker {$tracker->id}" : null,
                    'notes' => "Seed followup #{$i} for tracker {$tracker->id}",
                ]);
            }
        }

        $patientIds = EncounterModel::query()->distinct()->pluck('patient_id');
        $patientWithoutHospitalization = MssqlPatients::query()
            ->whereNotIn('id', $patientIds)
            ->value('id');

        if ($patientWithoutHospitalization) {
            HospitalizationTrackerFollowupModel::create([
                'patient_id' => $patientWithoutHospitalization,
                'assigned_to_id' => 1,
                'requested_by_id' => 1,
                'status' => HospitalizationTrackerFollowupStatusEnum::NEW,
                'scheduled_at' => now()->addDays(2),
                'notes' => 'Seed followup for patient without hospitalization',
            ]);
        }
    }
}
