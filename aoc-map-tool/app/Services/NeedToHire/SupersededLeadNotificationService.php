<?php

namespace App\Services\NeedToHire;

use App\Helpers\Notifications;
use App\Repositories\Notifications\NotificationWriteRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Helpers\LD;

class SupersededLeadNotificationService
{
    public function __construct(
        private NotificationWriteRepository $notificationWriteRepository
    ) {}
    public function processSupersededLeadsNotifications($allSupersededLeads, $event)
    {
        $allSupersededLeadIds = collect($allSupersededLeads)->pluck('id')->toArray();

        if (!empty($allSupersededLeadIds)) {
            $existingRecentNotifications = $this->notificationWriteRepository->getExistingRecentNotificationsForLead(
                $allSupersededLeadIds
            );

            $notificationsByLead = $existingRecentNotifications->groupBy('related_item')->map(function ($group) {
                return $group->sortByDesc('created_at')->first();
            });

            foreach ($allSupersededLeads as $supersededLead) {
                $notificationOnHold = $notificationsByLead->get($supersededLead->id);

                if (LD::checkFeatureFlag('nth-superseded-lead-notifications', $supersededLead->email)) {
                    $notesAddition = "<br>•LEAD-{$event->createdLeadId} ({$event->nthData->Recruiter}).";

                    if ($notificationOnHold) {
                        // Update existing via repository
                        $this->notificationWriteRepository->updateNotificationForSupersededLead($notificationOnHold->id, $notesAddition);
                    } else {
                        // Create new notification
                        $notificationId = Notifications::addNotification([
                            "related_item" => $supersededLead->id,
                            "type" => 'supersededLead',
                            "title" => "Your open lead (LEAD-{$supersededLead->id}) was superseded by:",
                            "notes" => "<br>•LEAD-{$event->createdLeadId} ({$event->nthData->Recruiter}).",
                            "user_id" => $supersededLead->recruiter_id,
                        ], true);

                        // Snooze it
                        $this->notificationWriteRepository->snoozeSupersededLeadNotification($notificationId);
                    }
                }
            }
        }
    }
}
