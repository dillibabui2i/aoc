<?php

namespace App\Kantime\Jobs;

use App\Kantime\Services\BillingInquiryNotesSyncService;

class SyncBillingInquiryNotes extends AbstractSyncJob
{
    public function handle(BillingInquiryNotesSyncService $billingInquiryNotesSyncService): void
    {
        $billingInquiryNotesSyncService->sync();
    }

    public function getType(): string
    {
        return BillingInquiryNotesSyncService::SYNC_TYPE;
    }
}
