<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateNthCheckListCurrentStep extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:nthchecklistcurrntstep';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Nth check list current step null to 1';

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
        $chunkSize = 100;
        DB::table("NeedToHire_Checklist")->whereNull('CurrentStep')->orderBy('id')->chunk($chunkSize, function($records) {
			foreach($records as $data) {
                echo $data->id . "\n";
				DB::table("NeedToHire_Checklist")->where('NeedToHire_Checklist.id',"=",$data->id)->update(["CurrentStep" => 1]);
			}
		});
        return 0;
    }
}
