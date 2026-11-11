<?php

namespace App\VBC\Enum\ImmunizationRecommendation;

/** Who a vaccine schedule is recommended for. Stored in `immunization_recommended_for.display_name` as the label. */
enum ImmunizationRecommendedForEnum: string
{
    case ALL_CHILDREN = 'ALL_CHILDREN';
    case HIGH_RISK_GROUPS = 'HIGH_RISK_GROUPS';
    case SHARED_CLINICAL_DECISION_MAKING = 'SHARED_CLINICAL_DECISION_MAKING';

    public function label(): string
    {
        return match ($this) {
            self::ALL_CHILDREN => 'All Children',
            self::HIGH_RISK_GROUPS => 'High risk groups or some populations',
            self::SHARED_CLINICAL_DECISION_MAKING => 'Shared Clinical Decision-Making',
        };
    }

    public static function fromLabel(?string $label): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->label() === $label) {
                return $case;
            }
        }

        return null;
    }

    public static function options(): array
    {
        return array_map(
            fn (self $case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            self::cases()
        );
    }

    public static function list(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->toArray();
    }
}
