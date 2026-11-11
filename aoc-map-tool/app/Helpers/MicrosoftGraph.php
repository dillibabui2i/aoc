<?php
namespace App\Helpers;

use App\Services\Microsoft\OutlookService;
use Microsoft\Graph\Generated\Models\Event;
use App\Helpers\H;
use Microsoft\Graph\Generated\Models\OnlineMeetingProviderType;
use Microsoft\Graph\Generated\Users\Item\Events\EventsRequestBuilderPostRequestConfiguration;
use Microsoft\Graph\Generated\Users\Item\Events\Item\EventItemRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\Generated\Users\Item\Events\Item\Cancel\CancelPostRequestBody;
use Microsoft\Graph\Generated\Models\ItemBody;
use Microsoft\Graph\Generated\Models\DateTimeTimeZone;
use Microsoft\Graph\Generated\Models\Location;
use Microsoft\Graph\Generated\Models\Attendee;
use Microsoft\Graph\Generated\Models\EmailAddress;
use Microsoft\Graph\Generated\Models\AttendeeType;

use Microsoft\Graph\Generated\Users\Item\Calendar\Events\EventsRequestBuilderGetRequestConfiguration;


/**
 * @deprecated
 * @use OutlookService
 */
class MicrosoftGraph extends MicrosoftGraphBase {


    public function __construct() {
       parent::__construct();
    }

    public function getCalendar(){

        if($this->getAuthUser()) {
            $calendarModel = $this->getAuthUser()->calendar()->get()->wait();
            $this->currentCalendar = $calendarModel;
            return $this;
        }
        return null;
    }

    public function createEvent($eventData = [],$aocEventData = [], bool $isOnlineMeeting = false){
        H::ExcelLog("Creating New Event :","microsoft_graph.log");
        H::ExcelLog("Event Request Data :".print_r($eventData,true),"microsoft_graph.log");
        H::ExcelLog("APP Event Data :".print_r($aocEventData,true),"microsoft_graph.log");
        $requestBody = new Event();
        $requestBody->setSubject($eventData['subject']);
        $body = new ItemBody();
        $body->setContent($eventData['description']);
        $requestBody->setBody($body);

        $start = new DateTimeTimeZone();
        $start->setDateTime($eventData['event_startdate']);
        $start->setTimeZone($eventData['timezone']);
        $requestBody->setStart($start);

        $end = new DateTimeTimeZone();
        $end->setDateTime($eventData['event_enddate']);
        $end->setTimeZone($eventData['timezone']);
        $requestBody->setEnd($end);

        $location = new Location();
        $location->setDisplayName($eventData['location']);
        $requestBody->setLocation($location);

        $attendeesAttendee1 = new Attendee();
        $attendeesAttendee1EmailAddress = new EmailAddress();
        $attendeesAttendee1EmailAddress->setAddress($eventData['attendee']);
        $attendeesAttendee1EmailAddress->setName($eventData['attendee_name']);
        $attendeesAttendee1->setEmailAddress($attendeesAttendee1EmailAddress);
        $attendeesAttendee1->setType(new AttendeeType('required'));
        $attendeesArray []= $attendeesAttendee1;
        $requestBody->setAttendees($attendeesArray);

        $requestConfiguration = new EventsRequestBuilderPostRequestConfiguration();
        $headers = [
            'Prefer' => 'outlook.timezone="Pacific Standard Time"',
        ];
        $requestConfiguration->headers = $headers;

        if ($isOnlineMeeting) {
            $requestBody->setIsOnlineMeeting(true);
            $requestBody->setOnlineMeetingProvider(new OnlineMeetingProviderType('teamsForBusiness'));
        }

        if($this->getAuthUser()) {
            $result = $this->getAuthUser()->events()->post($requestBody, $requestConfiguration)->wait();
            H::ExcelLog("Create Event Response :".print_r($result,true),"microsoft_graph.log");
            if(!empty($result->getId())) {
                $aocEventData['event_id'] = $result->getId();
                $this->createAOCEvent($aocEventData);
                return $result->getId();
            }

        }
        return null;

    }

    public function searchEvent($transactionId){
        $requestConfiguration = new EventsRequestBuilderGetRequestConfiguration();
        $queryParameters = EventsRequestBuilderGetRequestConfiguration::createQueryParameters();
        $queryParameters->filter = "transactionId  eq '".$transactionId."'";
        $queryParameters->filter = "startsWith(subject,'this')";
        $queryParameters->format = "json";

        $requestConfiguration->queryParameters = $queryParameters;
        $result = $this->getAuthUser()->calendar()->events()->get($requestConfiguration)->wait();
        return $result->getValue();
    }


    public function getEvent($eventId){

        $requestConfiguration = new EventItemRequestBuilderGetRequestConfiguration();
        $headers = [
                'Prefer' => 'outlook.timezone="Pacific Standard Time"',
            ];
        $requestConfiguration->headers = $headers;

        $queryParameters = EventItemRequestBuilderGetRequestConfiguration::createQueryParameters();
        $queryParameters->select = ["subject","body","start","end"];
        $requestConfiguration->queryParameters = $queryParameters;


        if($this->getAuthUser()) {
            $result = $this->getAuthUser()->events()->byEventId($eventId)->get($requestConfiguration)->wait();
            return $result;
        }
    }


	public function checkIfEventExistMS($eventId){

		$requestConfiguration = new EventItemRequestBuilderGetRequestConfiguration();
        $headers = [
                'Prefer' => 'outlook.timezone="Pacific Standard Time"',
            ];
        $requestConfiguration->headers = $headers;

        $queryParameters = EventItemRequestBuilderGetRequestConfiguration::createQueryParameters();
        $queryParameters->select = ["id", "subject", "isCancelled", "showAs", "responseStatus", "start", "end"];
        $requestConfiguration->queryParameters = $queryParameters;

	    try {
			if($this->getAuthUser()) {
				$result = $this->getAuthUser()->events()->byEventId($eventId)->get($requestConfiguration)->wait();

				if($result){
					return $result;
				} else {
					return null;
				}
			}
		} catch (\Exception $e){

			return null;
		}
	}

    public function cancelEvent($eventId){
        H::ExcelLog("Cancelling Event :".$eventId,"microsoft_graph.log");

        if($this->getAuthUser()) {
            $requestBody = new CancelPostRequestBody();
            $requestBody->setComment('cancelling this event');
			if($this->checkIfEventExistMS($eventId)) {

				$result = $this->getAuthUser()->events()->byEventId($eventId)->cancel()->post($requestBody)->wait();
				H::ExcelLog("Cancel Event Response :".print_r($result,true),"microsoft_graph.log");
				return $result;
			} else {

				H::ExcelLog("Event Not Exist:".$eventId,"microsoft_graph.log");
			}
            $this->updateAOCEvent(['event_status'=>'close'],$eventId);

        }
        return null;
    }

    public function deleteEvent($eventId){
        H::ExcelLog("Delete Event :".$eventId,"microsoft_graph.log");
        $res = '';
        if($this->getAuthUser()) {
			if($this->checkIfEventExistMS($eventId)){
				$res = $this->getAuthUser()->events()->byEventId($eventId)->delete()->wait();
				H::ExcelLog("Delete Event Response :".print_r($res,true),"microsoft_graph.log");
			} else {
				H::ExcelLog("Event Not Exist".$eventId,"microsoft_graph.log");
			}
            $this->updateAOCEvent(['event_status'=>'delete'],$eventId);
            return $res;
        }
        return null;
    }
}
?>
