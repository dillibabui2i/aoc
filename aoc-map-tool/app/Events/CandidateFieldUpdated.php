<?php

namespace App\Events;

use App\Candidates;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CandidateFieldUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
      public readonly Candidates $candidate,
      public readonly string $field,
      public readonly mixed $oldValue,
      public readonly mixed $newValue,
      public readonly int $userId
    ) {}
}
