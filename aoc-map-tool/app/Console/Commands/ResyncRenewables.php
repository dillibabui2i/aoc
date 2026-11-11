<?php

namespace App\Console\Commands;

use App\Kantime\Services\RenewablesSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ResyncRenewables extends Command
{
    protected $signature = 'renewables:resync {fromDate? : Date from which to re-sync data (Y-m-d)}';

    protected $description = 'Re-sync renewables data starting from the specified date';

    private const RESYNC_BATCH_SIZE = 500;

    public function handle(RenewablesSyncService $service): void
    {
        $providers = DB::table('renewables')
            ->where('updated_at', '>', $this->argument('fromDate') ?? '2026-04-25')
            ->select('ProviderID')
            ->groupBy('ProviderID')
            ->get()->pluck('ProviderID')->toArray();

        $this->info('Sync of providers data. Total count: ' . count($providers));
        //that was incorrectly updated after the fix [AOC-4283]
        foreach (array_chunk($providers, self::RESYNC_BATCH_SIZE) as $batchNumber => $batch) {
            try {
                $service->resync($batch);
            } catch (Throwable $e) {
                $this->error(sprintf('Resync batch #%d failed: %s', $batchNumber + 1, $e->getMessage()));
                logger()->error($e->getMessage(), ['exception' => $e]);
            }
        }
    }
}
