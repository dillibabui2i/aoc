<?php
namespace App\Helpers;

use App\Helpers\H;
use Twilio\Jwt\AccessToken;
use Twilio\Rest\Client;
use Twilio\Jwt\Grants\ChatGrant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\User;
use Illuminate\Support\Facades\Schema;

class MessageWidget
{

    public static function getToken(){
        $twilioAccountSid = env('TWILIO_ACCOUNT_SID');
        $twilioApiKey = env('TWILIO_API_KEY');
        $twilioApiSecret = env('TWILIO_API_SECRET');
        $loggedInUser = H::getLoggedInUser();
        if(Auth::user()->sending_sms_number != ''){
            $identity = str_replace('+','',Auth::user()->sending_sms_number);
            $token = new AccessToken(
                $twilioAccountSid,
                $twilioApiKey,
                $twilioApiSecret,
                3600,
                $identity
            );
            $chatGrant = new ChatGrant();
            $chatGrant->setServiceSid(env('TWILIO_SERVICE_SID'),);

            // Add grant to token
            $token->addGrant($chatGrant);
            H::ExcelLog("Twilio token has been generated to access the chat widget for (".$loggedInUser['name'].":".$loggedInUser['id']."):Token:".$token->toJWT(),'messageWidget.log');
            return $token->toJWT();
        }else{
            H::ExcelLog("Logged in user(".$loggedInUser['name'].":".$loggedInUser['id'].") doesn't have the twilio number configured in the user account",'messageWidget.log');
            return false;
        }
    }

    public function getDataTableHtml($type)
    {
        switch($type) {
            case 'message_center_conversations':
                $dataTable = [
                    'name' => 'User',
                    'receiver_name' => 'Name',
                    'read_status' => 'Read/Unread',
                    'last_send' => 'Last Outgoing',
                    'last_received' => 'Last Incoming',
                    'last_response' => 'Last Response'
                ];
            break;
            default:
                $dataTable = [];
            break;
        }
        return view('messageWidget.dashboard_conversation_datatable',["type" => $type,'dataTable'=>$dataTable]);
    }

    public function getMessageCenterList($type, $request = '',$showCount=0) {
        $candidateName = "(CASE WHEN conversation.aoc_candidate_id > 0 THEN (select CONCAT(candidates.firstName,' ',candidates.lastName) from candidates where candidates.id=conversation.aoc_candidate_id) WHEN conversation.aoc_provider_id > 0 THEN (select CONCAT(mssql_providers.FirstName,' ',mssql_providers.LastName) from mssql_providers where mssql_providers.id=conversation.aoc_provider_id) ELSE '' END) as receiver_name";

        $conversationRead = "(CASE WHEN conversation.is_read = 0 THEN 'Unread' ELSE 'Read' END) as read_status";

        $lastReceived = "(CASE WHEN conversation.aoc_candidate_id > 0 THEN (select created_at from conversation_reply where conversation_reply.conversation_id = conversation.id and  conversation_reply.aoc_candidate_id > 0 order by created_at DESC LIMIT 1) WHEN conversation.aoc_provider_id > 0 THEN (select created_at from conversation_reply where conversation_reply.conversation_id = conversation.id and  conversation_reply.aoc_provider_id > 0 order by created_at DESC LIMIT 1) END) as last_received";

        $lastSend = "(select created_at from conversation_reply where conversation_reply.conversation_id = conversation.id and  conversation_reply.user_id > 0 order by created_at DESC LIMIT 1) as last_send";

        $lastResponse = "(select (CASE WHEN conversation_reply.user_id > 0 THEN 'Internal' WHEN (CASE WHEN conversation_reply.aoc_candidate_id > 0 THEN conversation_reply.aoc_candidate_id WHEN conversation_reply.aoc_provider_id > 0 THEN conversation_reply.aoc_provider_id END) > 0 THEN 'External' END) from conversation_reply where conversation_reply.conversation_id = conversation.id order by created_at DESC LIMIT 1)";


        $bucket = DB::table('conversation') ->leftJoin('users', 'users.id', '=', 'conversation.user_id')->select('conversation.id','conversation.user_id','conversation.sid','conversation.is_read','users.name',DB::raw($conversationRead),DB::raw($candidateName),'conversation.receiver_mobile',DB::raw("DATE_FORMAT(conversation.created_at, '" . config('constant')['mysqlsearchdatetimeformat'] . "') as created_at"),DB::raw($lastSend),DB::raw($lastReceived),DB::raw($lastResponse." as last_response"));

        $filters = [];
        if(isset($request['filters'])) {
            foreach($request['filters'] as $fl) {
                if(!empty($fl['value'])) {
                    $filters[$fl['name']][] = $fl['value'];
                }
            }
            foreach($filters as $fkey => $fval) {
                if($fkey=='Recruiter') {
                    $bucket->where(function($query) use ($fval) {
                        $query->whereIn("conversation.user_id",$fval);
                    });
                }
                if($fkey=='dconmemode') {
                    $bucket->where("conversation.user_id","=",Auth::user()->id);
                }
                if($fkey=='last_response') {
                    $bucket->where($lastResponse, $fval[0]);
                }
            }
        }
        H::ExcelLog($bucket->toSql(),'getMessageCenterList.log');
        if($showCount) {
            return '('.$bucket->count().')';
        }
        H::ExcelLog('done','getMessageCenterList.log');
        return $bucket->get();
    }

