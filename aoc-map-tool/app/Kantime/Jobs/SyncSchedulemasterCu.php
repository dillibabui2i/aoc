<?php

namespace App\Kantime\Jobs;

use App\Kantime\Services\ScheduleMasterSyncService;

class SyncSchedulemasterCu extends AbstractSyncJob
{
    public function __construct(
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
        public readonly bool $force = false,
    ) {
    }

    public function getType() : string
    {
        return 'schedulescu';
    }

    /**
     * Retry the job after 30s on failure (e.g. a DB lock), giving locks time to clear.
     */
    public function backoff(): array
    {
        return [30];
    }

    public function handle(ScheduleMasterSyncService $service): void
    {
        if ($this->force && !empty($this->startDate)) {
            $service->backfill($this->startDate, $this->endDate);
            return;
        }

        $service->sync($this->getType());
    }
}
