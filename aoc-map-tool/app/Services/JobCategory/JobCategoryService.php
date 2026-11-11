<?php

namespace App\Services\JobCategory;

use App\Models\JobCategories;
use Illuminate\Database\Eloquent\Collection;

class JobCategoryService
{
    public const EXCLUDED_JOB_CATEGORY_CNA_FROM_NURSE_CHURN = 'CNA';

    public function __construct(private JobCategories $jobCategoriesModel)
    {
    }

    public function getCategories(): Collection
    {
        return $this->jobCategoriesModel->query()->get();
    }

    public function getFilteredCategories(): Collection
    {
        return $this->jobCategoriesModel
            ->query()
            ->get()
            ->reject(
                fn(JobCategories $c) => str_contains(
                    $c->category,
                    JobCategoryService::EXCLUDED_JOB_CATEGORY_CNA_FROM_NURSE_CHURN
                )
            );
    }
}
