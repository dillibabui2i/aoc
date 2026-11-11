<?php

namespace App\Services\Microsoft;

use App\DTO\Microsoft\OutlookEventDTO;
use App\Helpers\H;
use App\Repositories\Events\EventsWriteRepository;
use Exception;
use Microsoft\Graph\Generated\Models\Attendee;
use Microsoft\Graph\Generated\Models\AttendeeType;
use Microsoft\Graph\Generated\Models\DateTimeTimeZone;
use Microsoft\Graph\Generated\Models\EmailAddress;
use Microsoft\Graph\Generated\Models\Event;
use Microsoft\Graph\Generated\Models\ItemBody;
use Microsoft\Graph\Generated\Models\Location;
use Microsoft\Graph\Generated\Models\OnlineMeetingProviderType;
use Microsoft\Graph\Generated\Models\User;
use Microsoft\Graph\Generated\Users\Item\Events\EventsRequestBuilderPostRequestConfiguration;
use Microsoft\Graph\Generated\Users\Item\Events\Item\Cancel\CancelPostRequestBody;
use Microsoft\Graph\Generated\Users\Item\Events\Item\EventItemRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\Generated\Users\Item\Events\Item\EventItemRequestBuilderPatchRequestConfiguration;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Abstractions\ApiException;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use Throwable;

class OutlookService extends Microsoft
{
    public function __construct(
        ?User $currentUser,
        EventsWriteRepository $eventsWriteRepository
    ) {
        parent::__construct(
            $currentUser,
            $eventsWriteRepository
        );
    }

    /**
     * @param OutlookEventDTO $outlookEventDTO
     * @return string|null
     * @throws Exception
     */
    public function createEvent(OutlookEventDTO $outlookEventDTO): ?string
    {
        if (!$this->hasUser()) {
            $this->userNotSetException();
        }

        $event = new Event();
        $event->setSubject($outlookEventDTO->getSubject());
        $body = new ItemBody();
        $body->setContent($outlookEventDTO->getDescription());
        $event->setBody($body);

        $event->setStart($outlookEventDTO->getStartDate());
        $event->setEnd($outlookEventDTO->getEndDate());

        if ($outlookEventDTO->hasLocation()) {
            $location = new Location();
            $location->setDisplayName($outlookEventDTO->getLocation());
            $event->setLocation($location);
        }

        $attendees = [];

        foreach ($outlookEventDTO->getAttendeesDTO()->getAttendees() as $attendeeDTO) {
            $msAttendee = new Attendee();

            $emailAddress = new EmailAddress();
            $emailAddress->setAddress($attendeeDTO->getEmail());
            $emailAddress->setName($attendeeDTO->getFullName());

            $msAttendee->setEmailAddress($emailAddress);

            $msAttendee->setType(
                new AttendeeType($attendeeDTO->getType())
            );

            $attendees[] = $msAttendee;
        }

        $event->setAttendees($attendees);
        $event->setIsOnlineMeeting(false);

        $requestConfiguration = new EventsRequestBuilderPostRequestConfiguration();
        $headers = [
            'Prefer' => 'outlook.timezone="Central Standard Time"',
        ];
        $requestConfiguration->headers = $headers;


        if ($outlookEventDTO->isOnlineMeeting()) {
            $event->setIsOnlineMeeting(true);
            $event->setOnlineMeetingProvider(new OnlineMeetingProviderType('teamsForBusiness'));
        }

        $result = $this->getUserRequestBuilder()->events()->post($event, $requestConfiguration)->wait();


        if (!empty($result->getId())) {
            $aocEventData['event_id'] = $result->getId();
            $this->createAOCEvent($aocEventData);
            return $result->getId();
        }
        return null;
    }

    /**
     * @throws Exception
     */
    public function updateEvent(OutlookEventDTO $outlookEventDTO): bool
    {
        if (!$this->hasUser()) {
            $this->userNotSetException();
        }

        if (!$outlookEventDTO->hasMSEventId()) {
            throw new Exception('No MS Event ID specified.');
        }

        $event = new Event();
        $event->setSubject($outlookEventDTO->getSubject());
        $body = new ItemBody();
        $body->setContent($outlookEventDTO->getDescription());
        $event->setBody($body);

        $event->setStart($outlookEventDTO->getStartDate());
        $event->setEnd($outlookEventDTO->getEndDate());

        if ($outlookEventDTO->hasLocation()) {
            $location = new Location();
            $location->setDisplayName($outlookEventDTO->getLocation());
            $event->setLocation($location);
        }

        $attendees = [];

        foreach ($outlookEventDTO->getAttendeesDTO()->getAttendees() as $attendeeDTO) {
            $msAttendee = new Attendee();

            $emailAddress = new EmailAddress();
            $emailAddress->setAddress($attendeeDTO->getEmail());
            $emailAddress->setName($attendeeDTO->getFullName());

            $msAttendee->setEmailAddress($emailAddress);

            $msAttendee->setType(
                new AttendeeType($attendeeDTO->getType())
            );

            $attendees[] = $msAttendee;
        }

        $event->setAttendees($attendees);
        $event->setIsOnlineMeeting(false);

        if ($outlookEventDTO->isOnlineMeeting()) {
            $event->setIsOnlineMeeting(true);
            $event->setOnlineMeetingProvider(new OnlineMeetingProviderType('teamsForBusiness'));
        }

        $this->getUserRequestBuilder()
            ->events()
            ->byEventId($outlookEventDTO->getMSEventId())
            ->patch($event)
            ->wait();

        return true;
    }

