<?php

namespace App\PrivateAPI\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * Checks that an IPEA location can be resolved for the submitted value: either the value itself,
 * or the given fallback (the client's facility branch, or the schedule's existing location on a
 * reschedule). A no-op (always passes) when the operation isn't an IPEA.
 */
class IpeaBranchIsResolvable implements Rule
{
    private ?int $resolvedBranchId = null;

    public function __construct(
        private readonly bool $required,
        private readonly ?int $fallbackBranchId,
        private readonly string $message = 'ipeaBranch is required: no branch location could be resolved.',
    ) {
    }

    public function passes($attribute, $value): bool
    {
        $branchId = $value ?: $this->fallbackBranchId;
        $this->resolvedBranchId = $branchId ? (int) $branchId : null;

        return !$this->required || $this->resolvedBranchId !== null;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function getResolvedBranchId(): ?int
    {
        return $this->resolvedBranchId;
    }
}
