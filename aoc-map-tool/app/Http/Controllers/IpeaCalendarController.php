<?php

namespace App\Http\Controllers;

use App\BranchLocations;
use App\Enums\RecruitingScheduleEnum;
use App\Enums\ScheduleTypes;
use App\Helpers\Calendar;
use App\Helpers\CrossStateHelper;
use App\Helpers\H;
use App\Helpers\LD;
use App\MssqlFacilities;
use App\MssqlPatients;
use App\MssqlProviders;
use App\MssqlRecruitertracker;
use App\Repositories\OfficeNeo\OfficeNeoFAHistoryWriteRepository;
use App\ScheduleType;
use App\Candidates;
use App\FirstAdvantageCheck;
use App\Enums\IpeaCalendar;
use App\FirstAdvantageErrors;
use App\RecruitingSchedule;
use App\Services\FirstAdvantage\FirstAdvantageService;
use App\Services\FirstAdvantage\FirstAdvantageValidation;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Helpers\Notifications;
use Illuminate\Support\Facades\Auth;

class IpeaCalendarController extends Controller
{
    public function __construct(
        private FirstAdvantageCheck $firstAdvantage,
        private FirstAdvantageErrors $firstAdvantageErrors
    ) {
    }



    public function getWeekEvents(Request $request, CrossStateHelper $crossStateHelper)
    {
        $reqData = $request->all();
        $scheduleData = [];
        $branchData = [];
        $branches = H::getHelper('ConstantHelper')->getBranches()->toArray();
        $week = '';
        if (isset($reqData['state']) && $reqData['state'] != '' && isset($reqData['date']) && $reqData['date'] != '') {
            $searchArr = [];
            if (isset($reqData['search']) && $reqData['search'] != '') {
                $searchArr = json_decode($reqData['search'], true);
                $reqData['searchData'] = $reqData['search'];
                $reqData['search'] = $searchArr['label'];
            }
            Session::put(['lastCalendarSearch' => $reqData]);
            $week = Calendar::getWeekStartEndByDates($reqData['date']);
            $branchData = [];
            $states = [];
            $states = BranchLocations::select('id')->where('state', $reqData['state'])->get()->toArray();
            $states = array_column($states, 'id');

            $schedules = DB::table('recruiting_schedule')
                ->leftJoin('mssql_recruitertracker', 'recruiting_schedule.tracker_id', '=', 'mssql_recruitertracker.id')
                ->leftJoin('mssql_patients', 'mssql_recruitertracker.aoc_patient_id', '=', 'mssql_patients.id')
                ->select('recruiting_schedule.*', 'mssql_recruitertracker.Candidate as candidate', 'mssql_patients.PatientName as client', 'mssql_recruitertracker.Recruiter as recruiter')->whereBetween('recruiting_schedule.appointment_date', [$week['start'], $week['end']])->whereIn('recruiting_schedule.type_id', ['1', '999', '997', '998'])->where(function ($query) {
                    $query->where(DB::raw('lower(recruiting_schedule.outcome)'), '<>', 'Cancelled')->orWhereNull('recruiting_schedule.outcome');
                });
            $schedules->addSelect('mssql_recruitertracker.is_rehire as is_rehire');
            $schedules->addSelect('mssql_recruitertracker.intake_in_home as intake_in_home');
            $schedules->orderBy('recruiting_schedule.type_id', 'desc');

            if (count($states) > 0) {
                $schedules->whereIn('recruiting_schedule.ipea_location_id', $states);
            }
            if (isset($reqData['branch']) && $reqData['branch'] != '') {
                $branchData = BranchLocations::find($reqData['branch'])->toArray();
                $schedules->where('recruiting_schedule.ipea_location_id', '=', $reqData['branch']);
            }
            if (isset($searchArr['candidate'])) {
                $schedules->where('mssql_recruitertracker.Candidate', 'like', $searchArr['candidate']);
            }
            if (isset($searchArr['client'])) {
                $schedules->where('mssql_patients.PatientName', 'like', $searchArr['client']);
            }
            $schedules->where('recruiting_schedule.is_deleted', '<>', '1');
            $schedules = $schedules->get();
            foreach ($schedules as $key => $value) {

                // detect cross state for IPEA and client location
                if (LD::checkFeatureFlag('cross-state-dynamic-email')
                ) {
                    $value->cross_state = false;
                    $patientsDetails = MssqlPatients::find($value->aoc_patient_id);
                    $ipeaState = BranchLocations::where('id', $value->ipea_location_id)->value('state') ?? '';
                    $clientState = config('constant.states.' . $patientsDetails['Patient_State'] ?? '', '');

                    if ($crossStateHelper->isCrossState($ipeaState, $clientState)) {
                        $value->cross_state = true;
                    }
                }

                if ($value->appointment_time != '00:00:00') {
                    $t = date('ga', strtotime($value->appointment_time));
                    $day = date('l', strtotime($value->appointment_date));
                    if (date('G', strtotime($value->appointment_time)) <= 8) {
                        $scheduleData['8am'][$day][] = $value;
                    } elseif (date('G', strtotime($value->appointment_time)) >= 17) {
                        $scheduleData['5pm'][$day][] = $value;
                    } else {
                        $scheduleData[$t][$day][] = $value;
                    }
                }
            }
        }

        return view('ipea.weekEvents', ['scheduleData' => $scheduleData, 'branches' => $branches, 'branchData' => $branchData, 'week' => $week, 'state' => $reqData['state']]);
    }

    public function index(Request $request)
    {
        $branches = H::getHelper('ConstantHelper')->getBranches()->toArray();
        $states = H::getHelper('ConstantHelper')->getBranchStates()->toArray();
        $branches = H::arrGroupBy($branches, 'state');

        return view('ipea.calendar', ['branches' => $branches, 'states' => $states]);
    }

