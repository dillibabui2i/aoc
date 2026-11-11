<?php

namespace App\Console\Commands;

use App\Events\Map\MapUpdatedEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessCandidateTracker extends Command
{
    private bool $mapTouched = false;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:candidatetracker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set candidate tracker for map';

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
        //start batch
        echo "start process\n";
        // set them all to on_tracker = 0
        $chunkSize = 100;
        DB::table("candidates")->where('on_tracker','1')->orderBy('id')->chunk($chunkSize, function ($records) {
            foreach ($records as $account) {
                DB::table("candidates")->where('candidates.id', "=", $account->id)->update(["on_tracker" => 0]);
            }
            $this->mapTouched = true;
        });

        $notTrackerArray = ['Inactive', 'First Shift Confirmed', 'Bonus Paid', 'Current Nurse Placed', 'PRN'];
        DB::table('mssql_recruitertracker')->whereNotIn("Candidate_Current_Status", $notTrackerArray)->where("aoc_candidate_id", ">", 1)->where("is_active", 1)->orderBy('aoc_candidate_id')->chunk($chunkSize, function ($records) {
            foreach ($records as $account) {
                echo "sync in process \n";
                DB::table("candidates")->where("id", $account->aoc_candidate_id)->update(['on_tracker' => 1]);
            }
            $this->mapTouched = true;
        });

        if ($this->mapTouched) {
            event(new MapUpdatedEvent('candidates'));
        }

        echo "sync finished \n";
        return 0;
    }
}
