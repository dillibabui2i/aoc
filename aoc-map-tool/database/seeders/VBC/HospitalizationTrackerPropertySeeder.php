<?php

namespace Database\Seeders\VBC;

use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFieldEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerThirtyDayOutcomeEnum;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerCustomPropertyDefinitionModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerPropertyModel;
use Illuminate\Database\Seeder;

/**
 * The Current Hospitalization Info fields that live as tracker custom properties (EAV) rather than as
 * columns — without these the section renders empty for every seeded tracker.
 *
 * Values are written in the same shape the application stores them in: dates as plain 'Y-m-d H:i:s'
 * strings and enums as their backing value, so what the form reads back here is what it reads back after
 * a real save (see HospitalizationWriteRepository::normaliseForStorage).
 */
class HospitalizationTrackerPropertySeeder extends Seeder
{
    /** Statuses at which the post-discharge fields of the section are filled in. */
    private const POST_DISCHARGE_STATUSES = [
        HospitalizationTrackerStatusEnum::DISCHARGED,
        HospitalizationTrackerStatusEnum::READY_FOR_REVIEW,
        HospitalizationTrackerStatusEnum::READY_FOR_NEXT_STEPS,
        HospitalizationTrackerStatusEnum::COMPLETED,
        HospitalizationTrackerStatusEnum::CLOSED,
    ];

    private array $thirtyDayOutcomes = [
        HospitalizationTrackerThirtyDayOutcomeEnum::NO_HOSPITALIZATION_IN_PAST_30_DAYS,
        HospitalizationTrackerThirtyDayOutcomeEnum::CLIENT_HOSPITALIZATION_WITHIN_30_DAYS,
    ];

    public function run(): void
    {
        $definitionIds = HospitalizationTrackerCustomPropertyDefinitionModel::pluck('id', 'key');

        foreach (HospitalizationTrackerModel::with('encounter')->get() as $index => $tracker) {
            $admissionAt = $tracker->encounter->admission_at;

            $values = [
                HospitalizationTrackerFieldEnum::TT_CONTACTED_PRIOR_TO_ADMIT->trackerPropertyKey() => $index % 2 === 0,
                HospitalizationTrackerFieldEnum::FAMILY_CONTACT_AT->trackerPropertyKey() => $admissionAt
                    ?->copy()
                    ->addHours(4)
                    ->toDateTimeString(),
                HospitalizationTrackerFieldEnum::THIRTY_DAY_OUTCOME->trackerPropertyKey() => $this
                    ->thirtyDayOutcomes[$index % count($this->thirtyDayOutcomes)]->value,
            ];

            // ROC Date is the section's one optional field, so a third of the trackers leave it empty.
            if ($index % 3 !== 0) {
                $values[HospitalizationTrackerFieldEnum::ROC_DATE->trackerPropertyKey()] = $admissionAt
                    ?->copy()
                    ->addDays(20)
                    ->toDateTimeString();
            }

            if (in_array($tracker->status, self::POST_DISCHARGE_STATUSES, true)) {
                $completed = $index % 2 === 0;
                $values[HospitalizationTrackerFieldEnum::FIRST_BILLABLE_SHIFT_COMPLETED->trackerPropertyKey()] = $completed;

                if ($completed) {
                    $values[HospitalizationTrackerFieldEnum::FIRST_BILLABLE_SHIFT_AT->trackerPropertyKey()] = $tracker
                        ->encounter
                        ->discharged_at
                        ?->copy()
                        ->addDay()
                        ->toDateTimeString();
                }
            }

            foreach ($values as $key => $value) {
                if ($value === null) {
                    continue;
                }

                HospitalizationTrackerPropertyModel::create([
                    'hospitalization_tracker_id' => $tracker->id,
                    'definition_id' => $definitionIds[$key],
                    'value' => $value,
                ]);
            }
        }
    }
}
