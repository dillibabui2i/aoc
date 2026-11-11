<?php

namespace App\Kantime\Jobs;

use App\Kantime\Services\AuthorizationsMasterSyncService;

class SyncAuthorizationsMaster extends AbstractSyncJob
{
    public function handle(AuthorizationsMasterSyncService $authorizationsMasterSyncService): void
    {
        $authorizationsMasterSyncService->sync();
    }

    public function getType(): string
    {
        return AuthorizationsMasterSyncService::SYNC_TYPE;
    }
}
