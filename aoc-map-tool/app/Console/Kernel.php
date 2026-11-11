<?php

namespace App\Console;

use App\Kantime\Common\KantimeScheduler;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        app(KantimeScheduler::class)->register($schedule);

        if (config('cdc_vaccines.schedule_enabled')) {
            $schedule->command('vbc:cdc-vaccines-sync')
                ->cron(config('cdc_vaccines.schedule_cron'))
                ->withoutOverlapping();
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load([__DIR__ . '/Commands', __DIR__ . '/Kantime/Console/Commands']);

        require base_path('routes/console.php');
    }
}
