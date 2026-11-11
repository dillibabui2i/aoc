<?php

namespace App\Console\Commands;

use App\SyncProcesses\DaasRenewables;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FullRenewablesResync extends Command
{
    protected $signature = 'renewables:full-resync {--force : Confirm full batch reset and resync}';
    protected $description = 'Runs a full resync of Renewables using the new stable logic (bypassing the old incremental batch)';

    public function handle(): int
    {
        if (!$this->option('force')) {
            $this->error("This command will RESET process_settings for daas_renewables and REBUILD all renewables.");
            $this->error("Run again with --force to continue:");
            $this->info("php artisan renewables:full-resync --force");
            return self::FAILURE;
        }

        DB::table('process_settings')
            ->where('process', 'daas_renewables')
            ->update([
                'last_updated_id'   => 0,
                'last_updated_date' => null,
                'running_now'       => 0,
                'last_batch_start'  => null,
                'last_batch_end'    => null,
            ]);

        $this->info("Starting FULL Renewables Resync...");

        $sync = new DaasRenewables();

        // IMPORTANT: run only the full resync flow
        $sync->runFullResync('daas_renewables');

        $this->info("Full Renewables Resync finished successfully.");

        return self::SUCCESS;
    }
}
