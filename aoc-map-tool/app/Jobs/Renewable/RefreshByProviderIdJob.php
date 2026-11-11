<?php

namespace App\Jobs\Renewable;

use App\Jobs\UniqueJob;
use App\Kantime\Domain\ProviderId;
use App\Kantime\Services\RenewablesSyncService;

class RefreshByProviderIdJob extends UniqueJob
{
    /**
     * @param int $providerId
     */
    public function __construct(
        protected int $providerId
    ) {
    }

    public function handle(RenewablesSyncService $syncService): void
    {
        $syncService->syncOne(new ProviderId($this->providerId));
    }
}

