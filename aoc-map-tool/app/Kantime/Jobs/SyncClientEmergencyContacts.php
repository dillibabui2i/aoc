<?php

namespace App\Kantime\Jobs;

use App\Kantime\Services\ClientEmergencyContactsSyncService;

class SyncClientEmergencyContacts extends AbstractSyncJob
{
    public function handle(ClientEmergencyContactsSyncService $service)
    {
        $service->sync();
    }

    public function getType(): string
    {
        return 'daas_emergencycontact';
    }
}
