<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;

class DownloadEvaluationOnetime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rightsignature:downloadeval';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'download evaluation onetime';

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
        $type = ['therapy_eval','office_eval'];

        $today = date("Y-m-d");
       
        $pendingDocument = DB::table("aoc_documents")->select("*")
        ->whereIn("document_type",$type)
        ->where("status","pending")
        ->whereDate("signed_date",'>=','2024-09-01')
        ->whereNotNull('signed_date')
        ->get();
       
       $cntr = 0;
       foreach($pendingDocument as $undsigned ) {
            if(!empty($undsigned->document_id)) {

                H::getHelper("RightSignature")->downloadSignedDocument($undsigned->document_id);
                DB::table("aoc_documents")->where("document_id",$undsigned->document_id)->update(['status'=>'executed']);

                H::ExcelLog($undsigned->document_id,"evaluation_download.log");
               
            }
       }
       
      


        return 0;
    }
    
}
