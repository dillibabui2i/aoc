<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;

class RightsignatureReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rightsignature:reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Reminder to unsigned document recipients';

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
         $currentDate = date('Y-m-d',strtotime("-1 days"));
        $today = date("Y-m-d");
       
        $pendingDocument = DB::table("aoc_documents")->select("*")
        ->where("document_type","pay_rate")->where("status","pending")->where(function($q) use($currentDate){
            $q->whereNull("reminder_date")->orWhere("reminder_date","<",$currentDate);
        })->where("send_date","<",$currentDate)->get();
       
       
       foreach($pendingDocument as $undsigned ) {
            if(!empty($undsigned->document_id)) {
                H::getHelper("RightSignature")->sendReminder($undsigned->document_id);
                $this->sendNotes($undsigned->document_id);
            }
       }
       
       // send reminder to whose candidate start is near
       $this->sendReminderToCandidateStart();



        return 0;
    }

    public function sendNotes($documentId){

        $reminderNotes = DB::table("aoc_documents")
        ->select("aoc_documents.*")->where("document_id",$documentId)->first();
        $email = '';
        if($reminderNotes->entity_type=='candidate') {
            $noteData['entity'] = 'candidate';
            $candidateData = DB::table("candidates")->where("id",$reminderNotes->entity_id)->first();
            $email = $candidateData->email;
        } else {
            $noteData['entity'] = 'clinician';
            $providerData = DB::table("mssql_providers")->where("id",$reminderNotes->entity_id)->first();
            $email = $providerData->AOC_Email;
        }


        $note = '';
        $note.='Automated Email Reminder - Pay Rate Agreement sent
         to '.$email.' on '.H::getFormatedDate(date("Y-m-d")).'
         for '.$this->numToWords($reminderNotes->reminder_count).' time.';
        
        $noteData['entity_id'] = $reminderNotes->entity_id;
        $noteData['notes'] =$note;
        H::getHelper("ActivityNotes")->setActivityNotesFromConsole($noteData);

        if($reminderNotes->tracker_id) {
            $noteData['entity_id'] = $reminderNotes->tracker_id;
            $noteData['entity'] = 'tracker-activity';
            H::getHelper("ActivityNotes")->setActivityNotesFromConsole($noteData);
        }
       

    }

    public function sendReminderToCandidateStart(){
        $today = date("Y-m-d");

       
        $candidateStartFinding = DB::table("aoc_documents")
        ->select("aoc_documents.*","mssql_recruitertracker.Candidate_Start_Date")
        ->where("status","pending")->where("document_type","pay_rate")->where(function($q) use($today){
            $q->whereNull("reminder_date")->orWhere("reminder_date","<",$today);
        })->leftJoin("mssql_recruitertracker",function($query){
            $query->on("aoc_documents.tracker_id", "=", "mssql_recruitertracker.id");
        })->get();

       

        foreach($candidateStartFinding as $candidate) {
            $candidateStartDate = date("Y-m-d",strtotime($candidate->Candidate_Start_Date));
            $candidateStartPreviousDay =    date('Y-m-d',strtotime("-1 days",strtotime($candidateStartDate)));

            if(strtotime($today)==strtotime($candidateStartDate) || strtotime($today)==strtotime($candidateStartPreviousDay)) {
                H::getHelper("RightSignature")->sendReminder($candidate->document_id);
                $this->sendNotes($candidate->document_id);
            }
        }

    }

    public function numToWords($number) {
        $units = array('', 'one', 'two', 'three', 'four',
        'five', 'six', 'seven', 'eight', 'nine');
    
        $tens = array('', 'ten', 'twenty', 'thirty',
        'forty','fifty', 'sixty', 'seventy', 'eighty','ninety');
    
        $special = array('eleven', 'twelve', 'thirteen',
                         'fourteen', 'fifteen', 'sixteen',
                         'seventeen', 'eighteen', 'nineteen');
    
        $words = '';
        if ($number < 10) {
            $words .= $units[$number];
        } elseif ($number < 20) {
            $words .= $special[$number - 11];
        } else {
            $words .= $tens[(int)($number / 10)] . ' '
                      . $units[$number % 10];
        }
    
        return $words;
    }
    
}
