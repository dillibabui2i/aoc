<?php

namespace App\Kantime\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class AbstractSyncJob implements ShouldQueue, SyncJobInterface
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 1200;

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    /**
     * Optional: tags show up in Horizon.
     */
    public function tags(): array
    {
        return [
            'sync-daas',
            static::class,
            static::getType()
        ];
    }

    /**
     * Optional: common failure handler hook for all sync jobs.
     * Child can override and call parent::failed($e).
     */
    public function failed(\Throwable $e): void
    {
        logger()->error(static::class.' failed', [
            'message' => $e->getMessage(),
            'job' => static::class,
        ]);
    }
}
