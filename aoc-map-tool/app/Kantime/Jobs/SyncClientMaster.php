<?php

namespace App\Kantime\Jobs;

use App\Kantime\Services\ClientMasterSyncService;

class SyncClientMaster extends AbstractSyncJob
{
    public int $timeout = 3000;

    public function handle(ClientMasterSyncService $service): void
    {
        $service->sync();
    }

    public function getType(): string
    {
        return ClientMasterSyncService::SYNC_TYPE;
    }
}