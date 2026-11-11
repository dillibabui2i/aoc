<?php

namespace App\Kantime\Jobs;

use App\Kantime\Services\ClaimsAdjustmentSyncService;

class SyncClaimsAdjustment extends AbstractSyncJob
{
    public function handle(ClaimsAdjustmentSyncService $claimsAdjustmentSyncService): void
    {
        $claimsAdjustmentSyncService->sync();
    }

    public function getType(): string
    {
        return ClaimsAdjustmentSyncService::SYNC_TYPE;
    }
}
