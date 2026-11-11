<?php

namespace App\VBC\DTO\Immunization;

use App\VBC\Enum\ImmunizationDoseAge\ImmunizationDoseAgeUnitEnum;

/** One "Dose Age Target" row of the schedule modal. `toAge`/`toAgeUnit` are null for a single-value target. */
final readonly class DoseAgeTargetDTO
{
    public function __construct(
        public int $fromAge,
        public ImmunizationDoseAgeUnitEnum $fromAgeUnit,
        public ?int $toAge,
        public ?ImmunizationDoseAgeUnitEnum $toAgeUnit,
    ) {
    }
}
