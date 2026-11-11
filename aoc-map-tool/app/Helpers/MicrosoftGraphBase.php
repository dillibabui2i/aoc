<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Session;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use Microsoft\Kiota\Abstractions\ApiException;
use Microsoft\Graph\Generated\Users\UsersRequestBuilderGetRequestConfiguration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\H;

class MicrosoftGraphBase {
    
    private $tenantId;

    private $clientId;

    private $clientSecret;

    private $tokenRequestContext;

    private $currentUser = null;

    private $currentCalendar = null;

    protected $graphServiceClient = null;


    public function __construct() {

        $this->tenantId = env('_OAUTH_TENANTID');
        $this->clientId = env('_OAUTH_CLIENTID');
        $this->clientSecret = env('_OAUTH_SECRET');
        $this->currentUser = null;

        $this->tokenRequestContext = new ClientCredentialContext(
            $this->tenantId,
            $this->clientId,
            $this->clientSecret
        );

        $this->graphServiceClient = new GraphServiceClient($this->tokenRequestContext);

    }

    public function getAppUsers() {
        $users = $this->graphServiceClient->users()->get()->wait()->getValue();
        return $users;
    }

    public function getAuthUser(){
        $user = $this->getUser();
        if($user) {
            return $this->graphServiceClient->users()->byUserId($user->getId());
        } else {
            return null;
        }
       
    }

    public function getUser(){
        return $this->currentUser;
    }

    public function getUserByGraphId($id){
        
        $user = $this->graphServiceClient->users()->byUserId($id)->get()->wait();
        $this->currentUser = $user;
        return $this;
    }

    public function getUserByEmail($userEmail=''){
  
        $requestConfiguration = new UsersRequestBuilderGetRequestConfiguration();
        $queryParameters = UsersRequestBuilderGetRequestConfiguration::createQueryParameters();
        $queryParameters->filter = "mail  eq '".$userEmail."'";
        $requestConfiguration->queryParameters = $queryParameters;
        $users = $this->graphServiceClient->users()->get($requestConfiguration)->wait();
        
        if($users->getValue()) {
            $this->currentUser = $users->getValue()[0];
            return $this;
        } else {
            return null;
        }
    }

    public function isEventExist($entityId,$entityType){
        $event = DB::table("aoc_events")
        ->where("event_status","open")
        ->where("entity_id",$entityId)
        ->where("entity_type",$entityType)
        ->first();
        return $event;
    }

    public function getEventUserEmail($userId){
        $user = DB::table("users")->select("email")->where("id",$userId)->first();
        if($user){
            return $user->email;
        } else {
            return '';
        }
    }

    public function isEventRescheduleOrReassign($eventData,$newEventData) {
        if (!empty($eventData)) {
            if($eventData->intake_nurse_id!=$newEventData['intake_nurse_id']) {
                return 'reassign';
            } else {
                $eventDate = strtotime($eventData->admission_visit_date);
                $eventStartTime = strtotime($eventData->start_time);
                $eventEndTime = strtotime($eventData->end_time);

                $newEventDate = strtotime($newEventData['admission_visit_date']);
                $newEventStartTime = strtotime($newEventData['start_time']);
                $newEventEndTime = strtotime($newEventData['end_time']);
                
                if(($eventDate!=$newEventDate) || 
                ($eventStartTime!=$newEventStartTime) ||
                ($eventEndTime!=$newEventEndTime)) {
                    return "reschedule";
                }
            }
        }
        return '';
    }

    public function createAOCEvent($eventData){
        $eventData['created_by'] = Auth::user()->id;
        $aocEventId = DB::table("aoc_events")
        ->insertGetId($eventData);

        H::ExcelLog("Created Event in Database :".$aocEventId,"microsoft_graph.log");
        return $aocEventId;
    }
    public function updateAOCEvent($eventData,$eventId){
        $aocEventId = DB::table("aoc_events")->where("event_id",$eventId)
        ->update($eventData);
        H::ExcelLog("Updated Event in Database :".$eventId,"microsoft_graph.log");
        H::ExcelLog("Updated EventData in Database :".print_r($eventData,true),"microsoft_graph.log");
        return $aocEventId;
    }
}
?>
