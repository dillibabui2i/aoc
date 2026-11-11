<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use App\FieldEvaluation;
use App\OfficeEvaluation as OfficeEV;

class ProcessDocumentDescription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rightsignature:processdocument';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Right signature Document';

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
       
        $documents = DB::table("aoc_documents")->select("*")->whereIn("document_type",["field_eval","therapy_eval","office_eval"])->get();

     
       foreach($documents as $document ) {
            $type = $document->document_type;
            $sendDate = date("Y",strtotime($document->created_at));
            $description = '';
           
            if($type=='field_eval') {
                $description=$sendDate.' Field Evaluation'; 
            } elseif($type == 'therapy_eval'){
                $description = $sendDate . ' ' . "Therapy Evaluation";
            } elseif($type=='office_eval') {
                $description = $sendDate . ' ' . "Office Eval";
            }
            
            DB::table("aoc_documents")->where("id",$document->id)->update(["description"=>$description]);
            

            
        }
        return 0;
    }

   
}
