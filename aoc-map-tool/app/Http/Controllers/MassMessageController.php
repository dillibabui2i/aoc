<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\H;
use App\Conversation;
use Twilio\Jwt\AccessToken;
use Twilio\Rest\Client;
use Twilio\Jwt\Grants\ChatGrant;
use App\User;
use Illuminate\Support\Facades\Auth;
use App\MassMessage;
use DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Helpers\LD;
use App\TextTemplate;
class MassMessageController extends Controller
{
    //
    public function conversationWebhook(Request $request)
    {
        H::ExcelLog("request",'conversationWebhook.log');
        H::ExcelLog(print_r($request->all(), true),'conversationWebhook.log');
        $responseArray = $request->all();
        //1. Check if From and To already have conversation
        //1.1 Get user_id and aoc_provider_id or aoc_candidate_id based on mobile
        $receiveData = [];
        $receiveData['aoc_candidate_id'] = $receiveData['aoc_provider_id'] = 0;
        $getLatestSender = DB::table('mass_message')
        ->whereRaw("FIND_IN_SET(?, receivers)", [$responseArray['From']])
        ->where('started_at','!=',null)
        ->where('completed_at','!=',null)
        ->orderBy('completed_at','DESC')
        ->first();
        if(empty($getLatestSender)) {
            H::ExcelLog("mass message record not exist for mobile=".$responseArray['From'],'conversationWebhookData.log');
            exit;
        }
        $sendersData = User::where('id',$getLatestSender->user_id)->first();
        $receiveData['user_id'] = $sendersData->id;
        $responseArray['To'] = $sendersData->sending_sms_number;
        $formatRecieverNumberQuery = str_replace("+","",$responseArray['From']);
        $recieverNumberQuery = '(case WHEN length(REGEXP_REPLACE(`mobile`, "[^0-9]", "")) = 11
        THEN REGEXP_REPLACE(`mobile`, "[^0-9]", "")
        WHEN length(REGEXP_REPLACE(`mobile`, "[^0-9]", "")) = 10
        THEN concat("1",REGEXP_REPLACE(`mobile`, "[^0-9]", "")) end)';
        $candidateData = DB::table('candidates')->whereRaw($recieverNumberQuery."=?",[$formatRecieverNumberQuery])
        ->where('mobile','!=','')->latest('id')->first();
        $receiveData['friendly_name'] = '';
        $receiveData['unique_name'] = $receiveData['friendly_name'] =$receiveData['From'] =$responseArray['From'];
        $receiveData['To'] =$responseArray['To'];

