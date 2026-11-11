<?php

namespace App\VBC\Rules;

use App\VBC\Repositories\Followup\FollowupReadRepository;
use Illuminate\Contracts\Validation\Rule;

/**
 * The hospitalization tracker id names a tracker a Follow-up may be created against: it exists and its
 * status is not Closed (AC4/AC29) — the same condition the "Hospitalized Client Name" autocomplete uses,
 * so validation can never accept a tracker the search would not have offered.
 */
readonly class FollowupEligibleTracker implements Rule
{
    public function __construct(private FollowupReadRepository $repository)
    {
    }

    /**
     * @param  string  $attribute
     * @param  mixed  $value
     */
    public function passes($attribute, $value): bool
    {
        return $this->repository->existsEligibleTracker((int) $value);
    }

    public function message(): string
    {
        return 'The selected hospitalization is not available for a follow-up.';
    }
}