    public function saveNurseInfo(Request $request)
    {
        $reqData = $request->all();
        if (isset($reqData['name']) && $reqData['name'] != '' && ((strpos($reqData['name'], 'IPEA_pending') !== false) ? true : (isset($reqData['value']) && $reqData['value'] != '')) && isset($reqData['scheduleId']) && $reqData['scheduleId'] != '') {
            if ($reqData['name'] == 'IPEA_pending[]') {
                $reqData['name'] = 'IPEA_pending';
                if (!isset($reqData['value'])) {
                    $reqData['value'] = [];
                }
                $reqData['value'] = implode(',', $reqData['value']);
            }
            //set ipea_status for OC dashboard
            if ($reqData['name'] == 'IPEA_Confirmed') {
                if ($reqData['value'] == 'Yes') {
                    $ipea_status = 'IPEA Confirmed';
                } else {
                    $ipea_status = 'IPEA Not Confirmed';
                }
            } elseif ($reqData['name'] == 'IPEA_clinician_arrived') {
                if ($reqData['value'] == 'Yes') {
                    $ipea_status = 'Clinician Arrived';
                } else {
                    $ipea_status = 'Clinician ' . $reqData['value'];
                }
            } elseif ($reqData['name'] == 'IPEA_drug_test') {
                $ipea_status = 'Drug Test ' . $reqData['value'];
            } elseif ($reqData['name'] == 'IPEA_background') {
                $ipea_status = 'Background Check ' . $reqData['value'];
            } elseif ($reqData['name'] == 'IPEA_interview') {
                $ipea_status = 'Clinician Interview  ' . $reqData['value'];
            } elseif ($reqData['name'] == 'IPEA_competency_check') {
                $ipea_status = 'Clinical Skills Competency ' . $reqData['value'];
            } elseif ($reqData['name'] == 'IPEA_tpn_class') {
                $ipea_status = 'TPN Class ' . $reqData['value'];
            } elseif ($reqData['name'] == 'IPEA_trachvent_class') {
                $ipea_status = 'Trach / Vent Class ' . $reqData['value'];
            } elseif ($reqData['name'] == 'IPEA_onboarding_status') {
                if ($reqData['value'] == 'Incomplete') {
                    $ipea_status = 'Onboarding Incomplete';
                } else {
                    $ipea_status = 'Clinician ' . $reqData['value'];
                }
            } else {
                $ipea_status = '';
            }

            $updateArray = [$reqData['name'] => $reqData['value'], 'ipea_status' => $ipea_status];
            if ($reqData['name'] == 'IPEA_clinician_arrived' && $reqData['value'] == 'Cancelled') {
                $updateArray = [$reqData['name'] => $reqData['value'], 'outcome' => 'Cancelled', 'ipea_status' => $ipea_status];
            }
            if (RecruitingSchedule::where('id', $reqData['scheduleId'])->update($updateArray)) {

                // Updating live_ipea_status
                $currentData = RecruitingSchedule::find($reqData['scheduleId']);
                $liveIpeaStatus = '';

                switch ($reqData['name']) {
                    case 'IPEA_clinician_arrived':
                        if ($reqData['value'] == 'Yes') {
                            $liveIpeaStatus = 'Arrived';
                        } elseif ($reqData['value'] == 'NC/NS') {
                            $liveIpeaStatus = 'NCNS';
                        } elseif ($reqData['value'] == 'Rescheduled') {
                            $liveIpeaStatus = 'Rescheduled';
                        } elseif ($reqData['value'] == 'Cancelled') {
                            $liveIpeaStatus = 'Cancelled';
                        }
                        break;
                    case 'IPEA_interview':
                        if ($reqData['value'] == 'Good Fit') {
                            $liveIpeaStatus = 'Ready to schedule next steps';
                        } elseif ($reqData['value'] == 'Suggest Other Clients') {
                            $liveIpeaStatus = 'Need to review new clients';
                        } else {
                            $liveIpeaStatus = 'Not Qualified';
                        }
                        Notifications::editableNotification('recruiting_schedule', $reqData['scheduleId'], $reqData['name'], $reqData['value']);
                        break;
                    case 'IPEA_onboarding_status':
                        if ($reqData['value'] == 'Ready To Work') {
                            $liveIpeaStatus = 'Ready to work';
                            H::getHelper('RecruiterTrackerHelper')->
                            sendNotificationForReadyToWork($currentData->tracker_id);

                            if (LD::checkFeatureFlag('prn-process-active-coverage-bucket-fixes')) {
                                $providers = MssqlRecruitertracker::select("mssql_employmentstatus.EmploymentStatus")
                                    ->leftJoin("mssql_providers", function ($query) {
                                        $query->on("mssql_providers.id", '=', 'mssql_recruitertracker.aoc_provider_id');
                                    })
                                    ->leftJoin("mssql_employmentstatus", function ($query) {
                                        $query->on("mssql_employmentstatus.EmploymentStatusID", '=', 'mssql_providers.EmploymentStatusID');
                                    })->where('mssql_recruitertracker.id', $currentData->tracker_id)->first();

                                if (isset($providers->EmploymentStatus) && $providers->EmploymentStatus == 'Active Coverage') {
                                    MssqlRecruitertracker::where('id', $currentData->tracker_id)->update(['Candidate_Current_Status' => 'Ready for Active Coverage']);
                                }

                            }
                        } elseif ($reqData['value'] == 'Ineligible for Hire/Rehire') {
                            $liveIpeaStatus = 'Ineligible for Hire/Rehire';
                        }
                        break;
                    default:
                        // code...
                        break;
                }

                if (($currentData->IPEA_onboarding_status == 'Incomplete' && $reqData['name'] == 'IPEA_pending') || ($currentData->IPEA_onboarding_status == 'Incomplete' && !empty($currentData->IPEA_pending))) {
                    if ($reqData['value'] == 'Incomplete') {
                        $liveIpeaStatus = 'Pending:' . $currentData->IPEA_pending;
                    } else {
                        $liveIpeaStatus = 'Pending:' . $reqData['value'];
                    }
                }
                if ($reqData['value'] == 'None') {
                    $liveIpeaStatus = 'None';
                }
                if ($liveIpeaStatus != '') {
                    H::ExcelLog('tracker: ' . $currentData->tracker_id . ' - live_ipea_status: ' . $liveIpeaStatus, 'updateTracker.log');
                    MssqlRecruitertracker::where('id', $currentData->tracker_id)->update(['live_ipea_status' => $liveIpeaStatus]);
                }
                $optionTitleArray = [
                    'IPEA_Confirmed' => 'IPEA Confirmed by Location Day Before',
                    'IPEA_lunch_preference' => 'Lunch Preference',
                    'IPEA_clinician_arrived' => 'Clinician Arrived',
                    'IPEA_drug_test' => 'Drug Test',
                    'IPEA_background' => 'Background Check',
                    'IPEA_in_home_cargiver-none' => 'In-home Caregiver',
                    'IPEA_interview' => 'Clinician Interview Completed',
                    'IPEA_competency_check' => 'Clinical Skills Competency Check Completed',
                    'IPEA_tpn_class' => 'TPN Class',
                    'IPEA_trachvent_class' => 'Trach / Vent Class',
                    'IPEA_onboarding_status' => 'Onboarding Status',
                    'IPEA_pending' => 'Pending'
                ];

                $note = $optionTitleArray[$reqData['name']] . ' was set to ' . $reqData['value'] . ' by ' . Auth::user()->name;
                $notesData = ['entity' => 'tracker-activity', 'notes' => $note, 'type' => 'ipea', 'entity_id' => $currentData->tracker_id];
                H::getHelper('ActivityNotes')->setActivityNotes($notesData);

                /*
                if($reqData['name']=='IPEA_onboarding_status' && $reqData['value']=='Incomplete'){
                    MssqlRecruitertracker::where('id', $currentData->tracker_id)->update(['Candidate_Current_Status' => 'IPEA - Pending Orientation']);
                }
                */
                return response()->json(['status' => true]);
            } else {
                return response()->json(['status' => false]);
            }
        } else {
            return response()->json(['status' => false]);
        }
    }

    public function officeTherapyEventDetails(Request $request)
    {
        $scheduleId = $request->input('scheduleId');
        if (isset($scheduleId) && $scheduleId != '') {
            $scheduleDetails = RecruitingSchedule::find($scheduleId);
            $descriptionArr = H::isJSON($scheduleDetails->description) ? json_decode($scheduleDetails->description, true) : '';
            if ($descriptionArr != '') {
                if (isset($descriptionArr['First Name']) && isset($descriptionArr['Last Name'])) {
                    $descriptionArr['name'] = $descriptionArr['First Name'] . ' ' . $descriptionArr['Last Name'];
                } elseif (isset($descriptionArr['First Name'])) {
                    $descriptionArr['name'] = $descriptionArr['First Name'];
                } else {
                    $descriptionArr['name'] = $descriptionArr['Last Name'];
                }
            }
            if ($scheduleDetails->type_id == 999) {
                $descriptionArr = [];
                $branches = H::getHelper('ConstantHelper')->getBranches()->toArray();
                foreach ($branches as $key => $branch) {
                    if ($scheduleDetails->ipea_location_id == $branch['id']) {
                        $scheduleDetails->setAttribute('ipea_location_name', $branch['branch']);
                    }
                }
                $descriptionArr['name'] = $scheduleDetails->title;
            }
            return view('ipea.officeTherapyEventDetails', ['data' => $descriptionArr, 'scheduleDetails' => $scheduleDetails]);
        }
    }

