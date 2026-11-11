<?php

namespace App\Events\MssqlRecruiterTracker;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MssqlRecruiterTrackerOutdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly int $trackerId)
    {
    }
}
