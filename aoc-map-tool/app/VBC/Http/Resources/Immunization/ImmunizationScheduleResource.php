<?php

namespace App\VBC\Http\Resources\Immunization;

use App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendationStatusEnum;
use App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendedForEnum;
use App\VBC\Models\ImmunizationDoseAge\ImmunizationDoseAgeModel;
use App\VBC\Models\ImmunizationRecommendation\ImmunizationRecommendationModel;
use App\VBC\Models\ImmunizationRecommendedFor\ImmunizationRecommendedForModel;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One schedule with raw values, for pre-filling the Edit modal (the list resource only has display labels).
 *
 * @mixin ImmunizationRecommendationModel
 */
class ImmunizationScheduleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            // The vaccine relation is null when the vaccine was soft-deleted; the modal then asks for a new one.
            'cvx_group_code' => $this->vaccine?->cvx_group_code,
            'vaccine_cvx_code' => $this->vaccine_cvx_code,
            'state' => $this->originating_state,
            'recommended_for' => $this->recommendedForValue(),
            // A legacy ONCE row is not offered by the modal, so it comes back as-is and the user has to pick one.
            'frequency' => $this->frequency->value,
            'is_active' => $this->status === ImmunizationRecommendationStatusEnum::ACTIVE,
            'dose_age_targets' => $this->doseAges
                ->sortBy('id')
                ->map(fn (ImmunizationDoseAgeModel $dose) => [
                    'from_age' => $dose->from_age,
                    'from_age_unit' => $dose->from_age_unit?->value,
                    'to_range' => $dose->to_age !== null,
                    'to_age' => $dose->to_age,
                    'to_age_unit' => $dose->to_age_unit?->value,
                ])
                ->values()
                ->all(),
        ];
    }

    /** The modal holds one value; if several rows exist the latest wins. */
    private function recommendedForValue(): ?string
    {
        /** @var ImmunizationRecommendedForModel|null $row */
        $row = $this->recommendedFor->sortByDesc('id')->first();

        return ImmunizationRecommendedForEnum::fromLabel($row?->display_name)?->value;
    }
}