    /**
     * @throws Exception
     */
    public function cancelEvent(string $id)
    {
        if (!$this->hasUser()) {
            $this->userNotSetException();
        }

        $cancelRequest = new CancelPostRequestBody();
        $cancelRequest->setComment('Event cancelled.');

        if ($this->hasOutlookEvent($id)) {
            $response = $this->getUserRequestBuilder()
                ->events()
                ->byEventId($id)
                ->cancel()
                ->post($cancelRequest)
                ->wait();

            $this->updateAOCEvent($id, ['event_status' => 'close']);

            return $response;
        }

        throw new Exception(sprintf("Event %s wasn't canceled due to internal error", $id));
    }

    /**
     * @throws Exception
     */
    public function getEventInfo(string $eventId): ?array
    {
        if (!$this->hasUser()) {
            $this->userNotSetException();
        }

        $requestConfiguration = new EventItemRequestBuilderGetRequestConfiguration();
        $requestConfiguration->headers = [
            'Prefer' => 'outlook.timezone="Central Standard Time"',
        ];

        $queryParameters = EventItemRequestBuilderGetRequestConfiguration::createQueryParameters();
        $queryParameters->select = [
            'id',
            'subject',
            'isCancelled',
            'showAs',
            'responseStatus',
            'start',
            'end',
            'location',
            'onlineMeeting',
            'onlineMeetingProvider',
            'organizer',
            'attendees',
            'webLink',
            'bodyPreview',
        ];
        $requestConfiguration->queryParameters = $queryParameters;

        try {
            $event = $this->getUserRequestBuilder()
                ->events()
                ->byEventId($eventId)
                ->get($requestConfiguration)
                ->wait();

            if (!$event) {
                return null;
            }

            return [
                'id' => $event->getId(),
                'subject' => $event->getSubject(),
                'isCancelled' => (bool)$event->getIsCancelled(),
                'showAs' => $event->getShowAs()?->value ?? null,
                'responseStatus' => [
                    'response' => $event->getResponseStatus()?->getResponse()?->value ?? null,
                    'time' => $event->getResponseStatus()?->getTime()?->format('c') ?? null,
                ],
                'start' => [
                    'dateTime' => $event->getStart()?->getDateTime(),
                    'timeZone' => $event->getStart()?->getTimeZone(),
                ],
                'end' => [
                    'dateTime' => $event->getEnd()?->getDateTime(),
                    'timeZone' => $event->getEnd()?->getTimeZone(),
                ],
                'location' => $event->getLocation()?->getDisplayName(),
                'joinUrl' => $event->getOnlineMeeting()?->getJoinUrl(),
                'onlineMeetingProvider' => $event->getOnlineMeetingProvider()?->value ?? null,
                'organizer' => [
                    'name' => $event->getOrganizer()?->getEmailAddress()?->getName(),
                    'email' => $event->getOrganizer()?->getEmailAddress()?->getAddress(),
                ],
                'attendees' => array_map(function ($a) {
                    return [
                        'name' => $a->getEmailAddress()?->getName(),
                        'email' => $a->getEmailAddress()?->getAddress(),
                        'type' => $a->getType()?->value ?? null, // required/optional
                        'status' => $a->getStatus()?->getResponse()?->value ?? null,
                    ];
                }, $event->getAttendees() ?? []),
                'webLink' => $event->getWebLink(),
                'bodyPreview' => $event->getBodyPreview(),
            ];
        } catch (Throwable $e) {
            return null;
        }
    }

    public function hasOutlookEvent(string $eventId): bool
    {
        if (!$this->hasUser()) {
            return false;
        }

        $requestConfiguration = new EventItemRequestBuilderGetRequestConfiguration();
        $requestConfiguration->headers = [
            'Prefer' => 'outlook.timezone="Central Standard Time"',
        ];

        $queryParameters = EventItemRequestBuilderGetRequestConfiguration::createQueryParameters();
        $queryParameters->select = [
            'id',
            'isCancelled',
            'start',
            'end',
        ];
        $requestConfiguration->queryParameters = $queryParameters;

        try {
            $this->getUserRequestBuilder()->events()->byEventId($eventId)->get($requestConfiguration)->wait();

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
