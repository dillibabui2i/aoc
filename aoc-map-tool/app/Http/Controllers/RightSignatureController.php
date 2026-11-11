<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use App\Helpers\LD;
use App\RightSignatureDocuments;
use App\FieldEvaluation;
use App\OfficeEvaluation;
use Illuminate\Support\Facades\Log;

class RightSignatureController extends Controller
{
   public function getToken(Request $request) {
    //echo "test";
      //  var_dump($request->all());
      $this->initiateCurl();
   }

   public function responseToken(Request $request) {
        var_dump($request->all());
   }

   public function handleCallBackRightSignatureEventLevel(Request $request) {

        $reqData = $request->all();
        $data = ["status"=>$reqData['status']];
        $cDate = date("Y-m-d H:i:s");

        if(isset($reqData['event'])) {
            switch($reqData['event']) {
                case "created":
                case "viewed":
                case "voided":
                case "declined":
                        break;
                case "executed":
                    H::getHelper("RightSignature")->downloadSignedDocument($reqData['id']);
                    break;
                case "signed":
                    $documentD = DB::table("aoc_documents")->select("*")->where("document_id", $reqData['id'])->first();
                    H::getHelper("Renewables")->setRenewableDates($documentD);
                    $data = array_merge($data,["signed_date"=>$cDate]);

                    if(isset($documentD)
                 && $documentD->document_type=='pay_rate' ) {
                        $noteData = [];
                        if($documentD->entity_type=='candidate') {
                            $noteData['entity'] = 'candidate';
                            $noteData['misc4'] = 'pay_rate';
                            $candidateData = DB::table("candidates")
                            ->where("id",$documentD->entity_id)->first();
                            $clinician = $candidateData->fullName;

                        } elseif($documentD->entity_type=='provider') {
                            $noteData['entity'] = 'clinician';
                            $providerData = DB::table("mssql_providers")
                            ->where("id",$documentD->entity_id)->first();
                            $clinician = $providerData->fullName;
                        }
                        $senderData = json_decode($documentD->extra_data,true);

                        $note = '';
                        $note.=$clinician.' signed Pay Rate Agreement
                            on  '.H::getFormatedDateTime($cDate).'<br><br>';
                        $note.='Client: '.$senderData['client_id'].'<br/>';
                        $note.='Pay Rate: '.$senderData['pay_rate'].'<br/>';
                        $note.='Schedule: '.$senderData['schedule'].'<br/>';
                        $note.='Approved by: '.$senderData['approved_by'].'<br/>';

                        $noteData['entity_id'] = $documentD->entity_id;
                        $noteData['notes'] =$note;
                        H::getHelper("ActivityNotes")->setActivityNotesFromConsole($noteData);
                        if(!empty($documentD->tracker_id) && $documentD->tracker_id) {
                            $noteData['entity_id'] = $documentD->tracker_id;
                            $noteData['entity'] = 'tracker-activity';
                            H::getHelper("ActivityNotes")->setActivityNotesFromConsole($noteData);
                        }



                    }

                    break;
                default:
                    break;

            }

            H::getHelper("RightSignature")
            ->updateDocumentStatusByDocumentId($reqData['id'],$data);
            $documentData = DB::table("aoc_documents")
            ->select("document_type", "status", "evaluation_id")
            ->where("document_id", $reqData['id'])->first();
            if(!empty($documentData) && ($documentData->status == 'executed' && ($documentData->document_type=='field_eval' || $documentData->document_type=='therapy_eval'))) {
                FieldEvaluation::where('id', $documentData->evaluation_id)->update(['status' => 'Completed']);
            } else if(!empty($documentData) && ($documentData->status == 'executed' && $documentData->document_type=='office_eval')){
                OfficeEvaluation::where('id', $documentData->evaluation_id)->update(['status' => 'Employee Signed']);
            }

        } else {
            //Do nothing
        }
        H::ExcelLog(print_r($request->all(),true),"rightsignaturedocumentevent.log");

   }

