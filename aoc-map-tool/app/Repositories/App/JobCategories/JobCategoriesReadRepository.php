<?php

namespace App\Repositories\App\JobCategories;

use App\Enums\FeatureFlagsEnum;
use App\Helpers\LD;
use App\Models\JobCategories;
use Exception;

class JobCategoriesReadRepository
{
    public const NURSES_CATEGORIES = 'nurs';
    public const THERAPY_CATEGORIES = 'therap';

    /**
     * @param JobCategories $jobCategoriesModel
     */
    public function __construct(private readonly JobCategories $jobCategoriesModel)
    {
    }

    /**
     * @param string|null $searchKeyword
     * @return array
     * @throws Exception
     */
    public function getJobCategoriesFilteredByKeyword(?string $searchKeyword): array
    {
        $query = $this->jobCategoriesModel->where('is_enabled', true);

        if (!is_null($searchKeyword)) {
            if (
                LD::checkFeatureFlag(FeatureFlagsEnum::ENABLE_NURSING_FILTER->value)
                && str_contains($searchKeyword, self::NURSES_CATEGORIES)
            ) {
                $query = $query->where('category', 'like', sprintf('%%%s%%', self::NURSES_CATEGORIES));
            }

            if (
                LD::checkFeatureFlag(FeatureFlagsEnum::ENABLE_THERAPIST_BUCKET->value)
                && str_contains($searchKeyword, self::THERAPY_CATEGORIES)
            ) {
                $query = $query->where('category', 'like', sprintf('%%%s%%', self::THERAPY_CATEGORIES));
            }
        }

        return $query->pluck('category')->toArray();
    }
}
