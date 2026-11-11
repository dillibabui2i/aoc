<?php

namespace App\Observers;

use App\Services\TrachVentClass\TrachVentClassInstructorNotifier;
use App\TrachVentClassRequest;

class TrachVentClassRequestObserver
{
    public function __construct(
        private TrachVentClassInstructorNotifier $trachVentClassInstructorNotifier,
    ) {
    }

    public function created(TrachVentClassRequest $trachVentClassRequest): void
    {
        $this->trachVentClassInstructorNotifier->notify(
            (string) $trachVentClassRequest->trach_vent_class_occurrence_id,
            $trachVentClassRequest->candidate_id,
            $trachVentClassRequest->provider_id,
            $trachVentClassRequest->created_by,
            $trachVentClassRequest,
        );
    }
}
