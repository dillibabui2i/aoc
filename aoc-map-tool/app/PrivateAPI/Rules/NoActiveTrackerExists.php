<?php

namespace App\PrivateAPI\Rules;

use App\Repositories\MssqlRecruiterTracker\MssqlRecruiterTrackerReadRepository;
use Illuminate\Contracts\Validation\Rule;

class NoActiveTrackerExists implements Rule
{
    private string $message = 'An active recruiter tracker already exists for this patient/candidate combination.';

    public function __construct(
        private readonly MssqlRecruiterTrackerReadRepository $trackers,
        private readonly mixed $candidateId,
        private readonly mixed $providerId,
    ) {
    }

    public function passes($attribute, $value): bool
    {
        return !$this->trackers->hasActiveTrackerForPatient(
            (int) $value,
            $this->candidateId !== null ? (int) $this->candidateId : null,
            $this->providerId !== null ? (int) $this->providerId : null,
        );
    }

    public function message(): string
    {
        return $this->message;
    }
}