   public function handleCallBackRightSignatureDocumentLevel(Request $request) {
        $reqData = $request->all();
        H::ExcelLog(print_r($request->all(),true),"rightsignaturedocumentstatus.log");

        if(isset($reqData['status'])) {
            switch($reqData['status']) {
                case "completed":
                        $data = ["document_id"=>$reqData['document_template_id'],'status'=>"pending",'send_date'=>date("Y-m-d H:i:s",strtotime($reqData['created_at']))];
                        H::getHelper("RightSignature")->assignDocumentIdToRequestId($reqData['id'],$data);
                        H::ExcelLog("Inside completed case","rightsignaturedocumentstatus.log");
                            H::ExcelLog("Inside FF","rightsignaturedocumentstatus.log");
                            $documentData = DB::table("aoc_documents")->select("*")
                            ->where("document_id", $reqData['document_template_id'])
                            ->first();
                            $rightSignatureDocumentData = H::getHelper("RightSignature")
                            ->getDocumentByDocumentId($reqData['document_template_id']);

                            $signUrl = isset($rightSignatureDocumentData['document']['recipients'][0]['sign_url'])?
                            $rightSignatureDocumentData['document']['recipients'][0]['sign_url']
                            :'';
                            $rightData = ["document_url"=>$signUrl];
                            H::getHelper("RightSignature")
                            ->updateDocumentStatusByDocumentId($reqData['document_template_id'],$rightData);

                            if(isset($documentData) && $documentData->document_type=='pay_rate') {

                                $noteData = [];
                                $phone = '';
                                H::ExcelLog("Entity Type: ".$documentData->entity_type,"rightsignaturedocumentstatus.log");
                                if($documentData->entity_type=='candidate') {
                                    $noteData['entity'] = 'candidate';
                                    $noteData['misc4'] = 'pay_rate';
                                    $candidateData = DB::table("candidates")
                                    ->where("id",$documentData->entity_id)->first();
                                    $email = $candidateData->email;
                                    $phone = $candidateData->mobile;
                                    $firstName = $candidateData->firstName;
                                } elseif($documentData->entity_type=='provider') {
                                    $noteData['entity'] = 'clinician';
                                    $providerData = DB::table("mssql_providers")
                                    ->where("id",$documentData->entity_id)->first();
                                    $email = $providerData->AOC_Email;
                                    $phone = $providerData->Mobile;
                                    $firstName = $providerData->FirstName;
                                }
                                H::ExcelLog("Entity: ".$noteData['entity'],"rightsignaturedocumentstatus.log");
                                $senderData = json_decode($documentData->extra_data,true);
                                //Send SMS for pay rate
                                if($phone != '') {
                                    H::ExcelLog("Inside Phone","rightsignaturedocumentstatus.log");
                                    $tinyData['url'] = $docSignURL = $signUrl;
                                    $responseArray = H::getHelper('ConstantHelper')->getTinyUrl($tinyData);
                                    if(!empty($responseArray)) {
                                        $docSignURL = $responseArray['data']['tiny_url'];
                                    }
                                    H::ExcelLog("docSignURL for template_id=".$reqData['document_template_id']." is ".$docSignURL,
                                    "rightsignature_document.log");
                                    $smsMessageStr = "Hello ".$firstName.",";
                                    $smsMessageStr = $smsMessageStr." This is your Angels of Care Pay Rate Agreement.";
                                    $smsMessageStr = $smsMessageStr." Promptly click the link to view";
                                    $smsMessageStr = $smsMessageStr." and sign your documents.";
                                    $smsMessageStr = $smsMessageStr." Reply for help.";
                                    $smsMessageStr = $smsMessageStr." ".$docSignURL;
                                    $sendSmsData['message'] = $smsMessageStr;
                                    $sendSmsData['sms_to']  = $phone;
                                    H::ExcelLog("Send SMS for template_id=".$reqData['document_template_id'],
                                    "rightsignature_document.log");
                                    H::ExcelLog(print_r($sendSmsData,true),"rightsignature_document.log");
                                    H::getHelper('MessageWidget')->sendPayRateSMS($sendSmsData);
                                } else {
                                    H::ExcelLog("Not a valid phone for template_id=".
                                    $reqData['document_template_id'],"rightsignature_document.log");
                                }

                                $note = '';
                                $note.='Pay Rate Agreement was
                                 sent to '.$email.' & '.$phone.'<br/><br/>';
                                $note.='Client: '.$senderData['client_id'].'<br/>';
                                $note.='Pay Rate: '.$senderData['pay_rate'].'<br/>';
                                $note.='Schedule: '.$senderData['schedule'].'<br/>';
                                $note.='Approved by: '.$senderData['approved_by'].'<br/>';

                                $noteData['entity_id'] = $documentData->entity_id;
                                $noteData['notes'] =$note;
                                H::getHelper("ActivityNotes")->setActivityNotesFromConsole($noteData);

                                if(!empty($documentData->tracker_id) && $documentData->tracker_id) {
                                    $noteData['entity_id'] = $documentData->tracker_id;
                                    $noteData['entity'] = 'tracker-activity';
                                    H::getHelper("ActivityNotes")->setActivityNotesFromConsole($noteData);
                                }
                            }
                            H::ExcelLog(print_r($rightSignatureDocumentData,true),"rightsignature_document.log");
                        break;
                default:
                    break;
            }
        } else {
            //Do nothing
        }

    }

