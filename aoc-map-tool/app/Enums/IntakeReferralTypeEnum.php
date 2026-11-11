<?php

namespace App\Enums;

enum IntakeReferralTypeEnum: string
{
    case NEW_HOSPITAL_DISCHARGE = 'New - Hospital Discharge';
    case NEW_NON_HOSPITAL = 'New - Non Hospital';
    case TRANSFER_HOSPITAL_DISCHARGE = 'Transfer - Hospital Discharge';
    case TRANSFER_NON_HOSPITAL = 'Transfer - Non-Hospital';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function normalize(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $trimmed = trim($value);

        return match ($trimmed) {
            'Transfer - Non Hospital' => self::TRANSFER_NON_HOSPITAL->value,
            default => $trimmed,
        };
    }
}
