<?php

namespace App\Listeners\TrachVentClass;

use App\Events\TrachVentClass\ClinicianCancelledFromTrachVentClass;
use App\Helpers\Notifications;
use Carbon\Carbon;

final readonly class NotifyInstructorAboutCancelledClinician
{
    public function handle(ClinicianCancelledFromTrachVentClass $event): void
    {
        $occurrence = $event->occurrence;

        if (empty($occurrence->instructor_id)) {
            return;
        }

        $title = sprintf(
            '%s has cancelled %s from class %s at %s %s. Click here to view class details.',
            $event->cancelledByName,
            $event->clinicianName,
            optional($occurrence->trachVentClass->classCategory)->name,
            getFormatedDate($occurrence->class_date),
            Carbon::parse($occurrence->class_time)->format('g:i A'),
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
