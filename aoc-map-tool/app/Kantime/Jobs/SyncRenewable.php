<?php

namespace App\Kantime\Jobs;

use App\Kantime\Domain\ProviderId;
use App\Kantime\Services\RenewablesSyncService;

class SyncRenewable extends AbstractSyncJob
{
    protected string $syncType = 'renewables';

    public function __construct(private readonly int $id)
    {
    }

    public function handle(RenewablesSyncService $service): void
    {
        $service->syncOne(new ProviderId($this->id));
    }

    public function getType(): string
    {
        return $this->syncType;
    }
}
