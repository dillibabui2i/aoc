<?php

namespace App\Listeners\TrachVentClass;

use App\Events\TrachVentClass\TrachVentClassOccurrenceCancelled;
use App\Helpers\Notifications;
use Carbon\Carbon;

final readonly class NotifySchedulerAboutCancelledOccurrence
{
    public function handle(TrachVentClassOccurrenceCancelled $event): void
    {
        $occurrence = $event->occurrence;

        if ($event->enrollerUserIds === []) {
            return;
        }

        $title = sprintf(
            '%s on %s, %s has been cancelled. Please take appropriate action for any enrolled clinicians.',
            $occurrence->trachVentClass->classCategory?->name ?? 'Class',
            Carbon::parse($occurrence->class_date)->format('m/d/Y'),
            getFormatedTimeRange($occurrence->class_time, $occurrence->end_time),
        );

        foreach ($event->enrollerUserIds as $userId) {
            Notifications::addNotification([
                'user_id' => $userId,
                'title' => $title,
                'notes' => $title,
                'type' => 'trachVentCancelled',
                'related_item' => $occurrence->id,
            ]);
        }
    }
}
