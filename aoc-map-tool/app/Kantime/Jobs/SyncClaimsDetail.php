<?php

namespace App\Kantime\Jobs;

use App\Kantime\Services\ClaimsDetailSyncService;

class SyncClaimsDetail extends AbstractSyncJob
{
    public function handle(ClaimsDetailSyncService $claimsDetailSyncService): void
    {
        $claimsDetailSyncService->sync();
    }

    public function getType(): string
    {
        return ClaimsDetailSyncService::SYNC_TYPE;
    }
}
