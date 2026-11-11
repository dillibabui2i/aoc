<?php

namespace App\Kantime\Jobs;

use App\Kantime\Services\ClientMasterAdditionalSyncService;

class SyncClientMasterAdditional extends AbstractSyncJob
{
    public function handle(ClientMasterAdditionalSyncService $clientMasterAdditionalSyncService): void
    {
        $clientMasterAdditionalSyncService->sync();
    }

    public function getType(): string
    {
        return ClientMasterAdditionalSyncService::SYNC_TYPE;
    }
}
