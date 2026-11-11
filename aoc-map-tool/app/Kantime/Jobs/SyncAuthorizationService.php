<?php

namespace App\Kantime\Jobs;

use App\Kantime\Services\AuthorizationServiceSyncService;

class SyncAuthorizationService extends AbstractSyncJob
{
    public function handle(AuthorizationServiceSyncService $authorizationServiceSyncService): void
    {
        $authorizationServiceSyncService->sync();
    }

    public function getType(): string
    {
        return AuthorizationServiceSyncService::SYNC_TYPE;
    }
}
