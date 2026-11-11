<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InactivateNRNCTrackers
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ?int $trackerId,
        public readonly ?int $providerId,
        public readonly ?int $candidateId = null,
    ) {}
}
