<?php

namespace App\Kantime\Jobs;

use App\Kantime\Services\ServiceCodeRatesSyncService;

class SyncServiceCodeRates extends AbstractSyncJob
{
    public function handle(ServiceCodeRatesSyncService $service)
    {
        $service->sync();
    }

    public function getType(): string
    {
        return ServiceCodeRatesSyncService::SYNC_TYPE;
    }
}