    public function setConversationToArchive($conversationId){
        if($conversationId != ''){
            DB::table('conversation')->where('sid',$conversationId)->update(['is_archive'=>1]);
            $twilio = new Client(env('TWILIO_ACCOUNT_SID'), env("TWILIO_AUTH_TOKEN"));
            $conversation = $twilio->conversations->v1->conversations($conversationId)->update(array("state" => "closed"));

        }
    }
    public function sendPayRateSMS($data){
        H::ExcelLog("========Send SMS==========","sendPayRateSMS.log");
        H::ExcelLog(print_r($data,true),"sendPayRateSMS.log");
        $sendSmsData['msg'] = $data['message'];
        $sendSmsData['to']  = $data['sms_to'];
        $sendSmsData['from'] = env('TWILIO_PAYRATE_TOLL_FREE');
        $sendSmsData['messagingServiceSid'] = env('TWILIO_INTAKE_MESSAGING_SERVICE_SID');
        try {
            $sendSmsData['to'] = H::getHelper('MassMessageWidget')->getFormatPhoneForMessage($sendSmsData['to']);
            H::ExcelLog("Twilio Lookup API for - ".$sendSmsData['to'].' at '.date('Y-m-d H:i:s'),'sendPayRateSMS.log');
            //Twilio Lookup API
            $numberDetails = H::getHelper('MassMessageWidget')->checkValidNumber($sendSmsData['to']);
            if(!empty($numberDetails) && $numberDetails->valid ==1) {
                H::ExcelLog("Valid Number - ".$sendSmsData['to'],'sendPayRateSMS.log');
                H::getHelper('MassMessageWidget')->sendSMS($sendSmsData);
            } else {
                H::ExcelLog("Invalid Receiver Number - Twilio Error -".$sendSmsData['to'],'sendPayRateSMS.log');
            }
        } catch (\Throwable $th) {
            H::ExcelLog($sendSmsData['to'] .
            "Error Inside Throwable- processSendMassMessage===".$th->getMessage(),
            'sendPayRateSMS.log');
        }
    }

