<?php

namespace App\Kantime\Jobs;

use App\Kantime\Services\ReferralSourceSyncService;

class SyncReferralSource extends AbstractSyncJob
{

    public function handle(ReferralSourceSyncService $referralSourceSyncService)
    {
        $referralSourceSyncService->sync();
    }

    public function getType(): string
    {
        return ReferralSourceSyncService::SYNC_TYPE;
    }
}
