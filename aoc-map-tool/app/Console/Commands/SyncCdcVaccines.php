<?php

namespace App\Console\Commands;

use App\VBC\Jobs\CdcVaccine\SyncCdcVaccineGroupsJob;
use Illuminate\Console\Command;

class SyncCdcVaccines extends Command
{
    protected $signature = 'vbc:cdc-vaccines-sync';

    protected $description = 'Sync CDC vaccine groups and vaccines (queued); notify about groups without an AOC name';

    public function handle(): int
    {
        SyncCdcVaccineGroupsJob::dispatch();
        $this->info('CDC vaccines sync dispatched.');

        return self::SUCCESS;
    }
}
