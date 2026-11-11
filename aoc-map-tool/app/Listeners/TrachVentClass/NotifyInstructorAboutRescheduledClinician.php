<?php

namespace App\Listeners\TrachVentClass;

use App\Events\TrachVentClass\ClinicianRescheduledWithinTrachVentClass;
use App\Helpers\Notifications;
use Carbon\Carbon;

final readonly class NotifyInstructorAboutRescheduledClinician
{
    public function handle(ClinicianRescheduledWithinTrachVentClass $event): void
    {
        $occurrence = $event->occurrence;

        if (empty($occurrence->instructor_id)) {
            return;
        }

        $title = sprintf(
            '%s has rescheduled %s from class %s at %s %s to %s %s. Click here to view class details.',
            $event->rescheduledByName,
            $event->clinicianName,
            optional($occurrence->trachVentClass->classCategory)->name,
            getFormatedDate($occurrence->class_date),
            Carbon::parse($occurrence->class_time)->format('g:i A'),
            getFormatedDate($event->newOccurrence->class_date),
            Carbon::parse($event->newOccurrence->class_time)->format('g:i A'),
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
