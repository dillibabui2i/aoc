<?php

namespace App\VBC\DTO\Immunization;

use App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendationStatusEnum;

final readonly class ImmunizationScheduleFilterDTO
{
    public const SORT_VACCINE_CATEGORY = 'vaccine_category';
    public const SORT_VACCINE_NAME = 'vaccine_name';

    public const ALL_SORT_COLUMNS = [
        self::SORT_VACCINE_CATEGORY,
        self::SORT_VACCINE_NAME,
    ];

    public const ALLOWED_PER_PAGE = [10, 25, 50, 100];
    public const DEFAULT_PER_PAGE = 10;

    public function __construct(
        public int $page = 1,
        public int $perPage = self::DEFAULT_PER_PAGE,
        public string $sortBy = self::SORT_VACCINE_CATEGORY,
        public string $sortDir = 'asc',
        public ?int $vaccineGroupCode = null,
        public ?int $vaccineCvxCode = null,
        public array $states = [],
        public ?ImmunizationRecommendationStatusEnum $status = null,
        public ?string $search = null,
    ) {
    }
}
