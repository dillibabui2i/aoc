<?php

namespace App\Helpers;

use App\Helpers\H;
use Twilio\Jwt\AccessToken;
use Twilio\Rest\Client;
use Twilio\Jwt\Grants\ChatGrant;

class MassMessageWidget
{
    public $sid;
    public $token;
    public $twilio;
    function __construct() {
      $this->sid = env("TWILIO_ACCOUNT_SID");
      $this->token = env("TWILIO_AUTH_TOKEN");
      $this->twilio = new Client($this->sid, $this->token);
    }
    public function getConversationData($data)
    {
        $conversation = $this->twilio->conversations->v1->conversations($data['ConversationSid'])->fetch();
        return $conversation;
    }
    public function createConversation($receiveData)
    {
        $conversation = [];
        try {
        $conversation = $this->twilio->conversations->v1->conversations
            ->create(
                [
                    "friendlyName" => $receiveData['friendly_name'],
                    "uniqueName" => $receiveData['unique_name']
                ]
            );
        } catch (\Throwable $th) {
            H::ExcelLog('createConversation Throwable='.$th->getMessage(),'conversationWebhook.log');
        }
        return $conversation;
    }
    public function addChatParticipants($data)
    {
        $data['participant_number'] = str_replace("+","",$data['participant_number']);
        try {
            $user = $this->twilio->conversations->v1->users->create($data['participant_number']);
        } catch (\Throwable $th) {
            H::ExcelLog('addChatParticipants Throwable='.$th->getMessage(),'conversationWebhook.log');
        }
        $participant = $this->twilio->conversations->v1->conversations($data['conversation_id'])->participants->create(["identity" => $data['participant_number']]);
        return $participant;
    }
    public function addParticipants($data)
    {
        $participant = [];
        try {
            $participant = $this->twilio->conversations->v1->conversations($data['conversation_id'])->participants->create(
                [
                    "messagingBindingAddress" => $data['participant_number'],
                    "messagingBindingProxyAddress" => $data['twilio_number']
                ]);
        } catch (\Throwable $th) {
            H::ExcelLog('addParticipants Throwable='.$th->getMessage(),'conversationWebhook.log');
        }
        return $participant;
    }
    public function addMessageToConversation($data)
    {
        $data['author_number'] = str_replace("+","",$data['author_number']);
        $message = [];
        try {
            $message = $this->twilio->conversations->v1->conversations($data['conversation_id'])
                                    ->messages->create([
                                                "author" => $data['author_number'],
                                                "body" => $data['message']
                                            ]);
        } catch (\Throwable $th) {
            H::ExcelLog('addMessageToConversation Throwable='.$th->getMessage(),'conversationWebhook.log');
        }
        return $message;
    }
    public function sendSMS($data)
    {
        $message = $response =[];
        try {
            $message = $this->twilio->messages->create(
                $data['to'],
                array(
                    'from' => $data['from'],
                    'body' => $data['msg'],
                    "messagingServiceSid" => $data['messagingServiceSid']??'',
                )
            );
            $response['status']= $message->status;
            $response['successSent']= 1;
            $response['errorCode']= 0;
            $response['sid']= $message->sid;
            $response['messagingServiceSid']= $message->messagingServiceSid;
            $response['errorMessage']=  $message->errorMessage;
            //H::ExcelLog(print_r($message, true),'sendSMSCore.log');
			H::ExcelLog('sendSMS status: '.$response['status'].' and sid='.$response['sid'].' and error: '.$response['errorMessage'],'sendSMSCore.log');
        } catch (\Throwable $th) {
            $response['successSent']= 0;
            $response['errorCode']= $th->getCode();
            $response['errorMessage']= $th->getMessage();
            H::ExcelLog('sendSMS Throwable Error code='.$th->getCode().' && Error Message='.$th->getMessage(),'sendSMSCore.log');
        }
        return $response;
    }

    public function getFormatPhoneForMessage($phone_number)
    {
        $formatedPhoneNumber = preg_replace("/[^0-9]/", '', $phone_number);
        if ($formatedPhoneNumber == '0000000000' || $formatedPhoneNumber == '10000000000') {
            return '';
        }
        if (strlen($formatedPhoneNumber) == 10 && $formatedPhoneNumber != '0000000000') {
            $formatedPhoneNumber = "+1".$formatedPhoneNumber;
        } elseif (strlen($formatedPhoneNumber) == 11 && $formatedPhoneNumber != '10000000000') {
            $formatedPhoneNumber = "+".$formatedPhoneNumber;
        }
        return $formatedPhoneNumber;
    }
    public function getReceiverParticipantNumber($data)
    {
        $participants = $this->twilio->conversations->v1->conversations($data['ConversationSid'])->participants->read(20);
        $receiver_number = '';
        foreach ($participants as $record) {
            if($data['ParticipantSid'] != $record->sid) {
                $result = json_decode($record->attributes,true);echo "\n";
                if(count($result) > 0) {
                    $receiver_number = $result['identity'];
                }
            }
        }
        return $receiver_number;
    }
    public function updateParticipants($data)
    {
        $participant = [];
        try {
            $participant = $this->twilio->conversations->v1->conversations($data['conversation_id'])->participants($data['participant_id'])->update(["lastReadMessageIndex" => 0]);
        } catch (\Throwable $th) {
            H::ExcelLog('updateParticipants Throwable='.$th->getMessage(),'conversationWebhookError.log');
        }
        return $participant;
    }

    public function checkValidNumber($phone_number)
    {
        $numberDetails = [];
        try {
            $numberDetails = $this->twilio->lookups->v2->phoneNumbers($phone_number)->fetch();
        } catch (\Throwable $th) {
            H::ExcelLog('checkValidNumber Throwable='.$th->getMessage(),'conversationWebhookError.log');
        }
        return $numberDetails;
    }
}
