<?php

namespace App\Kantime\Jobs;

use App\Kantime\Services\ClaimDenialsSyncService;

class SyncClaimDenials extends AbstractSyncJob
{
    public function handle(ClaimDenialsSyncService $claimDenialsSyncService): void
    {
        $claimDenialsSyncService->sync();
    }

    public function getType(): string
    {
        return ClaimDenialsSyncService::SYNC_TYPE;
    }
}
