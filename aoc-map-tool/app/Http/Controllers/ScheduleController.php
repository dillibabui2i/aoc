<?php

namespace App\Http\Controllers;

use App\FirstAdvantageCheck;
use App\FirstAdvantageErrors;
use App\Helpers\H;
use App\Helpers\LD;
use App\RecruitingSchedule;
use App\Repositories\OfficeNeo\OfficeNeoFAHistoryWriteRepository;
use App\ScheduleType;
use App\Services\FirstAdvantage\FirstAdvantageService;
use App\BranchLocations;
use App\Enums\RecruitingScheduleEnum;
use App\Services\FirstAdvantage\FirstAdvantageValidation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class ScheduleController extends Controller
{
    public function __construct(private FirstAdvantageService $firstAdvantageService, private FirstAdvantageValidation $fistAdvantageCheckValidation)
    {}

    public function getSchedules(Request $request)
    {
        $columns = [
                1 =>'candidate',
                2 =>'client',
                3=> 'appointment_date',
                4=> 'appointment_time',
                5=> 'type',
                6=> 'recruiter',
                7=> 'branch',
                8=> 'created_at',
            ];
        if ($request->ajax()) {
            $filters = H::getFilterInputArr($request->all());

            $search = $request->input('search.value');
            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            $q = DB::table('recruiting_schedule')
                ->leftJoin('mssql_recruitertracker', 'recruiting_schedule.tracker_id', '=', 'mssql_recruitertracker.id')
                ->leftJoin('mssql_patients', 'recruiting_schedule.aoc_patient_id', '=', 'mssql_patients.id')
                ->leftJoin('schedule_types', 'recruiting_schedule.type_id', '=', 'schedule_types.id')
                ->leftJoin('branch_locations', 'recruiting_schedule.ipea_location_id', '=', 'branch_locations.id')
                ->select(
                    'recruiting_schedule.id as id',
                    'recruiting_schedule.tracker_id',
                    DB::raw('CASE WHEN (recruiting_schedule.type_id = 997 OR recruiting_schedule.type_id = 998) THEN CONCAT(JSON_UNQUOTE(json_extract(description, \'$.First Name\'))," ",JSON_UNQUOTE(json_extract(description, \'$.Last Name\'))) WHEN (recruiting_schedule.type_id = 999) THEN recruiting_schedule.title ELSE mssql_recruitertracker.Candidate END as candidate'),
                    DB::raw('CASE WHEN (recruiting_schedule.type_id = 999) THEN recruiting_schedule.description ELSE mssql_patients.PatientName END as client'),
                    DB::raw('DATE_FORMAT(appointment_date,"%m/%d/%Y") as appointment_date'), 'appointment_time', DB::raw('CASE WHEN recruiting_schedule.type_id = 997 THEN "Office NEO" WHEN  recruiting_schedule.type_id = 998 THEN "Therapy NEO" ELSE COALESCE(schedule_types.type,"Event") END as type'),
                    'recruiting_schedule.type_id',
                    'mssql_recruitertracker.Recruiter as recruiter',
                    DB::raw('CASE
                    WHEN COALESCE(schedule_types.type, "Event") = "Event" THEN branch_locations.branch
                    ELSE mssql_recruitertracker.Branch_Location
                    END as branch'),
                    DB::raw('DATE_FORMAT(recruiting_schedule.created_at,"%m/%d/%y %h:%i %p") as created_at')
                )
                ->where('recruiting_schedule.is_deleted', '0');

            $totalData = $q->count();

            if (!empty($search)) {
                $q->where(function ($query) use ($search){
                    $query->where(DB::raw("CASE WHEN (recruiting_schedule.type_id = 997 OR recruiting_schedule.type_id = 998) THEN CONCAT(JSON_UNQUOTE(json_extract(description, '\$.First Name')),' ',JSON_UNQUOTE(json_extract(description, '\$.Last Name'))) WHEN (recruiting_schedule.type_id = 999) THEN recruiting_schedule.title ELSE mssql_recruitertracker.Candidate END COLLATE utf8_unicode_ci"), 'LIKE', "%{$search}%")
                          ->orWhere(DB::raw("CASE WHEN (recruiting_schedule.type_id = 999) THEN recruiting_schedule.description ELSE mssql_patients.PatientName END COLLATE utf8_unicode_ci"), 'LIKE', "%{$search}%")
                          ->orWhere('mssql_recruitertracker.Recruiter', 'LIKE', "%{$search}%");
                });
            }
            if (!empty($filters)) {
                if(LD::checkFeatureFlag('change-branch-filter-as-per-event-filter-in-schedule-page')){
                    if(isset($filters['schedule_type']) && $filters['schedule_type'] == 999){
                        unset($filters['branch']);
                    }else{
                        unset($filters['ipea_location']);
                    }
                }
                if (isset($filters['appointment_date_from']) && isset($filters['appointment_date_to'])) {
                    $q->whereBetween('recruiting_schedule.appointment_date', [$filters['appointment_date_from'], $filters['appointment_date_to']]);
                }
                if (isset($filters['schedule_type'])) {
                    if (strstr($filters['schedule_type'], ",") !== false) {
                        $q->whereIn('recruiting_schedule.type_id', explode(",", $filters['schedule_type']));
                    } else {
                        $q->where('recruiting_schedule.type_id', '=', $filters['schedule_type']);
                    }
                }
                if (isset($filters['branch'])) {
                    $q->where('recruiting_schedule.aoc_facility_id', '=', $filters['branch']);
                }
                if (!isset($filters['show_all'])) {
                    $q->whereDate('recruiting_schedule.appointment_date', '>=', Carbon::now());
                }
                if(isset($filters['ipea_location'])){
                    $q->where('recruiting_schedule.ipea_location_id', '=', $filters['ipea_location']);
                }
            } else {
                if (!isset($filters['show_all'])) {
                    $q->whereDate('recruiting_schedule.appointment_date', '>=', Carbon::now());
                }
            }
            $totalFiltered = $q->count();
            $data = $q->offset($start)->limit($limit)->orderBy($order, $dir)->get();

            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => intval($totalData),
                'recordsFiltered' => intval($totalFiltered),
                'data'            => $data,
                ]);
        }
        $scheduleType = ScheduleType::all()->toArray();
        usort($scheduleType, fn ($a, $b) => $a['sort_order'] - $b['sort_order']);
        $scheduleTypeData = [];
        foreach ($scheduleType as $key => $value) {
            $scheduleTypeData[] = ['value'=>$value['id'], 'text'=>$value['type']];
        }
        $scheduleTypeData[] = ['value'=>999, 'text'=>'Event'];
        $scheduleTypeData[] = ['value'=>'997', 'text'=>'Office NEO'];
        $scheduleTypeData[] = ['value'=>'998', 'text'=>'Therapy NEO'];
        $branchesData = [];
        $branches = H::getHelper('ConstantHelper')->getFacilities()->toArray();
        foreach ($branches as $key => $value) {
            $branchesData[] = ['value'=>$value['id'], 'text'=>$value['FacilityName']];
        }
        if(LD::checkFeatureFlag('change-branch-filter-as-per-event-filter-in-schedule-page')){
            $ipeaLocationData = [];
            $iplocation = H::getHelper('ConstantHelper')->getBranches()->toArray();
            foreach ($iplocation as $key => $value) {
                $ipeaLocationData[] = ['value'=>$value['id'], 'text'=>$value['branch']];
            }
            return view('schedule.grid', ['scheduleType'=>$scheduleTypeData, 'branches'=>$branchesData, 'ipeaLocation'=>$ipeaLocationData]);
        }else{
            return view('schedule.grid', ['scheduleType'=>$scheduleTypeData, 'branches'=>$branchesData]);
        }
    }

    public function addEvents(Request $request)
    {
        $mode = 'add';
        $data = [];
        $params = $request->all();
        $branches = H::getHelper('ConstantHelper')->getBranches()->toArray();
        $branches = H::arrGroupBy($branches, 'state');

        if ($params['id'] != -1) {
            $data = RecruitingSchedule::find($params['id']);
            $mode = 'edit';
            if(isset($params['mode']) && $params['mode'] != '') {
                $mode = $params['mode'];
            }
        }
        return view('schedule.addEvents', ['branches'=>$branches, 'data' => $data, 'mode' => $mode]);
    }

    public function saveEvents(Request $request)
    {
        try {
            $data = $request->all();
            unset($data['_token']);

            $data['appointment_time'] = date("H:i:s", strtotime($data['appointment_time']));
            $data['appointment_end_time'] = date("H:i:s", strtotime($data['appointment_end_time']));
            $branchIds = $data['ipea_location_id'];
            unset($data['ipea_location_id']);

            if (isset($data['id'])) {
                // Only one branch can be updated at a time in edit mode
                $data['ipea_location_id'] = $branchIds[0];
                $data['updated_at'] = now();
                $data['updated_by'] = Auth::id();

                RecruitingSchedule::where('id', $data['id'])->update($data);
            } else {
                foreach ($branchIds as $branchId) {
                    $insertData = $data;
                    $insertData['ipea_location_id'] = $branchId;
                    $insertData['created_at'] = now();
                    $insertData['updated_at'] = now();
                    $insertData['created_by'] = Auth::id();
                    $insertData['updated_by'] = Auth::id();

                    RecruitingSchedule::insert($insertData);
                }
            }

            return response()->json(['success' => 1, 'message' => 'Event saved successfully!']);
        } catch (\Exception $ex) {
            return response()->json(['success' => 0, 'message' => 'Something went wrong, please try again.']);
        }
    }


    public function removeOfficeEvents(Request $request)
    {
        try{
            if($request->input('id') != ''){
                $scheduleDetails = RecruitingSchedule::find($request->input('id'));
                $type = ScheduleType::find($scheduleDetails->type_id);
                $user = H::getLoggedInUser();
                RecruitingSchedule::where('id',$request->input('id'))->update(['is_deleted'=>'1']);
                $typeName = isset($type->type) ? $type->type : "Event";
                // Adding the notes
                $notes = $typeName.' on '.H::getFormatedDate($scheduleDetails->appointment_date).' at '.H::getFormatedTime($scheduleDetails->appointment_date.' '.$scheduleDetails->appointment_time).' was deleted by '.$user['name'];
                $notesData = ['entity' => 'tracker', 'entity_id' => $scheduleDetails->tracker_id,'provider_id'=>$scheduleDetails->aoc_provider_id,'candidate_id'=>$scheduleDetails->aoc_candidate_id,'patient_id'=>$scheduleDetails->aoc_patient_id,'tracker_id'=>$scheduleDetails->tracker_id,'created_by'=>$user['id'], 'notes' => $notes, 'completed' => 1];
                H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                return response()->json(['status'=>true]);
            } else {
                return response()->json(['status'=>false]);
            }
        }catch(\Exception $e){
            return response()->json(['status'=>false]);
        }
    }

    public function removeSchedule(Request $request)
    {
        try{
            if($request->input('id') != ''){
                $scheduleDetails = RecruitingSchedule::find($request->input('id'));
                $type = ScheduleType::find($scheduleDetails->type_id);
                $user = H::getLoggedInUser();
                RecruitingSchedule::where('id',$request->input('id'))->update(['is_deleted'=>'1']);
                $typeName = isset($type->type) ? $type->type : "Event";
                // Adding the notes
                $notes = $typeName.' on '.H::getFormatedDate($scheduleDetails->appointment_date).' at '.H::getFormatedTime($scheduleDetails->appointment_date.' '.$scheduleDetails->appointment_time).' was deleted by '.$user['name'];
                $notesData = ['entity' => 'tracker', 'entity_id' => $scheduleDetails->tracker_id,'provider_id'=>$scheduleDetails->aoc_provider_id,'candidate_id'=>$scheduleDetails->aoc_candidate_id,'patient_id'=>$scheduleDetails->aoc_patient_id,'tracker_id'=>$scheduleDetails->tracker_id,'created_by'=>$user['id'], 'notes' => $notes, 'completed' => 1];
                H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                return response()->json(['status'=>true]);
            } else {
                return response()->json(['status'=>false]);
            }
        }catch(\Exception $e){
            return response()->json(['status'=>false]);
        }
    }

    public function removeNeoSchedule(Request $request)
    {
        try{
            if($request->input('id') != ''){
                $scheduleDetails = RecruitingSchedule::find($request->input('id'));
                $description = $scheduleDetails['description'];
                if($description){
                    foreach(json_decode($description) as $key => $data){
                        if($key == 'Attachment'){
                            $directory = 'neo_attachments';
                            $fileName = $data;
                            $filePath = storage_path('' . $directory . '/' . $fileName);
                            // Delete the file
                            if (File::exists($filePath)) {
                                File::delete($filePath);
                            }
                        }
                    }
                }
                $type = ScheduleType::find($scheduleDetails->type_id);
                $user = H::getLoggedInUser();
                RecruitingSchedule::where('id',$request->input('id'))->update(['is_deleted'=>'1']);
                $typeName = isset($type->type) ? $type->type : "Event";
                // Adding the notes
                $notes = $typeName.' on '.H::getFormatedDate($scheduleDetails->appointment_date).' at '.H::getFormatedTime($scheduleDetails->appointment_date.' '.$scheduleDetails->appointment_time).' was deleted by '.$user['name'];
                $notesData = ['entity' => 'tracker', 'entity_id' => $scheduleDetails->tracker_id,'provider_id'=>$scheduleDetails->aoc_provider_id,'candidate_id'=>$scheduleDetails->aoc_candidate_id,'patient_id'=>$scheduleDetails->aoc_patient_id,'tracker_id'=>$scheduleDetails->tracker_id,'created_by'=>$user['id'], 'notes' => $notes, 'completed' => 1];
                H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                H::ExcelLog($notes.'for Recruiting Schedule id='.$request->input('id'), 'deletedNEO.log');
                return response()->json(['status'=>true]);
            } else {
                return response()->json(['status'=>false]);
            }
        }catch(\Exception $e){
            return response()->json(['status'=>false]);
        }
    }

    public function addOfficeIpeaEvent(Request $request)
    {
        $mode = 'add';
        $data = [];
        $params = $request->all();
        $branches = H::getHelper('ConstantHelper')->getBranches()->toArray();
        $branches = H::arrGroupBy($branches, 'state');

        if ($params['id'] != -1) {
            $descriptionKeyArr = ['First Name' => 'first_name', 'Last Name' => 'last_name', 'Phone' => 'phone', 'Email' => 'email', 'FCRA Form completed?' => 'fcra_form_completed', 'Background Check Completed?' => 'background_check_completed', 'Onboarding Packet' => 'onboarding_packet', 'Position' => 'position', 'Work Location' => 'work_location', 'Supervisor' => 'supervisor', 'Full Time/Part Time' => 'full_part_time', 'Benefits Class' => 'benefits_class', 'Clinical License' => 'clinical_license', 'Attachment' => 'attachment'];
            $data = RecruitingSchedule::find($params['id']);
            foreach(json_decode($data->description) as $key => $value) {
                if (isset($descriptionKeyArr[$key])) {
                    $data->setAttribute($descriptionKeyArr[$key],$value);
                }
            }
            $mode = 'edit';
            if(isset($params['mode']) && $params['mode'] != '') {
                $mode = $params['mode'];
            }
        }

        return view('schedule.addOfficeEvents', ['branches' => $branches, 'data' => $data, 'mode' => $mode]);
    }

    public function saveOfficeIpeaEvent(Request $request, OfficeNeoFAHistoryWriteRepository $officeNeoFAHistoryWriteRepository)
    {
        $data = $insertData = $descriptionData=[];
        $descriptionJsonStr = '';
        try {
            $validator = Validator::make($request->all(), [
                'attachment' => 'mimes:jpeg,jpg,png,pdf'
            ]);

            if ($validator->fails()) {
                $message = $validator->errors()->get('attachment');
                return response()->json(['success' => 0, 'message' => $message[0]]);
            }
            $file = $request->file('attachment');
            $timestamp = time();
            $image = $request->attachment_hidden;
            if (!empty($file)) {
                $destinationPath = storage_path() . '/neo_attachments';
                $fileName = $timestamp . '_' . $file->getClientOriginalName();
                $file->move($destinationPath, $fileName);
                $image = $fileName;
            }
            if (!empty($request->clinical_license)) {
                $request->request->add(['clinical_license' => 'Yes']);
            }
            $descriptionKeyArr = ['first_name' => 'First Name', 'last_name' => 'Last Name', 'phone' => 'Phone', 'email' => 'Email', 'fcra_form_completed' => 'FCRA Form completed?', 'background_check_completed' => 'Background Check Completed?', 'onboarding_packet' => 'Onboarding Packet', 'position' => 'Position', 'work_location' => 'Work Location', 'supervisor' => 'Supervisor', 'full_part_time' => 'Full Time/Part Time', 'benefits_class' => 'Benefits Class', 'clinical_license' => 'Clinical License', 'attachment' => 'Attachment', 'attachment_hidden' => 'attachment_hidden'];
            $data = $request->all();
            foreach ($data  as $key => $value) {
                if (!empty($descriptionKeyArr[$key]) && $data[$key] != '') {
                    if($key == 'attachment'){
                        $descriptionData[$descriptionKeyArr[$key]] = isset($image) ? $image : '';
                    }elseif($key == 'attachment_hidden'){
                        $descriptionData[$descriptionKeyArr['attachment']] = isset($image) ? $image : '';
                    } else{
                        $descriptionData[$descriptionKeyArr[$key]] = $data[$key];
                    }
                }
            }
            $descriptionJsonStr = json_encode($descriptionData);
            $insertData['type_id']          = $data['type_id'];
            $insertData['ipea_location_id'] = $data['ipea_location_id'];
            $insertData['appointment_date'] = $data['appointment_date'];
            $insertData['appointment_time'] = date("H:i:s",strtotime($data['appointment_time']));
            $insertData['discipline'] = $data['discipline'];
            $insertData['description'] = $descriptionJsonStr;

            $type = 'neo';

            if (!$request->has('candidate_type')) {
                $insertData['candidate_type'] = 'New';
            }

            $branch = BranchLocations::find($request->input('ipea_location_id'));

            # Retrieves first advantage package for MC KINNEY
            $package = $this->firstAdvantageService->packages("mckinney");

            $isElibigleForBackgrounCheck = (
                $request->input('background_check_completed') === 'No' &&
                $request->input('clinical_license') === 'Yes' &&
                (
                    $request->input('discipline') === RecruitingScheduleEnum::DISCIPLINE_LVN_LPN->value ||
                    $request->input('discipline') === RecruitingScheduleEnum::DISCIPLINE_RN->value
                ) &&
                $branch->cityOf(BranchLocations::CITY_MC_KINNEY) &&
                LD::checkFeatureFlag("new-background-check")
            );

            if (isset($data['id'])) {
                $existingRecruitingSchedule = RecruitingSchedule::find($data['id']);
                $insertData['updated_at'] = date('Y-m-d H:i:s');
                $insertData['updated_by'] = Auth::user()->id;

                RecruitingSchedule::where('id', $data['id'])->update($insertData);

                /**
                 * @todo
                 *
                 * Refactor this code on ticket https://support.dataart.com/browse/AOC-1868
                 */
                if ($isElibigleForBackgrounCheck) {
                    $neoFARecord = $officeNeoFAHistoryWriteRepository->createOrUpdateRecord($request->get('email'));

                    $submittedRequests = FirstAdvantageCheck::where('aoc_candidate_id', $neoFARecord->id)
                        ->where('type','=', $type)
                        ->get();

                    $dealBrokenStatuses = collect(['DealBroken', 'Cancelled', 'Deleted', 'Expired'])->sort()->toArray();
                    $applicationStatuses = $submittedRequests->map(fn ($item) => $item->getRawOriginal('application_status'))->unique()->sort()->toArray();
                    $isSubmittedRequestsBroken = array_values($applicationStatuses) === array_values($dealBrokenStatuses);

                    if ($isSubmittedRequestsBroken || $submittedRequests->isEmpty()) {
                        $log = [];
                        $log['id'] = $data['id'];
                        $log['candidate_id'] = $neoFARecord->id;
                        $candidateData = [
                            'email' => $request->input('email'),
                            'override_candidate_data' => true,
                            'persons' => [
                                [
                                    'given_name' => $request->input('first_name'),
                                    'family_name' => $request->input('last_name'),
                                    'type' => 'subject'
                                ]
                            ],
                            'employers' => [
                                [
                                    'employer_name' => 'Angels of Care'
                                ]
                            ],
                            'education' => [
                                [
                                    'school_name' => 'Other',
                                    'contact_info' => [
                                        'contact_method' => [
                                            'internet_email_address' => $request->input('email')
                                        ]
                                    ],
                                    'person_name' => [
                                        'given_name' => $request->input('first_name'),
                                        'family_name' => $request->input('last_name'),
                                        'type' => 'subject'
                                    ]
                                ]
                            ],
                        ];

                        $log['candidateData'] = $candidateData;

                        # Creates a candidate in first advantage
                        $response = $this->firstAdvantageService->createCandidate($candidateData);
                        $log['createCandidate-response'] = $response;
                        if (!isset($response['error'])) {
                            $invite = [
                                'candidate_id' => $response['candidate_id'],
                                'package_id' => $package['package_id'],
                                'candidate_type' => 'PROFILE',
                                'quote_backs' => [
                                    [
                                        'name' => 'candidate_id',
                                        'value' => $response['candidate_id']
                                    ],
                                    [
                                        'name' => 'aoc_ipea_id',
                                        'value' => $neoFARecord->id,
                                    ],
                                    [
                                        'name' => 'aoc_clinician_id',
                                        'value' => $existingRecruitingSchedule->ClinicianID
                                    ]
                                ],
                                'additional_attributes' => [
                                    'order_as' => [
                                        'account_id' => $this->firstAdvantageService->getAccountID("mckinney"),
                                        'user_id' => "XCHANGE", /*Auth::user()->id,*/
                                        'email' => $request->input('email')
                                    ]
                                ],
                                'position_locations' => [
                                    [
                                        'country' => 'US',
                                        'city' => $branch->city ?? '',
                                        'state' => $branch->state ?? '',
                                    ]
                                ],
                                'locale' => [
                                    'language_code' => 'en',
                                    'country_code' => 'us'
                                ],
                            ];
                            $log['invite'] = $response;
                            $inviteResponse = $this->firstAdvantageService->invite($invite);
                            $log['invite-response'] = $inviteResponse;

                            FirstAdvantageCheck::create([
                                'aoc_candidate_id' => $neoFARecord->id,
                                'fa_candidate_id' => $response['candidate_id'],
                                'type' => 'candidate'
                            ]);

                            $this->fistAdvantageCheckValidation->checkForErrors([
                                'firstName' => $request->input('first_name'),
                                'lastName' => $request->input('last_name'),
                                'email' => $request->input('email'),
                                'city' => $branch->city ?? '',
                                'state' => $branch->state ?? '',
                                'aoc_candidate_id' => $neoFARecord->id,
                                'type' => $type,
                            ]);
                        }

                        if (isset($response['error'])) {
                            $response['aoc_candidate_id'] = $neoFARecord->id;
                            $response['type'] = $type;
                            $response['userId'] = auth()->user()->id;

                            FirstAdvantageErrors::create($response);
                        }

                        Log::channel('first_advantage')->error('First Advantage -> saveOfficeIpeaEvent[ID] -> [LOG]', [
                            'dump' => $log
                        ]);
                    }
                }


            } else {

                $insertData['created_at'] = date('Y-m-d H:i:s');
                $insertData['updated_at'] = date('Y-m-d H:i:s');
                $insertData['created_by'] = Auth::user()->id;
                $insertData['updated_by'] = Auth::user()->id;
                $existingRecruitingSchedule = RecruitingSchedule::create($insertData);

                $isElibigleForBackgrounCheck = (
                $request->input('background_check_completed') === 'No' &&
                $request->input('clinical_license') === 'Yes' &&
                (
                    $request->input('discipline') === RecruitingScheduleEnum::DISCIPLINE_LVN_LPN->value ||
                    $request->input('discipline') === RecruitingScheduleEnum::DISCIPLINE_RN->value
                ) &&
                $branch->cityOf(BranchLocations::CITY_MC_KINNEY) &&
                LD::checkFeatureFlag("new-background-check")
            );
                /**
                 * @todo
                 *
                 * Refactor this code on ticket https://support.dataart.com/browse/AOC-1868
                 */
                if ($isElibigleForBackgrounCheck) {
                    $neoFARecord = $officeNeoFAHistoryWriteRepository->createOrUpdateRecord($request->get('email'));
                    $submittedRequests = FirstAdvantageCheck::where('aoc_candidate_id', $neoFARecord->id)
                        ->where('type','=', $type)
                        ->get();

                    $dealBrokenStatuses = collect(['DealBroken', 'Cancelled', 'Deleted', 'Expired'])->sort()->toArray();
                    $applicationStatuses = $submittedRequests->map(fn ($item) => $item->getRawOriginal('application_status'))->unique()->sort()->toArray();
                    $isSubmittedRequestsBroken = array_values($applicationStatuses) === array_values($dealBrokenStatuses);

                    if ($isSubmittedRequestsBroken || $submittedRequests->isEmpty()) {
                        $log = [];

                        $candidateData = [
                            'email' => $request->input('email'),
                            'override_candidate_data' => true,
                            'persons' => [
                                [
                                    'given_name' => $request->input('first_name'),
                                    'family_name' => $request->input('last_name'),
                                    'type' => 'subject'
                                ]
                            ],
                            'phones' => [
                                [
                                    'number' => $request->input('phone')
                                ]
                            ],
                            'employers' => [
                                [
                                    'employer_name' => 'Angels of Care'
                                ]
                            ],
                            'education' => [
                                [
                                    'school_name' => 'Other',
                                    'contact_info' => [
                                        'contact_method' => [
                                            'internet_email_address' => $request->input('email')
                                        ]
                                    ],
                                    'person_name' => [
                                        'given_name' => $request->input('first_name'),
                                        'family_name' => $request->input('last_name'),
                                        'type' => 'subject'
                                    ]
                                ]
                            ],
                        ];

                        $log['candidateData'] = $candidateData;
                        # Creates a candidate in first advantage
                        $response = $this->firstAdvantageService->createCandidate($candidateData);
                        $log['candidateData-response'] = $response;

                        if (!isset($response['error'])) {
                            $invite = [
                                'candidate_id' => $response['candidate_id'],
                                'package_id' => $package['package_id'],
                                'candidate_type' => 'PROFILE',
                                'quote_backs' => [
                                    [
                                        'name' => 'candidate_id',
                                        'value' => $response['candidate_id']
                                    ],
                                    [
                                        'name' => 'aoc_clinician_id',
                                        'value' => $existingRecruitingSchedule->ClinicianID
                                    ]
                                ],
                                'additional_attributes' => [
                                    'order_as' => [
                                        'account_id' => $this->firstAdvantageService->getAccountID("mckinney"),
                                        'user_id' => "XCHANGE", /*Auth::user()->id,*/
                                        'email' => $request->input('email')
                                    ]
                                ],
                                'position_locations' => [
                                    [
                                        'country' => 'US',
                                        'city' => $branch->city ?? '',
                                        'state' => 'TX',
                                    ]
                                ],
                                'locale' => [
                                    'language_code' => 'en',
                                    'country_code' => 'us'
                                ],
                            ];
                            $log['invite'] = $invite;
                            $inviteResponse = $this->firstAdvantageService->invite($invite);
                            $log['invite-response'] = $inviteResponse;

                            FirstAdvantageCheck::create([
                                'aoc_candidate_id' => $neoFARecord->id,
                                'fa_candidate_id' => $response['candidate_id'],
                                'type' => $type
                            ]);

                            $this->fistAdvantageCheckValidation->checkForErrors([
                                'firstName' => $request->input('first_name'),
                                'lastName' => $request->input('last_name'),
                                'email' => $request->input('email'),
                                'city' => $branch->city ?? '',
                                'state' => 'TX',
                                'aoc_candidate_id' => $neoFARecord->id,
                                'type' => $type,
                            ]);
                        }

                        if (isset($response['error'])) {
                            $response['aoc_candidate_id'] = $neoFARecord->id;
                            $response['type'] = $type;
                            $response['userId'] = auth()->user()->id;

                            FirstAdvantageErrors::create($response);
                        }

                        Log::channel('first_advantage')->error('First Advantage -> saveOfficeIpeaEvent -> [LOG]', [
                            'dump' => $log
                        ]);
                    }
                }
            }
            return response()->json(['success' => 1, 'message' => 'Office NEO has been submitted successfully.']);
        } catch (\Exception $ex) {
            Log::debug($ex);
            return response()->json(['success' => 0, 'message' => 'Something went wrong, Please try again.']);
        }
    }
    public function addTherapyIpeaEvent(Request $request)
    {
        $mode = 'add';
        $data = [];
        $params = $request->all();
        $branches = H::getHelper('ConstantHelper')->getBranches()->toArray();
        $branches = H::arrGroupBy($branches, 'state');
        if ($params['id'] != -1) {
            if(LD::checkFeatureFlag('add-new-form-filed-in-therapy-neo-form')){
                $descriptionKeyArr = ['First Name' => 'first_name', 'Last Name' => 'last_name', 'Discipline' => 'discipline', 'Phone' => 'phone', 'Email' => 'email', 'Zipcode' => 'zipcode', 'Recruiter' => 'recruiter', 'notes' => 'notes', 'Bilingual' => 'Bilingual', 'Attachment' => 'attachment'];
            }else{
                $descriptionKeyArr = ['First Name' => 'first_name', 'Last Name' => 'last_name', 'Discipline' => 'discipline', 'Phone' => 'phone', 'Email' => 'email', 'Zipcode' => 'zipcode', 'Recruiter' => 'recruiter', 'Hours/Days' => 'hours_days', 'Attachment' => 'attachment'];
            }
            $data = RecruitingSchedule::find($params['id']);
            foreach(json_decode($data->description) as $key => $value) {
                if (isset($descriptionKeyArr[$key])) {
                    $data->setAttribute($descriptionKeyArr[$key],$value);
                }
            }
            $mode = 'edit';
            if(isset($params['mode']) && $params['mode'] != '') {
                $mode = $params['mode'];
            }
        }

        return view('schedule.addTherapyEvents', ['branches' => $branches, 'data' => $data, 'mode' => $mode]);
    }

    public function saveTherapyIpeaEvent(Request $request)
    {
        $data = $insertData = $descriptionData=[];
        $descriptionJsonStr = '';
        try {
            $validator = Validator::make($request->all(), [
                'attachment' => 'mimes:jpeg,jpg,png,pdf'
            ]);

            if ($validator->fails()) {
                $message = $validator->errors()->get('attachment');
                return response()->json(['success' => 0, 'message' => $message[0]]);
            }
            $file = $request->file('attachment');
            $timestamp = time();
            $image = $request->attachment_hidden;
            if (!empty($file)) {
                $destinationPath = storage_path() . '/neo_attachments';
                $fileName = $timestamp . '_' . $file->getClientOriginalName();
                $file->move($destinationPath, $fileName);
                $image = $fileName;
            }
            if(LD::checkFeatureFlag('add-new-form-filed-in-therapy-neo-form')){
                $descriptionKeyArr = ['first_name' => 'First Name', 'last_name' => 'Last Name', 'discipline' => 'Discipline', 'phone' => 'Phone', 'email' => 'Email', 'zipcode' => 'Zipcode', 'recruiter' => 'Recruiter', 'notes' => 'notes', 'bilingual' => 'Bilingual', 'attachment' => 'Attachment', 'attachment_hidden' => 'attachment_hidden'];
            }else{
                $descriptionKeyArr = ['first_name' => 'First Name', 'last_name' => 'Last Name', 'discipline' => 'Discipline', 'phone' => 'Phone', 'email' => 'Email', 'zipcode' => 'Zipcode', 'recruiter' => 'Recruiter', 'hours_days' => 'Hours/Days', 'attachment' => 'Attachment', 'attachment_hidden' => 'attachment_hidden'];
            }
            $data = $request->all();
            foreach ($data  as $key => $value) {
                if (!empty($descriptionKeyArr[$key]) && $data[$key] != '') {
                    if($key == 'attachment'){
                        $descriptionData[$descriptionKeyArr[$key]] = isset($image) ? $image : '';
                    }elseif($key == 'attachment_hidden'){
                        $descriptionData[$descriptionKeyArr['attachment']] = isset($image) ? $image : '';
                    }else{
                        $descriptionData[$descriptionKeyArr[$key]] = $data[$key];
                    }
                }
            }
            $descriptionJsonStr = json_encode($descriptionData);
            $insertData['type_id']          = $data['type_id'];
            $insertData['ipea_location_id'] = $data['ipea_location_id'];
            $insertData['appointment_date'] = $data['appointment_date'];
            $insertData['appointment_time'] = date("H:i:s",strtotime($data['appointment_time']));
            $insertData['description'] = $descriptionJsonStr;
            if (isset($data['id'])) {
                $insertData['updated_at'] = date('Y-m-d H:i:s');
                $insertData['updated_by'] = Auth::user()->id;
                RecruitingSchedule::where('id', $data['id'])->update($insertData);
            } else {
                $insertData['created_at'] = date('Y-m-d H:i:s');
                $insertData['updated_at'] = date('Y-m-d H:i:s');
                $insertData['created_by'] = Auth::user()->id;
                $insertData['updated_by'] = Auth::user()->id;
                RecruitingSchedule::insert($insertData);
            }
            return response()->json(['success' => 1, 'message' => 'Therapy NEO has been submitted successfully.']);
        } catch (\Exception $ex) {
            return response()->json(['success' => 0, 'message' => 'Something went wrong, Please try again.']);
        }
    }

    public function privewAttachment($id){
        if($id != NULL){
            $scheduleDetails = RecruitingSchedule::find($id);
            $descriptionArr = H::isJSON($scheduleDetails->description) ? json_decode($scheduleDetails->description,true) : '';
            if(isset($descriptionArr['Attachment']) && !empty($descriptionArr['Attachment'])){
                $filename = $descriptionArr['Attachment'];
                // Get the full path of the file
                $path = storage_path('neo_attachments/' . $filename);

                // Check if the file exists
                if (!file_exists($path)) {
                    abort(404);
                }

                // Define the content type based on the file extension
                $contentType = mime_content_type($path);

                // Load the file contents
                $fileContents = file_get_contents($path);

                // Create a response with the file contents and content type
                return Response::make($fileContents, 200, [
                    'Content-Type' => $contentType,
                    'Content-Disposition' => 'inline; filename="'.$filename.'"',
                ]);
            }
        }
    }
}
