<?php

namespace App\Kantime\Jobs;

use App\Kantime\Services\ClientPaymentSourceSyncService;

class SyncClientPaymentSource extends AbstractSyncJob
{

    public function handle(ClientPaymentSourceSyncService $service) : void
    {
        $service->sync();
    }

    public function getType(): string
    {
        return 'daas_client_payment_source';
    }
}
