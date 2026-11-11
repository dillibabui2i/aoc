<?php

namespace App\VBC\Rules;

use App\VBC\Repositories\Hospitalization\HospitalizationReadRepository;
use Illuminate\Contracts\Validation\Rule;

/**
 * The client id names a client the form is actually allowed to open a tracker for: the row exists, it is
 * the current admission (not a superseded one) and the DaaS sync has not marked it deleted.
 *
 * The conditions themselves are not repeated here — they live in the read repository next to the
 * autocomplete that offers these clients, so validation can never accept an id the search would not
 * have offered.
 */
readonly class ActiveClientExists implements Rule
{
    public function __construct(private HospitalizationReadRepository $repository)
    {
    }

    /**
     * @param  string  $attribute
     * @param  mixed  $value
     */
    public function passes($attribute, $value): bool
    {
        return $this->repository->existsSelectableClient((int) $value);
    }

    public function message(): string
    {
        return 'The selected client is not available.';
    }
}