    public function sendSmsToIntakeCareTeam($data){
        H::ExcelLog("========Send SMS==========","sendSmsToIntakeCareTeam.log");
        H::ExcelLog(print_r($data,true),"sendSmsToIntakeCareTeam.log");
        $smsMessageStr = " An urgent update has been provided on a referral.";
        $smsMessageStr = $smsMessageStr." Please log into the AOC App to view additional details.";
        $smsMessageStr = $smsMessageStr." https://angelsofcareapp.com/";
        $sendSmsData['msg'] = $smsMessageStr;
        $teamData = [];
        foreach($data['careTeamRole'] as $careTeamRoleId) {
            $teamData['careTeamRole'] = $careTeamRoleId;
            $teamData['aoc_patient_id'] = $data['aoc_patient_id'];
            H::ExcelLog("========teamData==========","sendSmsToIntakeCareTeam.log");
            H::ExcelLog(print_r($teamData,true),"sendSmsToIntakeCareTeam.log");
            $careTeamMemberDetails = H::getHelper("ConstantHelper")->getCareTeamByRoleForIntakeSms($teamData);
            H::ExcelLog("========careTeamMemberDetails==========","sendSmsToIntakeCareTeam.log");
            H::ExcelLog(print_r($careTeamMemberDetails,true),"sendSmsToIntakeCareTeam.log");
            if($careTeamMemberDetails['phone_number'] == '') {
                H::ExcelLog("Receiver Number Not Exist for user -".$careTeamMemberDetails['name'],'sendSmsToIntakeCareTeam.log');
                continue;
            }

            H::ExcelLog("Receiver Number Exist for user -".$careTeamMemberDetails['name'],'sendSmsToIntakeCareTeam.log');
            H::ExcelLog("recieverDetails==========","sendSmsToIntakeCareTeam.log");
            $sendSmsData['to']  = $careTeamMemberDetails['phone_number'];
            $sendSmsData['from'] = env('TWILIO_INTAKE_TOLL_FREE');
            $sendSmsData['messagingServiceSid'] = env('TWILIO_INTAKE_MESSAGING_SERVICE_SID');
            try {
                $sendSmsData['to'] = H::getHelper('MassMessageWidget')->getFormatPhoneForMessage($sendSmsData['to']);
                H::ExcelLog("Twilio Lookup API for - ".$sendSmsData['to'].' at '.date('Y-m-d H:i:s'),'sendSmsToIntakeCareTeam.log');
                //Twilio Lookup API
                $numberDetails = H::getHelper('MassMessageWidget')->checkValidNumber($sendSmsData['to']);
                if(!empty($numberDetails) && $numberDetails->valid ==1) {
                    H::ExcelLog("Valid Number - ".$sendSmsData['to'],'sendSmsToIntakeCareTeam.log');
                    H::getHelper('MassMessageWidget')->sendSMS($sendSmsData);
                } else {
                    H::ExcelLog("Invalid Receiver Number - Twilio Error -".$sendSmsData['to'],'sendSmsToIntakeCareTeam.log');
                }
            } catch (\Throwable $th) {
                H::ExcelLog($sendSmsData['to'] .
                "Error Inside Throwable- processSendMassMessage===".$th->getMessage(),
                'sendSmsToIntakeCareTeam.log');
            }
        }
    }

    public function handleTwilioError($data) {
        H::ExcelLog('handleTwilioError ','handleTwilioError.log');
        H::ExcelLog(print_r($data,true),'handleTwilioError.log');
        try {
            //Code to set is_ignore in maps table
            $updated_map_name = '';
            $formatRecieverNumberQuery = str_replace("+","",$data['To']);
            $showMassTextOptionMapArray = ['maps_textblast','maps_covclin','candidates','maps_bon_fl','maps_nthalerts'];
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
                if($map_tablename == 'maps_nthalerts') {
                    $matchQuery = str_replace("Mobile",'MobilePhone',$matchQuery);
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
                        $updated_map_name = $map_tablename;
                        H::ExcelLog( 'Record id ='.$candidateMapDetails->id .' for map ' . $map_tablename.' is set to is_ignored',
                        'handleTwilioError.log');
                        DB::table($map_tablename)->where('id', $candidateMapDetails->id)->update(['is_ignored' =>1]);
                    }
                }
            }
            //Log into twilio error log table
            if($updated_map_name != '') {
                //get map id from map name
                $mapDetails = DB::table('map_configurations')
                ->where('map_tablename',$updated_map_name)->first();
                //get mobile field name
                $mapMobileFieldArr =config('constant.map_mobile_field');
                $mapMobileField =  $mapMobileFieldArr[$updated_map_name];
            }
            $errorMesseageArr =config('constant.twilioErrorCodes');
            $twilioData['phone_number'] = H::getHelper('MassMessageWidget')->getFormatPhoneForMessage($data['To']);
            $twilioData['error_code'] = $data['ErrorCode'];
            $twilioData['error_message'] = $errorMesseageArr[$data['ErrorCode']] ?? 'Unknown error code';
            $twilioData['status'] = $data['SmsStatus']??'undelivered';
            $twilioData['mobile_field_name'] = $mapMobileField??'';
            $twilioData['map_id'] = $mapDetails->map_id??0;
            H::ExcelLog(print_r($twilioData,true),'handleTwilioError.log');
            DB::table("twilio_error_logs")->insert($twilioData);
        } catch (\Throwable $th) {
            H::ExcelLog("Error Inside Throwable- handleTwilioError===".$th->getMessage(),
            'handleTwilioError.log');
        }
    }
}
