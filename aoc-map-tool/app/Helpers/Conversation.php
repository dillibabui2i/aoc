<?php

namespace App\Helpers;
use App\Conversation as ConversationModel;
use Illuminate\Support\Facades\DB;
use Exception;

class Conversation
{

    public static function getConversation($id,$type)
    {
        try {
            if($id != '' && $type != ''){
                $whereId = '';
                if($type == 'candidate'){
                    $whereId = 'aoc_candidate_id';
                }else{
                    $whereId = 'aoc_provider_id';
                }
                return ConversationModel::select('conversation.*','users.name','users.sending_sms_number')->where($whereId,$id)
                ->join("users","users.id",'=','conversation.user_id')
                ->get();
            }else{
                return false;
            }
        } catch (Exception $e) {
            H::ExcelLog('getConversation:'.$e->getMessage(),'conversation.log');
            return false;
        }
    }
    public static function addConversation($data)
    {
        try {
            $conversation = [
                'sid' => $data['sid'],
                'messaging_service_sid' => $data['messagingServiceSid'],
                'chat_service_sid' => $data['chatServiceSid'],
                'user_id' => $data['user_id'],
                'aoc_provider_id' => $data['aoc_provider_id'],
                'aoc_candidate_id' => $data['aoc_candidate_id'],
                'unique_name' => $data['unique_name'],
                'friendly_name' => $data['friendly_name'],
                'receiver_mobile' => $data['receiver_mobile']
            ];
            return ConversationModel::insertGetId($conversation);
        } catch (Exception $e) {
            H::ExcelLog('getConversation:'.$e->getMessage(),'conversation.log');
            return false;
        }
    }
}
