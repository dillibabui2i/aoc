<?php

namespace App\Listeners\TrachVentClass;

use App\Events\TrachVentClass\ClinicianCancelledFromTrachVentClass;
use App\Services\TrachVentClass\TrachVentClassClinicianNotifier;
use Illuminate\Support\Facades\DB;

final readonly class EmailClinicianAboutCancelledClass
{
    public function __construct(private TrachVentClassClinicianNotifier $notifier)
    {
    }

    public function handle(ClinicianCancelledFromTrachVentClass $event): void
    {
        // Runs after the surrounding transaction commits, so a rollback sends nothing.
        DB::afterCommit(fn () => $this->notifier->cancelled($event->registration, $event->occurrence, $event->cancelledByName));
    }
}
