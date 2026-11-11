<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use App\FieldEvaluation;
use App\OfficeEvaluation as OfficeEV;

class RightsignatureReminderEval extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rightsignature:remindereval';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Reminder to unsigned document recipients for eval';

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
        $currentDate = date('Y-m-d',strtotime("-13 days"));
        H::ExcelLog("Start process to send evaluation reminders last sent on " . $currentDate,'EvaluationReminders.log');
        $pendingDocument = DB::table("aoc_documents")->select("*")->where("status","pending")->whereIn("document_type",["field_eval","therapy_eval","office_eval"])->where(function($q) use($currentDate){
            $q->whereNull("reminder_date")->orWhere("reminder_date","<",$currentDate);
        })->where("send_date","<",$currentDate)->limit(25)->get();

        //->where(function($qq){
          //  $qq->whereNull("reminder_count")->orWhere("reminder_count","<",2);
        //})->get();

     
       foreach($pendingDocument as $undsigned ) {
            
            $excelLogMessage = "Set evaluation status to 2nd Attempt";
            echo "\n";
            H::ExcelLog(print_r($undsigned,true),'EvaluationReminders.log');
            if(!empty($undsigned->document_id)) {
                
                $result = H::getHelper("RightSignature")->sendReminder($undsigned->document_id);
                var_dump($result);
                echo "\n\n\n";
                H::ExcelLog("result: " . print_r($result,true),'EvaluationReminders.log');
                $reminderData = DB::table("aoc_documents")->select("entity_id","reminder_count", "evaluation_id","document_type")->where("document_id",$undsigned->document_id)->first();
                //var_dump($reminderData);
                if(!empty($reminderData)) {
                    $reminderCount = $reminderData->reminder_count + 1;
                    echo 'count: ' . htmlspecialchars($reminderCount, ENT_QUOTES, 'UTF-8') . "\n";
                    if ($reminderCount == 2) {
                        if($reminderData->document_type=='office_eval') {
                           // OfficeEV::where("id", $reminderData->evaluation_id)->update(['status' => '2nd Attempt']);
                            H::ExcelLog($excelLogMessage,'EvaluationReminders.log');
                        } else {
                           
                            $documentData = H::getHelper("RightSignature")->getDocumentByDocumentId($undsigned->document_id);
                            if(isset($documentData['document']['recipients'][0])) {
                                $recipientData = $documentData['document']['recipients'][0];
                                $name = $recipientData['name'];
                                $email = $recipientData['email'];
                               
                                $noteData = [];
                                $noteData['entity'] = 'clinician';
                                $noteData['entity_id'] = $reminderData->entity_id;
                                $noteData['notes'] = "Annual evaluation was sent to ".$name." at ".$email." for the second time.";
                               
    
                                H::getHelper("ActivityNotes")->setActivityNotesFromConsole($noteData);
                            }
                            


                            FieldEvaluation::where("id", $reminderData->evaluation_id)->update(['status' => '2nd Attempt']);
                            H::ExcelLog($excelLogMessage,'EvaluationReminders.log');
                        }

                    }
                    if ($reminderCount == 3) {
                        if($reminderData->document_type=='office_eval') {
                           // OfficeEV::where("id", $reminderData->evaluation_id)->update(['status' => '3rd Attempt']);
                            H::ExcelLog($excelLogMessage,'EvaluationReminders.log');
                        } else {

                            $documentData = H::getHelper("RightSignature")->getDocumentByDocumentId($undsigned->document_id);
                            if(isset($documentData['document']['recipients'][0])) {
                                $recipientData = $documentData['document']['recipients'][0];
                                $name = $recipientData['name'];
                                $email = $recipientData['email'];
                               
                                $noteData = [];
                                $noteData['entity'] = 'clinician';
                                $noteData['entity_id'] = $reminderData->entity_id;
                                $noteData['notes'] = "Annual evaluation was sent to ".$name." at ".$email." for the 3rd and final time.";
                               
    
                                H::getHelper("ActivityNotes")->setActivityNotesFromConsole($noteData);


                                H::getHelper("Renewables")->setRenewableDates($undsigned);
                            }


                            FieldEvaluation::where("id", $reminderData->evaluation_id)->update(['status' => '3rd Attempt']);
                            H::ExcelLog("Set evaluation status to 3rd Attempt",'EvaluationReminders.log');
                        }
                    }
                }
            }
        }
        return 0;
    }

   
}
