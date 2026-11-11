<?php

namespace App\VBC\Services\Hospitalization;

use RuntimeException;

/** The requested Outcome transition cannot be applied to the tracker's current state. */
class InvalidHospitalizationOutcomeTransitionException extends RuntimeException
{
    /**
     * @param  array<int, string>  $missingFields  keys of the required fields still empty, when that is the reason
     */
    public function __construct(string $message, public readonly array $missingFields = [])
    {
        parent::__construct($message);
    }
}
