<?php

namespace Database\Seeders\VBC;

use App\MssqlPatients;
use App\VBC\Enum\ImmunizationTracker\ImmunizationTrackerStatusEnum;
use App\VBC\Enum\VaccineDose\VaccineDoseStatusEnum;
use App\VBC\Enum\VaccineDose\VaccineDoseStatusReasonEnum;
use App\VBC\Models\CdcVaccine\CdcVaccineModel;
use App\VBC\Models\ImmunizationTracker\ImmunizationTrackerModel;
use App\VBC\Models\VaccineDose\VaccineDoseModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ImmunizationTrackerSeeder extends Seeder
{
    private const PATIENTS = 5;
    private const VACCINES_PER_PATIENT = 3;
    private const DOSES_PER_TRACKER = 3;
    private const DOSE_INTERVAL_DAYS = 60;
    private const BUCKET_PATIENTS = 20;
    private const BUCKET_ROUNDS = 2;

    /**
     * Planned doses for the Immunizations dashboard bucket: [earliest_date, due_date] as day offsets from
     * today, grouped by the outcome they must produce (see VaccineDoseOutcomeEnum), boundaries included.
     */
    private const BUCKET_SCENARIOS = [
        // Overdue: today > due date
        'overdue, long ago' => [-120, -60],
        'overdue, last month' => [-45, -10],
        'overdue, yesterday' => [-30, -1],
        // Missing: earliest date <= today <= due date
        'missing, earliest today, due today' => [0, 0],
        'missing, earliest today' => [0, 30],
        'missing, earliest yesterday' => [-1, 14],
        'missing, mid-window' => [-20, 25],
        // Upcoming: today < earliest date <= today + 60 days
        'upcoming, earliest tomorrow' => [1, 30],
        'upcoming, in 2 weeks' => [14, 45],
        'upcoming, in 45 days' => [45, 90],
        'upcoming, earliest in exactly 60 days' => [60, 120],
        // Planned: earliest date > today + 60 days
        'planned, earliest in 61 days' => [61, 120],
        'planned, in 3 months' => [90, 150],
        'planned, in 6 months' => [180, 240],
    ];

    private array $assigneeIds = [1, 11];
    /** Bucket doses cycle through these assignees; null is an unassigned tracker. */
    private array $bucketAssigneeIds = [1, 11, null];
    private array $trackerStatuses = [
        ImmunizationTrackerStatusEnum::OPEN,
        ImmunizationTrackerStatusEnum::IN_PROGRESS,
        ImmunizationTrackerStatusEnum::COMPLETED,
    ];

    public function run(): void
    {
        // IsLatestAdmit = 1, the same pairing the app uses to list clients: other rows are superseded
        // admissions that no screen can reach.
        $patientIds = MssqlPatients::query()
            ->where('IsLatestAdmit', true)
            ->orderBy('id', 'desc')
            ->limit(self::PATIENTS)
            ->pluck('id');
        $cvxCodes = CdcVaccineModel::query()->orderBy('cvx_code')->pluck('cvx_code');
        $statuses = VaccineDoseStatusEnum::cases();
        $doseIndex = 0;

        foreach ($patientIds as $patientIndex => $patientId) {
            foreach (range(0, self::VACCINES_PER_PATIENT - 1) as $vaccineIndex) {
                $tracker = ImmunizationTrackerModel::create([
                    'status' => $this->trackerStatuses[($patientIndex + $vaccineIndex) % count($this->trackerStatuses)],
                    'patient_id' => $patientId,
                    'vaccine_cvx_code' => $cvxCodes[($patientIndex + $vaccineIndex) % count($cvxCodes)],
                    'assignee_id' => $this->assigneeIds[$patientIndex % count($this->assigneeIds)],
                    'created_by_id' => $this->assigneeIds[($patientIndex + 1) % count($this->assigneeIds)],
                ]);

                foreach (range(1, self::DOSES_PER_TRACKER) as $sequenceNumber) {
                    // Rotated through every status (incl. Canceled), so each one is represented in the data.
                    $status = $statuses[$doseIndex++ % count($statuses)];
                    $earliestDate = now()->addDays(($sequenceNumber - 2) * self::DOSE_INTERVAL_DAYS)->startOfDay();

                    VaccineDoseModel::create([
                        'immunization_tracker_id' => $tracker->id,
                        'sequence_number' => $sequenceNumber,
                        'earliest_date' => $earliestDate,
                        'due_date' => $earliestDate->copy()->addDays(rand(14, 30)),
                        'administered_date' => $status === VaccineDoseStatusEnum::ADMINISTERED
                            ? $earliestDate->copy()->addDays(rand(0, 7))
                            : null,
                        'status' => $status,
                        'status_reason' => $status === VaccineDoseStatusEnum::DECLINED
                            ? VaccineDoseStatusReasonEnum::RELIG
                            : null,
                    ]);
                }
            }
        }

        $this->seedBucketDoses($cvxCodes);
    }

    /** Planned doses spread over every outcome, patients, branches, vaccines and assignees. */
    private function seedBucketDoses(Collection $cvxCodes): void
    {
        $patientIds = MssqlPatients::query()
            ->where('IsLatestAdmit', true)
            ->orderBy('id', 'desc')
            ->limit(self::BUCKET_PATIENTS)
            ->pluck('id');
        $index = 0;

        foreach (range(1, self::BUCKET_ROUNDS) as $round) {
            foreach (self::BUCKET_SCENARIOS as [$earliestOffset, $dueOffset]) {
                $tracker = ImmunizationTrackerModel::create([
                    'status' => ImmunizationTrackerStatusEnum::OPEN,
                    'patient_id' => $patientIds[$index % count($patientIds)],
                    'vaccine_cvx_code' => $cvxCodes[($index * 3) % count($cvxCodes)],
                    'assignee_id' => $this->bucketAssigneeIds[$index % count($this->bucketAssigneeIds)],
                    'created_by_id' => $this->assigneeIds[$index % count($this->assigneeIds)],
                ]);

                VaccineDoseModel::create([
                    'immunization_tracker_id' => $tracker->id,
                    'sequence_number' => $round,
                    'earliest_date' => now()->addDays($earliestOffset)->startOfDay(),
                    'due_date' => now()->addDays($dueOffset)->startOfDay(),
                    'administered_date' => null,
                    'status' => VaccineDoseStatusEnum::PLANNED,
                    'status_reason' => null,
                ]);

                $index++;
            }
        }
    }
}
