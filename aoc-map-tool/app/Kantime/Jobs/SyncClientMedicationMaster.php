<?php

namespace App\Kantime\Jobs;

use App\Kantime\Services\ClientMedicationMasterSyncService;

class SyncClientMedicationMaster extends AbstractSyncJob
{
    public function handle(ClientMedicationMasterSyncService $clientMedicationMasterSyncService): void
    {
        $clientMedicationMasterSyncService->sync();
    }

    public function getType(): string
    {
        return ClientMedicationMasterSyncService::SYNC_TYPE;
    }
}
