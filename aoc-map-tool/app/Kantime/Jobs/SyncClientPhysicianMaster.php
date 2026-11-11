<?php

namespace App\Kantime\Jobs;

use App\Kantime\Services\ClientPhysicianMasterSyncService;

class SyncClientPhysicianMaster extends AbstractSyncJob
{
    public function handle(ClientPhysicianMasterSyncService $clientPhysicianMasterSyncService): void
    {
        $clientPhysicianMasterSyncService->sync();
    }

    public function getType(): string
    {
        return ClientPhysicianMasterSyncService::SYNC_TYPE;
    }
}
