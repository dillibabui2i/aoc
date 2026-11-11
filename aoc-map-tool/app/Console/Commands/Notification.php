<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Notification as NotificationModel;

class Notification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete cleared notication older then 60 days';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $date = \Carbon\Carbon::now()->subDays(60);
        NotificationModel::where('cleared', 1)->where('cleared_at', '<=', $date)->delete();
        return 0;
    }
}