        if(empty($candidateData)) {
            $recieverNumberQuery = '(case when length(REGEXP_REPLACE(`Mobile`, "[^0-9]", "")) = 11
            then REGEXP_REPLACE(`Mobile`, "[^0-9]", "")
            when length(REGEXP_REPLACE(`Mobile`, "[^0-9]", "")) = 10
            then concat("1",REGEXP_REPLACE(`Mobile`, "[^0-9]", "")) end)';
            $candidateData = DB::table('mssql_providers')
            ->whereRaw($recieverNumberQuery."=?",[$formatRecieverNumberQuery])
            ->where('mssql_providers.is_deleted',0)->where('Mobile','!=','')->first();
            $receiveData['aoc_provider_id'] = $candidateData->id;
            $receiveData['friendly_name'] = $candidateData->FirstName.' '.$candidateData->LastName;
            $receiveData['unique_name'] = $receiveData['aoc_provider_id'].'#'.$responseArray['From'].'#clinician';
        } else {
            $receiveData['aoc_candidate_id'] = $candidateData->id;
            $receiveData['friendly_name'] = $candidateData->firstName.' '.$candidateData->lastName;
            $receiveData['unique_name'] = $receiveData['aoc_candidate_id'].'#'.$responseArray['From'].'#candidate';
        }

        //Added recruiter number to make unique name
        $receiveData['unique_name'] = $receiveData['unique_name']."#".$receiveData['To'];
        $receiveData['receiver_mobile'] = $responseArray['From'];
        //$recruiterNumber = str_replace("+","",$responseArray['To']);
        //$recruiterNumber = $responseArray['To'];
        //$sendersData = User::where('sending_sms_number',$recruiterNumber)->first();
        //$receiveData['user_id'] = $sendersData->id;
        H::ExcelLog(print_r($receiveData, true),'conversationWebhookData.log');
        $massMessageData = DB::table('mass_message')
        ->where('user_id',$receiveData['user_id'])
        ->whereRaw("FIND_IN_SET(?, receivers)", [$responseArray['From']])
        ->where('started_at','!=',null)
        ->where('completed_at','!=',null)
        ->whereRaw('started_at >= DATE_SUB(CURDATE(),INTERVAL 7 DAY)')
        ->orderBy('completed_at','ASC')
        ->get()
        ->toArray();
        if(count($massMessageData) == 0) {
            $massMessageData = DB::table('mass_message')
            ->where('user_id',$receiveData['user_id'])
            ->whereRaw("FIND_IN_SET(?, receivers)", [$responseArray['From']])
            ->where('started_at','!=',null)
            ->where('completed_at','!=',null)
            ->orderBy('completed_at','DESC')
            ->limit(1)
            ->get()
            ->toArray();
        }
		//Code to check if reploy having stop word
        $stopWordFound = 0;
        $stopWordList = ['stop'];
        foreach ($stopWordList as $stopWord) {
            $pos = strpos(strtolower($responseArray['Body']), $stopWord);
            if ($pos !== FALSE && $pos == 0) {
                $stopWordFound = 1;
            }
        }
        H::ExcelLog("stopWordFound=".$stopWordFound,'conversationWebhookData.log');
        //Code to set is_ignore in map table if candidate reply with stop word
        $showMassTextOptionMapArray = ['maps_textblast','maps_covclin','candidates','maps_bon_fl'];
        if($stopWordFound == 1) {
            if($receiveData['aoc_candidate_id'] > 0) {
                foreach ($showMassTextOptionMapArray as $map_tablename) {
                    $candidateMapDetails = $paramsArray = [];
                    $matchFinalQuery = '';
                    $matchQuery = '(case when length(REGEXP_REPLACE(`Mobile`, "[^0-9]", "")) = 11
                    then REGEXP_REPLACE(`Mobile`, "[^0-9]", "")
                    when length(REGEXP_REPLACE(`Mobile`, "[^0-9]", "")) = 10
                    then concat("1",REGEXP_REPLACE(`Mobile`, "[^0-9]", "")) end)';
                    if($map_tablename == 'candidates') {
                        $matchQuery = str_replace("Mobile",'mobile',$matchQuery);
                    }
                    if($map_tablename == 'maps_bon_fl') {
                        $matchQuery = str_replace("Mobile",'Phone_Number',$matchQuery);
                    }
                    $matchFinalQuery = $matchQuery."=?";
                    $paramsArray[] = $formatRecieverNumberQuery;
                    if($map_tablename == 'maps_textblast' || $map_tablename == 'maps_covclin') {
                        $matchNewQuery = '(case when length(REGEXP_REPLACE(`Phone`, "[^0-9]", "")) = 11
                        then REGEXP_REPLACE(`Phone`, "[^0-9]", "")
                        when length(REGEXP_REPLACE(`Phone`, "[^0-9]", "")) = 10
                        then concat("1",REGEXP_REPLACE(`Phone`, "[^0-9]", "")) end)';
                        $matchFinalQuery = $matchFinalQuery.' OR '.$matchNewQuery."=?";
                        array_push($paramsArray, $formatRecieverNumberQuery);
                    }
                    if (Schema::hasTable($map_tablename)) {
                        $candidateMapDetails = DB::table($map_tablename)
                        ->whereRaw($matchFinalQuery,$paramsArray)
                        ->latest('id')->first();
                        if(!empty($candidateMapDetails)) {
                            H::ExcelLog( 'Record id ='.$candidateMapDetails->id .' for map ' . $map_tablename.' is set to is_ignored','conversationWebhookMap.log');
                            DB::table($map_tablename)->where('id', $candidateMapDetails->id)->update(['is_ignored' =>1]);
                        }
                    }
                }
            }
            //Opt out
            $optOutDetails = DB::table('text_opt_out')->where('phone',$responseArray['From'])->latest('id')->first();
            if(empty($optOutDetails)) {
                H::ExcelLog( 'Number ='.$responseArray['From'] .' is set to text_opt_out','conversationWebhookOptout.log');
                DB::table('text_opt_out')->insert(['phone' =>$responseArray['From']]);
            }
        }
        if(count($massMessageData) > 0) {
            //1.2 Check if conversation exist
            $conversationDataExist = Conversation::where('user_id',$receiveData['user_id'])->where('receiver_mobile',$responseArray['From'])->first();
            //2. If not, create one conversation and add participants
            if(empty($conversationDataExist)) {
                H::ExcelLog("conversationDataExist",'conversationWebhookData.log');
				//If stop word found in reply and if conversation does not exists, then we should not create new conversation
                if($stopWordFound == 1) {
                    return false;
                }
                $conversationName = $receiveData['friendly_name'];
                //create conversation on Twilio
                $conversationDetails =  H::getHelper('MassMessageWidget')->createConversation($receiveData);
                try{
                    //add recruiter as chat participant
                    $addRecruiterParticipantData = ['conversation_id' => $conversationDetails->sid,'participant_number' => $responseArray['To']];
                    $participantsRecruiterDetails =  H::getHelper('MassMessageWidget')->addChatParticipants($addRecruiterParticipantData);
                    //Insert record into conversation table
                    $participantsData = ['sid' => $conversationDetails->sid,'messaging_service_sid' => $conversationDetails->messagingServiceSid,'chat_service_sid' => $conversationDetails->chatServiceSid,'user_id' => $receiveData['user_id'],'api_response' => serialize($conversationDetails),'aoc_provider_id' => $receiveData['aoc_provider_id'],'aoc_candidate_id' => $receiveData['aoc_candidate_id'],'unique_name' => $receiveData['unique_name'],'friendly_name' => $receiveData['friendly_name'],'receiver_mobile' => $receiveData['receiver_mobile']];
                    $id = Conversation::insertGetId($participantsData);
                    H::ExcelLog("Conversation Insert DATA",'conversationWebhookData.log');
                    H::ExcelLog(print_r($participantsData, true),'conversationWebhookData.log');
                    //Add old mass message into conversation
                    foreach($massMessageData as $massMessageTemp) {
                        $initialMessage  = ['conversation_id' =>$id, 'user_id' => $receiveData['user_id'],'aoc_provider_id' => 0,'aoc_candidate_id' => 0,'message'=>$massMessageTemp->message];
                        DB::table('conversation_reply')->insert($initialMessage);
                        $initialAddMessage['author_number'] = $responseArray['To'];
                        $initialAddMessage['conversation_id'] = $conversationDetails->sid;
                        $initialAddMessage['message'] = $massMessageTemp->message;
                        H::getHelper('MassMessageWidget')->addMessageToConversation($initialAddMessage);
                    }
                    //Add reply to conversation
                    $replyMessage = ['conversation_id' =>$id, 'user_id' => 0,'aoc_provider_id' => $receiveData['aoc_provider_id'],
                        'aoc_candidate_id' => $receiveData['aoc_candidate_id'],'message'=>$responseArray['Body']];
                    DB::table('conversation_reply')->insert($replyMessage);
                    //Add reply to conversation on Twilio
                    $replyAddMessage['author_number'] = $responseArray['From'];
                    $replyAddMessage['message'] = $responseArray['Body'];
                    $replyAddMessage['conversation_id'] = $conversationDetails->sid;
                    H::getHelper('MassMessageWidget')->addMessageToConversation($replyAddMessage);
                    //Add lastReadMessageIndex to 0 for Recruiter
                    $updateParticipantData = ['conversation_id' => $conversationDetails->sid,'participant_id' => $participantsRecruiterDetails->sid];
                    H::getHelper('MassMessageWidget')->updateParticipants($updateParticipantData);
                    //add candidate as non-chat participant
                    $addCandidateParticipantData = ['conversation_id' => $conversationDetails->sid,
                    'participant_number' => $responseArray['From'],
                    'twilio_number' =>  env("TWILIO_TOLL_FREE")];
                    $participantsCandidateDetails =  H::getHelper('MassMessageWidget')->addParticipants($addCandidateParticipantData);
                } catch (\Twilio\Exceptions\RestException $re) {
                    H::ExcelLog("Participants=".$responseArray['From'] . ' : ' . $responseArray['To'],'conversationWebhookError.log');
                    H::ExcelLog($re->getStatusCode() . ' : ' . $re->getMoreInfo(),'conversationWebhookError.log');
                    H::ExcelLog(print_r($re->getDetails(), true),'conversationWebhookError.log');
                } catch (\Exception $e) {
                    H::ExcelLog($e->getCode() . ' : ' . $e->getMessage(),'conversationWebhookError.log');
                }
            }else {
                H::ExcelLog("conversationDataExist=".$conversationDataExist->id,'conversationWebhookData.log');
				try{
                    //Mark conversation is_read = 0 for any new message
                    Conversation::where('id',$conversationDataExist->id)->update(['is_read'=>0]);
					DB::table('conversation_reply')->insert(
						['conversation_id' =>$conversationDataExist->id, 'user_id' => 0,'aoc_provider_id' => $receiveData['aoc_provider_id'],
						'aoc_candidate_id' => $receiveData['aoc_candidate_id'],'message'=>$responseArray['Body']]
					);
				}catch (\Throwable $th) {
					H::ExcelLog( $th->getMessage(),'conversationWebhookError.log');
				}
            }
        }else {
            //Case when mass message does not exist but candidate reply for direct message.
            //1.2 Check if conversation exist
            $conversationDataExist = Conversation::where('user_id',$receiveData['user_id'])->where('receiver_mobile',$responseArray['From'])->first();
            //2. If not, create one conversation and add participants
            if(!empty($conversationDataExist)) {
                //Mark conversation is_read = 0 for any new message
                Conversation::where('id',$conversationDataExist->id)->update(['is_read'=>0]);
                DB::table('conversation_reply')->insert(
                    ['conversation_id' =>$conversationDataExist->id, 'user_id' => 0,'aoc_provider_id' => $receiveData['aoc_provider_id'],
                    'aoc_candidate_id' => $receiveData['aoc_candidate_id'],'message'=>$responseArray['Body']]
                );
            }
        }
    }

    public function saveMassMessage(Request $request)
    {
        H::ExcelLog(print_r($request->all(), true),'saveMassMessage.log');
        try{
            $requestData = $request->all();
            H::ExcelLog("frompage == ".$requestData['frompage'],'saveMassMessage.log');
            $receiversNumber = $receiversIdArr = [];
            if ($requestData['frompage'] == 'candidates') {
                $candidateIdArray = explode(",",$requestData['candidate_ids']);
                foreach($candidateIdArray as $candidateIdData) {
                    $candidateData = DB::table('candidates')->where('id',$candidateIdData)->first();
                    if(!empty($candidateData) && $candidateData->mobile != '') {
                        $candidateReceiversNumber = H::getHelper('MassMessageWidget')->getFormatPhoneForMessage($candidateData->mobile);
                        if($candidateReceiversNumber  != '') {
                            $receiversNumber[]= $candidateReceiversNumber;
                        }else {
							H::ExcelLog("Invalid Mobile for ClinicianID=".$candidateIdData,'invalidMobileMassText.log');
						}
                    }else {
                        H::ExcelLog("Invalid Mobile for ClinicianID=".$candidateIdData,'invalidMobileMassText.log');
                    }
                }
            } elseif ($requestData['frompage'] == 'maps_textblast') {
                $mapInfo = H::getSelectedMapInfo();
                if($requestData['candidate_ids'] == 'selectallmap') {
                    H::ExcelLog("candidate_ids == selectallmap",'saveMassMessage.log');
                    $reqD = $request->all();
                   
                    $reqD['type'] = 'download';
                    $reqD['search']['value'] = $requestData['dtsearch'];
                    $data = H::getMapData($reqD);

                    if(LD::checkFeatureFlag('text-blast-map-sms-radius-limit')) {
                        $loggedInUser = H::getLoggedInUser();
                        $smsLimit = is_numeric($loggedInUser['sms_radius_limit'])?$loggedInUser['sms_radius_limit']:50;
                        if($data){ 
                            $tempData = [];
                            foreach($data as $d) {
                                if($d->distance){
                                    if($d->distance <= $smsLimit) {
                                        $tempData[] = $d;
                                    }
                                }
                            }
                            if(!empty($tempData)) {
                                $data = $tempData;
                            }
                        }
                    }
                    H::ExcelLog("Total Map Data =".count($data),'saveMassMessage.log');
					$ClinicianID = 0;
                    foreach ($data as $tableData) {
                        if ($mapInfo[0]->map_tablename == 'candidates') {
                            if($tableData->mobile != '') {
                                $candidateReceiversNumber = H::getHelper('MassMessageWidget')->getFormatPhoneForMessage($tableData->mobile);
                                if($candidateReceiversNumber  != '') {
                                    $receiversNumber[]= $candidateReceiversNumber;
                                }else {
                                    H::ExcelLog("Invalid Mobile for CandidateId=".$tableData->id,'invalidMobileMassText.log');
                                }
                            }else {
                                H::ExcelLog("Invalid Mobile for CandidateId=".$tableData->id,'invalidMobileMassText.log');
                            }
                        } else {
                            
                            if ($mapInfo[0]->map_tablename == 'maps_textblast') {
                                $ClinicianID = $tableData->Clinician_ID;
                            }elseif($mapInfo[0]->map_tablename == 'maps_covclin') {
                                $ClinicianID = $tableData->ClinicianID;
                            }
                            H::ExcelLog("ClinicianID=".$ClinicianID,'saveMassMessage.log');
                            $candidateData = DB::table('mssql_providers')->where('ClinicianID', $ClinicianID)->first();
                            if(!empty($candidateData) && $candidateData->Mobile != '') {
                                $candidateReceiversNumber = H::getHelper('MassMessageWidget')->getFormatPhoneForMessage($candidateData->Mobile);
                                if($candidateReceiversNumber  != '') {
                                    $receiversNumber[]= $candidateReceiversNumber;
                                }else {
                                    H::ExcelLog("Invalid Mobile for ClinicianID=".$ClinicianID,'invalidMobileMassText.log');
                                }
                            }elseif (substr($ClinicianID, 0, 6) == '999999') {
								$candidateReceiversNumber = H::getHelper('MassMessageWidget')->getFormatPhoneForMessage($tableData->Mobile);
								if($candidateReceiversNumber  != '') {
									$receiversNumber[]= $candidateReceiversNumber;
								}else {
									H::ExcelLog("Invalid Mobile for ClinicianID starts with 999=".$ClinicianID,'invalidMobileMassText.log');
								}
							}else {
                                H::ExcelLog("Invalid Mobile for ClinicianID=".$ClinicianID,'invalidMobileMassText.log');
                            }
                        }
                    }
                    
                } else {
                    $candidateIdArray = explode(",",$requestData['candidate_ids']);
                    foreach($candidateIdArray as $candidateIdData) {
                        if ($mapInfo[0]->map_tablename == 'candidates') {
                            $candidateData = DB::table('candidates')->where('id',$candidateIdData)->first();
                            if(!empty($candidateData) && $candidateData->mobile != '') {
                                $candidateReceiversNumber = H::getHelper('MassMessageWidget')->getFormatPhoneForMessage($candidateData->mobile);
                                if($candidateReceiversNumber  != '') {
                                    $receiversNumber[]= $candidateReceiversNumber;
                                }else {
                                    H::ExcelLog("Invalid Mobile for CandidateId=".$candidateIdData,'invalidMobileMassText.log');
                                }
                            }else {
                                H::ExcelLog("Invalid Mobile for CandidateId=".$candidateIdData,'invalidMobileMassText.log');
                            }
                        } else {
                            $candidateData = DB::table('mssql_providers')->where('ClinicianID', $candidateIdData)->first();
                            if(!empty($candidateData) && $candidateData->Mobile != '') {
                                $candidateReceiversNumber = H::getHelper('MassMessageWidget')->getFormatPhoneForMessage($candidateData->Mobile);
                                if($candidateReceiversNumber  != '') {
                                    $receiversNumber[]= $candidateReceiversNumber;
                                }else {
                                    H::ExcelLog("Invalid Mobile for ClinicianID=".$candidateIdData,'invalidMobileMassText.log');
                                }
                            }else {
                                H::ExcelLog("Invalid Mobile for ClinicianID=".$candidateIdData,'invalidMobileMassText.log');
                            }
                        }
                    }
                }
            } elseif($requestData['frompage'] == 'maps_bon_fl') {
                if($requestData['candidate_ids'] == 'selectallmap') {
                    $mapInfo = H::getSelectedMapInfo();
                    H::ExcelLog("candidate_ids == selectallmap",'saveMassMessage.log');
                    $reqD = $request->all();
                    $reqD['type'] = 'download';
                    $reqD['search']['value'] = $requestData['dtsearch'];
                    $data = H::getMapData($reqD);
                    H::ExcelLog("Total Map Data =".count($data),'saveMassMessage.log');
                    foreach ($data as $tableData) {
                        if($tableData->Phone_Number != '') {
                            $recieverNumber = H::getHelper('MassMessageWidget')->getFormatPhoneForMessage($tableData->Phone_Number);
                            if($recieverNumber  != '') {
                                $receiversNumber[]= $recieverNumber;
                            }else {
                                H::ExcelLog("Invalid Mobile for ID=".$tableData->id,'invalidMobileMassText.log');
                            }
                        }else {
                            H::ExcelLog("Invalid Mobile for ID=".$tableData->id,'invalidMobileMassText.log');
                        }
                    }
                } else {
                    $candidateIdArray = explode(",",$requestData['candidate_ids']);
                    $mapsBonFLs= DB::table("maps_bon_fl")->whereIn("id",$candidateIdArray)->get();
                    foreach($mapsBonFLs as $mapsBonFL) {
                        if(!empty($mapsBonFL) && $mapsBonFL->Phone_Number != '') {
                            $recieverNumber = H::getHelper('MassMessageWidget')->getFormatPhoneForMessage($mapsBonFL->Phone_Number);
                            if($recieverNumber  != '') {
                                $receiversNumber[]= $recieverNumber;
                            }else {
                                H::ExcelLog("Invalid Mobile for ID=".$mapsBonFL,'invalidMobileMassText.log');
                            }
                        }else {
                            H::ExcelLog("Invalid Mobile for ID=".$mapsBonFL,'invalidMobileMassText.log');
                        }
                    }
                }
            } elseif($requestData['frompage'] == 'caseload') {
                $clientIdArray = explode(",",$requestData['candidate_ids']);
                H::ExcelLog("Patient Id Array",'saveMassMessage.log');
                H::ExcelLog(print_r($clientIdArray, true),'saveMassMessage.log');
                $patientList= DB::table("mssql_patients")
                ->select('id','Patient_MobilPhone','Patient_Phone')
                ->whereIn("id",$clientIdArray)->get();
                foreach($patientList as $patientData) {
                    if(!empty($patientData) && $patientData->Patient_MobilPhone != '') {
                        H::ExcelLog("Patient ID from DB = ".$patientData->id,'saveMassMessage.log');
                        H::ExcelLog("Patient_MobilPhone from DB = ".$patientData->Patient_MobilPhone,
                        'saveMassMessage.log');
                        $recieverNumber = H::getHelper('MassMessageWidget')
                        ->getFormatPhoneForMessage($patientData->Patient_MobilPhone);
                        H::ExcelLog("Formatted reciever Number = ".$recieverNumber,'saveMassMessage.log');
                        if($recieverNumber  != '') {
                            $receiversNumber[]= $recieverNumber;
                            $receiversIdArr[$recieverNumber] = $patientData->id;
                        }else {
                            H::ExcelLog("Invalid Mobile for ID=".$patientData->id,'invalidMobileMassText.log');
                        }
                    }else {
                        H::ExcelLog("Invalid Mobile for ID=".$patientData->id,'invalidMobileMassText.log');
                    }
                }
            }
            
            if (!isset($requestData['continuemassmessage'])) {
                $existingMessageRecords = MassMessage::where('message', $requestData['send_message'])->whereDate('created_at', Carbon::today());
                if ($existingMessageRecords->count() > 0) {
                    return response()->json(['success' => -1,'message' => 'You sent this same message to ' . $existingMessageRecords->count() . ' numbers at ' . $existingMessageRecords->first()->created_at->format('h:i A') . ' today. Are you sure you want to proceed?']);
                }
            }
            H::ExcelLog("receiversNumber",'saveMassMessage.log');
            H::ExcelLog(print_r($receiversNumber, true),'saveMassMessage.log');
            if(!empty($receiversNumber)) {
                $insertDetailData = [];
                $insertOneByOne = 0;
                if(LD::checkFeatureFlag('mass-text-template-option')) {
                    if(!empty($requestData['department'])) {
                        $departmentKeyworkList = config('constant')['massTextKeywords'][$requestData['department']];
                        if(!empty($departmentKeyworkList)) {
                            foreach ($departmentKeyworkList as $keyword) {
                                if (strpos($requestData['send_message'], $keyword) !== false) {
                                    H::ExcelLog("Keyword Found = ".$keyword,'saveMassMessage.log');
                                    $insertOneByOne = 1;
                                    break;
                                }
                            }
                        }
                    }
                }
                if($insertOneByOne == 1) {
                    H::ExcelLog("Saving one by one START",'saveMassMessage.log');
                    foreach($receiversNumber as $numberDetail) {
                        $patientId = $receiversIdArr[$numberDetail];
                        H::ExcelLog("PatientId = ".$patientId,'saveMassMessage.log');
                        $massMessageText = TextTemplate::replaceTemplateKeywords($patientId,$requestData);
                        H::ExcelLog("massMessageText = ".$massMessageText,'saveMassMessage.log');
                        $insertData = [];
                        $insertData['receivers']=$numberDetail;
                        $insertData['user_id']=Auth::user()->id;
                        $insertData['message'] = $massMessageText;
                        $insertData['total_count'] = 1;
                        H::ExcelLog(print_r($insertData, true),'saveMassMessage.log');
                        $massMessageDetail = MassMessage::create($insertData);
                        $lastId = $massMessageDetail->id;
                        $insertDetailData[] = ['mass_message_id'=>$lastId,
                        'receiver_mobile'=>$numberDetail,
                        'receive_status'=>'Initiated'];
                    }
                    DB::table('mass_message_detail')->insert($insertDetailData);
                    H::ExcelLog("Saving one by one END",'saveMassMessage.log');
                } else{
                    $insertData['receivers']=implode(",",$receiversNumber);
                    $insertData['user_id']=Auth::user()->id;
                    $insertData['message'] = $requestData['send_message'];
                    $insertData['total_count'] = count($receiversNumber);
                    H::ExcelLog(print_r($insertData, true),'saveMassMessage.log');
                    $massMessageDetail = MassMessage::create($insertData);
                    $lastId = $massMessageDetail->id;
                    foreach($receiversNumber as $numberDetail) {
                        $insertDetailData[] = ['mass_message_id'=>$lastId, 'receiver_mobile'=>$numberDetail, 'receive_status'=>'Initiated'];
                    }
                    DB::table('mass_message_detail')->insert($insertDetailData);
                }
                return response()->json(['success' => 1,'message' => 'Mass text scheduled successfully.']);
            }else {
                H::ExcelLog("Invalid mobile number Count=".count($receiversNumber),'saveMassMessage.log');
                return response()->json(['success' => 0,'message' => 'Invalid mobile number(s).']);
            }
        } catch(\Exception $e) {
            H::ExcelLog("Exception=".$e->getMessage(),'saveMassMessage.log');
            return response()->json(['success' => 0,'message' => 'Something went wrong, Please try again.']);
        }
        
    }

    public function getMassTextForms(Request $request)
    {
        
        $senderInformation = User::find(Auth::user()->id);
        $requestData = $request->all();
       
        $dtsearch = isset($requestData['dtsearch'])?$requestData['dtsearch']:'';
        $totalSelectedCount = 0;
        if($requestData['ids'] == 'selectallmap') {
            $reqD = $request->all();
            $reqD['type'] = 'download';
            $reqD['search']['value'] = $requestData['dtsearch'];
            $data = H::getMapData($reqD);
            if(LD::checkFeatureFlag('text-blast-map-sms-radius-limit')) {
                $loggedInUser = H::getLoggedInUser();
                $smsLimit = is_numeric($loggedInUser['sms_radius_limit'])?$loggedInUser['sms_radius_limit']:50;
                if($data){ 
                    $tempData = [];
                    foreach($data as $d) {
                        if($d->distance){
                            if($d->distance <= $smsLimit) {
                                $tempData[] = $d;
                            }
                        }
                    }
                    if(!empty($tempData)) {
                        $data = $tempData;
                    }
                }
               
            }
            H::ExcelLog("Total Map Data =".count($data),'getMassTextForms.log');
            $totalSelectedCount = count($data);
        }
        $templateList = [];
        $department = '';
        if(LD::checkFeatureFlag('mass-text-template-option')) {
            $department = $requestData['department'];
            if(!empty($department )) {
                //get templates
                $templateList = TextTemplate::getTemplateList($department);
            }
        }

        return view('candidates.masstextpopup',['senderInformation' => $senderInformation,
        'ids'=>$requestData['ids'],'dtsearch'=>$dtsearch,
        'totalSelectedCount'=>$totalSelectedCount,
        'frompage' => $requestData['frompage'],
        'templateList' => $templateList,
        'department' => $department,
        ]);
    }

    public function directConversationWebhook(Request $request)
    {
        H::ExcelLog("request",'directConversationWebhook.log');
        H::ExcelLog(print_r($request->all(), true),'directConversationWebhook.log');
        $responseArray = $request->all();
        $receiveData = [];
        $receiveData['aoc_candidate_id'] = $receiveData['aoc_provider_id'] = 0;
        if($responseArray['EventType'] == 'onMessageAdded' && $responseArray['Source'] == 'SDK') {
            $recruiterNumber = "+".$responseArray['Author'];
            $sendersData = User::where('sending_sms_number',$recruiterNumber)->first();
            $receiveData['user_id'] = $sendersData->id;
            $conversationDataExist = Conversation::select('id','user_id','aoc_provider_id','aoc_candidate_id')->where('sid',$responseArray['ConversationSid'])->where('chat_service_sid',$responseArray['ChatServiceSid'])->first();
            if(empty($conversationDataExist)) {
                $conversationDetail = H::getHelper('MassMessageWidget')->getConversationData($responseArray);
                $receiveData['friendly_name'] = $conversationDetail->friendlyName;
                $receiveData['unique_name'] = $conversationDetail->uniqueName;
                $receiversNumber = H::getHelper('MassMessageWidget')->getReceiverParticipantNumber($responseArray);
                $formatRecieverNumberQuery = str_replace("+","",$receiversNumber);
                $recieverNumberQuery = '(case WHEN length(REGEXP_REPLACE(`mobile`, "[^0-9]", "")) = 11
                THEN REGEXP_REPLACE(`mobile`, "[^0-9]", "")
                WHEN length(REGEXP_REPLACE(`mobile`, "[^0-9]", "")) = 10
                THEN concat("1",REGEXP_REPLACE(`mobile`, "[^0-9]", "")) end)';
                $candidateData = DB::table('candidates')
                ->whereRaw($recieverNumberQuery."=?",[$formatRecieverNumberQuery])
                ->where('mobile','!=','')->latest('id')->first();

                if(empty($candidateData)) {
                    $recieverNumberQuery = '(case when length(REGEXP_REPLACE(`Mobile`, "[^0-9]", "")) = 11
                    then REGEXP_REPLACE(`Mobile`, "[^0-9]", "")
                    when length(REGEXP_REPLACE(`Mobile`, "[^0-9]", "")) = 10
                    then concat("1",REGEXP_REPLACE(`Mobile`, "[^0-9]", "")) end)';
                    $candidateData = DB::table('mssql_providers')
                    ->whereRaw($recieverNumberQuery."=?",[$formatRecieverNumberQuery])
                    ->where('mssql_providers.is_deleted',0)->where('Mobile','!=','')->first();
                    $receiveData['aoc_provider_id'] = $candidateData->id;
                } else {
                    $receiveData['aoc_candidate_id'] = $candidateData->id;
                }
                $participantsData = ['sid' => $responseArray['ConversationSid'],'messaging_service_sid' => $responseArray['MessagingServiceSid'],'chat_service_sid' => $responseArray['ChatServiceSid'],'user_id' => $receiveData['user_id'],'api_response' => serialize($responseArray),'aoc_provider_id' => $receiveData['aoc_provider_id'],'aoc_candidate_id' => $receiveData['aoc_candidate_id'],'unique_name' => $receiveData['unique_name'],'friendly_name' => $receiveData['friendly_name'],'receiver_mobile' => $receiversNumber];
                $id = Conversation::insertGetId($participantsData);
                H::ExcelLog("Conversation Insert DATA",'directConversationWebhookData.log');
                H::ExcelLog(print_r($participantsData, true),'directConversationWebhookData.log');
                $initialMessage  = ['conversation_id' =>$id, 'user_id' => $receiveData['user_id'],'aoc_provider_id' => 0,'aoc_candidate_id' => 0,'message'=>$responseArray['Body']];
                DB::table('conversation_reply')->insert($initialMessage);
            }else {
                H::ExcelLog("Update Conversation Message Service Sid",'directConversationWebhookData.log');
                H::ExcelLog("conversation_id=".$conversationDataExist->id,'directConversationWebhookData.log');
                H::ExcelLog("messaging_service_sid=".$responseArray['MessagingServiceSid'],'directConversationWebhookData.log');
                Conversation::where('id',$conversationDataExist->id)->update(['messaging_service_sid' => $responseArray['MessagingServiceSid']]);
                $conversationReplyInsertArr = ['conversation_id' =>$conversationDataExist->id, 'user_id' => $receiveData['user_id'],'aoc_provider_id' => 0,'aoc_candidate_id' => 0,'message'=>$responseArray['Body']];
                H::ExcelLog("Conversation Reply Insert DATA",'directConversationWebhookData.log');
                H::ExcelLog(print_r($conversationReplyInsertArr, true),'directConversationWebhookData.log');
                DB::table('conversation_reply')->insert($conversationReplyInsertArr);
            }
        }
        //$responseArray['From']
    }

    public function list(Request $request) {
        $columns = [
            ['name' => 'username', 'label' => 'User', 'width' => '20%'],
            ['name' => 'receivers', 'label' => 'Receivers', 'width' => '20%'],
            ['name' => 'message', 'label' => 'Message', 'width' => '40%'],
            ['name' => 'started_at', 'label' => 'Sending Started At', 'width' => '10%'],
            ['name' => 'completed_at', 'label' => 'Sending Completed At', 'width' => '10%'],
        ];
        if ($request->ajax()) {
            $filters = H::getFilterInputArr($request->all());

            $search = $request->input('search.value');
            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columns[$request->input('order.0.column')]['name'];
            $dir = $request->input('order.0.dir');

            $q = DB::table('mass_message')
                ->leftJoin('users', 'mass_message.user_id', '=', 'users.id')
                ->select('mass_message.receivers', 'mass_message.message', 'mass_message.started_at', 'mass_message.completed_at', 'users.name as username');

            $totalData = $q->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $q->whereNested(function($q) use ($search) {
                    $q->where('mass_message.receivers', 'LIKE', "%{$search}%")
                    ->orWhere('mass_message.message', 'LIKE', "%{$search}%")
                    ->orWhere('users.name', 'LIKE', "%{$search}%");
                });
                $totalFiltered = $q->count();
            }
            $data = $q->offset($start)->limit($limit)->orderBy($order, $dir)->get();

            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => intval($totalData),
                'recordsFiltered' => intval($totalFiltered),
                'data'            => $this->DataProcess($data),
            ]);
        }
        return view('candidates.masstextgrid', ['columns' => $columns]);
    }

    private function DataProcess($data) {
        foreach ($data as $key => $value) {
            $value->started_at = H::getFormatedDateTime($value->started_at);
            $value->completed_at = H::getFormatedDateTime($value->completed_at);
            $value->receivers = str_replace(",",", ",$value->receivers);
        }
        return $data;
    }

    public function payRateWebhook(Request $request)
    {
        H::ExcelLog("request",'payRateWebhook.log');
        H::ExcelLog(print_r($request->all(), true),'payRateWebhook.log');
        $responseArray = $request->all();
        $receiveData = [];
        $receiveData['aoc_entity_id'] = 0;
        $receiveData['aoc_candidate_id'] = $receiveData['aoc_provider_id'] = 0;
        try{
            $formatRecieverNumberQuery = str_replace("+","",$responseArray['From']);
            $recieverNumberQuery = '(case WHEN length(REGEXP_REPLACE(`mobile`, "[^0-9]", "")) = 11
            THEN REGEXP_REPLACE(`mobile`, "[^0-9]", "")
            WHEN length(REGEXP_REPLACE(`mobile`, "[^0-9]", "")) = 10
            THEN concat("1",REGEXP_REPLACE(`mobile`, "[^0-9]", "")) end)';
            $candidateData = DB::table('candidates')->whereRaw($recieverNumberQuery."=?",[$formatRecieverNumberQuery])
            ->where('mobile','!=','')->latest('id')->first();
            $receiveData['friendly_name'] = $firstName = '';
            $receiveData['unique_name'] = $receiveData['friendly_name'] =$receiveData['From'] =$responseArray['From'];

            if(empty($candidateData)) {
                $recieverNumberQuery = '(case when length(REGEXP_REPLACE(`Mobile`, "[^0-9]", "")) = 11
                then REGEXP_REPLACE(`Mobile`, "[^0-9]", "")
                when length(REGEXP_REPLACE(`Mobile`, "[^0-9]", "")) = 10
                then concat("1",REGEXP_REPLACE(`Mobile`, "[^0-9]", "")) end)';
                $providerData = DB::table('mssql_providers')
                ->whereRaw($recieverNumberQuery."=?",[$formatRecieverNumberQuery])
                ->where('mssql_providers.is_deleted',0)->where('Mobile','!=','')->first();
                $receiveData['aoc_provider_id'] = $receiveData['aoc_entity_id'] = $providerData->id;
                $receiveData['friendly_name'] = $providerData->FirstName.' '.$providerData->LastName;
                $receiveData['unique_name'] = $providerData->id.'#'.$responseArray['From'].'#clinician';
                $firstName = $providerData->FirstName;
            } else {
                $receiveData['aoc_candidate_id'] = $receiveData['aoc_entity_id'] = $candidateData->id;
                $receiveData['friendly_name'] =  $candidateData->firstName.' '.$candidateData->lastName;
                $receiveData['unique_name'] = $candidateData->id.'#'.$responseArray['From'].'#candidate';
                $firstName = $candidateData->firstName;
            }
            $receiveData['receiver_mobile'] = $responseArray['From'];
            $replyReceivedMessage = "Reply for pay rate: ";
            $replyReceivedMessage = $replyReceivedMessage.$responseArray['Body'];

            H::ExcelLog(print_r($receiveData, true),'payRateWebhook.log');
            $documentData = DB::table("aoc_documents")->select("*")
            ->where('entity_id',$receiveData['aoc_entity_id'])
            ->where("document_type", 'pay_rate')
            ->orderBy('created_at','DESC')
            ->first();
            H::ExcelLog("aoc documents data",'payRateWebhook.log');
            H::ExcelLog(print_r($documentData, true),'payRateWebhook.log');

            if(!empty($documentData)) {
                $signUrl = $documentData->document_url;
                $smsMessageStr = "Hello ".$firstName.",";
                $smsMessageStr = $smsMessageStr." This is your Angels of Care Pay Rate Agreement.";
                $smsMessageStr = $smsMessageStr." Promptly click the link to view";
                $smsMessageStr = $smsMessageStr." and sign your documents.";
                $smsMessageStr = $smsMessageStr." Reply for help.";
                $firstMessage  = $smsMessageStr." ".$signUrl;
                //Get Sender Data
                $sendersData = User::where('id',$documentData->sender_id)->first();
                $receiveData['user_id'] = $sendersData->id;
                $responseArray['To'] = $sendersData->sending_sms_number;
                //Added payrate sender number to make unique name
                $receiveData['unique_name'] = $receiveData['unique_name']."#".$responseArray['To'];

                //1 Check if conversation exist
                $conversationDataExist = Conversation::where('user_id',$receiveData['user_id'])
                ->where('receiver_mobile',$responseArray['From'])
                ->first();
                //2. If not, create one conversation and add participants
                if(empty($conversationDataExist)) {
                    H::ExcelLog("conversationDataNotExist",'payRateWebhookData.log');
                    try{
                        //create conversation on Twilio
                        $conversationDetails =  H::getHelper('MassMessageWidget')->createConversation($receiveData);
                        H::ExcelLog("conversationDetails---",'payRateWebhookData.log');
                        H::ExcelLog(print_r($conversationDetails, true),'payRateWebhookData.log');

                        //add recruiter as chat participant
                        $addRecruiterParticipantData = ['conversation_id' => $conversationDetails->sid,
                        'participant_number' => $responseArray['To']];
                        H::ExcelLog("addRecruiterParticipantData----",'payRateWebhookData.log');
                        H::ExcelLog(print_r($addRecruiterParticipantData, true),'payRateWebhookData.log');
                        $participantsRecruiterDetails =  H::getHelper('MassMessageWidget')
                        ->addChatParticipants($addRecruiterParticipantData);

                        //Insert record into conversation table
                        $participantsData = ['sid' => $conversationDetails->sid,
                        'messaging_service_sid' => $conversationDetails->messagingServiceSid,
                        'chat_service_sid' => $conversationDetails->chatServiceSid,
                        'user_id' => $receiveData['user_id'],
                        'api_response' => serialize($conversationDetails),
                        'aoc_provider_id' => $receiveData['aoc_provider_id'],
                        'aoc_candidate_id' => $receiveData['aoc_candidate_id'],
                        'unique_name' => $receiveData['unique_name'],
                        'friendly_name' => $receiveData['friendly_name'],
                        'receiver_mobile' => $receiveData['receiver_mobile']
                        ];
                        $id = Conversation::insertGetId($participantsData);
                        H::ExcelLog("Conversation Insert DATA",'payRateWebhookData.log');
                        H::ExcelLog(print_r($participantsData, true),'payRateWebhookData.log');

                        //Add first payrate sms into conversation
                        $initialMessage  = ['conversation_id' =>$id,
                        'user_id' => $receiveData['user_id'],
                        'aoc_provider_id' => 0,
                        'aoc_candidate_id' => 0,
                        'message'=> $firstMessage];
                        DB::table('conversation_reply')->insert($initialMessage);
                        $initialAddMessage['author_number'] = $responseArray['To'];
                        $initialAddMessage['conversation_id'] = $conversationDetails->sid;
                        $initialAddMessage['message'] = $firstMessage;
                        H::getHelper('MassMessageWidget')->addMessageToConversation($initialAddMessage);
                        H::ExcelLog("Add initialMessage----",'payRateWebhookData.log');
                        H::ExcelLog(print_r($initialAddMessage, true),'payRateWebhookData.log');

                        //Add reply to conversation
                        $replyMessage = ['conversation_id' =>$id,
                        'user_id' => 0,
                        'aoc_provider_id' => $receiveData['aoc_provider_id'],
                        'aoc_candidate_id' => $receiveData['aoc_candidate_id'],
                        'message'=>$replyReceivedMessage];
                        DB::table('conversation_reply')->insert($replyMessage);

                        //Add reply to conversation on Twilio
                        $replyAddMessage['author_number'] = $responseArray['From'];
                        $replyAddMessage['message'] = $replyReceivedMessage;
                        $replyAddMessage['conversation_id'] = $conversationDetails->sid;
                        H::getHelper('MassMessageWidget')->addMessageToConversation($replyAddMessage);
                        H::ExcelLog("Add replyAddMessage----",'payRateWebhookData.log');
                        H::ExcelLog(print_r($replyAddMessage, true),'payRateWebhookData.log');

                        //Add lastReadMessageIndex to 0 for Recruiter
                        $updateParticipantData = ['conversation_id' => $conversationDetails->sid,
                        'participant_id' => $participantsRecruiterDetails->sid];
                        H::getHelper('MassMessageWidget')->updateParticipants($updateParticipantData);

                        //add candidate as non-chat participant
                        $addCandidateParticipantData = ['conversation_id' => $conversationDetails->sid,
                        'participant_number' => $responseArray['From'],
                        'twilio_number' => env("TWILIO_PAYRATE_TOLL_FREE")
                        ];
                        H::getHelper('MassMessageWidget')->addParticipants($addCandidateParticipantData);
                    } catch (\Twilio\Exceptions\RestException $re) {
                        H::ExcelLog("Participants=".$responseArray['From'] . ' : ' . $responseArray['To'],
                        'payRateWebhookError.log');
                        H::ExcelLog($re->getStatusCode() . ' : ' . $re->getMoreInfo(),'payRateWebhookError.log');
                        H::ExcelLog(print_r($re->getDetails(), true),'payRateWebhookError.log');
                    } catch (\Exception $e) {
                        H::ExcelLog($e->getCode() . ' : ' . $e->getMessage(),'payRateWebhookError.log');
                    }
                }else {
                    H::ExcelLog("conversationDataExist=".$conversationDataExist->id,'payRateWebhookData.log');
                    try{
                        //Mark conversation is_read = 0 for any new message
                        Conversation::where('id',$conversationDataExist->id)->update(['is_read'=>0]);
                        DB::table('conversation_reply')->insert(
                            ['conversation_id' =>$conversationDataExist->id,
                            'user_id' => 0,
                            'aoc_provider_id' => $receiveData['aoc_provider_id'],
                            'aoc_candidate_id' => $receiveData['aoc_candidate_id'],
                            'message'=>$replyReceivedMessage
                            ]
                        );
                    }catch (\Throwable $th) {
                        H::ExcelLog( $th->getMessage(),'payRateWebhookError.log');
                    }
                }
            }
        } catch (\Throwable $th) {
            H::ExcelLog( "Main Catch " . $th->getMessage(),'payRateWebhookError.log');
        }
    }

    public function checkDeliveryStatusWebhook(Request $request){
        $twilioErrorData = [];
        H::ExcelLog("First Request",'checkDeliveryStatusWebhook.log');
        H::ExcelLog(print_r($request->all(), true),'checkDeliveryStatusWebhook.log');
        try{
            if($request->MessageStatus == 'undelivered' || $request->MessageStatus == 'failed') {
                H::ExcelLog("request to handle Twilio Error",'checkDeliveryStatusWebhook.log');
                $twilioErrorData = $request->all();
                H::getHelper('MessageWidget')->handleTwilioError($twilioErrorData);
            }
        } catch (\Throwable $th) {
            H::ExcelLog( "Main Catch " . $th->getMessage(),'checkDeliveryStatusWebhook.log');
        }
    }
}
