<?php

namespace App\Kantime\Jobs;


use App\Kantime\Services\RenewablesTypeSyncService;

class SyncRenewablesTypes extends AbstractSyncJob
{

    public function getType(): string
    {
        return 'renewables_types';
    }

    public function handle(RenewablesTypeSyncService $service)
    {
        $service->sync();
    }
}
