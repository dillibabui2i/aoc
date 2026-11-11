<?php

namespace App\Kantime\Jobs;

use App\Kantime\Exception\KantimeSyncException;
use App\Kantime\Services\ServiceCodesSyncService;

class SyncServiceCodes extends AbstractSyncJob
{

    /**
     * @throws KantimeSyncException
     */
    public function handle(ServiceCodesSyncService $service)
    {
        $service->sync();
    }

    public function getType(): string
    {
        return ServiceCodesSyncService::SYNC_TYPE;
    }
}
