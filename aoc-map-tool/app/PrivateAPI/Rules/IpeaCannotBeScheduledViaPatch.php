<?php

namespace App\PrivateAPI\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * IPEA is always the first meeting for a tracker and can only be created through POST -
 * regardless of whether one already exists, PATCH is never allowed to schedule a fresh one.
 */
class IpeaCannotBeScheduledViaPatch implements Rule
{
    public function __construct(private readonly string $scheduleType)
    {
    }

    public function passes($attribute, $value): bool
    {
        return $this->scheduleType !== 'IPEA';
    }

    public function message(): string
    {
        return 'Conflict: meeting exists. IPEA cannot be scheduled via PATCH.';
    }
}
