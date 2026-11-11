<?php

namespace App\VBC\Repositories\Immunization;

use App\VBC\DTO\Immunization\DoseAgeTargetDTO;
use App\VBC\DTO\Immunization\ImmunizationScheduleDTO;
use App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendedForEnum;
use App\VBC\Models\ImmunizationRecommendation\ImmunizationRecommendationModel;

class ImmunizationScheduleWriteRepository
{
    public function __construct(private ImmunizationRecommendationModel $model)
    {
    }

    public function create(ImmunizationScheduleDTO $dto): ImmunizationRecommendationModel
    {
        /** @var ImmunizationRecommendationModel $schedule */
        $schedule = $this->model->newQuery()->create([
            'vaccine_cvx_code' => $dto->vaccineCvxCode,
            'frequency' => $dto->frequency,
            'originating_state' => $dto->state,
            'status' => $dto->status,
        ]);

        return $schedule;
    }

    public function update(ImmunizationRecommendationModel $schedule, ImmunizationScheduleDTO $dto): ImmunizationRecommendationModel
    {
        $schedule->update([
            'vaccine_cvx_code' => $dto->vaccineCvxCode,
            'frequency' => $dto->frequency,
            'originating_state' => $dto->state,
            'status' => $dto->status,
        ]);

        return $schedule;
    }

    /**
     * Dose targets are edited as a whole set: the old rows are soft-deleted and the submitted ones inserted
     * (nothing references a dose-age row by id).
     *
     * @param  array<int, DoseAgeTargetDTO>  $targets
     */
    public function replaceDoseAges(ImmunizationRecommendationModel $schedule, array $targets): void
    {
        $schedule->doseAges()->delete();

        foreach ($targets as $target) {
            $schedule->doseAges()->create([
                'from_age' => $target->fromAge,
                'from_age_unit' => $target->fromAgeUnit,
                'to_age' => $target->toAge,
                'to_age_unit' => $target->toAgeUnit,
            ]);
        }
    }

    /** The modal picks one value; the table allows several, so the single row is replaced like the dose ages. */
    public function replaceRecommendedFor(ImmunizationRecommendationModel $schedule, ImmunizationRecommendedForEnum $recommendedFor): void
    {
        $schedule->recommendedFor()->delete();
        $schedule->recommendedFor()->create(['display_name' => $recommendedFor->label()]);
    }
}
