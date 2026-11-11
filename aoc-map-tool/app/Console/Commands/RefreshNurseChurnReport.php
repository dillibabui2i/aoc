<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RefreshNurseChurnReport extends Command
{
    protected $signature = 'reports:refresh-nurse-churn';
    protected $description = 'Invalidate the Nurse Churn Report cache (runs daily at 4 AM CST)';

    public function handle(): int
    {
        Cache::store('nurse_churn')->flush();
        $this->info('Nurse Churn Report cache invalidated.');

        return 0;
    }
}