    public function executePastEvaluations(Request $request) {
        // Function is to update the renewables from past.
        $req = $request->all();

        $date = $req['date'];
        $type = isset($req['type'])?$req['type']:'';
        $defaultType = ["field_eval","therapy_eval"];
        $documentType = !empty($type)?[$type]:$defaultType;

        $aocDocuments = DB::table("aoc_documents")->select("*")
        ->whereIn("document_type",$documentType)
        ->where("status","executed")
        ->whereDate("signed_date",$date)->get();

       if(count($aocDocuments)) {
            foreach($aocDocuments as $aocDocument) {
                var_dump($aocDocument->document_id);
                echo "<br>";

                H::getHelper("RightSignature")->downloadSignedDocument($aocDocument->document_id);
            }
       } else {
            echo "No data";
       }


    }

    public function sendEvaluationTest(){
        // this function will be used only for sending the speicific evaluation for testitong purpose to rachel
        $fEvaluation = DB::table('field_evaluations')->select('mssql_providers.ProviderID','mssql_providers.External_Email','mssql_providers.DisciplineName','mssql_providers.HiredDate','mssql_providers.created_at as provider_created_at','field_evaluations.*', 'mssql_facilities.FacilityID','mssql_facilities.FacilityName', 'mssql_providers.ClinicianID', DB::raw("DATE_FORMAT(field_evaluations.created_at,'%m/%d/%Y') as fcreated_at"), 'users.name', DB::raw("CONCAT(mssql_providers.FirstName,' ', mssql_providers.LastName) as clinicianName"),'field_evaluations.status')
        ->leftJoin('mssql_providers', 'field_evaluations.aoc_provider_id', '=', 'mssql_providers.id')
        ->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_providers.FacilityID')
        ->leftJoin('users', 'users.id', '=', 'field_evaluations.manager')
        ->whereNotIn('mssql_providers.DisciplineName',H::getTherapyEvaluationDiscipline())
        ->where('mssql_providers.is_deleted',0)->where("field_evaluations.aoc_provider_id","353143")->limit(1)->first();
        $disciplineName = isset($fEvaluation->DisciplineName)?$fEvaluation->DisciplineName:'';
       //var_dump($fEvaluation);
       //exit;
        if(!H::isTherapyEvaluation($disciplineName)) {
            H::ExcelLog('Sending Evaluation for ' . $fEvaluation->clinicianName . ' to ' . $fEvaluation->External_Email,'sendFieldEvaluations.log');

            $clinicianName = isset($fEvaluation->clinicianName)?$fEvaluation->clinicianName:'';
            $PDFData['name']=$clinicianName;

            $hireDate = isset($fEvaluation->HiredDate)?$fEvaluation->HiredDate:'';
            $PDFData['hiredate']=H::getFormatedDate($hireDate);
            $PDFData['jobtitle']=$disciplineName;

            $provderCreatedAt = isset($fEvaluation->provider_created_at)?$fEvaluation->provider_created_at:'';
            //$PDFData['review_period']=H::getFormatedDate(date('Y-m-d', strtotime('first day of january previous year'))) . ' - ' . H::getFormatedDate(date('Y-m-d', strtotime('last day of december previous year')));
            $PDFData['review_period'] = '09/01/2023 - 08/31/2023';

            $PDFData['manager'] =  H::getHelper("RightSignature")->getCCMFromProvider($fEvaluation->ProviderID,'2022-01-04','2023-01-03');

            $fcreated_at = isset($fEvaluation->fcreated_at)?$fEvaluation->fcreated_at:'';
            $PDFData['createdDate'] = $fcreated_at;

            $PDFData['job_knowledge'] = $fEvaluation->item1_score;
            $PDFData['job_knowledge_comment'] = $fEvaluation->item1_note;
            $PDFData['work_quality'] = $fEvaluation->item2_score;
            $PDFData['work_quality_commnet'] = $fEvaluation->item2_note;
            $PDFData['attendance'] = $fEvaluation->item3_score;
            $PDFData['attendance_comment'] = $fEvaluation->item3_note;
            $PDFData['professionalism'] = $fEvaluation->item4_score;
            $PDFData['professionalism_comment'] = $fEvaluation->item4_note;
            //$PDFData['general_comment'] = $fEvaluation->comments;


            $fileName = 'field_evaluation_'.$fEvaluation->aoc_provider_id.'_'.strtotime("now").'.pdf';
            $template = 'fieldevaluation';

            $filePath = H::getHelper("RightSignature")->generateRightSignaturePDFTemplete($fileName,$PDFData,$template);

            if(is_file($filePath)) {
                $documentType = 'field_eval';
                $documentTitle = "Performance Evaluation";
                $recipientName = $PDFData['name'];
                //$recipientEmail = isset($fEvaluation->External_Email)?$fEvaluation->External_Email:'';
                //$recipientEmail = $fEvaluation->External_Email;
                $recipientEmail = 'rmeeks@angelsofcare.com';
                $sendDocumentResponse = H::getHelper("RightSignature")->sendDocumentRequest($filePath,0,$fEvaluation->aoc_provider_id,"provider",$documentType,$documentTitle,$recipientName,$recipientEmail,$fEvaluation->id);
                if (!empty($sendDocumentResponse)) {
                    FieldEvaluation::where('id', $fEvaluation->id)->update(['status' => '1st Attempt']);
                }

            }


        }

    }

