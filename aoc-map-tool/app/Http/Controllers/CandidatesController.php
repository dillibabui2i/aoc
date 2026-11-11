<?php

namespace App\Http\Controllers;

use App\Candidates;
use App\DTO\Microsoft\AttendeeDTO;
use App\DTO\Microsoft\AttendeesDTO;
use App\DTO\Microsoft\OutlookEventDTO;
use App\Enums\CandidateDocumentTypeEnum;
use App\Enums\EntityTypeEnum;
use App\Enums\RolesEnum;
use App\Enums\JobviteTherapyOutcomeEnum;
use App\Events\CandidateFieldUpdated;
use App\Events\Offer\OfferApprovedEvent;
use App\Helpers\Geocoding;
use App\Helpers\ConstantHelper;
use App\Helpers\H;
use App\Helpers\LD;
use App\Helpers\Notifications;
use App\Helpers\UserPermissionResolver;
use App\Http\Requests\Candidates\SaveOutcomeRequest;
use App\Http\Requests\Candidates\StoreCandidateRequest;
use App\Http\Requests\Candidates\UpdateCandidateRequest;
use App\Jobs;
use App\Map;
use App\Models\CandidateClinicalInterviewQuestion;
use App\Models\Offer;
use App\Models\CandidateScreening;
use App\Models\OfferCompensationItem;
use App\Models\OfferField;
use App\Models\OfferValue;
use App\MssqlProviders;
use App\MssqlRecruitertracker;
use App\Repositories\Documents\DocumentReadRepository;
use App\RightSignatureDocuments;
use App\Services\Candidate\CandidateService;
use App\Services\Microsoft\OutlookService;
use App\Services\Offer\OfferResourcesService;
use App\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Pdf\Therapy\TherapyPdfService;
use Illuminate\Support\Str;
use PDF;
use Throwable;
use App\Repositories\App\JobCategories\JobCategoriesReadRepository;

class CandidatesController extends Controller
{
    private const DEFAULT_EVENT_TIME_IN_MINUTES = 60;

    public function __construct(
        private CandidateService $service,
        private DocumentReadRepository $documentReadRepository,
        private OfferResourcesService $offerResourcesService,
    ){
    }

