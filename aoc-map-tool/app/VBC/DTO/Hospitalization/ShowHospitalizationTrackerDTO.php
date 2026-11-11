<?php

namespace App\VBC\DTO\Hospitalization;

use App\VBC\Enum\Encounter\EncounterTypeEnum;

final readonly class ShowHospitalizationTrackerDTO
{
    public function __construct(
        public EncounterTypeEnum $encounterType,
    ) {
    }
}
