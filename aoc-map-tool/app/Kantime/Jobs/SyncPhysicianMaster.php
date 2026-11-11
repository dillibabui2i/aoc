<?php

namespace App\Kantime\Jobs;

use App\Kantime\Services\PhysicianMasterSyncService;

class SyncPhysicianMaster extends AbstractSyncJob
{
    public function handle(PhysicianMasterSyncService $physicianMasterSyncService): void
    {
        $physicianMasterSyncService->sync();
    }

    public function getType(): string
    {
        return PhysicianMasterSyncService::SYNC_TYPE;
    }
}
