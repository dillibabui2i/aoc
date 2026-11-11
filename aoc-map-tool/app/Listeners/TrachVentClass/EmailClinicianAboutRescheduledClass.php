<?php

namespace App\Listeners\TrachVentClass;

use App\Events\TrachVentClass\ClinicianRescheduledWithinTrachVentClass;
use App\Services\TrachVentClass\TrachVentClassClinicianNotifier;
use Illuminate\Support\Facades\DB;

final readonly class EmailClinicianAboutRescheduledClass
{
    public function __construct(private TrachVentClassClinicianNotifier $notifier)
    {
    }

    public function handle(ClinicianRescheduledWithinTrachVentClass $event): void
    {
        // Runs after the surrounding transaction commits, so a rollback sends nothing.
        DB::afterCommit(fn () => $this->notifier->rescheduled($event->registration, $event->occurrence, $event->newOccurrence, $event->rescheduledByName));
    }
}