    public function initiateCurl(){
        // intentionally kept blank for testing some right signature feature
    }

    public function getDocumentDescription(Request $request){
        $req = $request->all();

       // $documentId = '43b0c344-0350-4d73-874e-ba57d03833dc';
       $documentId = $req['documentid'];
       echo "<pre>";
        var_dump(H::getHelper("RightSignature")->getDocumentByDocumentId($documentId));
        exit;
    }

   public function generateAndSendNonTherapyPDF(Request $request)
   {
        $q = DB::table('field_evaluations')->select(
                'mssql_providers.ProviderID',
                'mssql_providers.External_Email',
                'mssql_providers.DisciplineName',
                DB::raw("IF(
                    (
                        mssql_providers.RehireDate = '0000-00-00' ||
                        mssql_providers.RehireDate = '1900-01-01' ||
                        mssql_providers.RehireDate = '' ||
                        mssql_providers.RehireDate is null
                    ),
                    mssql_providers.HiredDate,
                    coalesce(mssql_providers.RehireDate, mssql_providers.HiredDate)
                ) as HiredDate"),
                'mssql_providers.created_at as provider_created_at',
                'field_evaluations.*',
                'mssql_facilities.FacilityID',
                'mssql_facilities.FacilityName',
                'mssql_providers.ClinicianID',
                DB::raw("DATE_FORMAT(field_evaluations.created_at,'%m/%d/%Y') as fcreated_at"),
                'users.name',
                DB::raw("CONCAT(mssql_providers.FirstName,' ', mssql_providers.LastName) as clinicianName"),
                'field_evaluations.status'
            )
        ->leftJoin('mssql_providers', 'field_evaluations.aoc_provider_id', '=', 'mssql_providers.id')
        ->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_providers.FacilityID')
        ->leftJoin('users', 'users.id', '=', 'field_evaluations.manager')
        ->whereNotIn('mssql_providers.DisciplineName',H::getTherapyEvaluationDiscipline());

        $q->whereNotIn("field_evaluations.id",function($query){
            $query->select("evaluation_id")->from("aoc_documents")->where("document_type","field_eval");
        })->limit(25);
        $q->where('mssql_providers.is_deleted',0);
        $q->where('mssql_facilities.State',"!=","CO");
        if (LD::checkFeatureFlag('add-delete-reason-field-evaluation')) {
            $q->whereNested(function ($q) {
                $q->where('field_evaluations.is_deleted', 0)
                    ->orWhereNull('field_evaluations.is_deleted');
            });
        }
        $records = $q->get();

        foreach($records as $fEvaluation) {
            H::ExcelLog('Processing EvaluationId ' . $fEvaluation->id . ' for ' . $fEvaluation->clinicianName . ' (' . $fEvaluation->ClinicianID . ') in ' . $fEvaluation->FacilityName, 'sendFieldEvaluations.log');
            $disciplineName = isset($fEvaluation->DisciplineName) ? $fEvaluation->DisciplineName : '';
            H::ExcelLog('discipline name: ' . $disciplineName, 'sendFieldEvaluations.log');

            if (!H::isTherapyEvaluation($disciplineName)) {
                H::ExcelLog('Sending Evaluation for ' . $fEvaluation->clinicianName . ' to ' . $fEvaluation->External_Email, 'sendFieldEvaluations.log');

                $clinicianName = isset($fEvaluation->clinicianName) ? $fEvaluation->clinicianName : '';
                $PDFData['name'] = $clinicianName;

                $hireDate = isset($fEvaluation->HiredDate) ? $fEvaluation->HiredDate : '';
                $PDFData['hiredate'] = H::getFormatedDate($hireDate);
                $PDFData['jobtitle'] = $disciplineName;

                $provderCreatedAt = isset($fEvaluation->provider_created_at) ? $fEvaluation->provider_created_at : '';
                //$PDFData['review_period']=H::getFormatedDate(date('Y-m-d', strtotime('first day of january previous year'))) . ' - ' . H::getFormatedDate(date('Y-m-d', strtotime('last day of december previous year')));
                $PDFData['review_period'] = config('fieldevaluation.review_period');

                $PDFData['manager'] = H::getHelper("RightSignature")->getCCMFromProvider($fEvaluation->ProviderID, '2022-01-04', '2023-01-03');

                $fcreated_at = isset($fEvaluation->fcreated_at) ? $fEvaluation->fcreated_at : '';
                $PDFData['createdDate'] = $fcreated_at;

                $PDFData['job_knowledge'] = $fEvaluation->item1_score;
                $PDFData['job_knowledge_comment'] = $fEvaluation->item1_note;
                $PDFData['work_quality'] = $fEvaluation->item2_score;
                $PDFData['work_quality_commnet'] = $fEvaluation->item2_note;
                $PDFData['attendance'] = $fEvaluation->item3_score;
                $PDFData['attendance_comment'] = $fEvaluation->item3_note;
                $PDFData['professionalism'] = $fEvaluation->item4_score;
                $PDFData['professionalism_comment'] = $fEvaluation->item4_note;
                $PDFData['general_comment'] = $fEvaluation->comments;

                $fileName = 'field_evaluation_' . $fEvaluation->aoc_provider_id . '_' . strtotime("now") . '.pdf';
                $template = 'fieldevaluation';

                $filePath = H::getHelper('RightSignature')->generateRightSignaturePDFTemplete($fileName, $PDFData, $template);

                if (is_file($filePath)) {
                    $documentType = 'field_eval';
                    $documentTitle = 'Performance Evaluation';
                    $recipientName = $PDFData['name'];
                    $recipientEmail = isset($fEvaluation->External_Email) ? $fEvaluation->External_Email : '';

                    $sendDocumentResponse = H::getHelper('RightSignature')
                        ->sendDocumentRequest(
                            $filePath,
                            0,
                            $fEvaluation->aoc_provider_id,
                            'provider',
                            $documentType,
                            $documentTitle,
                            $recipientName,
                            $recipientEmail,
                            $fEvaluation->id
                        );

                    if (!empty($sendDocumentResponse)) {
                        FieldEvaluation::where('id', $fEvaluation->id)->update(['status' => '1st Attempt']);
                    }
                } else {
                    H::ExcelLog('Skipping Evaluation for ' . $fEvaluation->clinicianName, 'sendFieldEvaluations.log');
                }

                sleep(3);
                if (isset($request->isTestRequest)) { ////todo will remove after testing on prod
                    Log::info('Test request: ');
                    Log::info('Recepient email: '. $recipientEmail);
                    Log::info('fEvaluation->id: '. $fEvaluation->id);

                    exit;
                }
            }
        }
    }
}
