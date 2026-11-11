<?php

namespace App\Kantime\Jobs;

use App\Kantime\Services\RenewablesSyncService;

class SyncRenewables extends AbstractSyncJob
{
    protected string $syncType = 'renewables';

    public function __construct(
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
        public readonly bool $force = false,
    ) {}

    public function handle(RenewablesSyncService $service): void
    {
        $service->sync($this->startDate, $this->endDate, $this->force);
    }

    public function getType(): string
    {
        return $this->syncType;
    }
}
