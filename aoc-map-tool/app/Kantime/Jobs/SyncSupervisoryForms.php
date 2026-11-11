<?php

namespace App\Kantime\Jobs;

use App\Kantime\Services\SupervisoryFormSyncService;

class SyncSupervisoryForms extends AbstractSyncJob
{
    public function handle(SupervisoryFormSyncService $service)
    {
        $service->sync();
    }

    public function getType(): string
    {
        return SupervisoryFormSyncService::SYNC_TYPE;
    }
}
