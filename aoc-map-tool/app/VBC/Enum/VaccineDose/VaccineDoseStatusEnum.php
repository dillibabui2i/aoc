<?php

namespace App\VBC\Enum\VaccineDose;

enum VaccineDoseStatusEnum: string
{
    case PLANNED = 'PLANNED';
    case INACTIVE = 'INACTIVE';
    case ADMINISTERED = 'ADMINISTERED';
    case NOT_ADMINISTERED = 'NOT_ADMINISTERED';
    case DECLINED = 'DECLINED';
    case CANCELED = 'CANCELED';

    public function label(): string
    {
        return match ($this) {
            self::PLANNED => 'Planned',
            self::INACTIVE => 'Inactive',
            self::ADMINISTERED => 'Administered',
            self::NOT_ADMINISTERED => 'Not Administered',
            self::DECLINED => 'Declined',
            self::CANCELED => 'Canceled',
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
