<?php

namespace App\Repositories\Notifications;

use App\Helpers\Notifications;
use App\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NotificationWriteRepository
{
    private Notification $notification;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    public function getExistingRecentNotificationsForLead($allSupersededLeadIds) {

        return DB::table('notifications')
            ->whereIn('related_item', $allSupersededLeadIds)
            ->where('type', 'supersededLead')
            ->where('created_at', '>', Carbon::now()->subMinutes(10))
            ->orderByDesc('created_at')
            ->get();
    }
    public function updateNotificationForSupersededLead($notificationId, $notesAddition)
    {
        DB::table('notifications')
            ->where('id', $notificationId)
            ->update([
                'notes' => DB::raw("CONCAT(COALESCE(notes, ''), '{$notesAddition}')"),
                'updated_at' => now(),
            ]);
    }

    public function snoozeSupersededLeadNotification($notificationId, $minutes = 10)
    {
        DB::table('notifications')
            ->where('id', $notificationId)
            ->update([
                'snooze_until' => Carbon::now()->addMinutes($minutes),
                'updated_at' => now(),
            ]);
    }

    public function createNotification($data): void
    {
        $this->notification->insert($data);
    }
}
