<?php

namespace App\Listeners\TrachVentClass;

use App\Events\TrachVentClass\ClinicianScheduledIntoTrachVentClass;
use App\Helpers\Notifications;
use Carbon\Carbon;

final readonly class NotifyInstructorAboutScheduledClinician
{
    public function handle(ClinicianScheduledIntoTrachVentClass $event): void
    {
        $occurrence = $event->occurrence;

        if (empty($occurrence->instructor_id)) {
            return;
        }

        $title = sprintf(
            '%s has scheduled %s %s for class %s at %s %s. Click here to view class details.',
            $event->scheduledByName,
            $event->nurseFirstName,
            $event->nurseLastName,
            optional($occurrence->trachVentClass->classCategory)->name,
            Carbon::parse($occurrence->class_date)->format('m/d/Y'),
            getFormatedTimeRange($occurrence->class_time, $occurrence->end_time),
        );

        Notifications::addNotification([
            'user_id' => $occurrence->instructor_id,
            'title' => $title,
            'notes' => $title,
            'type' => 'trachVentScheduled',
            'related_item' => $occurrence->id,
        ]);
    }
}
