<?php

namespace App\Kantime\Jobs;

use App\Kantime\Services\ClaimsMasterSyncService;

class SyncClaimsMaster extends AbstractSyncJob
{
    public function handle(ClaimsMasterSyncService $claimsMasterSyncService): void
    {
        $claimsMasterSyncService->sync();
    }

    public function getType(): string
    {
        return ClaimsMasterSyncService::SYNC_TYPE;
    }
}
