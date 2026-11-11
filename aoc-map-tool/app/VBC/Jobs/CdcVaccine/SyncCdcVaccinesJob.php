<?php

namespace App\VBC\Jobs\CdcVaccine;

use App\Jobs\Job;
use App\VBC\Services\CdcVaccine\CdcVaccineSyncService;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncCdcVaccinesJob extends Job
{
    public int $tries = 3;
    public int $backoff = 60;

    public function handle(CdcVaccineSyncService $service): void
    {
        $service->syncVaccines();
        $service->notifyGroupsWithoutAocName();
    }

    public function failed(Throwable $exception): void
    {
        Log::error('CDC vaccines sync failed', ['error' => $exception->getMessage()]);
    }
}
