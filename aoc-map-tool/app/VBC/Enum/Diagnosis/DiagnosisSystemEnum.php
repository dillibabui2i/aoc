<?php

namespace App\VBC\Enum\Diagnosis;

/** The body system a diagnosis belongs to — BA-provided value set (PD-3498). */
enum DiagnosisSystemEnum: string
{
    case CARDIOVASCULAR = 'CARDIOVASCULAR';
    case RESPIRATORY = 'RESPIRATORY';
    case GASTROINTESTINAL = 'GASTROINTESTINAL';
    case NERVOUS = 'NERVOUS';
    case MUSCULOSKELETAL = 'MUSCULOSKELETAL';
    case INTEGUMENTARY = 'INTEGUMENTARY';
    case ENDOCRINE = 'ENDOCRINE';
    case RENAL_URINARY = 'RENAL_URINARY';
    case REPRODUCTIVE = 'REPRODUCTIVE';
    case HEMATOLOGIC = 'HEMATOLOGIC';
    case IMMUNE_LYMPHATIC = 'IMMUNE_LYMPHATIC';
    case VISUAL = 'VISUAL';
    case AUDITORY_VESTIBULAR = 'AUDITORY_VESTIBULAR';
    case MULTISYSTEM = 'MULTISYSTEM';
    case OTHER = 'OTHER';
    case UNSPECIFIED = 'UNSPECIFIED';

    public function label(): string
    {
        return match ($this) {
            self::CARDIOVASCULAR => 'Cardiovascular',
            self::RESPIRATORY => 'Respiratory',
            self::GASTROINTESTINAL => 'Gastrointestinal',
            self::NERVOUS => 'Nervous',
            self::MUSCULOSKELETAL => 'Musculoskeletal',
            self::INTEGUMENTARY => 'Integumentary (Skin)',
            self::ENDOCRINE => 'Endocrine',
            self::RENAL_URINARY => 'Renal / Urinary',
            self::REPRODUCTIVE => 'Reproductive',
            self::HEMATOLOGIC => 'Hematologic',
            self::IMMUNE_LYMPHATIC => 'Immune / Lymphatic',
            self::VISUAL => 'Visual',
            self::AUDITORY_VESTIBULAR => 'Auditory / Vestibular',
            self::MULTISYSTEM => 'Multiple Systems / Systemic',
            self::OTHER => 'Other',
            self::UNSPECIFIED => 'Unspecified',
        };
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