    public function index(Request $request)
    {
        try {
            if ($request->input('id') != '') {
                $candidatesDetails = Candidates::find($request->input('id'));

                $jobDetails = DB::table('candidate_applications')
                    ->leftJoin('jobs', 'candidate_applications.job_id', '=', 'jobs.id')
                    ->select('jobs.*')
                    ->where('candidate_applications.candidate_id',$request->input('id'))->get();
                $twilioErrorData = [];
                if (
                    LD::checkFeatureFlag('display-twilio-error-on-profile-page') &&
                    env('APP_ENV') === 'production'
                ) {
                    $candidateReceiversNumber = H::getHelper('MassMessageWidget')
                    ->getFormatPhoneForMessage($candidatesDetails->mobile);
                    $twilioErrorData = DB::table('twilio_error_logs')->where('phone_number',$candidateReceiversNumber)->first();
                }

                $documents = $this->documentReadRepository->getCandidateDocumentsByType(
                    $request->input('id'),
                    CandidateDocumentTypeEnum::OFFER_EXTENDED_ATTACHMENT
                );

                $documentsInfo = [];
                foreach ($documents as $document) {
                    $extraData = json_decode($document->extra_data, true);
                    $documentsInfo[] = [
                        'filename' => $document->document_filename,
                        'original_filename' => $extraData['original_filename'] ?? '',
                    ];
                }

                $customFields = $this->service->getCustomFieldsForCandidate();

                return view('candidates.details', [
                    'candidatesDetails' => $candidatesDetails,
                    'jobDetails' => $jobDetails,
                    'twilioErrorData' => $twilioErrorData,
                    'customFields' => $customFields,
                    'type' => $request->get('type'),
                    'documents' => $documentsInfo,
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('therapy_candidates')->error($e);
            return response()->noContent(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
    * This is will show the next step form for jobvite
    */
    public function showNextStepForm(Request $request, UserPermissionResolver $helper)
    {
        try {
            if ($request->input('id') != '') {
                $candidatesDetails = Candidates::with('screening')->find($request->input('id'));
                $jobDetails = DB::table('candidate_applications')
                    ->leftJoin('jobs', 'candidate_applications.job_id', '=', 'jobs.id')
                    ->select('jobs.*')
                    ->where('candidate_applications.candidate_id',$request->input('id'))->first();

                return view('candidates.nextstep', [
                    'candidatesDetails' => $candidatesDetails,
                    'jobDetails' => $jobDetails,
                    'bucket' => $candidatesDetails->appWorkflowState,
                    'type' => $request->get('type'),
                    'isTherapyClinicalManager' => $helper->isUserHasRole('Therapy Clinical Manager', Auth::id()),
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('therapy_candidates')->error($e);
            return response()->noContent(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
    * This is will load the form based on the selection of the jobvite outcome
    */
    public function loadOutcomeForm(
        Request $request,
        UserPermissionResolver $permissionResolver,
        OutlookService $outlookService,
    ) {
        try {
            if ($request->input('bucketKey') != '' && $request->input('id') != '') {
                $folder = 'outcomesForm';
                $clinicalManagers = [];
                $date = null;
                $time = null;
                $selectedClinicalManager = 0;
                $key = $request->input('bucketKey');
                if($key == 'pet_1' || $key == 'pet_2' || $key == 'pet_3'){
                    $key = 'pet';
                }
                $candidatesDetails = Candidates::with('screening')->find($request->input('id'));
                $states = H::getHelper("ConstantHelper")->getDashboardState();
                if($key == 'do_not_contactdo_not_hire'){
                    $key = 'do_not_contact_do_not_hire';
                }

                if ($request->get('isTherapy') === 'true') {
                    $folder = 'outcomesFormTherapy';
                }

                if ($key === 'scheduled_clinical_interview' || $key === 'rescheduled_clinical_interview') {
                    $clinicalManagers = $permissionResolver->getUsersByRoleName(RolesEnum::getRoleName(RolesEnum::CLINICAL_CARE_MANAGER->value));

                    $selectedClinicalManager = User::find($candidatesDetails->interviewManager);

                    try {
                        // The event may live in a different mailbox than the currently assigned
                        // manager if outlookEventOwnerId drifted from interviewManager (e.g. rows
                        // created before this column existed) — resolve the mailbox separately from
                        // what's shown as the "current manager" in the form.
                        $eventOwnerId = $candidatesDetails->outlookEventOwnerId ?? $candidatesDetails->interviewManager;
                        $eventOwner = User::find($eventOwnerId);

                        if ($eventOwner) {
                            $outlookService = $outlookService->setUserByEmail($eventOwner->email);
                            $event = $outlookService->getEventInfo($candidatesDetails->outlookEventId);

                            if (isset($event['start']['dateTime'])) {
                                $dt = Carbon::parse($event['start']['dateTime']);
                                $date = $dt->format('m/d/Y');
                                $time = $dt->format('g:ia');
                            }
                        }
                    } catch (Throwable $e) {
                        Log::channel('therapy_candidates')->error($e);
                    }
                    if ($selectedClinicalManager) {
                        $selectedClinicalManager = $selectedClinicalManager->id;
                    }
                }

                $offer = $candidatesDetails->offer;
                $isTherapyClinicalManager = $permissionResolver->isUserHasRole('Therapy Clinical Manager', Auth::id());
                $isRecruiter = $permissionResolver->isUserHasRole('Recruiter', Auth::id());
                $branches = H::getHelper('ConstantHelper')->getBranches()->toArray();
                $branches = H::arrGroupBy($branches, 'state');

                return view("candidates.$folder._".$key, [
                    'candidatesDetails' => $candidatesDetails,
                    'states' => $states,
                    'clinicalManagers' => $clinicalManagers,
                    'selectedClinicalManager' => $selectedClinicalManager,
                    'date' => $date,
                    'time' => $time,
                    'data' => $offer?->originalValues->keyBy('key'),
                    'finalData' => $offer?->finalValues->keyBy('key'),
                    'offer' => $offer,
                    'compensationFieldOptions' => OfferField::compensation()->get(),
                    'isRecruiter' => $isRecruiter,
                    'isClinicalCareManager' => $isTherapyClinicalManager,
                    'isTherapy' => $request->get('isTherapy'),
                    'branches' => $branches,
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('therapy_candidates')->error($e);
            return response()->noContent(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
    * This is will save the form of jobvite outcome
    */
    public function saveOutcome(SaveOutcomeRequest $request, OutlookService $outlookService, ConstantHelper $helper)
    {
        try {
            $reqData = $request->all();
            $msError = false;
            $msErrorMessage = '';
            if(isset($reqData['jobViteOutcome']) && $reqData['jobViteOutcome'] != '' && isset($reqData['id']) && $reqData['id'] != ''){
                $isTherapy = $this->service->isTherapyForCandidate($reqData['id']);

                $currentDateTime = date('Y-m-d H:i:s');
                $daysToPlus = H::getCoreSettings('jobvite_follow_up_date');
                if($daysToPlus != ''){
                    $daysToPlus = '+'.$daysToPlus." days";
                }else{
                    $daysToPlus = "+15 days";
                }
                $updateArr = [];
                if($reqData['jobViteOutcome'] != 'Schedule IPEA' && $reqData['jobViteOutcome'] != 'Document Follow Up'){
                    $updateArr['appWorkflowState'] = ($reqData['jobViteOutcome'] == 'Do Not Contact/Do Not Hire') ? 'Do Not Contact' : $reqData['jobViteOutcome'];
                    $updateArr['workFlowStateChangeDate'] = $currentDateTime;
                    $updateArr['updated_at'] = $currentDateTime;
                    $updateArr['updated_by'] = Auth::user()->id;
                }
                $updateArr['updated_at'] = date('Y-m-d H:i:s');
                $updateArr['updated_by'] = Auth::user()->id;
                $updateArr['syncToJobVite'] = 1;
                $updateArr['workFlowStateChangeDate'] = date('Y-m-d H:i:s');

                if(strpos($reqData['jobViteOutcome'],'P.E.T') !== false){
                    if($reqData['jobViteOutcome'] === 'P.E.T. #1'){
                        $updateArr['PET1Date'] = $currentDateTime;
                    }elseif($reqData['jobViteOutcome'] === 'P.E.T. #2'){
                        $updateArr['PET2Date'] = $currentDateTime;
                    }elseif($reqData['jobViteOutcome'] === 'P.E.T. #3'){
                        $updateArr['PET3Date'] = $currentDateTime;
                        if (!$isTherapy) {
                            $updateArr['appWorkflowState'] = 'Rejected By Agency';
                            $updateArr['RejectedByAgencyReason'] = 'Unable to contact';
                            $updateArr['rejectedByAgencyDate'] = $currentDateTime;
                        }
                    }
                    $updateArr['outcomeNotes'] = $reqData['note'];

                    //Adding Notes
                    $notesData = ['entity' => 'candidate','type'=>'outcome', 'entity_id' => $reqData['id'], 'notes' => 'Status updated to '.$reqData['jobViteOutcome'].' by '.Auth::user()->name, 'completed' => 1];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);

                    if($reqData['jobViteOutcome'] === 'P.E.T. #3' && !$isTherapy) {
                        $notesData = ['entity' => 'candidate','type'=>'outcome', 'entity_id' => $reqData['id'], 'notes' => 'The candidate was rejected by agency for reason: Unable to contact.', 'completed' => 1];
                        H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                    }
                }elseif($reqData['jobViteOutcome'] === 'Recruiter Phone Screen'){

                    if ($isTherapy) {
                        $updateArr['recruiterPhoneScreenDate'] = $currentDateTime;
                        $data = $reqData;
                        $notesData = ['entity' => 'candidate','type'=>'outcomeRPS', 'entity_id' => $reqData['id'], 'notes' => 'Status updated to '.$reqData['jobViteOutcome'].' by '.Auth::user()->name, 'completed' => 1];
                        H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                        $data['candidate_id'] = $reqData['id'];
                        $data['recruiter_id'] = Auth::id();
                        unset($data['id']);

                        if (isset($data['supervise']) && $data['supervise'] === 'on') {
                            $data['supervise'] = 1;
                        } else {
                            $data['supervise'] = 0;
                        }

                        if (isset($data['bilingual']) && $data['bilingual'] === 'on') {
                            $data['bilingual'] = 1;
                        } else {
                            $data['bilingual'] = 0;
                        }

                        $data['start_date'] = Carbon::parse($data['start_date']);

                        CandidateScreening::create($data);
                    } else {
                        $updateArr['FollowUpDateTime'] = date('Y-m-d',strtotime($reqData['followUpDate'])).' '.date('H:i:s',strtotime($reqData['followUpTime']));
                        $updateArr['recruiterPhoneScreenDate'] = $currentDateTime;
                        $updateArr['outcomeNotes'] = $reqData['note'];
                        $notesData = ['entity' => 'candidate','type'=>'outcomeRPS', 'entity_id' => $reqData['id'], 'notes' => 'Status updated to '.$reqData['jobViteOutcome'].' by '.Auth::user()->name." Comments: ".$reqData['note'], 'completed' => 1];
                        H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                    }
                } elseif($reqData['jobViteOutcome'] === JobviteTherapyOutcomeEnum::SCHEDULED_RECRUITER_PHONE_SCREEN->value){
                    $scheduledAt = Carbon::createFromFormat(
                        'm/d/Y g:ia',
                        $reqData['date'] . ' ' . $reqData['time'],
                        config('app.timezone')
                    );
                    $updateArr['scheduledRecruiterPhoneScreenDate'] = $scheduledAt->copy();
                    $updateArr['outcomeNotes'] = $reqData['note'] ?? null;

                    $notesData = [
                        'entity' => EntityTypeEnum::CANDIDATE->value,
                        'type' => 'outcomeScheduledRPS',
                        'entity_id' => $reqData['id'],
                        'notes' => 'Scheduled Recruiter Phone Screen by '.Auth::user()->name.' at '.$scheduledAt->format('m/d/y g:i A'),
                        'completed' => 1,
                    ];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                } elseif($reqData['jobViteOutcome'] === 'Therapist Student: Pending Graduation'){

                    $updateArr['GraduationDate'] = date('Y-m-d',strtotime($reqData['GraduationDate']));
                    $updateArr['FollowUpDateTime'] = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . $daysToPlus));
                    $updateArr['nursingSchoolGradDate'] = now();
                    $updateArr['outcomeNotes'] = $reqData['outcomeNotes'];

                    //Adding Notes
                    $notesData = ['entity' => 'candidate','type'=>'outcome', 'entity_id' => $reqData['id'], 'notes' => 'Therapist Graduation date updated to '.$updateArr['GraduationDate'].' by '.Auth::user()->name . ' Comments: ' . $reqData['outcomeNotes'], 'completed' => 1];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);

                } elseif($reqData['jobViteOutcome'] === 'Scheduled Clinical Interview'){
                    $updateArr['scheduleInterviewDate'] = Carbon::now();
                    $dt = Carbon::createFromFormat(
                        'm/d/Y g:ia',
                        $request['date'] . ' ' . $request['time'],
                        config('app.timezone')
                    );
                    $updateArr['clinicalInterviewDate'] = $dt->copy();
                    $updateArr['interviewManager'] = $reqData['clinicalManager'];
                    $end = $dt->copy();
                    $date = $dt->format('F jS, Y');
                    $time = $dt->format('h:i A') . ' '
                        . $end->copy()->addMinutes(self::DEFAULT_EVENT_TIME_IN_MINUTES)->format('h:i A');

                    try {
                        $interviewer = User::find($reqData['clinicalManager']);
                        $candidate = Candidates::find($reqData['id']);
                        $description = view('candidates.emails.therapy_interview_invite', [
                            'candidate' => Candidates::find($reqData['id']),
                            'interviewer' => $interviewer,
                            'date' => $date,
                            'time' => $time,
                        ])->render();

                        $attendees = new AttendeesDTO(
                            new AttendeeDTO($candidate->email, sprintf('%s %s', $candidate->firstName, $candidate->lastName)),
                        );
                        $eventDTO = new OutlookEventDTO(
                            sprintf('Clinical Interview: %s %s', $candidate->firstName, $candidate->lastName),
                            $description,
                            $dt,
                            $end->addMinutes(self::DEFAULT_EVENT_TIME_IN_MINUTES),
                            config('app.timezone'),
                            '',
                            true,
                            '',
                            $attendees,
                        );

                        $offer = Offer::firstOrCreate(
                            ['employee_id' => $reqData['id']],
                            ['employee_id' => $reqData['id'], 'status' => 0, 'created_by' => Auth::id()]
                        );

                        if ($offer->originalValues->isEmpty()) {
                            // Compensation is no longer seeded here — the dynamic Compensation
                            // section starts empty (AC1) and is populated via syncCompensationItems().
                            $o = [
                                [
                                    'offer_id' => $offer->id,
                                    'key' => 'employee',
                                    'version' => 0,
                                ],
                                [
                                    'offer_id' => $offer->id,
                                    'key' => 'job_title',
                                    'version' => 0,
                                ],
                                [
                                    'offer_id' => $offer->id,
                                    'key' => 'direct_report',
                                    'version' => 0,
                                ],
                                [
                                    'offer_id' => $offer->id,
                                    'key' => 'effective_date',
                                    'version' => 0,
                                ],
                                [
                                    'offer_id' => $offer->id,
                                    'key' => 'visits_per_week',
                                    'version' => 0,
                                ],
                                [
                                    'offer_id' => $offer->id,
                                    'key' => 'territory',
                                    'version' => 0,
                                ],
                            ];

                            OfferValue::insert($o);
                        }

                        $result = $outlookService->setUserByEmail($interviewer->email)->createEvent($eventDTO);

                        $updateArr['outlookEventId'] = $result['id'] ?? null;
                        $updateArr['outlookEventOwnerId'] = $reqData['clinicalManager'];
                        $updateArr['outlookEventICalUId'] = $result['iCalUId'] ?? null;
                    } catch (Exception $exception) {
                        $msError = true;
                        $msErrorMessage = $exception->getMessage();
                    }

                    //Adding Notes
                    $notesData = [
                        'entity' => 'candidate',
                        'type' => 'outcome',
                        'entity_id' => $reqData['id'],
                        'notes' => sprintf('Start date - %s %s', $date, $time),
                        'completed' => 1,
                    ];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                } elseif($reqData['jobViteOutcome'] === 'Rescheduled Clinical Interview'){
                    $updateArr['rescheduleInterviewDate'] = Carbon::now();
                    $dt = Carbon::createFromFormat(
                        'm/d/Y g:ia',
                        $request['date'] . ' ' . $request['time'],
                        config('app.timezone')
                    );
                    $updateArr['clinicalInterviewDate'] = $dt->copy();
                    $updateArr['interviewManager'] = $reqData['clinicalManager'];
                    $end = $dt->copy();
                    $date = $dt->format('F jS, Y');
                    $time = $dt->format('h:i A') . ' ' . $end->copy()
                            ->addMinutes(self::DEFAULT_EVENT_TIME_IN_MINUTES)
                            ->format('h:i A');

                    try {
                        $candidate = Candidates::find($reqData['id']);
                        $interviewer = User::find($reqData['clinicalManager']);

                        // outlookEventOwnerId reflects who actually owns the mailbox holding the live
                        // Graph event; interviewManager is only a fallback for rows created before that
                        // column existed, since interviewManager can drift once reassignment happens.
                        $ownerId = $candidate->outlookEventOwnerId ?? $candidate->interviewManager;
                        $oldInterviewer = User::find($ownerId);
                        $description = view('candidates.emails.therapy_interview_invite', [
                            'candidate' => Candidates::find($reqData['id']),
                            'interviewer' => $interviewer,
                            'date' => $date,
                            'time' => $time,
                        ])->render();

                        $attendees = new AttendeesDTO(
                            new AttendeeDTO($candidate->email, sprintf('%s %s', $candidate->firstName, $candidate->lastName)),
                        );
                        $eventDTO = new OutlookEventDTO(
                            sprintf('Clinical Interview: %s %s', $candidate->firstName, $candidate->lastName),
                            $description,
                            $dt,
                            $end->addMinutes(self::DEFAULT_EVENT_TIME_IN_MINUTES),
                            config('app.timezone'),
                            '',
                            true,
                            '',
                            $attendees,
                        );

                        if ((int)$ownerId === (int)$reqData['clinicalManager']) {
                            $eventDTO = new OutlookEventDTO(
                                sprintf('Clinical Interview: %s %s', $candidate->firstName, $candidate->lastName),
                                $description,
                                $dt,
                                $end->addMinutes(self::DEFAULT_EVENT_TIME_IN_MINUTES),
                                config('app.timezone'),
                                '',
                                true,
                                $candidate->outlookEventId,
                                $attendees,
                            );
                            $outlookService = $outlookService->setUserByEmail($interviewer->email);

                            $event = $outlookService->setUserByEmail($interviewer->email)->getEventInfo($candidate->outlookEventId);

                            if (!empty($event)) {
                                if ($event['start']['dateTime'] !== $dt->format('Y-m-d\TH:i:s.u') . 0) {
                                    $outlookService->setUserByEmail($interviewer->email)->updateEvent($eventDTO);
                                }
                            }

                            $updateArr['outlookEventOwnerId'] = $reqData['clinicalManager'];
                        } else {
                            $outlookService->setUserByEmail($oldInterviewer->email)->cancelEvent($candidate->outlookEventId);
                            $result = $outlookService->setUserByEmail($interviewer->email)->createEvent($eventDTO);
                            $updateArr['outlookEventId'] = $result['id'] ?? null;
                            $updateArr['outlookEventOwnerId'] = $reqData['clinicalManager'];
                            $updateArr['outlookEventICalUId'] = $result['iCalUId'] ?? null;
                        }

                    } catch (Exception $exception) {
                        Log::channel('therapy_candidates')->error($exception);
                        $msError = true;
                        $msErrorMessage = $exception->getMessage();
                    }

                    $notesData = [
                        'entity' => 'candidate',
                        'type' => 'outcome',
                        'entity_id' => $reqData['id'],
                        'notes' => sprintf('Interview has been rescheduled - %s:%s', $date, $time),
                        'completed' => 1,
                    ];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);

                    $notesData = [
                        'entity' => 'candidate',
                        'type' => 'outcome',
                        'entity_id' => $reqData['id'],
                        'notes' => 'status updated - Reschedule clinical interview',
                        'completed' => 1,
                    ];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                } elseif($reqData['jobViteOutcome'] == 'Clinical Interview') {
                    $updateArr['clinicalInterviewDate'] = Carbon::now();
                    $updateOfferFields = [
                        'employee',
                        'job_title',
                        'direct_report',
                        'effective_date',
                        'visits_per_week',
                        'territory',
                    ];

                    $offer = Offer::firstOrCreate(
                        ['employee_id' => $reqData['id']],
                        ['status' => 0, 'created_by' => Auth::id()]
                    );

                    $offerFieldDefs = OfferField::whereIn('key', $updateOfferFields)->get()->keyBy('key');

                    foreach ($updateOfferFields as $offerFieldKey) {
                        $type = $offerFieldDefs->get($offerFieldKey)?->value_type ?? 'string';

                        $updateData = match ($type) {
                            'decimal' => ['value_decimal' => $reqData[$offerFieldKey]],
                            'int' => ['value_int' => $reqData[$offerFieldKey]],
                            'date' => ['value_date' => $reqData[$offerFieldKey]],
                            default => ['value_string' => $reqData[$offerFieldKey]],
                        };

                        OfferValue::updateOrCreate(
                            ['offer_id' => $offer->id, 'key' => $offerFieldKey, 'version' => 0],
                            $updateData
                        );

                        OfferValue::updateOrCreate(
                            ['offer_id' => $offer->id, 'key' => $offerFieldKey, 'version' => 1],
                            $updateData
                        );
                    }

                    // Clinical manager's compensation entry seeds both the original record and
                    // the recruiter's starting point (mirrors the version 0 + 1 copy above).
                    $compensationItems = $reqData['compensation_items'] ?? [];
                    $this->syncCompensationItems($offer, $compensationItems, OfferCompensationItem::VERSION_ORIGINAL);
                    $this->syncCompensationItems($offer, $compensationItems, OfferCompensationItem::VERSION_FINAL);

                    $interviewFields = [
                        'about_self',
                        'position_expectations',
                        'pediatric_clinical_skills',
                        'professional_strengths',
                        'discipline_specific_questions',
                        'areas_for_improvement',
                        'organizational_skills',
                        'challenging_situation',
                        'previous_employer_feedback',
                        'emr_and_technology_experience',
                        'community_travel_comfort',
                        'non_english_family_experience',
                        'five_year_goals',
                        'leadership_interest',
                        'discussion_notes',
                        'desired_weekly_visits',
                        'preferred_area_and_driving_range',
                        'early_intervention_interest',
                        'candidate_questions',
                        'desired_pay_per_visit',
                        'additional_comments',
                    ];

                    $interview = [];

                    foreach ($interviewFields as $interviewField) {
                        $interview[$interviewField] = $reqData[$interviewField];
                    }

                    CandidateClinicalInterviewQuestion::updateOrCreate(
                        ['employee_id' => $reqData['id']],
                        ['interview' => $interview],
                    );

                    $jobDetails = DB::table('candidate_applications')
                        ->leftJoin('jobs', 'candidate_applications.job_id', '=', 'jobs.id')
                        ->leftJoin('branch_locations', 'jobs.region', '=', 'branch_locations.branch')
                        ->select('jobs.*', 'branch_locations.id as bid')
                        ->where('candidate_applications.candidate_id',$request->input('id'))->first();

                    $recruitersId = ($jobDetails && $jobDetails->bid)
                        ? H::getRecruitersByBranchId($jobDetails->bid)
                        : [];
                    $candidate = Candidates::find($reqData['id']);

                    foreach ($recruitersId as $recruiterId) {
                        Notifications::addNotification([
                            'user_id' => $recruiterId,
                            'title' => 'Therapy candidate status were changed to Clinical Interview',
                            'notes' => sprintf(
                                'Candidate %s status with email %s were changed to Clinical Interview',
                                $candidate->fullName,
                                $candidate->email
                            ),
                            'type' => 'therapyCandidateStatusClinicalInterview',
                            'related_item' => $reqData['id'],
                        ]);
                    }

                    $notesData = [
                        'entity' => 'candidate',
                        'type'=>'outcome',
                        'entity_id' => $reqData['id'],
                        'notes' => 'Status updated to ' . $reqData['jobViteOutcome'] . ' by ' . Auth::user()->name . " <br>Comments: Clinical Interview date -" . now()->format('m/d/Y h:i:s'),
                        'completed' => 1,
                    ];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);

                    $notesData = [
                        'entity' => 'candidate',
                        'type'=>'outcome',
                        'entity_id' => $reqData['id'],
                        'notes' => 'Status updated to ' . $reqData['jobViteOutcome'] . ' by ' . Auth::user()->name,
                        'completed' => 1,
                    ];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                } elseif($reqData['jobViteOutcome'] == 'Offer Extended') {
                    $isTherapyClinicalManager = $helper->getIsUserInRoleSafe('Therapy Clinical Manager', Auth::id());
                    $isRecruiter = $helper->getIsUserInRoleSafe('Recruiter', Auth::id());
                    $candidate = Candidates::find($reqData['id']);

                    // Optional attachments, already validated by SaveOutcomeRequest.
                    if ($request->hasFile('offer_extended_files')) {
                        $this->offerResourcesService->storeOfferExtendedFiles($request->file('offer_extended_files'), $reqData['id']);
                    }

                    if ($isTherapyClinicalManager && isset($reqData['ccm_note']) && $reqData['ccm_note'] !== '') {
                        $notesData = [
                            'entity' => 'candidate',
                            'type' => 'outcome',
                            'entity_id' => $reqData['id'],
                            'notes' => 'follow-up notes: ' . $reqData['ccm_note'],
                            'completed' => 1,
                        ];

                        H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                    }

                    if ($isRecruiter && isset($reqData['note'])) {
                        $notesData = [
                            'entity' => 'candidate',
                            'type' => 'outcome',
                            'entity_id' => $reqData['id'],
                            'notes' => 'follow-up notes: ' . $reqData['note'],
                            'completed' => 1,
                        ];

                        H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                    }

                    $offerExtendedFields = [
                        'employee',
                        'job_title',
                        'direct_report',
                        'effective_date',
                        'visits_per_week',
                        'territory',
                    ];

                    $offer = Offer::where('employee_id', $reqData['id'])->first();

                    if ($offer) {
                        $offerFieldDefs = OfferField::whereIn('key', $offerExtendedFields)->get()->keyBy('key');

                        foreach ($offerExtendedFields as $offerFieldKey) {
                            if (!isset($reqData[$offerFieldKey])) {
                                continue;
                            }

                            $type = $offerFieldDefs->get($offerFieldKey)?->value_type ?? 'string';

                            $updateData = match ($type) {
                                'decimal' => ['value_decimal' => $reqData[$offerFieldKey]],
                                'int' => ['value_int' => $reqData[$offerFieldKey]],
                                'date' => ['value_date' => $reqData[$offerFieldKey]],
                                default => ['value_string' => $reqData[$offerFieldKey]],
                            };

                            OfferValue::updateOrCreate(
                                ['offer_id' => $offer->id, 'key' => $offerFieldKey, 'version' => 1],
                                $updateData
                            );
                        }

                        if (isset($reqData['compensation_items'])) {
                            $this->syncCompensationItems($offer, $reqData['compensation_items'], OfferCompensationItem::VERSION_FINAL);
                        }
                    }

                    if ($candidate->appWorkflowState !== 'Offer Extended') {
                        $updateArr['appWorkflowState'] = 'Offer Extended';
                        $updateArr['offerExtendedDate'] = now();

                        if ($isRecruiter) {
                            event(new OfferApprovedEvent($reqData['id'], Auth::id()));

                            $notesData = [
                                'entity' => 'candidate',
                                'type' => 'outcome',
                                'entity_id' => $reqData['id'],
                                'notes' => 'Status updated to '.$reqData['jobViteOutcome'].' by '.Auth::user()->name,
                                'completed' => 1,
                            ];

                            H::getHelper('ActivityNotes')->setActivityNotes($notesData);

                            $notesData = [
                                'entity' => 'candidate',
                                'type' => 'outcome',
                                'entity_id' => $reqData['id'],
                                'notes' => 'Offer sent at - ' . now()->format('m/d/Y'),
                                'completed' => 1,
                            ];
                            H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                        }
                    }
                } elseif($reqData['jobViteOutcome'] == 'Offer Accepted') {
                    $candidateId = $reqData['id'];


                    $tracker = MssqlRecruitertracker::where('aoc_candidate_id', $candidateId)
                        ->where('is_active', 1)
                        ->orderBy('id', 'desc')
                        ->first();
                    $trackerId = $tracker ? $tracker->id : null;

                    $documentsPath = storage_path() . '/documents/';
                    if (!is_dir($documentsPath)) {
                        mkdir($documentsPath);
                    }

                    if ($request->hasFile('offer_accepted_file')) {
                        $file = $request->file('offer_accepted_file');
                        $filename = time() . '_' . $file->getClientOriginalName();
                        $file->move($documentsPath, $filename);
                        RightSignatureDocuments::insert([
                            'entity_type' => 'candidate',
                            'entity_id' => $candidateId,
                            'tracker_id' => $trackerId,
                            'document_type' => 'offer_accepted',
                            'document_filename' => $filename,
                            'description' => date('Y') . ' Offer Accepted',
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    }

                    if ($request->hasFile('attachment')) {
                        $file = $request->file('attachment');
                        $filename = time() . '_' . $file->getClientOriginalName();
                        $file->move($documentsPath, $filename);
                        RightSignatureDocuments::insert([
                            'entity_type' => 'candidate',
                            'entity_id' => $candidateId,
                            'tracker_id' => $trackerId,
                            'document_type' => 'neo_attachment',
                            'document_filename' => $filename,
                            'description' => date('Y') . ' NEO Attachment',
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    }

                    if (isset($reqData['neo_form'])) {
                        $schedule = app(ScheduleController::class);
                        $schedule->saveTherapyIpeaEvent($request);
                    }

                    $updateArr['appWorkflowState'] = 'Offer Accepted';
                    $updateArr['offerAcceptedDate'] = now();
                } elseif($reqData['jobViteOutcome'] == 'Offer Rejected'){
                    $updateArr['OfferRejectedReason'] = $reqData['OfferRejectedReason'];
                    $updateArr['Client'] = isset($reqData['Client'])?$reqData['Client']:'';
                    $updateArr['Payer'] = isset($reqData['Payer'])?$reqData['Payer']:'';
                    $updateArr['Pay_Rate'] = isset($reqData['Pay_Rate'])?$reqData['Pay_Rate']:'';
                    $updateArr['GP'] = isset($reqData['GP'])?$reqData['GP']:'';
                    $updateArr['offerRejectedDate'] = $currentDateTime;

                    if ($isTherapy) {
                        $rejectedReason = $reqData['OfferRejectedReason'];
                        if ($rejectedReason === 'Pay') {
                            $notesText = 'Candidate rejected offer: Reason: Pay | Client: ' . $updateArr['Client'] . ' | Payer: ' . $updateArr['Payer'] . ' | Pay Rate: ' . $updateArr['Pay_Rate'] . ' | GP: ' . $updateArr['GP'];
                        } else {
                            $notesText = 'Candidate rejected offer: Reason: ' . $rejectedReason;
                        }
                    } else {
                        $notesText = 'Candidate rejected offer: ' . $updateArr['Client'] . ' Pay Rate:' . $reqData['Pay_Rate'] . ' GP:' . $updateArr['GP'];
                    }
                    $notesData = ['entity' => 'candidate', 'type' => 'outcome', 'entity_id' => $reqData['id'], 'notes' => $notesText, 'completed' => 1];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                }elseif(strtolower($reqData['jobViteOutcome']) == strtolower('Rejected By Agency')){
                    $updateArr['RejectedByAgencyReason'] = $reqData['RejectedByAgencyReason'];
                    $updateArr['rejectedByAgencyDate'] = $currentDateTime;
                    $updateArr['outcomeNotes'] = $reqData['note'];

                    //Adding Notes
                    $notesData = ['entity' => 'candidate','type'=>'outcome', 'entity_id' => $reqData['id'], 'notes' => 'Candidate rejected by agency: '.$updateArr['RejectedByAgencyReason'] . ' (' . $reqData['note'] . ')', 'completed' => 1];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                }elseif($reqData['jobViteOutcome'] == 'Added To Potential Nurse List'){
                    $insertArr = $request->except(['jobViteOutcome','id','_token']);
                    $insertArr['Skills_Experience'] = isset($insertArr['Skills_Experience']) ? implode(',',$insertArr['Skills_Experience']) : '';
                    if (LD::checkFeatureFlag('potential-nurse-map-changes')) {
                        $typeOfShift = [];
                        $desirectSchedule = [];
                        foreach ($insertArr['selectdays'] as $selectdayKey => $selectdayValue) {
                            $time = H::getHelper("ConstantHelper")->getTimeDifference($insertArr['start_time'][$selectdayKey], $insertArr['end_time'][$selectdayKey]);
                            foreach($selectdayValue as $day) {
                                $shiftType = H::getHelper("ConstantHelper")->getShiftType($day, $time['startAMPM'], $time['endAMPM']);
                                $typeOfShift[] = $shiftType->ShiftTypeDescription;

                                $days = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
                                switch($day) {
                                    case "Sunday":
                                        $tmp = 1;
                                        break;
                                    case "Monday":
                                        $tmp = 2;
                                        break;
                                    case "Tuesday":
                                        $tmp = 3;
                                        break;
                                    case "Wednesday":
                                        $tmp = 4;
                                        break;
                                    case "Thursday":
                                        $tmp = 5;
                                        break;
                                    case "Friday":
                                        $tmp = 6;
                                        break;
                                    case "Saturday":
                                        $tmp = 7;
                                        break;
                                    default:
                                        $tmp = 0;
                                        break;
                                }
                                $startAt = $days[$tmp - 1] . ' ' . $insertArr['start_time'][$selectdayKey];
                                if($insertArr['end_time'][$selectdayKey] < $insertArr['start_time'][$selectdayKey]){
                                    $tmp = $tmp + 1;
                                    if($tmp > 7) $tmp = 1;
                                }
                                $endAt = $days[$tmp - 1] . ' ' . $insertArr['end_time'][$selectdayKey];
                                $desirectSchedule[] = $startAt . ' to ' . $endAt;
                            }
                            $insertArr['desired_schedule'] = implode(" | ", $desirectSchedule);
                            $insertArr['type_of_shift'] = implode(" | ", $typeOfShift);
                        }
                        unset($insertArr['selectdays']);
                        unset($insertArr['start_time']);
                        unset($insertArr['end_time']);
                    }
                    $insertArr['Date_created'] = date('Y-m-d');
                    $insertArr['needs_geocoding'] = 1;
                    $insertArr['created_at'] = $insertArr['updated_at'] = date('Y-m-d H:i:s');
                    DB::table('maps_potential_nurse')->insert($insertArr);
                    $updateArr['addedToPotentialNurseDate'] = $currentDateTime;

                    //Adding Notes
                    $notesData = ['entity' => 'candidate','type'=>'outcome', 'entity_id' => $reqData['id'], 'notes' => 'Status updated to '.$reqData['jobViteOutcome'].' by '.Auth::user()->name, 'completed' => 1];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                }elseif($reqData['jobViteOutcome'] == 'Nursing Student: Pending Graduation'){
                    $updateArr['GraduationDate'] = date('Y-m-d',strtotime($reqData['GraduationDate']));
                    $updateArr['FollowUpDateTime'] = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . $daysToPlus));
                    $updateArr['nursingSchoolGradDate'] = $currentDateTime;

                    //Adding Notes
                    $notesData = ['entity' => 'candidate','type'=>'outcome', 'entity_id' => $reqData['id'], 'notes' => 'Nurse Graduation date updated to '.$updateArr['GraduationDate'].' by '.Auth::user()->name, 'completed' => 1];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                }elseif($reqData['jobViteOutcome'] == 'Document Follow Up'){
                    $updateArr['FollowUpDateTime'] = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . $daysToPlus));
                    $updateArr['outcomeNotes'] = $reqData['note'];

                    //Adding Notes
                    $notesData = ['entity' => 'candidate','type'=>'outcome', 'entity_id' => $reqData['id'], 'notes' => $reqData['note'], 'completed' => 1];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                }elseif($reqData['jobViteOutcome'] == 'Do Not Contact/Do Not Hire'){
                    $updateArr['donothireReason'] = $reqData['Donothire'];
                    $updateArr['donothireDate'] = $currentDateTime;
                    $updateArr['outcomeNotes'] = $reqData['note'];
                    //Adding Notes
                    $notesData = ['entity' => 'candidate','type'=>'outcome', 'entity_id' => $reqData['id'], 'notes' => 'Candidate Do Not Contact/Do Not Hire: '.$updateArr['donothireReason'] . ' (' . $reqData['note'] . ')', 'completed' => 1];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                } elseif($reqData['jobViteOutcome']=='Reschedule Follow Up') {
                    $date = \DateTime::createFromFormat("m/d/Y H:i:s" , $reqData['followupdate']);
                    $updateArr['FollowUpDateTime'] = $date->format('Y-m-d H:i:s');
                    unset($updateArr['appWorkflowState']);
                    $notesData = ['entity' => 'candidate','type'=>'outcome', 'entity_id' => $reqData['id'], 'notes' => 'Candidate Followup date changed: '.$reqData['followupdate'], 'completed' => 1];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                }
                // If the Graph call for a clinical interview failed, don't let the DB assert a
                // calendar state (owner/id/dates) that doesn't actually exist in Outlook.
                if ($msError && in_array($reqData['jobViteOutcome'], ['Scheduled Clinical Interview', 'Rescheduled Clinical Interview'], true)) {
                    unset(
                        $updateArr['outlookEventId'],
                        $updateArr['outlookEventOwnerId'],
                        $updateArr['outlookEventICalUId'],
                    );
                }

                //Update candidates user_id
                //$updateArr['user_id'] = Auth::user()->id; todo need to remove if it's not affect another places. the user_id already exist when the candidate was created
                                                        /// and this user_id is an recruiter
                Candidates::where('id',$reqData['id'])->update($updateArr);

                if ($msError) {
                    return ['status' => false, 'msg' => $msErrorMessage];
                }

                return ['status'=>true];
            }else{
                return ['status'=>false];
            }
        } catch (\Exception $e) {
            Log::channel('therapy_candidates')->error($e->getMessage(), [
                'candidate_id' => $reqData['id'] ?? null,
                'jobViteOutcome' => $reqData['jobViteOutcome'] ?? null,
                'trace' => $e->getTraceAsString(),
            ]);

            return ['status' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * Replaces an offer's dynamic compensation items for one version (0 = original / clinical
     * manager, 1 = final / recruiter) with the submitted set. Full replace-on-submit: this is
     * what makes an unsaved add/remove in the form a no-op if the outcome is never submitted.
     */
    private function syncCompensationItems(Offer $offer, array $items, int $version): void
    {
        $fieldsByKey = OfferField::compensation()->get()->keyBy('key');

        $rows = [];

        foreach (array_values($items) as $index => $item) {
            $typeKey = $item['type_key'] ?? null;
            $field = $fieldsByKey->get($typeKey);

            if (!$field || $typeKey === '') {
                continue;
            }

            $amount = (float) ($item['amount'] ?? 0);
            $customLabel = $typeKey === 'other' ? trim((string) ($item['custom_label'] ?? '')) : null;

            if ($typeKey === 'other' && $customLabel === '') {
                continue;
            }

            $rows[] = [
                'offer_id' => $offer->id,
                'version' => $version,
                'offer_field_id' => $field->id,
                'custom_label' => $customLabel,
                'amount' => max(0, $amount),
                'notes' => $item['notes'] ?? null,
                'sort_order' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        OfferCompensationItem::where('offer_id', $offer->id)->where('version', $version)->delete();

        if (!empty($rows)) {
            OfferCompensationItem::insert($rows);
        }
    }

    public function goToNeedToHireMap(Request $request)
    {
        if($request->input('candidateId') != ''){
            $map = Map::where('map_name','like','%Need To Hire%')->first();
            if(isset($map->map_id) && $map->map_id != ''){
                $request->session()->put('selectedmap', $map->map_id);
                $candidatesDetails = Candidates::find($request->input('candidateId'));
                $addressString = $candidatesDetails->address.','.$candidatesDetails->address2.','.$candidatesDetails->city.','.$candidatesDetails->state.','.$candidatesDetails->country;
                $addressString = str_replace(',,',',',trim($addressString,','));
                $locationData = Geocoding::getGeoGoogle($addressString);
                if(isset($locationData['lat']) && $locationData['lat'] != '' && isset($locationData['lng']) && $locationData['lng'] != ''){
                    $address['lastmapsearch'] = ['formatedString'=>$addressString,'location'=>$addressString,'radius'=>10,'lat'=>$locationData['lat'],'lng'=>$locationData['lng'],'mode'=>"search",'candidateId'=>$candidatesDetails->id];
                    $request->session()->put($map->map_tablename, $address);
                }else{
                    $address['lastmapsearch'] = ['candidateId'=>$candidatesDetails->id];
                    $request->session()->put($map->map_tablename, $address);
                }
                return redirect()->route('map.map');
            }
        }
    }

    public function listing() {
        $dataTable = H::getHelper('Datatable')->getConfiguration('candidates');
        return view('candidates.grid', ['dataTable' => $dataTable]);
    }

    public function ajaxCandidateListing(Request $request) {
        $data = H::getHelper('Datatable')->ajaxDataResponse($request, 'candidates');
        return $data;
    }

    public function exportCandidatesData(Request $request){
        $data = H::getHelper('Datatable')->exportCandidatesRecordWithFilter($request, 'candidates');

        $dataTableFields = [
            'fullName' => 'Full Name',
            'jbtitle' => 'Title',
            'jbcity' => 'City',
            'application_date' => 'Application Date',
            'mobile' => 'Phone',
            'email' => 'Email',
            'appWorkFlowState' => 'Workflow State',
            'recruiterPhoneScreenDate' => 'Recruiter Phone Screen',
            'PET1Date' => 'PET1',
            'PET2Date' => 'PET2',
            'PET3Date' => 'PET3',
            'IPEAScheduledDate' => 'IPEA Scheduled',
            'nursingSchoolGradDate' => 'Nursing Student Pending Graduation',
            'addedToPotentialNurseDate' => 'Added to Potential Nurse List',
            'offerRejectedDate' => 'Offer Rejected',
            'rejectedByAgencyDate' => 'Rejected By Agency',
        ];
        if (LD::checkFeatureFlag('add-column-candidates-export')) {
            $dataTableFieldNew = [
                'RejectedByAgencyReason' => 'Rejected by Agency Reason',
                'RejectedByAgencyDetailReason' => 'Rejected by Agency Detailed Reason',
                'OfferRejectedReason' => 'Offer Rejected Reason',
                'donothireReason' => 'Do Not Contact/Do Not Hire Reason',
                'donothireDetailReason' => 'Do Not Contact/Do Not Hire Detailed Reason'];

            $dataTableFields = array_merge($dataTableFields,$dataTableFieldNew);
        }
        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=candidatesData.csv",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $columns = [];

        foreach ($dataTableFields as $k => $v) {
            $columns[] = $v;
        }
        $callback = function () use ($data, $columns, $dataTableFields) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($data as $tableData) {
                $csvData = [];
                foreach ($dataTableFields as $fields => $val) {
                    $csvData[] = $tableData->{$fields};
                }

                fputcsv($file, $csvData);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    /**
    * Save new candidate form
    * @author Nayan
    */
    public function getAddCandidateForm(Request $request, JobCategoriesReadRepository $jobCategoriesReadRepository, ConstantHelper $helper){
        return view('candidates.add_candidate', [
            'recruiters' => $helper->getRecruiters('email'),
            'categoryList' => $jobCategoriesReadRepository->getJobCategoriesFilteredByKeyword($request->get('jobCategoryType')),
            'appSourceTypeData' => config('constant')['appSourceType'],
            'appSourceType' => array_keys(config('constant')['appSourceType']),
            'recruiterEmail' => Auth::user()->email,
            'customFields' => $this->service->getCustomFieldsForCandidate(),
            'jobCategoryType' => $request->get('jobCategoryType'),
        ]);
    }

    public function getCandidateImport(Request $request){
        return view('candidates.import');
    }

    public function downloadSampleCSV(Request $request) {
        $dataTableFields = [
            'firstName' => 'firstName',
            'lastName' => 'lastName',
            'mobile' => 'mobile',
            'email' => 'email',
            'title' => 'title',
            'GraduationDate' => 'GraduationDate',
            'address' => 'address',
            'city' => 'city',
            'state' => 'state',
            'postalCode' => 'postalCode',
        ];

        $data = [
            [
            'firstName' => 'Susie',
            'lastName' => 'Smith',
            'mobile' => '555-555-5555',
            'email' => 'susie@email.com',
            'title' => 'RN',
            'GraduationDate' => '5/11/2024',
            'address' => '123 Main Street',
            'city' => 'Dallas',
            'state' => 'TX',
            'postalCode' => '98998',
            ],
            [
                'firstName' => 'Sam',
                'lastName' => 'Smith',
                'mobile' => '444-444-4444',
                'email' => 'sam@email.com',
                'title' => 'AIDE',
                'GraduationDate' => '',
                'address' => '111 Ave A',
                'city' => 'Dallas',
                'state' => 'TX',
                'postalCode' => '45875',
                ]
        ];

        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=candidates_export_template.csv",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $columns = [];

        foreach ($dataTableFields as $k => $v) {
            $columns[] = $v;
        }
        $callback = function () use ($data, $columns, $dataTableFields) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($data as $tableData) {
                $csvData = [];
                foreach($tableData as $key => $value) {
                    $csvData[] = $value;
                }
                fputcsv($file, $csvData);
            }

            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function processCandidateImport(Request $request){
        if(LD::checkFeatureFlag('candidate-acquisition-import')){
            $request->validate(["candidatecsv"=>'required','source_type'=>'required']);
        }else{
            $request->validate(["candidatecsv"=>'required','recruiter'=>'required','source_type'=>'required']);
        }

        $files = $request->file('candidatecsv')->getRealPath();

       $candidatesData = H::getHelper('Jobvite')->csvToArray($files);

       $selectedRecruiter = !empty($request->input('recruiter'))
            ? User::where('email', $request->input('recruiter'))->first()
            : null;

       $otherFields = [
            "jobRecruiters"=>$request->input("recruiter") ?? '',
            "appSourceType"=>$request->input("source_type"),
            "appSource"=>$request->input("source"),
            'jobCategoryName' => LD::checkFeatureFlag('candidate-acquisition-import') ? $request->input('category') :'',
            'jobTitle' => '',
            'jobId' =>0,
            'eId' =>  0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' =>  date('Y-m-d H:i:s'),
            'appSentDate' => date('Y-m-d H:i:s'),
            'application_date'=> date('Y-m-d'),
            'created_by'  =>Auth::user()->id,
            'updated_by'  =>Auth::user()->id,
            'user_id' => $selectedRecruiter->id ?? Auth::user()->id,
            'appWorkflowState' => LD::checkFeatureFlag('candidate-acquisition-import') ? 'Acquisition Import' :'New',
            'needs_geocoding' => LD::checkFeatureFlag('candidate-acquisition-import') ? 1 : NULL
        ];


        $candidateCombineData = [];
        foreach($candidatesData as $candidate){
            $candidateCombineData[] = array_merge($candidate,$otherFields,['fullName'=>$candidate['firstName'] . ' ' . $candidate['lastName'],'Search_Name'=>$candidate['firstName'] . ' ' . $candidate['lastName'].' '.$candidate['firstName']]);
        }

        //Code for Acquisition Import
        if(LD::checkFeatureFlag('candidate-acquisition-import')) {
            $allEmails = array_filter(array_column($candidateCombineData, 'email'));
            $allPhones = array_filter(array_column($candidateCombineData, 'mobile'));

            $existingCandidates = Candidates::where(function ($query) use ($allEmails, $allPhones) {
                $query->whereIn('email', $allEmails)
                    ->orWhereIn('mobile', $allPhones);
            })->get(['email', 'mobile']);
            $existingEmails = $existingCandidates->pluck('email')->map(fn($e) => trim(strtolower($e)))->toArray();
            $existingPhones = $existingCandidates->pluck('mobile')->map(fn($p) => trim($p))->toArray();
        }

        //Insert into candidate applications

        $candidate_applications['workflowState'] = 'New';
        if(LD::checkFeatureFlag('candidate-acquisition-import')) {
            $candidate_applications['workflowState'] = 'Acquisition Import';
        }
        $candidate_applications['personalDataProcessingStatus'] = 'active';

        $candidate_applications['created_at'] = $candidate_applications['updated_at'] = $candidate_applications['lastUpdatedDate'] = $candidate_applications['sentDate'] = date('Y-m-d H:i:s');
        $candidate_applications['application_date'] = date('Y-m-d');
        $candidate_applications['created_by'] = $candidate_applications['updated_by'] =Auth::user()->id;

        $candidate_applications['source'] = $request->source ?? '';
        $candidate_applications['sourceType'] = $request->source_type ?? '';

        $skippedCount = 0;
        $importedCount = 0;

        foreach($candidateCombineData as $candidate) {

            $isDuplicate = false;
            if (LD::checkFeatureFlag('candidate-acquisition-import')) {
                $email = trim(strtolower($candidate['email'] ?? ''));
                $phone = trim($candidate['mobile'] ?? '');

                if (!empty($email) && in_array($email, $existingEmails)) {
                    $isDuplicate = true;
                } elseif (!empty($phone) && in_array($phone, $existingPhones)) {
                    $isDuplicate = true;
                }

                if ($isDuplicate) {
                    $skippedCount++;
                    H::ExcelLog('Skipped duplicate candidate: ' . print_r($candidate,true), 'saveNewCandidates.log');
                    continue;
                }
            }


            if(!empty($candidate['GraduationDate'])) {
                $candidate['GraduationDate'] = date('Y-m-d',strtotime($candidate['GraduationDate']));
            }

            $data = Candidates::create($candidate);
            $importedCount++;
            $candidate_id = $data->id;
            $candidate_applications['candidate_id'] = $candidate_id;
            H::ExcelLog('save candidate: ' . $candidate_id . ' - ' . print_r($data, true), 'saveNewCandidates.log');
            if($request->job_id != '') {
                $jobIdArray = $request->job_id;
                $eIdArray = $request->eid;
                $candidate_applications['eId']  = $eIdArray;
                $candidate_applications['job_id'] = $jobIdArray;
                DB::table('candidate_applications')->insert($candidate_applications);
                H::ExcelLog('insert candidate application: ' . print_r($candidate_applications, true), 'saveNewCandidates.log');
            }
        }
        H::ExcelLog("Import Summary — Imported: $importedCount, Skipped: $skippedCount", 'saveNewCandidates.log');
        return redirect()->route('candidatesListing')->withSuccess("Candidates data processed successfully");

    }

    /**
    * This is will search job based on category and search terms
    * @author Nayan
    */
    public function getJobListByCategory(Request $request)
    {
        $term = str_replace('  ', ' ', str_replace(',', '', $request->term));
        $category = $request->category;

        $clientlist = Jobs::select('jobs.id','jobs.title','jobs.eId','jobs.locationCity')
        ->join('job_categories', 'jobs.category', '=', 'job_categories.category')
        ->where('jobs.jobState',  'Open')
        ->where(
            function ($query) use ($term, $category) {
                $searchValue = '%' . $term . '%';
                $query->where(function ($query) use ($searchValue) {
                    $query->where('jobs.locationCity', 'like', $searchValue)
                    ->orWhere('jobs.title', 'like', $searchValue);
                });
                if ($category != '') {
                    $query->where('jobs.category', 'like', '%' . $category . '%');
                }
            }
        )
        ->get();
        $clientlist = json_decode(json_encode($clientlist), true);

        $clientListData = [];
        $cnt = 0;
        foreach ($clientlist as $clientSubData) {
            $clientListData[] = ['id' => $clientSubData['id'], 'label' => $clientSubData['title'].', '.$clientSubData['locationCity'], 'eId' => $clientSubData['eId'], 'locationCity' => $clientSubData['locationCity'] ];
            $cnt++;
        }

        return response()->json($clientListData);
    }

    public function saveNewCandidate(StoreCandidateRequest $request): JsonResponse
    {
        $data = $request->validated();
        try {
            if ($this->service->isTherapy($request->get('jobCategoryType'))) {
                $check = $this->checkCandidate($request);

                if (!$check->getData(true)['success']) {
                    return $check;
                }
            }


            DB::beginTransaction();

            if (!empty($request->recruiter)) {
                $userDetail = User::where('email', $request->recruiter)->first();
                $data['recruiter_id'] = $userDetail->id ?? 0;
            }

            $candidate = $this->service->create($data);
            $this->service->attachApplications($candidate, $data);

            DB::commit();

            return response()->json(
              [
                'modal' => false,
                'success' => 1,
                'message' => 'Infomation saved successfully',
                'candidate_id' => $candidate->id,
              ]
            );

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('route saveNewCandidate failed', [
              'request_data' => $data,
              'error' => $e->getMessage(),
              'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
              'success' => 0,
              'error' => 'Something went wrong, Please try again.',
            ]);
        }
    }

    public function checkCandidate(Request $request): JsonResponse
    {
        $conditions = array_merge($request->only(['email', 'mobile', 'firstName', 'lastName']), ['appWorkflowState' => 'Do not contact/do not hire']);

        return response()->json([
            'modal' => true,
            'success' => !Candidates::where($conditions)->exists(),
            'error' => 'Candidate cannot be added — previously marked as Do Not Contact/Do Not Hire.',
        ]);
    }

    public function checkCandidateMatch(Request $request){

        $matchedCandidate = MssqlProviders::query()
            ->leftJoin('mssql_employmentstatus', 'mssql_employmentstatus.EmploymentStatusID', '=', 'mssql_providers.EmploymentStatusID')
            ->where(function ($query) use ($request) {
                $query->where('Phone', $request->phone)
                    ->orWhere('Mobile', $request->phone);
            })
            ->orWhere('External_Email', $request->email)
            ->whereNotIn('mssql_employmentstatus.EmploymentStatus', ['Terminated', 'Active Coverage'])
            ->select('mssql_providers.*')
            ->first();
        if($matchedCandidate) {
            if(LD::checkFeatureFlag('add-clinician-active-coverage-allow-terminated-clinician')) {
                if($request->candidate_type=='prn') {
                    if(in_array((int)$matchedCandidate->EmploymentStatusID, [12, 24])) { // Terminated or Active Coverage
                        $matchedCandidate = [];
                    }
                } else {
                    $matchedCandidate = $matchedCandidate->toArray();
                }
            } else {
                $matchedCandidate = $matchedCandidate->toArray();
            }
        }else{
            $matchedCandidate = [];
        }
        return response()->json($matchedCandidate);
    }

    public function getCandidateResume(Request $request) {
        $resumeContent = DB::table("candidates")->select("candidate_applications.resumeContent",DB::raw("CONCAT(candidates.firstName,' ',candidates.lastName) as candidatefullName"))
        ->leftjoin("candidate_applications",function($q){
            $q->on("candidates.id","=","candidate_applications.candidate_id");
        })->where("candidate_id",$request->input('id'))->first();
        $showDownload = 0;
        $rcontent = 'Candidate did not provide a resume';
        if(isset($resumeContent->resumeContent) && !empty($resumeContent->resumeContent)) {
            $rcontent = $resumeContent->resumeContent;
        }

        $candidateName = '';
        if(isset($resumeContent->candidatefullName)) {
            $candidateName = $resumeContent->candidatefullName;
        }
        if($rcontent!='Candidate did not provide a resume'){
            $showDownload =1;
        }
        return view('candidates.resumepopup',['resume'=>$this->replaceBetween($rcontent,"{{DOCUMENT_DATESTAMP}}","{{END_DOCUMENT_DATESTAMP}}",""),'candidateName'=>$candidateName,'id'=>$request->input("id"),'showDownload'=>$showDownload]);
    }

    public function replaceBetween($str, $needle_start, $needle_end, $replacement) {
        $pos = strpos($str, $needle_start);

        if($pos!==false) {
            $start = $pos == false ? 0 : $pos + strlen($needle_start);

            $pos = strpos($str, $needle_end, $start);
            $end = $pos == false ? strlen($str) : $pos;

            return str_replace($needle_start,"",str_replace($needle_end,"",substr_replace($str, $replacement, $start, $end - $start)));
        } else {
            return $str;
        }
    }

    public function pdfView(Request $request) {
        $resumeContent = DB::table("candidates")->select("candidate_applications.resumeContent",DB::raw("CONCAT(candidates.firstName,' ',candidates.lastName) as candidatefullName"))
        ->leftjoin("candidate_applications",function($q){
            $q->on("candidates.id","=","candidate_applications.candidate_id");
        })->where("candidate_id",$request->input('id'))->first();

        $rcontent = 'Candidate did not provide a resume';
        if(isset($resumeContent->resumeContent) && !empty($resumeContent->resumeContent)) {
            $rcontent = $resumeContent->resumeContent;
        }

        $candidateName = '';
        if(isset($resumeContent->candidatefullName)) {
            $candidateName = $resumeContent->candidatefullName;
        }

        $pdfContent = nl2br(ltrim($this->replaceBetween($rcontent,"{{DOCUMENT_DATESTAMP}}","{{END_DOCUMENT_DATESTAMP}}","")));

        if($request->has('download')){
            view()->share('content',$pdfContent);

            $pdf = PDF::loadView('candidates.pdfdownload');
            return $pdf->download($candidateName.'_resume.pdf');
        }

    }

    public function generateTherapyPdf(int $candidateId, string $step, TherapyPdfService $pdfService)
    {
        return $pdfService->generate($candidateId, $step);
    }

    public function updateField(UpdateCandidateRequest $request): JsonResponse
    {
        $value = $request->value;
        $type = $request->type;
        $candidate = Candidates::query()->findOrFail($request->candidate_id);
        $oldValue = $candidate->{$request->field};

        if (is_string($value) && Str::isJson($value)) {
            $value = json_decode($value, true);
        }

        $value = $this->service->normalizeCustomFieldValueByType($value, $type);

        $candidate->{$request->field} = is_array($value)
          ? implode(',',$value)
          : $value;

        $candidate->save();

        [$logOldValue, $logNewValue] = $this->service->formatValuesForLog($oldValue, $value, $type);

        event(new CandidateFieldUpdated(
          candidate: $candidate,
          field: $request->field,
          oldValue: $logOldValue,
          newValue: $logNewValue,
          userId: Auth::id()
        ));

        return response()->json(['success' => true]);
    }
}
