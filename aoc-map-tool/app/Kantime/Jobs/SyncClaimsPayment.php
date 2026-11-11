<?php

namespace App\Kantime\Jobs;

use App\Kantime\Services\ClaimsPaymentSyncService;

class SyncClaimsPayment extends AbstractSyncJob
{
    public function handle(ClaimsPaymentSyncService $claimsPaymentSyncService): void
    {
        $claimsPaymentSyncService->sync();
    }

    public function getType(): string
    {
        return ClaimsPaymentSyncService::SYNC_TYPE;
    }
}
