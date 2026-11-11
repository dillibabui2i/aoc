<?php

namespace App\Listeners\TrachVentClass;

use App\Events\TrachVentClass\ClinicianScheduledIntoTrachVentClass;
use App\Services\TrachVentClass\TrachVentClassClinicianNotifier;
use Illuminate\Support\Facades\DB;

final readonly class EmailClinicianAboutScheduledClass
{
    public function __construct(private TrachVentClassClinicianNotifier $notifier)
    {
    }

    public function handle(ClinicianScheduledIntoTrachVentClass $event): void
    {
        // Runs after the surrounding transaction commits, so a rollback sends nothing.
        DB::afterCommit(fn () => $this->notifier->registered($event->registration, $event->occurrence, $event->scheduledByName));
    }
}
