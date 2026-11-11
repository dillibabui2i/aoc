<?php

namespace App\VBC\Http\Resources\Immunization;

use App\VBC\Enum\ImmunizationDoseAge\ImmunizationDoseAgeUnitEnum;
use App\VBC\Models\ImmunizationDoseAge\ImmunizationDoseAgeModel;
use App\VBC\Models\ImmunizationRecommendation\ImmunizationRecommendationModel;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ImmunizationRecommendationModel */
class ImmunizationScheduleCollectionItemResource extends JsonResource
{
    private const BIRTH_LABEL = 'Birth';
    private const EMPTY_VALUE = '—';

    public function toArray($request): array
    {
        $group = $this->vaccine->group;

        return [
            'id' => $this->id,
            'vaccine_category' => $group?->aoc_name ?? $group?->cdc_name ?? self::EMPTY_VALUE,
            'vaccine_name' => $this->vaccine->cdc_full_name,
            'dose_age_targets' => $this->doseAgeLabels(),
            'state' => $this->originating_state ?? self::EMPTY_VALUE,
            'status' => $this->status->label(),
        ];
    }

    /** @return array<int, string> */
    private function doseAgeLabels(): array
    {
        return $this->doseAges
            ->sortBy(fn (ImmunizationDoseAgeModel $dose) => $this->toMonths($dose->from_age, $dose->from_age_unit))
            ->map(fn (ImmunizationDoseAgeModel $dose) => $this->doseAgeLabel($dose))
            ->values()
            ->all();
    }

    private function doseAgeLabel(ImmunizationDoseAgeModel $dose): string
    {
        $isBirth = $dose->from_age === 0;
        $hasTo = $dose->to_age !== null;

        if ($isBirth && !$hasTo) {
            return self::BIRTH_LABEL;
        }

        $from = $isBirth ? self::BIRTH_LABEL : $this->ageText($dose->from_age, $dose->from_age_unit);

        if (!$hasTo) {
            return $from;
        }

        if (!$isBirth && $dose->from_age_unit === $dose->to_age_unit) {
            return $dose->from_age . '-' . $this->ageText($dose->to_age, $dose->to_age_unit);
        }

        return $from . '-' . $this->ageText($dose->to_age, $dose->to_age_unit);
    }

    private function ageText(?int $age, ?ImmunizationDoseAgeUnitEnum $unit): string
    {
        $unitText = strtolower($unit?->label() ?? '');

        return trim($age . ' ' . $unitText . ($age === 1 ? '' : 's'));
    }

    private function toMonths(?int $age, ?ImmunizationDoseAgeUnitEnum $unit): int
    {
        return ($age ?? 0) * ($unit === ImmunizationDoseAgeUnitEnum::YEAR ? 12 : 1);
    }
}
