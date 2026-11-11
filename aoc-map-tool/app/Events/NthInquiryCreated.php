<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NthInquiryCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $createdLeadId,
        public Object $clinicianData,
        public Object $nthData,
        public Object $newestLead
    ) {}
}
