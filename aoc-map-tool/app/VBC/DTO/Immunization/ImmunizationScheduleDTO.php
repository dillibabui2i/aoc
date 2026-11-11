<?php

namespace App\VBC\DTO\Immunization;

use App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendationFrequencyEnum;
use App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendationStatusEnum;
use App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendedForEnum;

/** One submit of the "Add/Edit Immunization Schedule" modal — shared shape for create and edit. */
final readonly class ImmunizationScheduleDTO
{
    /**
     * @param  array<int, DoseAgeTargetDTO>  $doseAgeTargets
     */
    public function __construct(
        public int $vaccineCvxCode,
        public string $state,
        public ImmunizationRecommendedForEnum $recommendedFor,
        public ImmunizationRecommendationFrequencyEnum $frequency,
        public ImmunizationRecommendationStatusEnum $status,
        public array $doseAgeTargets,
    ) {
    }
}
