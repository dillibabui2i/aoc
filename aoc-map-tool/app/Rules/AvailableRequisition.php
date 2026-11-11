<?php

namespace App\Rules;

use App\Enums\JobStateEnum;
use App\Jobs;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

/**
 * Mirrors the requisition dropdown on the add-candidate form: the requisition has to exist,
 * still be open, and belong to a category the form offered for this job category type.
 *
 * @see \App\Http\Controllers\CandidatesController::getJobListByCategory
 */
class AvailableRequisition implements Rule
{
    private bool $wrongCategory = false;

    /**
     * @param string[] $availableCategories categories the form offered, see JobCategoriesReadRepository
     */
    public function __construct(private readonly array $availableCategories, private readonly Jobs $jobs)
    {
    }

    public function passes($attribute, $value): bool
    {
        $this->wrongCategory = false;

        if (!is_numeric($value)) {
            return false;
        }

        $category = $this->jobs::query()
            ->where('id', (int) $value)
            ->where('jobState', JobStateEnum::OPEN->value)
            ->value('category');

        if ($category === null) {
            return false;
        }

        if (!in_array($category, $this->availableCategories, true)) {
            $this->wrongCategory = true;

            return false;
        }

        return true;
    }

    public function message(): string
    {
        return $this->wrongCategory
            ? 'The selected requisition belongs to a category that is not available here. Please select a requisition from the list.'
            : 'The selected requisition is no longer available. Please select a requisition from the list.';
    }
}
