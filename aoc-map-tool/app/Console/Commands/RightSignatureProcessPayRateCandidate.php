<?php

namespace App\Console\Commands;

use App\Enums\TodoSourceEnum;
use App\Notes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;

class RightSignatureProcessPayRateCandidate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rightsignature:payratecandidate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'RightSignature Proccess Payrate Candidate';

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
        $pendingDocument = DB::table("aoc_documents")->select("*")
        ->where("document_type","pay_rate")->where("entity_type","candidate")->get();

        foreach($pendingDocument as $candidatePayrate) {

             $trackerId = $candidatePayrate->tracker_id;
            $candidateId = $candidatePayrate->entity_id;

            $recruiterTracker = DB::table("mssql_recruitertracker")
            ->select("aoc_provider_id")
            ->where("id",$trackerId)->first();

            if(!empty($recruiterTracker->aoc_provider_id)) {
                $this->processDocument($candidatePayrate,$recruiterTracker->aoc_provider_id);
                $this->copyNotes($candidatePayrate,$recruiterTracker->aoc_provider_id);
            }

        }

        return 0;
    }

    public function processDocument($document,$provider){
        DB::table("aoc_documents")
        ->where("id",$document->id)
        ->update(["entity_id"=>$provider,"entity_type"=>"provider"]);

        return 1;
    }

    public function copyNotes($candidatePayrate,$provider){
       $candidateNotes = DB::table("notes")->select("*")
        ->where("entity","candidate")
        ->where("entity_id",$candidatePayrate->entity_id)
        ->where("misc4","pay_rate")->orderBy("id","asc")->get();

        foreach($candidateNotes as $note) {

            $noteData = json_decode(json_encode($note),true);

            unset($noteData['id']);
            unset($noteData['created_at']);
            unset($noteData['updated_at']);
            unset($noteData['misc4']);

            $noteData['entity'] = 'clinician';
            $noteData['source'] = TodoSourceEnum::AOC_PLATFORM->value;
            $noteData['entity_id'] = $provider;
            $noteData['created_at'] = date("Y-m-d H:i:s");
            $noteData['updated_at'] = date("Y-m-d H:i:s");

            DB::table("notes")->insert($noteData);
        }
    }
}
