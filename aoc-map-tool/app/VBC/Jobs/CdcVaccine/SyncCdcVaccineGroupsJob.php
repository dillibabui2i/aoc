<?php

namespace App\VBC\Jobs\CdcVaccine;

use App\Jobs\Job;
use App\VBC\Services\CdcVaccine\CdcVaccineSyncService;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncCdcVaccineGroupsJob extends Job
{
    public int $tries = 3;
    public int $backoff = 60;

    /**
     * The vaccine job is dispatched only after the groups are fully loaded (a failure here stops the run).
     */
    public function handle(CdcVaccineSyncService $service): void
    {
        $service->syncGroups();

        SyncCdcVaccinesJob::dispatch();
    }

    public function failed(Throwable $exception): void
    {
        Log::error('CDC vaccine groups sync failed', ['error' => $exception->getMessage()]);
    }
}
