<?php

namespace App\Kantime\Jobs;

use App\Kantime\Services\PaymentSourceSyncService;

class SyncPaymentSource extends AbstractSyncJob
{

    public function handle(PaymentSourceSyncService $paymentSourceSyncService): void
    {
        $paymentSourceSyncService->sync();
    }

    public function getType(): string
    {
        return 'daas_paymentsource';
    }
}
