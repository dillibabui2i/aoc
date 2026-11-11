<?php

namespace App\VBC\Rules;

use App\VBC\Repositories\Immunization\ImmunizationScheduleReadRepository;
use Illuminate\Contracts\Validation\Rule;

/**
 * A vaccine may have only one schedule template per state — inactive templates count, they are kept
 * for history and re-activating one next to a new duplicate would leave two live templates.
 */
readonly class UniqueVaccinePerState implements Rule
{
    public function __construct(
        private ImmunizationScheduleReadRepository $repository,
        private ?string $state,
        private ?int $ignoreScheduleId = null,
    ) {
    }

    public function passes($attribute, $value): bool
    {
        if ($this->state === null || $this->state === '') {
            return true;
        }

        return !$this->repository->existsForVaccineAndState((int) $value, $this->state, $this->ignoreScheduleId);
    }

    public function message(): string
    {
        return 'An immunization schedule for this vaccine and state already exists.';
    }
}
