<?php

namespace App\Listeners;

use App\Events\NthInquiryCreated;
use App\Helpers\ActivityNotes;
use App\Repositories\NthInquiry\NthInquiryReadRepository;
use App\Services\NeedToHire\SupersededLeadNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use App\Helpers\LD;
use App\Helpers\Notifications;

class HandleNthInquiryCreated
{
    public function __construct(
        protected NthInquiryReadRepository $nthInquiryReadRepository,
        protected ActivityNotes $activityNotesHelper,
        protected SupersededLeadNotificationService $leadNotificationService
    ) {}

    public function handle(NthInquiryCreated $event)
    {
        $newestLead = $event->newestLead;

        $this->activityNotesHelper->setActivityNotesFromConsole([
            'entity' => 'nthalerts',
            'entity_id' => $newestLead->id,
            'notes' => "System: Superseded by LEAD-{$event->createdLeadId} ({$event->nthData->Recruiter})"
        ]);

        $allSupersededLeads = $this->nthInquiryReadRepository->getAllSupersededPendingLeads(
            $event->createdLeadId,
            $event->clinicianData->id
        );

        $this->leadNotificationService->processSupersededLeadsNotifications($allSupersededLeads, $event);

    }
}