    public function neoIpeaTemplateDetails(
        Request $request,
        FirstAdvantageCheck $firstAdvantageCheckModel,
        OfficeNeoFAHistoryWriteRepository $officeNeoFAHistoryWriteRepository
    ) {
        $scheduleId = $request->input('scheduleId');
        $ipeaChecklistIndex = 'credentials / renewables';
        if (isset($scheduleId) && // +1
            $scheduleId != '') { // +1
            $email = null;
            $scheduleDetails = RecruitingSchedule::find($scheduleId);
            $descriptionArr = H::isJSON($scheduleDetails->description) ? json_decode($scheduleDetails->description, true) : '';
            if ($descriptionArr != '') { // +2, nesting = 1
                $email = $descriptionArr['Email'] ?? null;
                if (isset($descriptionArr['First Name']) &&  // +3, nesting = 2
                    isset($descriptionArr['Last Name'])) { // +1
                    $descriptionArr['name'] = $descriptionArr['First Name'] . ' ' . $descriptionArr['Last Name'];
                } elseif (isset($descriptionArr['First Name'])) { // +1
                    $descriptionArr['name'] = $descriptionArr['First Name'];
                } elseif (isset($descriptionArr['Last Name'])) { // +1
                    $descriptionArr['name'] = $descriptionArr['Last Name'];
                }
            }
            $ipeaLocationDetails = [];
            if ($scheduleDetails->ipea_location_id != '') { // +2, nesting = 1
                $ipeaLocationDetails = BranchLocations::find($scheduleDetails->ipea_location_id);
            }
            $branches = H::getHelper('ConstantHelper')->getBranches()->toArray();
            $branches = H::arrGroupBy($branches, 'state');

            //Fatching the current renewables based on the state and the skills of candidate
            $renewables = DB::table('renewables_types')->select('name')
                ->leftJoin('renewable_rules', 'renewables_types.id', '=', 'renewable_rules.renewable_type_id');
            if ($ipeaLocationDetails->state != '') {
                $dashboardState = array_flip(config('constant.dashboard_state'));
                $renewables->whereRaw('FIND_IN_SET(?,renewable_rules.states)',
                    [$dashboardState[$ipeaLocationDetails->state]]);
            }
            if ($scheduleDetails->discipline != '') {
                $renewables->
                WhereRaw('(FIND_IN_SET(?,renewable_rules.discipline) OR renewable_rules.discipline = "ALL STAFF")',
                    [$scheduleDetails->discipline]);
            }
            //Added condition for office NEO
            if ($scheduleDetails->type_id == '997') {
                $renewables->where('isOfficeStaff', 1);
                if ($scheduleDetails->description != '') {
                    $scheduleDescription = json_decode($scheduleDetails->description, true);
                    if (isset($scheduleDescription['Clinical License']) &&
                        $scheduleDescription['Clinical License'] != '') {
                        $renewables->where('isClinician', 1);
                    }
                }
            }
            $renewables = $renewables->get()->toArray();
            if (count($renewables) > 0) { // +2, nesting = 1
                $renewables = array_column(json_decode(json_encode($renewables), true), 'name');
            } else { // +1
                $renewables = [];
            }
            $ipeaChecklist = DB::table('ipeaChecklist')->orderBy('sortOrder', 'asc')->get()->toArray();
            $ipeaChecklist = H::arrGroupBy($ipeaChecklist, 'title', false);

            foreach ($ipeaChecklist[$ipeaChecklistIndex] as $key => $value) { // +2, nesting = 1
                if (!in_array($value->checklist, $renewables)) { // +3, nesting = 2
                    unset($ipeaChecklist[$ipeaChecklistIndex][$key]);
                }
            }

            $currentChecklists = DB::table('ipeaChecklistRecruitingSchedule')->select('ipea_checklist_id')->where('schedule_id', $scheduleId)->get();
            $currentChecklists = H::objToArr($currentChecklists);
            if (count($currentChecklists) > 0) { // +2, nesting = 1
                $currentChecklists = array_column($currentChecklists, 'ipea_checklist_id');
            } else { // +1
                $currentChecklists = [];
            }

            $type = 'neo';
            $firstAdvantage = [
                'applicationStatus' => 'Pending Start', // Where application status comes from
                'orderStatus' => '--',
                'resultStatus' => '--',
                'updated_at' => '',
            ];
            $faCheck = null;
            $hasFACheck = false;
            $lastAvailableError = null;
            $id = 0;

            $backgroundCheckCompleted = isset($descriptionArr['Background Check Completed?']) ?
                $descriptionArr['Background Check Completed?'] : '';


            if ($email && $backgroundCheckCompleted === 'No') {
                $id = $officeNeoFAHistoryWriteRepository->createOrUpdateRecord($email)->id;

                $hasFACheck = $this->firstAdvantage->hasInitiated($id, $type);
                $faCheck = $firstAdvantageCheckModel->findByScheduleIDAndType($id, $type);

                $firstAdvantage = [
                    'applicationStatus' => !empty($faCheck) ? $faCheck['application_status'] : 'Pending Start', // Where application status comes from
                    'orderStatus' => !empty($faCheck) ? $faCheck['status'] : '--',
                    'resultStatus' => !empty($faCheck) ? $faCheck['result_status'] : '--',
                    'updated_at' => !empty($faCheck) ? $faCheck['updated_at'] : '',
                ];

                $lastAvailableError = $this->firstAdvantageErrors->getLatestError($id, $type);
            }

            $shouldShowNewBackground = (
                $backgroundCheckCompleted === 'No' &&
                $scheduleDetails->candidate_type === 'New' &&
                $hasFACheck &&
                LD::checkFeatureFlag("new-background-check") &&
                $email
            );

            $shouldDisplayRetryButton = !is_null($lastAvailableError);

            if (!LD::checkFeatureFlag("new-background-check")) {
                $shouldDisplayRetryButton = false;
                $shouldShowNewBackground = false;
            }

            if($scheduleDetails->type_id == '997'){ // +2, nesting = 1
                return view('ipea.neoIpeaTemplate', [
                    'data' => $descriptionArr,
                    'scheduleDetails' => $scheduleDetails,
                    'ipeaLocationDetails' => $ipeaLocationDetails,
                    'branches' => $branches,
                    'ipeaChecklist' => $ipeaChecklist,
                    'renewables' => $renewables,
                    'currentChecklists' => $currentChecklists,
                    'firstAdvantage' => $firstAdvantage,
                    'shouldShowNewBackground' => $shouldShowNewBackground,
                    'shouldDisplayRetryButton' => $shouldDisplayRetryButton,
                    'lastAvailableError' => $lastAvailableError,
                    'aoc_candidate_id' => $id,
                    'type' => 'neo',
                ]);
            }elseif($scheduleDetails->type_id == '998'){ // +1
                return view('ipea.therapyIpeaTemplate', ['data'=>$descriptionArr,'scheduleDetails' => $scheduleDetails,'ipeaLocationDetails'=>$ipeaLocationDetails,'branches'=>$branches,'ipeaChecklist'=>$ipeaChecklist,'renewables'=>$renewables,'currentChecklists'=>$currentChecklists]);
            }
        }
    }

