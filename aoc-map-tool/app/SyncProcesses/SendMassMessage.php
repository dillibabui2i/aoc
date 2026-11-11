<?php
namespace App\SyncProcesses;

use App\Helpers\H;
use Illuminate\Support\Facades\DB;
use App\MassMessage;
use App\User;
class SendMassMessage
{
    public static function processSendMassMessage(){
        //start batch
        $syncType = 'MassText';
        $message = '';
        $batch_status = H::getHelper('Sync')->startBatch($syncType);
        echo $batch_status . "\n";
        if ($batch_status == -1) {
            echo "stop process\n";
            exit;
        }else{
            echo "start process\n";
        }
        $pendingMessageList = MassMessage::where('completed_at',NULL)->orderBy('updated_at','asc')->get()->toArray();
        //$pendingMessageList = MassMessage::where('completed_at',NULL)->orderBy('last_updated_count','asc')->get()->toArray();
        $receiverList = [];
        $resumeCount = $i = 0;
        $batchCount = 0;
        $batchLimit = 200;
        echo 'here' . "\n";
        if(count($pendingMessageList)) {
            try {
                foreach($pendingMessageList as $pendingMessageData) {
                    if($pendingMessageData['receivers'] != '') {
						$receiverList = explode(",",$pendingMessageData['receivers']);
                        if(count($receiverList) > 15000) {
                            continue;
                        }
                        if($pendingMessageData['started_at'] == NULL) {
                            MassMessage::where('id',$pendingMessageData['id'])->update(['started_at' => date('Y-m-d H:i:s')]);
                        }
                        $receiverList = explode(",",$pendingMessageData['receivers']);
                        $sendSmsData['from'] = env('TWILIO_TOLL_FREE');
                        $sendSmsData['messagingServiceSid'] = env('TWILIO_MESSAGING_SERVICE_SID');
                        $resumeCount = $pendingMessageData['last_updated_count'];
                        $totalCount = $pendingMessageData['total_count'];
                        H::ExcelLog("Total Count: " . $totalCount . " -> array count: " . count($receiverList),'smsMessageCounts.log');

                        echo 'totalCount: ' . $totalCount . "\n";
                        if($pendingMessageData['total_count'] == 0) {
                            $totalCount = count($receiverList);
                            H::ExcelLog($pendingMessageData['id'] . " - Update Total Count to : " . $totalCount,'smsMessageCounts.log');
                            MassMessage::where('id',$pendingMessageData['id'])->update(['total_count' => $totalCount]);
                        }
                        H::ExcelLog($pendingMessageData['id'] . " - resumeCount: ".$resumeCount,'smsMessageCounts.log');
                        H::ExcelLog($pendingMessageData['id'] . " - totalCount: ".$totalCount,'smsMessageCounts.log');
                        if($resumeCount > 0) { $resumeCount = $resumeCount+1; }
                        H::ExcelLog($pendingMessageData['id'] . " - new resumeCount: ".$resumeCount,'smsMessageCounts.log');
                        if($totalCount > 0) {
                            H::ExcelLog($pendingMessageData['id'] . " - start loop for message",'smsMessageCounts.log');
                            $pendingMessageData['message'] = $pendingMessageData['message']."\nReply STOP to opt out";
                            $sendCount = 0;
                            for($i=$resumeCount;$i < $totalCount; $i++) {
                                $sendCount++;
                                try {
                                    echo 'sending to: ' . $receiverList[$i] . "\n";
                                    $sendSmsData['to'] = $receiverList[$i];
                                    H::ExcelLog("Receiver Number - ".$sendSmsData['to'],'smsProcessSendMassMessage2.log');
                                    $sendSmsData['msg'] = $pendingMessageData['message'];
                                    //Check opt out table
                                    $optOutDetails = DB::table('text_opt_out')->where('phone',$sendSmsData['to'])->first();
                                    if(empty($optOutDetails)) {
                                        H::ExcelLog("Inside optOutDetails Receiver Number - ".$sendSmsData['to'].' at '.date('Y-m-d H:i:s'),
                                        'smsProcessSendMassMessage.log');
                                        $twilioErrorData = DB::table('twilio_error_logs')->where('phone_number',$sendSmsData['to'])->first();
                                        if(empty($twilioErrorData)) {
                                            H::ExcelLog("Inside twilioErrorData Receiver Number - ".$sendSmsData['to'].' at '.date('Y-m-d H:i:s'),
                                        'smsProcessSendMassMessage.log');
                                            //Twilio Lookup API
                                            $numberDetails = H::getHelper('MassMessageWidget')->checkValidNumber($sendSmsData['to']);
                                            //H::ExcelLog(print_r($numberDetails,true),'smsTwilioLookup.log');
                                            if(!empty($numberDetails) && $numberDetails->valid ==1) {
                                                H::ExcelLog("Valid Number - ".$sendSmsData['to'],'smsProcessSendMassMessage.log');
                                                H::ExcelLog('Send Message','smsProcessSendMassMessage.log');
                                                $smsResponseLog =  H::getHelper('MassMessageWidget')->sendSMS($sendSmsData);
                                                H::ExcelLog(print_r($smsResponseLog,true),'smsTwilioResponse.log');
                                                if($smsResponseLog['successSent'] == 0) {
                                                    $errorCodesToFlag = [21211, 21408, 21610, 30003, 30005, 30006, 30008];
                                                    if (in_array($smsResponseLog['errorCode'], $errorCodesToFlag)) {
                                                        H::ExcelLog('sendSMS Throwable ErrorCode='.$smsResponseLog['errorCode'].
                                                        ' && ErrorMessage='.$smsResponseLog['errorMessage'],'errorTwilioMessage.log');
                                                        $twilioErrorData = [];
                                                        $twilioErrorData['To'] = $sendSmsData['to'];
                                                        $twilioErrorData['ErrorCode'] = $smsResponseLog['errorCode'];
                                                        H::getHelper('MessageWidget')->handleTwilioError($twilioErrorData);
                                                    }
                                                }
                                            } else {
                                                H::ExcelLog("Invalid Receiver Number - ".$sendSmsData['to'],'smsProcessSendMassMessage.log');
                                            }
                                        } else {
                                            H::ExcelLog("Twilio Error Code Number - ".$sendSmsData['to'],'smsProcessSendMassMessage.log');
                                        }
                                    } else {
                                        H::ExcelLog("Optout Number - ".$sendSmsData['to'],'smsProcessSendMassMessage.log');
                                    }
                                    MassMessage::where('id',$pendingMessageData['id'])->update(['last_updated_count' => ($i+1)]);
                                    $batchCount++;
                                    echo 'batchCount: ' . $batchCount . "\n";
                                    //H::ExcelLog(print_r($message, true),'smsProcessSendMassMessageDetails.log');
                                    if($batchCount > $batchLimit){
                                        echo "I've sent " . $batchCount . " messages.  I'm stopping now" . "\n";
                                        H::ExcelLog($pendingMessageData['id'] . " - stop processing at " . $batchCount . ' (' . $i . ')','smsMessageCounts.log');
                                        break;
                                    }
                                } catch (\Throwable $th) {
                                    //throw $th;
                                    H::ExcelLog("Inside Throwable- processSendMassMessage===".$th->getMessage(),'smsProcessSendMassMessageErrors.log');
                                    H::ExcelLog($receiverList[$i] . ' Error','smsRecipients.log');
                                }
                            }
                        }
                        H::ExcelLog("Outside - ".$i,'smsProcessSendMassMessage.log');
                        H::ExcelLog($pendingMessageData['id'] . " - finished loop after: " . $sendCount . " / " . $batchCount,'smsMessageCounts.log');
                        if($i >= $totalCount) {
                            MassMessage::where('id',$pendingMessageData['id'])->update(['completed_at' => date('Y-m-d H:i:s')]);
                            H::ExcelLog($pendingMessageData['id'] . " - mark completed",'smsMessageCounts.log');
                        }
                        if($batchCount > $batchLimit){
                            echo "I've sent " . $batchCount . " messages.  I'm stopping now" . "\n";
                            H::ExcelLog($pendingMessageData['id'] . " - stop processing 2 at " . $batchCount . ' (' . $i . ')','smsMessageCounts.log');
                            break;
                        }
                    }
                }
                echo 'Finished with all messages' . "\n";
                H::ExcelLog("Stop processing messages",'smsMessageCounts.log');
            } catch (\Throwable $th) {
                //throw $th;
                H::ExcelLog("Outside Throwable - processSendMassMessage===".$th->getMessage(),'smsProcessSendMassMessageErrors.log');
                H::ExcelLog("Untrapped Error",'smsMessageCounts.log');
            }
        }
        echo "sync finished \n";
        H::ExcelLog("Finished Sync",'smsMessageCounts.log');
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }
    public static function processSendMassMessageOld(){
        $pendingMessageList = MassMessage::where('started_at',NULL)->get()->toArray();
        $receiverList = [];
        if(count($pendingMessageList)) {
            foreach($pendingMessageList as $pendingMessageData) {
                MassMessage::where('id',$pendingMessageData['id'])->update(['started_at' => date('Y-m-d H:i:s')]);
                if($pendingMessageData['receivers'] != '') {
                    //$receiverList = explode(",",$pendingMessageData['receivers']);
                    $receiverList = ['+19035639962'];
                    $sendData = User::where('id',$pendingMessageData['user_id'])->first();
                    $sendSmsData['from'] = $sendData->sending_sms_number;
                    foreach($receiverList as $receiverData) {
                        $sendSmsData['to'] = $receiverData;
                        $sendSmsData['msg'] = $pendingMessageData['message']. ' at '.date('Y-m-d H:i:s');
                        H::getHelper('MassMessageWidget')->sendSMS($sendSmsData);
                    }
                }
            }
            MassMessage::where('id',$pendingMessageData['id'])->update(['completed_at' => date('Y-m-d H:i:s')]);
        }
    }
}