    public function neoIpeaSaveValues(Request $request)
    {
        $reqData = $request->all();
        if (isset($reqData['name']) && $reqData['name'] == 'IPEA_clinician_arrived' && isset($reqData['scheduleId']) && $reqData['scheduleId'] != '') {
            $notes = '';
            $updateArray = ['IPEA_clinician_arrived' => $reqData['value']];
            if ($reqData['value'] == 'Yes') {
                $notes = 'Candidate was marked as arrived by ' . Auth::user()->name;
            } else if ($reqData['value'] == 'NC/NS') {
                $notes = 'Candidate was marked as NC/NS by ' . Auth::user()->name;
            } else if ($reqData['value'] == 'Cancelled') {
                $notes = 'Candidate was marked as Cancelled by ' . Auth::user()->name;
                $updateArray['outcome'] = 'Cancelled';
            }

            if (RecruitingSchedule::where('id', $reqData['scheduleId'])->update($updateArray)) {
                H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'recruiting-schedule', 'entity_id' => $reqData['scheduleId'], 'notes' => $notes]);
                return response()->json(['status' => true]);
            } else {
                return response()->json(['status' => false]);
            }
        } else {
            return response()->json(['status' => false]);
        }
    }

    /**
     * @param Request $request
     * @param FirstAdvantageCheck $firstAdvantageCheckModel
     * @return Application|Factory|View|void
     */
    public function updateNurseIpeaInfo(Request $request, FirstAdvantageCheck $firstAdvantageCheckModel, CrossStateHelper $crossStateHelper)
    {
        $scheduleId = $request->input('scheduleId');
        $providerDetails = [];
        $patientsDetails = [];
        $branchDetails = [];
        $ipeaLocationDetails = [];

        $nextScheduleDetails = [];
        $recruiterDetails = [];
        $candidateDetails = [];

        $ipeaPendings = config('constant')['ipeaPendings'];
        $ipeaChecklistIndex = 'credentials / renewables';
        $scheduleTypes = H::getHelper('ConstantHelper')->getScheduleTypes();

        if (isset($scheduleId) && $scheduleId != '') {
            $scheduleDetails = RecruitingSchedule::find($scheduleId);
            if ($scheduleDetails->tracker_id != '') {
                $recruiterDetails = MssqlRecruitertracker::find($scheduleDetails->tracker_id);

                abort_if(!$recruiterDetails, Response::HTTP_BAD_REQUEST, 'Recruiter not found');

                if ($recruiterDetails->aoc_provider_id != '') {
                    $providerDetails = MssqlProviders::find($recruiterDetails->aoc_provider_id);
                }
                if ($recruiterDetails->aoc_patient_id != '') {
                    $patientsDetails = MssqlPatients::find($recruiterDetails->aoc_patient_id);
                }
                if ($recruiterDetails->aoc_facility_id != '') {
                    $branchDetails = MssqlFacilities::find($recruiterDetails->aoc_facility_id);
                }
                if ($scheduleDetails->ipea_location_id != '') {
                    $ipeaLocationDetails = BranchLocations::find($scheduleDetails->ipea_location_id);
                }
                if ($scheduleDetails->aoc_candidate_id != '') {
                    $candidateDetails = Candidates::find($scheduleDetails->aoc_candidate_id);
                }

                $nextScheduleDetails = RecruitingSchedule::where('tracker_id', '=', $scheduleDetails->tracker_id)
                    ->where('appointment_date', '>=', $scheduleDetails->appointment_date)
                    ->where('type_id', '<>', '1')
                    ->orderBy('appointment_date', 'asc')
                    ->orderBy('appointment_time', 'desc')
                    ->get();

                if (isset($nextScheduleDetails[0])) {
                    $nextScheduleDetails = $nextScheduleDetails[0];
                }
            }

            $branches = H::getHelper('ConstantHelper')->getBranches()->toArray();
            $branches = H::arrGroupBy($branches, 'state');

            //Fatching the current renewables based on the state and the skills of candidate
            $renewables = DB::table('renewables_types')->select('name')
                ->leftJoin('renewable_rules', 'renewables_types.id', '=', 'renewable_rules.renewable_type_id');

            if ($branchDetails) {
                if ($branchDetails->State != '') {
                    $renewables->whereRaw('FIND_IN_SET(?,renewable_rules.states)', [$branchDetails->State]);
                }
            }

            if ($recruiterDetails->Skillset != '') {
                $renewables->whereRaw(
                    '(FIND_IN_SET(?,renewable_rules.discipline) OR renewable_rules.discipline = "ALL STAFF")',
                    [$recruiterDetails->Skillset]
                );
            }

            $renewables = $renewables->get()->toArray();

            if (count($renewables) > 0) {
                $renewables = array_column(json_decode(json_encode($renewables), true), 'name');
            } else {
                $renewables = [];
            }

            $ipeaChecklist = DB::table('ipeaChecklist')->orderBy('sortOrder', 'asc')->get()->toArray();
            $ipeaChecklist = H::arrGroupBy($ipeaChecklist, 'title', false);

            foreach ($ipeaChecklist[$ipeaChecklistIndex] as $key => $value) {
                if (!in_array($value->checklist, $renewables)) {
                    unset($ipeaChecklist[$ipeaChecklistIndex][$key]);
                }
            }

            $currentChecklists = DB::table('ipeaChecklistRecruitingSchedule')
                ->select('ipea_checklist_id')
                ->where('schedule_id', $scheduleId)
                ->get();

            $currentChecklists = H::objToArr($currentChecklists);

            if (count($currentChecklists) > 0) {
                $currentChecklists = array_column($currentChecklists, 'ipea_checklist_id');
            } else {
                $currentChecklists = [];
            }

                $type = 'provider';
                $i = $scheduleDetails->aoc_provider_id;

                if (!empty($scheduleDetails->aoc_candidate_id)) {
                    $i = $scheduleDetails->aoc_candidate_id;
                    $type = 'candidate';
                }


                if ($type === 'provider') {
                    $p = MssqlProviders::find($i);
                    $searchIds = MssqlProviders::where('AOC_Email', '=', $p->AOC_Email)->pluck('id')->toArray();
                } else {
                    $p = Candidates::find($i);
                    $searchIds = Candidates::where('email', '=', $p->email)->pluck('id')->toArray();
                }

                $faCheck = null;

                foreach ($searchIds as $searchId) {
                    $faCheck = $firstAdvantageCheckModel->findByScheduleIDAndType($searchId, $type);

                    if (is_array($faCheck)) {
                        $i = $faCheck['aoc_candidate_id'];
                        break;
                    }
                }

                $firstAdvantage = [
                    'applicationStatus' => !empty($faCheck) ? $faCheck['application_status'] : 'Pending Start', // Where application status comes from
                    'orderStatus' => !empty($faCheck) ? $faCheck['status'] : '--',
                    'resultStatus' => !empty($faCheck) ? $faCheck['result_status'] : '--',
                    'updated_at' => !empty($faCheck) ? $faCheck['updated_at'] : '',
                ];

                $branch = BranchLocations::where('id', $scheduleDetails->ipea_location_id)->first();

                $isIPEACalendar = ($scheduleDetails->type_id == IpeaCalendar::IPEA_CALENDAR->value);
                $isOfficeCalendar = ($scheduleDetails->type_id == IpeaCalendar::OFFICE_CALENDAR->value);

                $cityOfDallas = $branch->cityOf(BranchLocations::CITY_DALLAS);
                $cityOfMcKinney = $branch->cityOf(BranchLocations::CITY_MC_KINNEY);

                $hasInitiated = $this->firstAdvantage->hasInitiated($i, $type);
                $shouldShowNewBackground = ( $hasInitiated &&
                    (($isIPEACalendar === true && $cityOfDallas === true) ||
                    ($isOfficeCalendar === true && $cityOfMcKinney === true))
                );

                $lastAvailableError = $this->firstAdvantageErrors->getLatestError($i, $type);
                $shouldDisplayRetryButton = !is_null($lastAvailableError);

                if (!LD::checkFeatureFlag("new-background-check")) {
                    $shouldDisplayRetryButton = false;
                    $shouldShowNewBackground = false;
                }

                // add cross state check for IPEA and client locations
                $crossState = false;

                if (LD::checkFeatureFlag('cross-state-dynamic-email')) {
                    $ipeaState = BranchLocations::where('id', $scheduleDetails->ipea_location_id)->value('state') ?? '';
                    $clientState = config('constant.states.' . $patientsDetails['Patient_State'] ?? '', '');

                    if ($crossStateHelper->isCrossState($ipeaState, $clientState)) {
                        $crossState = true;
                    }
                }

                return view('ipea.updateNurseIpeaInfoRedesign', [
                        'shouldShowNewBackground' => $shouldShowNewBackground,
                        'shouldDisplayRetryButton' => $shouldDisplayRetryButton,
                        'lastAvailableError' => $lastAvailableError,
                        'firstAdvantage' => $firstAdvantage,
                        'recruiterDetails' =>$recruiterDetails,
                        'providerDetails' => $providerDetails,
                        'patientsDetails' => $patientsDetails,
                        'scheduleDetails' => $scheduleDetails,
                        'branchDetails' => $branchDetails,
                        'ipeaLocationDetails' => $ipeaLocationDetails,
                        'scheduleTypes' => $scheduleTypes,
                        'nextScheduleDetails' => $nextScheduleDetails,
                        'ipeaPendings' => $ipeaPendings,
                        'candidateDetails' => $candidateDetails,
                        'branches' => $branches,
                        'ipeaChecklist' => $ipeaChecklist,
                        'renewables' => $renewables,
                        'currentChecklists' => $currentChecklists,
                        'recruitingScheduleID' => $scheduleDetails->id,
                        'type' => $type,
                        'crossState' => $crossState,
                    ]
                );
        }
    }

    public function getCalendarSearchString(Request $request)
    {
        $term = $request->input('term');
        $data = [];
        $q = DB::table('recruiting_schedule')
            ->leftJoin('mssql_recruitertracker', 'recruiting_schedule.tracker_id', '=', 'mssql_recruitertracker.id')
            ->leftJoin('mssql_patients', 'recruiting_schedule.aoc_patient_id', '=', 'mssql_patients.id')
            ->leftJoin('branch_locations', 'recruiting_schedule.ipea_location_id', '=', 'branch_locations.id')
            ->select('recruiting_schedule.id as id', 'recruiting_schedule.tracker_id', 'mssql_recruitertracker.Candidate as candidate', 'mssql_patients.PatientName as client', 'appointment_date as date', DB::raw('DATE_FORMAT(appointment_date,"%m/%d/%Y") as appointment_date'), 'branch_locations.branch', 'recruiting_schedule.ipea_location_id as branchId');
        $q->where('recruiting_schedule.is_deleted', '<>', '1');
        $q->where('recruiting_schedule.outcome', '<>', 'Cancelled');

        if (isset($term) && $term != '') {
            $q->where('recruiting_schedule.type_id', '=', '1');
            $data = $q->where(function ($query) use ($term) {
                $query->where('mssql_recruitertracker.Candidate', 'LIKE', "%{$term}%")->orWhere('mssql_patients.Search_Name', 'LIKE', "%{$term}%");
            })->orderBy('client', 'desc')->get();
            $res = [];
            foreach ($data as $key => $value) {
                $label = '';
                $label .= isset($value->candidate) && $value->candidate != '' ? $value->candidate . ',' : '';
                $label .= isset($value->client) && $value->client != '' ? $value->client . ',' : '';
                $label .= isset($value->branch) && $value->branch != '' ? $value->branch . ',' : '';
                $label .= isset($value->appointment_date) && $value->appointment_date != '' ? $value->appointment_date . ',' : '';
                $label = str_replace(',,', ',', $label);
                $label = str_replace(',,', ',', $label);
                $res[] = ['id' => $value->id, 'label' => trim($label, ','), 'date' => $value->date, 'candidate' => $value->candidate, 'client' => $value->client, 'branchId' => $value->branchId, 'month' => date('n', strtotime($value->date))];
            }

            return response()->json($res);
        } else {
            return response()->json($data);
        }
    }

    public function saveNeoReschedule(Request $request)
    {
        try {
            $reqData = $request->all();

            $updateScheduleData = [];
            $userId = Auth::user()->id;
            if (isset($reqData['scheduleId']) && $reqData['scheduleId'] != '') {
                if (isset($reqData['IPEA_Date']) && $reqData['IPEA_Date'] != '' && isset($reqData['IPEA_Time']) && $reqData['IPEA_Time'] != '' && isset($reqData['ipea_location_id']) && $reqData['ipea_location_id'] != '') {

                    $updateScheduleData['appointment_date'] = date('Y-m-d', strtotime($reqData['IPEA_Date']));
                    $updateScheduleData['appointment_time'] = date('H:i:s', strtotime($reqData['IPEA_Time']));

                    $updateScheduleData['updated_at'] = $updateTrackerData['updated_at'] = date('Y-m-d H:i:s');
                    $updateScheduleData['updated_by'] = $updateTrackerData['updated_by'] = $userId;
                    $updateScheduleData['ipea_location_id'] = $reqData['ipea_location_id'];
                    $updateScheduleData['IPEA_clinician_arrived'] = 'NULL';

                    // Updating the schedule
                    RecruitingSchedule::where('id', $reqData['scheduleId'])->update($updateScheduleData);
                    H::ExcelLog('Reschedule Schedule ID:' . $reqData['scheduleId'], 'neoReschedule.log');

                    //Adding notes
                    $schedule = RecruitingSchedule::find($reqData['scheduleId']);
                    $notes = 'IPEA was rescheduled from ' . H::getFormatedDate($schedule->appointment_date) . ' at ' . H::getFormatedTime($schedule->appointment_time) . ' to ' . H::getFormatedDate($reqData['IPEA_Date']) . ' at ' . H::getFormatedTime($updateScheduleData['appointment_time']) . ' by ' . Auth::user()->name;
                    H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'recruiting-schedule', 'entity_id' => $reqData['scheduleId'], 'notes' => $notes]);

                    return response()->json(['status' => true]);
                } else {
                    return response()->json(['status' => false]);
                }
            } else {
                return response()->json(['status' => false]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false]);
        }
    }

    public function saveReschedule(
        Request $request,
        FirstAdvantageService $advantageService,
        FirstAdvantageValidation $fistAdvantageCheckValidation
    ) {
        $reqData = $request->all();
        $updateTrackerData = [];
        $updateScheduleData = [];
        $userId = Auth::user()->id;
        if (isset($reqData['scheduleId']) && $reqData['scheduleId'] != '' && isset($reqData['trackerId']) && $reqData['trackerId'] != '') {
            if (isset($reqData['IPEA_Date']) && $reqData['IPEA_Date'] != '' && isset($reqData['IPEA_Time']) && $reqData['IPEA_Time'] != '' && isset($reqData['ipea_location_id']) && $reqData['ipea_location_id'] != '') {
                if (LD::checkFeatureFlag('new-background-check')) {
                    $schedule = RecruitingSchedule::find($reqData['scheduleId']);
                    $recruiterDetails = MssqlRecruitertracker::find($schedule->tracker_id);
                    $skillSet = $recruiterDetails->Skillset;

                    $isCandidate = false;
                    $currentCandidate = MssqlProviders::find($schedule->aoc_provider_id);


                    if (!empty($schedule->aoc_candidate_id)) {
                        $isCandidate = true;
                        $currentCandidate = Candidates::find($schedule->aoc_candidate_id);
                    }

                    if (!$isCandidate) {
                        $searchIds = MssqlProviders::where('AOC_Email', '=', $currentCandidate->AOC_Email)->pluck('id')->toArray();
                        $currentCandidate->email = $currentCandidate->AOC_Email;
                    } else {
                        $searchIds = Candidates::where('email', '=', $currentCandidate->email)->pluck('id')->toArray();
                    }

                    $branch = BranchLocations::where('id', $request->get('ipea_location_id'))->first();
                    $hasBackgroundCheck = FirstAdvantageCheck::whereIn('aoc_candidate_id', $searchIds)
                        ->where('type', $isCandidate ? 'candidate' : 'provider')
                        ->whereNotIn('application_status', ['DealBroken', 'Cancelled', 'Deleted', 'Expired'])
                        ->exists();


                    /**
                     * @todo
                     *
                     * Refactor this code on ticket https://support.dataart.com/browse/AOC-1868
                     */
                    //TODO this should be changed after mapping task are done, waiting FA to give proper data
                    $packageId = 2377;

                    $relatedIpeaBackgroundStatus = isset($relatedIPEA) ? $relatedIPEA->IPEA_background : null;

                    $inRequiredFaSkills = (
                        $skillSet === RecruitingScheduleEnum::DISCIPLINE_LVN->value ||
                        $skillSet === RecruitingScheduleEnum::DISCIPLINE_LPN->value ||
                        $skillSet === RecruitingScheduleEnum::DISCIPLINE_CNA->value ||
                        $skillSet === RecruitingScheduleEnum::DISCIPLINE_RN->value
                    );

                    if (
                        $schedule->type_id === ScheduleTypes::IPEA->value &&
                        $branch->cityOf(BranchLocations::CITY_DALLAS) &&
                        !$hasBackgroundCheck &&
                        LD::checkFeatureFlag('new-background-check') &&
                        $inRequiredFaSkills &&
                        $relatedIpeaBackgroundStatus === null
                    ) {
                        $log = [];
                        $log['request'] = $request->all();

                        $submittedRequests = FirstAdvantageCheck::whereIn('aoc_candidate_id', $searchIds)
                            ->where('type','=', $isCandidate ? 'candidate' : 'provider')
                            ->whereNotIn('application_status', ['DealBroken', 'Cancelled', 'Deleted', 'Expired'])
                            ->get();

                        if ($submittedRequests->isEmpty()) {
                            $fullName = $currentCandidate->fullName;
                            $fullName = (strpos($fullName, ' ') !== false) ? explode(' ', $fullName) : $fullName;

                            $firstName = $fullName[0] ?? '';
                            $lastName = $fullName[1] ?? '';

                            if (count($fullName) === 3) {
                                $firstName = sprintf('%s %s', $fullName[0], $fullName[1]);
                                $lastName = $fullName[2] ?? '';
                            }

                            $candidateData = [
                                'email' => $currentCandidate->email,
                                'override_candidate_data' => true,
                                'persons' => [
                                    [
                                        'given_name' => $firstName,
                                        'family_name' => $lastName,
                                        'type' => 'subject'
                                    ],
                                ],
                            ];


                            $fistAdvantageCheckValidation->checkForErrors([
                                'firstName' => $firstName,
                                'lastName' => $lastName,
                                'email' => $currentCandidate->email,
                                'city' => $branch->city ?? '',
                                'state' => 'TX',
                                'aoc_candidate_id' => $searchIds[0]
                            ]);

                            $log['candidateData'] = $candidateData;

                            $response = $advantageService->createCandidate($candidateData);

                            $log['createCandidate-response'] = $response;

                            if (!isset($response['error'])) {
                                $invite = [
                                    'candidate_id' => $response['candidate_id'],
                                    'package_id' => $packageId,
                                    'candidate_type' => 'PROFILE',
                                    'quote_backs' => [
                                        [
                                            'name' => 'candidate_id',
                                            'value' => $response['candidate_id']
                                        ],
                                        [
                                            'name' => 'aoc_clinician_id',
                                            'value' => $updateRecruiter['ClinicianID'] ?? ""
                                        ]
                                    ],
                                    'additional_attributes' => [
                                        'order_as' => [
                                            'account_id' => $advantageService->getAccountID("texas"),
                                            'user_id' => "XCHANGE", /*Auth::user()->id*/
                                            'email' => $currentCandidate->email
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
                                $intiveResponse = $advantageService->invite($invite);
                                $log['invite-response'] = $intiveResponse;
                            }

                            $conditionsData = [
                                'aoc_candidate_id' => $searchIds[0],
                                'fa_candidate_id' => $response['candidate_id'] ?? null,
                                'type' => $isCandidate ? 'candidate' : 'provider'
                            ];
                            FirstAdvantageCheck::updateOrCreate($conditionsData, $conditionsData);

                            if (isset($response['error'])) {
                                $response['aoc_candidate_id'] = $searchIds[0];
                                $response['userId'] = auth()->user()->id;
                                $response['type'] = $isCandidate ? 'candidate' : 'provider';

                                FirstAdvantageErrors::create($response);
                            }

                            Log::channel('first_advantage')->error('First Advantage -> saveScheduleNext -> [LOG]', [
                                'dump' => $log
                            ]);
                        }
                    }
                }

                $updateTrackerData['IPEA_Date'] = date('Y-m-d H:i:s', strtotime($reqData['IPEA_Date']));
                $updateScheduleData['appointment_date'] = date('Y-m-d', strtotime($reqData['IPEA_Date']));

                $updateTrackerData['IPEA_Time'] = date('H:i:s', strtotime($reqData['IPEA_Time']));
                $updateScheduleData['appointment_time'] = $updateTrackerData['IPEA_Time'];

                $updateScheduleData['updated_at'] = $updateTrackerData['updated_at'] = date('Y-m-d H:i:s');
                $updateScheduleData['updated_by'] = $updateTrackerData['updated_by'] = $userId;
                $updateScheduleData['ipea_location_id'] = $reqData['ipea_location_id'];
                $schedule = RecruitingSchedule::find($reqData['scheduleId']);
                $recruiterDetails = MssqlRecruitertracker::find($schedule->tracker_id);

                // Updating the tracker and schedule
                MssqlRecruitertracker::where('id', $reqData['trackerId'])->update($updateTrackerData);
                RecruitingSchedule::where('id', $reqData['scheduleId'])->update($updateScheduleData);

                //Resetting fields on rescheduling
                RecruitingSchedule::where('id', $reqData['scheduleId'])->update([
                    'IPEA_clinician_arrived' => 'NULL',
                    'IPEA_lunch_preference' => '',
                    'IPEA_Confirmed' => 'NULL'
                ]);

                H::getHelper("IpeaEmail")->processEmail($request, $reqData['trackerId'], true);
                // Sending Notification to Recruiter and Recruiter Admin
                if (isset($recruiterDetails->user_id) && $recruiterDetails->user_id != '') {
                    $data = [];
                    $data['user_id'] = $recruiterDetails->user_id;
                    $data['title'] = 'The IPEA for ' . $recruiterDetails->Candidate . ' was rescheduled to ' . H::getFormatedDate($updateScheduleData['appointment_date']) . ' ' . H::getFormatedTime($updateScheduleData['appointment_time']) . ' by the branch.';
                    $data['type'] = 'ipea';
                    $data['related_item'] = $reqData['scheduleId'];
                    $data['notes'] = '';
                    Notifications::addNotification($data);
                    H::ExcelLog('recruiter: ' . $recruiterDetails->user_id, 'dbRescheduledIpea.log');
                    $ra = H::getRecruiterAdmin($recruiterDetails->user_id, true);
                    H::ExcelLog('ra: ' . $ra, 'dbRescheduledIpea.log');
                    if ($ra != '') {
                        $data['user_id'] = $ra;
                        Notifications::addNotification($data);
                        H::ExcelLog('ra notification added', 'dbRescheduledIpea.log');
                    }
                }

                //Adding notes
                $notes = 'IPEA was rescheduled from ' . $schedule->appointment_date . ' at ' . $schedule->appointment_time . ' to ' . date('Y-m-d', strtotime($reqData['IPEA_Date'])) . ' at ' . $updateTrackerData['IPEA_Time'] . ' by ' . Auth::user()->name;
                H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'tracker_activity', 'entity_id' => $schedule->tracker_id, 'tracker_id' => $schedule->tracker_id, 'notes' => $notes]);
                return response()->json(['status' => true]);

            }
        }
    }

    public function listing(Request $request)
    {
        $columns = [
            ['name' => 'id', 'label' => 'id', 'visible' => false, 'width' => '0'],
            ['name' => 'Candidate', 'label' => 'Candidate', 'width' => '15%'],
            ['name' => 'branch', 'label' => 'Branch', 'width' => '10%'],
            ['name' => 'appointment_date', 'label' => 'IPEA Date', 'width' => '10%'],
            ['name' => 'OC', 'label' => 'OC', 'width' => '8%'],
            ['name' => 'Skillset', 'label' => 'Discipline', 'width' => '10%'],
            ['name' => 'Client_Name', 'label' => 'Client', 'width' => '10%'],
            ['name' => 'ipea_status', 'label' => 'Last Step Completed ', 'width' => '15%'],
            ['name' => 'POC', 'label' => 'Pending OC Items', 'width' => '12%'],
            ['name' => 'NS', 'label' => 'Next Steps', 'width' => '10%'],
        ];
        if ($request->ajax()) {
            $filters = H::getFilterInputArr($request->all());

            $search = $request->input('search.value');
            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columns[$request->input('order.0.column')]['name'];
            $dir = $request->input('order.0.dir');

            $q = DB::table('recruiting_schedule')
                ->leftJoin('mssql_recruitertracker', 'recruiting_schedule.tracker_id', '=', 'mssql_recruitertracker.id')
                ->leftJoin('branch_locations', 'recruiting_schedule.ipea_location_id', '=', 'branch_locations.id')
                ->select(
                    'recruiting_schedule.*',
                    'mssql_recruitertracker.Candidate as Candidate',
                    'mssql_recruitertracker.Client_Name as Client_Name',
                    'mssql_recruitertracker.Skillset as Skillset',
                    'branch_locations.branch as branch',
                    DB::raw("(SELECT users.name from users left join users_has_roles on users.id = users_has_roles.user_id left join roles on users_has_roles.role_id = roles.id where roles.name='Orientation Coordinator' and users.status='1' and find_in_set(recruiting_schedule.ipea_location_id, users.branchid ) limit 1) as OC"),
                    DB::raw("(SELECT CONCAT(type,': ',DATE_FORMAT(appointment_date, '" . config('constant')['mysqlsearchdateformat'] . "'),' ',TIME_FORMAT(appointment_time, '" . config('constant')['mysqlsearchtimeformat'] . "')) FROM recruiting_schedule as rsinner left join schedule_types on rsinner.type_id = schedule_types.id WHERE rsinner.type_id <> 1 AND rsinner.appointment_date >= CURDATE()  AND rsinner.tracker_id = recruiting_schedule.tracker_id order by appointment_date asc, appointment_time desc limit 1) as NS"),
                    DB::raw("IF(recruiting_schedule.IPEA_onboarding_status = 'Incomplete', recruiting_schedule.IPEA_pending, '') as POC")
                )->where('recruiting_schedule.is_deleted', '0')->where('recruiting_schedule.type_id', '1');

            $totalData = $q->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $q->whereNested(function ($q) use ($search) {
                    $q->where('mssql_recruitertracker.Candidate', 'LIKE', "%{$search}%")
                        ->orWhere('branch_locations.branch', 'LIKE', "%{$search}%")
                        ->orWhere(DB::raw('DATE_FORMAT(recruiting_schedule.appointment_date, "' . config('constant')['mysqlsearchdateformat'] . '")'), 'LIKE', "%{$search}%")
                        ->orWhere('mssql_recruitertracker.Skillset', 'LIKE', "%{$search}%")
                        ->orWhere('mssql_recruitertracker.Client_Name', 'LIKE', "%{$search}%")
                        ->orWhere(DB::raw("IF(recruiting_schedule.IPEA_onboarding_status = 'Incomplete', recruiting_schedule.IPEA_pending, '')"), 'LIKE', "%{$search}%");
                });
                $totalFiltered = $q->count();
            }
            $includeCompleted = 0;
            if (!empty($filters)) {
                if (LD::checkFeatureFlag('ipea-multiselect-filter')) {
                    $filters = [];
                    foreach ($request['filters'] as $fl) {
                        $filters[$fl['name']][] = $fl['value'];
                    }

                    if (isset($filters['State'])) {
                        $fval = $filters['State'];
                        $q->where(function ($query) use ($fval) {
                            foreach ($fval as $val) {
                                $query->orWhere("branch_locations.state", $val);
                            }
                        });
                    }
                    if (isset($filters['Branch'])) {
                        $fval = $filters['Branch'];
                        $q->where(function ($query) use ($fval) {
                            foreach ($fval as $val) {
                                $query->orWhere("branch_locations.id", $val);
                            }
                        });
                    }
                    if (isset($filters['OC'])) {
                        $fval = $filters['OC'];
                        $q->where(function ($query) use ($fval) {
                            foreach ($fval as $val) {
                                $query->orWhereIn("recruiting_schedule.ipea_location_id", explode(",", $val));
                            }
                        });
                    }
                    if (isset($filters['include_completed'])) {
                        $includeCompleted = 1;
                    }
                } else {
                    if (isset($filters['State'])) {
                        $q->where('branch_locations.state', $filters['State']);
                    }
                    if (isset($filters['Branch'])) {
                        $q->where('branch_locations.id', $filters['Branch']);
                    }
                    if (isset($filters['OC'])) {
                        $q->whereIn('recruiting_schedule.ipea_location_id', explode(",", $filters['OC']));
                    }
                    if (isset($filters['include_completed'])) {
                        $includeCompleted = 1;
                    }
                }
                $totalFiltered = $q->count();
            }
            if ($includeCompleted == 0) {
                $q->where('recruiting_schedule.appointment_date', '>=', DB::raw('CURDATE()'));
                $totalFiltered = $q->count();
            }
            $data = $q->offset($start)->limit($limit)->orderBy($order, $dir)->get();

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => intval($totalData),
                'recordsFiltered' => intval($totalFiltered),
                'data' => $this->DataProcess($data),
            ]);
        }
        return view('ipea.grid', ['columns' => $columns]);
    }

    private function DataProcess($data)
    {
        foreach ($data as $key => $value) {
            $value->appointment_date = H::getFormatedDate($value->appointment_date);
        }
        return $data;
    }

    public function ipeaExport(Request $request)
    {
        $dataTableFields = [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'OC_email' => 'Email Address',
            'Candidate' => 'Candidate',
            'branch' => 'Branch',
            'appointment_date' => 'IPEA Date',
            'OC' => 'OC',
            'Skillset' => 'Discipline',
            'Client_Name' => 'Client',
            'ipea_status' => 'Last Step Completed',
            'IPEA_Confirmed' => 'IPEA Confirmed by Location Day Before',
            'IPEA_lunch_preference' => 'Lunch Preference',
            'IPEA_clinician_arrived' => 'Clinician Arrived',
            'IPEA_drug_test' => 'Drug Test',
            'IPEA_background' => 'Background Check',
            'IPEA_interview' => 'Clinician Interview Completed',
            'why_not_qualified' => 'Reason if provided',
            'IPEA_competency_check' => 'Clinical Skills Competency Check Completed',
            'IPEA_tpn_class' => 'TPN Class',
            'IPEA_trachvent_class' => 'Trach / Vent Class',
            'IPEA_onboarding_status' => 'Onboarding Status',
            'IPEA_pending' => 'Pending Items',
            'branch_notes' => 'Branch Notes',
            'clinician_availability' => 'When is clinician available for next steps?',
            'Recruiter' => 'Recruiter',
            'NS' => 'Next Steps',
        ];

        $filters = H::getFilterInputArr($request->all());
        $search = $request->input('search.value');

        $q = DB::table('recruiting_schedule')
            ->leftJoin('mssql_recruitertracker', 'recruiting_schedule.tracker_id', '=', 'mssql_recruitertracker.id')
            ->leftJoin('branch_locations', 'recruiting_schedule.ipea_location_id', '=', 'branch_locations.id')
            ->leftJoin('candidates', 'mssql_recruitertracker.aoc_candidate_id', '=', 'candidates.id')
            ->select(
                'recruiting_schedule.*',
                'mssql_recruitertracker.Candidate as Candidate',
                'mssql_recruitertracker.Client_Name as Client_Name',
                'mssql_recruitertracker.Skillset as Skillset',
                'branch_locations.branch as branch',
                'mssql_recruitertracker.Recruiter as Recruiter',
                'mssql_recruitertracker.clinician_availability as clinician_availability',
                DB::raw("(SELECT name FROM users WHERE find_in_set(recruiting_schedule.ipea_location_id, branchid) limit 1) as OC"),
                DB::raw("candidates.email as OC_email"),
                DB::raw("(SELECT CONCAT(type,': ',DATE_FORMAT(appointment_date, '" . config('constant')['mysqlsearchdateformat'] . "'),' ',TIME_FORMAT(appointment_time, '" . config('constant')['mysqlsearchtimeformat'] . "'))
                      FROM recruiting_schedule as rsinner
                      left join schedule_types on rsinner.type_id = schedule_types.id
                      WHERE rsinner.type_id <> 1
                      AND rsinner.appointment_date >= CURDATE()
                      AND rsinner.tracker_id = recruiting_schedule.tracker_id
                      order by appointment_date asc, appointment_time desc
                      limit 1) as NS"),
                DB::raw("IF(recruiting_schedule.IPEA_onboarding_status = 'Incomplete', recruiting_schedule.IPEA_pending, '') as POC")
            )
            ->where('recruiting_schedule.is_deleted', '0')
            ->where('recruiting_schedule.type_id', '1');

        if (!empty($search)) {
            $q->where(function ($q) use ($search) {
                $q->where('mssql_recruitertracker.Candidate', 'LIKE', "%{$search}%")
                    ->orWhere('branch_locations.branch', 'LIKE', "%{$search}%")
                    ->orWhere(DB::raw('DATE_FORMAT(recruiting_schedule.appointment_date, "' . config('constant')['mysqlsearchdateformat'] . '")'), 'LIKE', "%{$search}%")
                    ->orWhere('mssql_recruitertracker.Skillset', 'LIKE', "%{$search}%")
                    ->orWhere('mssql_recruitertracker.Client_Name', 'LIKE', "%{$search}%")
                    ->orWhere(DB::raw("IF(recruiting_schedule.IPEA_onboarding_status = 'Incomplete', recruiting_schedule.IPEA_pending, '')"), 'LIKE', "%{$search}%");
            });
        }

        $includeCompleted = 0;
        if (!empty($filters)) {
            if (LD::checkFeatureFlag('ipea-multiselect-filter')) {
                $filters = [];
                foreach ($request['filters'] as $fl) {
                    $filters[$fl['name']][] = $fl['value'];
                }

                if (isset($filters['State'])) {
                    $q->where(function ($query) use ($filters) {
                        foreach ($filters['State'] as $val) {
                            $query->orWhere('branch_locations.state', $val);
                        }
                    });
                }
                if (isset($filters['Branch'])) {
                    $q->where(function ($query) use ($filters) {
                        foreach ($filters['Branch'] as $val) {
                            $query->orWhere('branch_locations.id', $val);
                        }
                    });
                }
                if (isset($filters['OC'])) {
                    $q->where(function ($query) use ($filters) {
                        foreach ($filters['OC'] as $val) {
                            $query->orWhereIn('recruiting_schedule.ipea_location_id', explode(",", $val));
                        }
                    });
                }
                if (isset($filters['include_completed'])) {
                    $includeCompleted = 1;
                }
            } else {
                if (isset($filters['State'])) {
                    $q->where('branch_locations.state', $filters['State']);
                }
                if (isset($filters['Branch'])) {
                    $q->where('branch_locations.id', $filters['Branch']);
                }
                if (isset($filters['OC'])) {
                    $q->whereIn('recruiting_schedule.ipea_location_id', explode(",", $filters['OC']));
                }
                if (isset($filters['include_completed'])) {
                    $includeCompleted = 1;
                }
            }
        }

        if ($includeCompleted == 0) {
            $q->where('recruiting_schedule.appointment_date', '>=', DB::raw('CURDATE()'));
        }

        $data = $q->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=ipea.csv",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = [];
        foreach ($dataTableFields as $k => $v) {
            $columns[] = $v;
        }

        $callback = function () use ($data, $columns, $dataTableFields) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($data as $row) {
                $csv = [];
                $fullName = trim($row->Candidate ?? '');
                $nameParts = explode(' ', $fullName, 2);
                $firstName = $nameParts[0] ?? '';
                $lastName = $nameParts[1] ?? '';
                $csv[] = $firstName;
                $csv[] = $lastName;
                $csv[] = $row->OC_email ?? '';
                foreach ($dataTableFields as $key => $label) {
                    if (in_array($key, ['first_name', 'last_name', 'OC_email'])) {
                        continue;
                    }
                    $csv[] = $row->{$key} ?? '';
                }
                fputcsv($file, $csv);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }


    public function getBranchUpcomingEvents(Request $request)
    {
        try {
            $ipeaLocationId = $request->input('ipea_location_id');
            $schedules = [];
            if (isset($ipeaLocationId) && $ipeaLocationId != '') {
                $schedules = DB::table('recruiting_schedule')
                    ->select('recruiting_schedule.*', DB::raw("DATE_FORMAT(CONCAT(appointment_date,' ',appointment_time), '" . config('constant')['mysqlsearchdatetimeformat'] . "') as appointment_date"))->whereDate('recruiting_schedule.appointment_date', '>', date('Y-m-d'))->whereIn('recruiting_schedule.type_id', ['999'])->where('recruiting_schedule.ipea_location_id', $ipeaLocationId)->where(function ($query) {
                        $query->where(DB::raw('lower(recruiting_schedule.outcome)'), '<>', 'Cancelled')->orWhereNull('recruiting_schedule.outcome');
                    })->get();
            }
            return response()->json(['status' => true, 'events' => $schedules]);
        } catch (\Exception $e) {
            H::ExcelLog('getBranchUpcomingEvents:' . $e->getMessage(), 'ipeaCalendar.log');
            return response()->json(['status' => false, 'msg' => $e->getMessage()]);
        }
    }

    public function saveChecklist(Request $request)
    {
        try {
            $reqData = $request->all();
            $loggedInUser = H::getLoggedInUser();
            $needsToInsertIds = [];

            if (isset($reqData['scheduleId']) && $reqData['scheduleId'] != '') {
                $schedule = RecruitingSchedule::find($reqData['scheduleId']);
                if ($reqData['checklist'] != '') {
                    $needsToInsertIds = explode(',', $reqData['checklist']);
                }
                $currentChecklists = DB::table('ipeaChecklistRecruitingSchedule')
                    ->select('ipea_checklist_id')
                    ->where('schedule_id', $reqData['scheduleId'])
                    ->get();

                $currentChecklists = H::objToArr($currentChecklists);
                $currentChecklistIds = array_column($currentChecklists, 'ipea_checklist_id');

                // Identify new checklists to insert
                $newChecklists = array_diff($needsToInsertIds, $currentChecklistIds);

                // Identify existing checklists to delete
                $checklistsToDelete = array_diff($currentChecklistIds, $needsToInsertIds);
                // Insert new checklists
                if (!empty($newChecklists)) {
                    $insertArr = [];
                    foreach ($newChecklists as $value) {
                        $insertArr[] = [
                            'schedule_id' => $reqData['scheduleId'],
                            'ipea_checklist_id' => $value,
                            'created_by' => $loggedInUser['id']
                        ];
                    }
                    DB::table('ipeaChecklistRecruitingSchedule')->insert($insertArr);
                    $ipeaChecklistName = DB::table('ipeaChecklist')->select(DB::raw('GROUP_CONCAT(checklist) as checklists'))->whereIn('id', $newChecklists)->first();
                    if ($schedule->type_id == 1) {
                        $note = 'IPEA Checklist: ' . $ipeaChecklistName->checklists . '  was marked Complete by ' . $loggedInUser['name'];
                        $notesData = ['entity' => 'tracker-activity', 'notes' => $note, 'type' => 'ipea', 'entity_id' => $reqData['trackerId']];
                        H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                    } else if ($schedule->type_id == 998) {
                        $notes = 'IPEA Therapy Checklist: ' . $ipeaChecklistName->checklists . '  was marked Complete by ' . $loggedInUser['name'];
                        H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'recruiting-schedule', 'entity_id' => $reqData['scheduleId'], 'notes' => $notes]);
                    } else if ($schedule->type_id == 997) {
                        $notes = 'IPEA NEO Checklist: ' . $ipeaChecklistName->checklists . '  was marked Complete by ' . $loggedInUser['name'];
                        H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'recruiting-schedule', 'entity_id' => $reqData['scheduleId'], 'notes' => $notes]);
                    }
                }

                // Delete existing checklists
                if (!empty($checklistsToDelete)) {
                    DB::table('ipeaChecklistRecruitingSchedule')
                        ->where('schedule_id', $reqData['scheduleId'])
                        ->whereIn('ipea_checklist_id', $checklistsToDelete)
                        ->delete();

                    $ipeaChecklistName = DB::table('ipeaChecklist')->select(DB::raw('GROUP_CONCAT(checklist) as checklists'))->whereIn('id', $checklistsToDelete)->first();
                    if ($schedule->type_id == 1) {
                        $note = 'IPEA Checklist: ' . $ipeaChecklistName->checklists . '  was unmarked ' . $loggedInUser['name'];
                        $notesData = ['entity' => 'tracker-activity', 'notes' => $note, 'type' => 'ipea', 'entity_id' => $reqData['trackerId']];
                        H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                    } else if ($schedule->type_id == 998) {
                        $notes = 'IPEA Therapy Checklist: ' . $ipeaChecklistName->checklists . '  was unmarked ' . $loggedInUser['name'];
                        H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'recruiting-schedule', 'entity_id' => $reqData['scheduleId'], 'notes' => $notes]);
                    } else if ($schedule->type_id == 997) {
                        $notes = 'IPEA NEO Checklist: ' . $ipeaChecklistName->checklists . '  was unmarked ' . $loggedInUser['name'];
                        H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'recruiting-schedule', 'entity_id' => $reqData['scheduleId'], 'notes' => $notes]);
                    }
                }
            }
            return response()->json(['status' => true]);
        } catch (\Exception $e) {
            H::ExcelLog('saveChecklist:' . $e->getMessage(), 'ipeaCalendar.log');
            return response()->json(['status' => false, 'msg' => $e->getMessage()]);
        }
    }

    public function saveNextStep(Request $request)
    {
        try {
            $reqData = $request->all();
            $value = $reqData['value'] ?? null;
            $field = $reqData['field'];
            $notes = '';
            if ($field == 'shift_type') {
                $value = implode(",", $value ?? []);
            }
            if ($field == 'days_available') {
                if (!empty($value)) {
                    $tempValue = $value;
                    $value = implode(",", array_map(function ($value) {
                        return date('Y-m-d', strtotime($value));
                    }, explode(",", $value ?? '')));


                    $notes = 'Next steps days available ' . $tempValue . ' selected  by ' . Auth::user()->name;
                }
            } else {
                $notes = 'Next steps preferred shift type was changed to ' . $value . ' by ' . Auth::user()->name;
            }
            MssqlRecruitertracker::where('id', $reqData['id'])->update([$field => $value]);
            $notesData = [
                'entity' => 'tracker-activity',
                'notes' => $notes,
                'type' => 'ipea',
                'entity_id' => $reqData['id']
            ];
            H::getHelper('ActivityNotes')->setActivityNotes($notesData);
            return response()->json(['status' => true]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'msg' => $e->getMessage()]);
        }
    }
}
