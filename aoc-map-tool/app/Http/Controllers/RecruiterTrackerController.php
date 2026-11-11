<?php

namespace App\Http\Controllers;

use App\MssqlFacilities;
use App\MssqlPatients;
use App\MssqlProviders;
use App\MssqlRecruitertracker;
use App\RecruitingSchedule;
use App\Schedule;
use App\Candidates;
use App\ScheduleType;
use App\Helpers\H;
use App\Helpers\LD;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use DB;
use DataTables;
use App\NeedToHireAccounts;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class RecruiterTrackerController extends Controller
{
    public function reloadClientMatch(Request $request) {
        return json_encode(["counter"=>H::getHelper('ClientMatch')->getClientMatchData($request->input("type"),$request,1),"type"=>$request->input("type")]);
    }
    public function getClientMatch(Request $request) {
        $data = H::getHelper("ClientMatch")->getClientMatchData($request->input("type"),$request);
        return Datatables::of($data)->editColumn('Candidate_Start_Date', function ($row) {
                $date = new \DateTime($row->Candidate_Start_Date);
                $formattedDate = $date->format('m/d/Y');
                return $formattedDate;

        })->addColumn('select', function ($row){
            $checkbox = '';
            $checkbox = '<input type="checkbox" name="checkbox-ccc_client_match[]" class="form-input td select-all-ccc_client_match" value="' . $row->id . '" />';
            return $checkbox;
        })->editColumn('Clinician', function ($row) {
            if(empty($row->Clinician)){
                return $row->Clinician_Candidate;
            } else {
                return $row->Clinician;
            }

    })->rawColumns(['select'])->make(true);
    }

    public function saveClientMatchCompleteAction(Request $request) {

        if(!empty($request->ids)){
            $ids = explode(",",$request->ids);
            $recruiterTracker['checked_off_at']=date("Y-m-d H:i:s");
            $recruiterTracker['checked_off_by']=Auth::user()->id;
            MssqlRecruitertracker::whereIn('id', $ids)->update($recruiterTracker);
            return response()->json(['status' => true]);
        } else {
            return response()->json(['status' => false]);
        }

    }
    public function getClientMatchCompleteTemplate(Request $request) {
        $requestData = $request->all();
        return view('ccc.clientMatch.clientMatchCompleteTemplate',['ids'=>$requestData['ids']]);
    }

    public function getClientMatchTemplate(Request $request) {
        $recruiterDetails = [];
        $patientsDetails = [];
        $needToHireAccounts = [];
        $providerDetails = [];
        $candidateDetails = [];

        if ($request->input('id') != '') {
            $recruiterDetails = MssqlRecruitertracker::find($request->input('id'));

            if ($recruiterDetails->aoc_patient_id != '') {
                $clientId = $recruiterDetails->aoc_patient_id;
                $patientsDetails = MssqlPatients::select("mssql_patients.*","mssql_facilities.FacilityName",DB::raw("CONCAT(FLOOR((TIMESTAMPDIFF(MONTH, mssql_patients.Patient_DOB, CURDATE()) / 12)), ' Years ') AS Patient_age"))
                ->leftJoin('mssql_facilities', 'mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id')
                ->where("mssql_patients.id",$clientId)->first();

                if($patientsDetails->PatientName == ''){
                    $patientsDetails->PatientName = $patientsDetails->Patient_FirstName.' '.$patientsDetails->Patient_LastName;
                }
                //Check if Work force exist
                $is_workForce = 0;
                if (substr_count(strtolower($patientsDetails->CF_WorkForceDevProj), 'yes')) {
                    $is_workForce = 1;
                }
                $skillArray = $skillAddedArray = [];
                $skillDisplayValue = '<div class="tags-cnt">';
                if ($patientsDetails->CF_Skills != '') {
                    $skillAddedArray = explode(",", $patientsDetails->CF_Skills);
                    foreach ($skillAddedArray  as $skillValue) {
                        if (strtolower($skillValue) == 'trach' || strtolower($skillValue) == 'vent') {
                            $skillArray[] = $skillValue;
                        }
                    }
                    if (!empty($skillArray)) {
                        foreach ($skillArray as $skillData) {
                            $skillDisplayValue .= '<span class="tag tag-grey">' . $skillData . '</span>';
                        }
                    }
                }
                $skillDisplayValue .= '</div>';
                $patientsDetails->setAttribute('skillDisplayValue', $skillDisplayValue);
                //Client Preference - START
                $clientPreference = '<div class="tags-cnt">';
                if ($patientsDetails->CF_MeetingNurses != '' && substr($patientsDetails->CF_MeetingNurses . '   ',0,2)=='No') {
                    $clientPreference .='<span class="tag tag-red">Not Meeting Nurses</span>';
                }else {
                    $clientPreference .='<span class="tag">Meeting Nurses</span>';
                }
                $petsinHomeArray = [];
                if ($patientsDetails->PetsinHome != '') {
                    $petsinHomeArray = explode(",", $patientsDetails->PetsinHome);
                    foreach ($petsinHomeArray  as $petsinHomeValue) {
                        if($petsinHomeValue !='') {
                            $clientPreference .= '<span class="tag">' . $petsinHomeValue . '</span>';
                        }
                    }
                }
                $clientLangArray = [];
                if ($patientsDetails->Languages != '') {
                    $clientLangArray = explode(",", $patientsDetails->Languages);
                    foreach ($clientLangArray  as $clientLangValue) {
                        if($clientLangValue !='') {
                            $clientPreference .= '<span class="tag">' . $clientLangValue . '</span>';
                        }
                    }
                }
                if ($patientsDetails->PreferenceList != '' && strpos(strtolower($patientsDetails->PreferenceList),"nurse attending child") !== false) {
                    $clientPreference .='<span class="tag">Nurse attending child\'s school</span>';
                }
                if ($patientsDetails->PreferenceList != '' && strpos(strtolower($patientsDetails->PreferenceList),"client prefers non-smoking") !== false) {
                    $clientPreference .='<span class="tag">Non-smoking Nurses</span>';
                }
                if ($patientsDetails->PreferenceList != '' && strpos(strtolower($patientsDetails->PreferenceList),"media consent form on file") !== false) {
                    $clientPreference .='<span class="tag">Media consent on file</span>';
                }
                if ($patientsDetails->PreferenceList != '' && strpos(strtolower($patientsDetails->PreferenceList),"accepts coverage : yes") !== false) {
                    $clientPreference .='<span class="tag">Accepts Coverage</span>';
                }
                $clientPreference .= '</div>';
                $patientsDetails->setAttribute('clientPreference', $clientPreference);
                //Client Preference - END
                //Clinician Preference - START
                $clinicianPreference = '<div class="tags-cnt">';
                $clinicianDisciplineArray = [];
                if ($patientsDetails->CF_ClinicianDiscipline != '') {
                    $clinicianDisciplineArray = explode(",", $patientsDetails->CF_ClinicianDiscipline);
                    foreach ($clinicianDisciplineArray  as $clinicianDisciplineValue) {
                        if($clinicianDisciplineValue !='') {
                            $clinicianPreference .= '<span class="tag">' . $clinicianDisciplineValue . '</span>';
                        }
                    }
                }
                $petsNurseCannotOwnArray = [];
                if ($patientsDetails->PetsNurseCannotOwn != '') {
                    $petsNurseCannotOwnArray = explode(",", $patientsDetails->PetsNurseCannotOwn);
                    foreach ($petsNurseCannotOwnArray  as $petsNurseCannotOwnValue) {
                        if($petsNurseCannotOwnValue !='') {
                            $clinicianPreference .= '<span class="tag">No ' . $petsNurseCannotOwnValue . '</span>';
                        }
                    }
                }
                $clinicianLangArray = [];
                if ($patientsDetails->CF_NurseLanguage != '') {
                    $clinicianLangArray = explode(",", $patientsDetails->CF_NurseLanguage);
                    foreach ($clinicianLangArray  as $clinicianLangValue) {
                        if($clinicianLangValue !='') {
                            $clinicianPreference .= '<span class="tag">' . $clinicianLangValue . '</span>';
                        }
                    }
                }
                if ($patientsDetails->CF_LiftingRequired != '' && substr_count(strtolower($patientsDetails->CF_LiftingRequired), 'yes')) {
                    $clinicianPreference .='<span class="tag">Lifting Required</span>';
                }else {
                    $clinicianPreference .='<span class="tag">No Lifting Required</span>';
                }
                $genderPref = 'Either Gender';
                if(strpos(strtolower($patientsDetails->PreferenceList),'does not accept female nurses') !== false){
                    $genderPref = 'Male Only';
                }elseif (strpos(strtolower($patientsDetails->PreferenceList),'male nurse accepted') === false){
                    $genderPref = 'Female Only';
                }
                $clinicianPreference .= '<span class="tag">' . $genderPref . '</span>';

                if ($patientsDetails->PreferenceList != '' && strpos(strtolower($patientsDetails->PreferenceList),"smokers live in home") !== false) {
                    $clinicianPreference .='<span class="tag">Smokers live in home</span>';
                }
                if ($patientsDetails->PreferenceList != '' && strpos(strtolower($patientsDetails->PreferenceList),"nurses must be covid-19 vaccin") !== false) {
                    $clinicianPreference .='<span class="tag">COVID-19 Vaccinated</span>';
                }
                $clinicianPreference .= '</div>';
                $patientsDetails->setAttribute('clinicianPreference', $clinicianPreference);
                //Clinician Preference - END

                $patientsDetails->setAttribute('is_workForce', $is_workForce);


                $needToHireAccounts = NeedToHireAccounts::select("NeedToHire_Checklist.*",'NeedToHire_Accounts.*', DB::raw("DATEDIFF(now(),NeedToHire_Accounts.created_at) as daysInNTH"),DB::raw("NeedToHire_Accounts.id as needToHireId"),DB::raw("NeedToHire_Checklist.id as needToHireChecklistId"))
                        ->leftJoin('NeedToHire_Checklist', 'NeedToHire_Checklist.aoc_nthaccounts_id', '=', 'NeedToHire_Accounts.id')
                        ->where("NeedToHire_Accounts.aoc_patient_id",$clientId)->orderBy('NeedToHire_Accounts.id','desc')->first();


                if($needToHireAccounts) {
                    $needToHireAccounts->setAttribute('isOnNTH', "Yes");
                }

            }


            if ($recruiterDetails->aoc_provider_id != '') {
                $providerDetails = MssqlProviders::find($recruiterDetails->aoc_provider_id);
            }

            if($recruiterDetails->aoc_candidate_id != '') {
                $candidateDetails = Candidates::find($recruiterDetails->aoc_candidate_id);
            }
            $outcome = false;
            if(LD::checkFeatureFlag('add-outcomes-to-track-client-match')){
                $outcome = $request->input('outcome');
            }
        }

        return view('ccc.clientMatch.popuptemplate',["recruiterDetails"=>$recruiterDetails,'candidateDetails'=>$candidateDetails, 'patientsDetails'=>$patientsDetails,'providerDetails'=>$providerDetails,'needToHireAccounts'=>$needToHireAccounts, 'outcome' => $outcome]);

    }

    public function index(Request $request)
    {
        if ($request->input('id') != '') {
            $recruiterDetails = MssqlRecruitertracker::find($request->input('id'));
            $providerDetails = [];
            if ($recruiterDetails->aoc_provider_id != '') {
                $providerDetails = MssqlProviders::find($recruiterDetails->aoc_provider_id);
            }
            $patientsDetails = [];
            if ($recruiterDetails->aoc_patient_id != '') {
                $patientsDetails = MssqlPatients::find($recruiterDetails->aoc_patient_id);
                if(isset($patientsDetails)){
                    if(LD::checkFeatureFlag('client-preferences-tags-on-tracker-detail')) {
                        $skillDisplayValue = '<div class="tags-cnt">';
                        if ($patientsDetails->CF_Skills != '') {
                            $skillAddedArray = explode(",", $patientsDetails->CF_Skills);
                            foreach ($skillAddedArray as $skillData) {
                                $skillDisplayValue .= '<span class="tag">' . $skillData . '</span>';
                            }
                        }
                        $skillDisplayValue .= '</div>';
                        //Client Preference - START
                        $clientPreference = '<div class="tags-cnt">';
                        if ($patientsDetails->CF_MeetingNurses != '' && substr($patientsDetails->CF_MeetingNurses . '   ',0,2)=='No') {
                            $clientPreference .='<span class="tag tag-red">Not Meeting Nurses</span>';
                        }else {
                            $clientPreference .='<span class="tag">Meeting Nurses</span>';
                        }
                        $petsinHomeArray = [];
                        if ($patientsDetails->PetsinHome != '') {
                            $petsinHomeArray = explode(",", $patientsDetails->PetsinHome);
                            foreach ($petsinHomeArray  as $petsinHomeValue) {
                                if($petsinHomeValue !='') {
                                    $clientPreference .= '<span class="tag">' . $petsinHomeValue . '</span>';
                                }
                            }
                        }
                        $clientLangArray = [];
                        if ($patientsDetails->Languages != '') {
                            $clientLangArray = explode(",", $patientsDetails->Languages);
                            foreach ($clientLangArray  as $clientLangValue) {
                                if($clientLangValue !='') {
                                    $clientPreference .= '<span class="tag">' . $clientLangValue . '</span>';
                                }
                            }
                        }
                        if ($patientsDetails->PreferenceList != '' && strpos(strtolower($patientsDetails->PreferenceList),"nurse attending child") !== false) {
                            $clientPreference .='<span class="tag">Nurse attending child\'s school</span>';
                        }
                        if ($patientsDetails->PreferenceList != '' && strpos(strtolower($patientsDetails->PreferenceList),"client prefers non-smoking") !== false) {
                            $clientPreference .='<span class="tag">Non-smoking Nurses</span>';
                        }
                        if ($patientsDetails->PreferenceList != '' && strpos(strtolower($patientsDetails->PreferenceList),"media consent form on file") !== false) {
                            $clientPreference .='<span class="tag">Media consent on file</span>';
                        }
                        if ($patientsDetails->PreferenceList != '' && strpos(strtolower($patientsDetails->PreferenceList),"accepts coverage : yes") !== false) {
                            $clientPreference .='<span class="tag">Accepts Coverage</span>';
                        }
                        $clientPreference .= '</div>';
                        $patientsDetails->setAttribute('clientPreference', $clientPreference);
                    } else {
                        $skillDisplayValue = '<div class="tags-cnt">';
                        if ($patientsDetails->CF_Skills != '') {
                            $skillAddedArray = explode(",", $patientsDetails->CF_Skills);
                            foreach ($skillAddedArray as $skillData) {
                                $skillDisplayValue .= '<span class="tag">' . $skillData . '</span>';
                            }
                        }
                        if ($patientsDetails->PreferenceList != '' && strpos(strtolower($patientsDetails->PreferenceList),"nurse attending child") !== false) {
                            $skillDisplayValue .='<span class="tag">Nurse attending child\'s school</span>';
                        }
                        if ($patientsDetails->PreferenceList != '' && strpos(strtolower($patientsDetails->PreferenceList),"client prefers non-smoking") !== false) {
                            $skillDisplayValue .='<span class="tag">Non-smoking Nurses</span>';
                        }
                        if ($patientsDetails->PreferenceList != '' && strpos(strtolower($patientsDetails->PreferenceList),"media consent form on file") !== false) {
                            $skillDisplayValue .='<span class="tag">Media consent on file</span>';
                        }
                        if ($patientsDetails->PreferenceList != '' && strpos(strtolower($patientsDetails->PreferenceList),"accepts coverage : yes") !== false) {
                            $skillDisplayValue .='<span class="tag">Accepts Coverage</span>';
                        }
                        $skillDisplayValue .= '</div>';
                    }
                    $patientsDetails->setAttribute('skillDisplayValue', $skillDisplayValue);
                }
            }
            $branchDetails = [];
            if ($recruiterDetails->aoc_facility_id != '') {
                $branchDetails = MssqlFacilities::find($recruiterDetails->aoc_facility_id);
            }
            $candidateDetails = [];
            if ($recruiterDetails->aoc_candidate_id != '') {
                $candidateDetails = Candidates::find($recruiterDetails->aoc_candidate_id);
            }
            $schedules = [];
            if ($recruiterDetails->id != '') {
                $patientId = $recruiterDetails->aoc_patient_id;
                $schedules = Schedule::where('tracker_id', '=', $recruiterDetails->id)->where('aoc_patient_id','=', $patientId)->orderBy('created_at', 'desc')->get();
                $latest = [];
                $schedulesNew = [];
                foreach ($schedules as $key => $value) {
                    if (!in_array($value->type_id, $latest)) {
                        $schedulesNew[] = $value;
                        $latest[] = $value->type_id;
                    }
                }
                $schedules = $schedulesNew;
            }

            $scheduleType = ScheduleType::all()->toArray();
            usort($scheduleType, fn ($a, $b) => $a['sort_order'] - $b['sort_order']);
            $clientEmergencyContact = [];
            if(LD::checkFeatureFlag('show-client-emergency-contacts-on-tracker-detail-and-sns-form')) {
                if ($recruiterDetails->aoc_patient_id != '') {
                    $clientEmergencyContactList = H::getHelper('ConstantHelper')
                    ->getClientEmergencyContactDetails($recruiterDetails->aoc_patient_id);
                    if(!empty($clientEmergencyContactList)) {
                        foreach($clientEmergencyContactList as $clientEmergencyContactData) {
                            $clientEmergencyContact[$clientEmergencyContactData->relation] =
                            $clientEmergencyContactData->telephone;
                        }
                    }
                }
            }
            if(LD::checkFeatureFlag('add-schedule-matching-the-nth-schedules')) {
                $nthSchedules = H::getHelper('RecruiterTrackerHelper')->getnthschedulebyclientid($recruiterDetails->aoc_patient_id);
                if (LD::checkFeatureFlag('add-schedule-validation')) {
                    if (!empty($recruiterDetails->aoc_candidate_id)) {
                        $recruiter_nth_schedule_ids = DB::select(
                            "SELECT GROUP_CONCAT(nth_schedule_ids) as recruiter_nth_schedule_ids
                            FROM mssql_recruitertracker
                            WHERE mssql_recruitertracker.aoc_candidate_id = :aoc_candidate_id
                            AND mssql_recruitertracker.id != :recruiter_tracker_id
                            AND mssql_recruitertracker.is_active = 1
                            AND mssql_recruitertracker.nth_schedule_ids IS NOT NULL
                            GROUP BY mssql_recruitertracker.aoc_candidate_id
                            ORDER BY mssql_recruitertracker.id DESC",
                            [
                                'aoc_candidate_id' => $recruiterDetails->aoc_candidate_id,
                                'recruiter_tracker_id' => $recruiterDetails->id,
                            ]
                        );
                    } else {
                        $recruiter_nth_schedule_ids = DB::select(
                            "SELECT GROUP_CONCAT(nth_schedule_ids) as recruiter_nth_schedule_ids
                            FROM mssql_recruitertracker
                            WHERE mssql_recruitertracker.aoc_provider_id = :aoc_provider_id
                            AND mssql_recruitertracker.id != :recruiter_tracker_id
                            AND mssql_recruitertracker.is_active = 1
                            AND mssql_recruitertracker.nth_schedule_ids IS NOT NULL
                            GROUP BY mssql_recruitertracker.aoc_candidate_id
                            ORDER BY mssql_recruitertracker.id DESC",
                            [
                                'aoc_provider_id' => $recruiterDetails->aoc_provider_id,
                                'recruiter_tracker_id' => $recruiterDetails->id,
                            ]
                        );
                    }
                    return view('recruiterTracker.details', ['recruiterDetails'=>$recruiterDetails,
                    'providerDetails'=>$providerDetails,
                    'patientsDetails'=>$patientsDetails,
                    'scheduleType'=>$scheduleType,
                    'schedules'=>$schedules,
                    'branchDetails'=>$branchDetails,
                    'candidateDetails'=>$candidateDetails,
                    'nthSchedules' => $nthSchedules,
                    'recruiter_nth_schedule_ids' =>($recruiter_nth_schedule_ids) ?
                    $recruiter_nth_schedule_ids[0]->recruiter_nth_schedule_ids : '',
                    'clientEmergencyContact' => $clientEmergencyContact]);
                } else {
                    return view('recruiterTracker.details', ['recruiterDetails'=>$recruiterDetails,
                    'providerDetails'=>$providerDetails,
                    'patientsDetails'=>$patientsDetails,
                    'scheduleType'=>$scheduleType, 'schedules'=>$schedules,
                    'branchDetails'=>$branchDetails,
                    'candidateDetails'=>$candidateDetails,
                    'nthSchedules' => $nthSchedules,
                    'clientEmergencyContact' => $clientEmergencyContact]);
                }
            } else {
                return view('recruiterTracker.details', ['recruiterDetails'=>$recruiterDetails,
                'providerDetails'=>$providerDetails,
                'patientsDetails'=>$patientsDetails,
                'scheduleType'=>$scheduleType,
                'schedules'=>$schedules,
                'branchDetails'=>$branchDetails,
                'candidateDetails'=>$candidateDetails,
                'clientEmergencyContact' => $clientEmergencyContact]);
            }
        }
    }

    public function exportTrackerData(Request $request){
        $data = H::getHelper('Datatable')->ExportTrackerRecordWithFilter($request, 'mssql_recruitertracker');
        $allRecruitersAdmin = H::getAllRecruitersAdmin();
        $dataTableFields = [
            'Recruiter'=>'Recruiter',
            'RecruiterAdmin'=>'Recruiting Admin',
            'Branch_Location'=>'Branch Location',
            'Candidate'=>'Candidate',
            'aoc_provider_id'=>'New or Current',
            'Clinician_ID'=>'Clinician ID',
            'Skillset'=>'Skillset',
            'Candidate_Current_Status'=>'Candidate Current Status',
            'Schedule'=>'Schedule',
            'Total_Hours'=>'Total Hours',
            'Agreed_Pay_Rate'=>'Pay Rate',
            'GP'=>'GP',
            'Approved_By'=>'Approved By',
            'PatientName'=>'Client',
            'Client_ID'=>'Client ID',
            'Payer'=>'Payer',
            'is_intake'=>'Is Intake?',
            'IPEA_Scheduled'=>'IPEA Scheduled Date',
            'IPEA_Date'=>'IPEA Date',
            'IPEA_Time'=>'IPEA Time',
            'IPEA_Outcome'=>'IPEA Outcome',
            'PEPI_Scheduled'=>'PEPI Scheduled Date',
            'PEPI_Date'=>'PEPI Date',
            'PEPI_Time'=>'PEPI Time',
            'PEPI-Outcome'=>'PEPI Outcome',
            'WEPI_Scheduled'=>'WEPI Scheduled Date',
            'WEPI_Date'=>'WEPI Date',
            'WEPI_Time'=>'WEPI Time',
            'WEPI-Outcome'=>'WEPI Outcome',
            'Reason_Nurse_Declined_AOC'=>'Reason Nurse Declined AOC',
            'training_shift_scheduled_date'=>'Training Shift Scheduled Date',
            'Training_Shift'=>'Training Shift Date',
            'Training_Shift_Time'=>'Training Shift Time',
            'training_shift_outcome'=>'Training Shift Outcome',
            'Client_Match_Date'=>'Client Match Date',
            'Candidate_Start_Date'=>'Candidate Start Date',
            'Hours_Worked'=>'Hours Worked',
            'candidate_start_outcome'=>'Candidate Start Outcome',
            'ipea_day_diff'=>'Days between IPEA and Candidate Start',
            'Bonus_Status'=>'Bonus Status',
            'Bonus_Paid_Date'=>'Bonus Paid Date',
            'soc_date' => 'SOC Date',
            'soc_after_sap' => 'SOC After SAP',
            'payer_group' => 'SAP Payor',
            'Candidate_Notes'=>'Notes'
        ];

        if (LD::checkFeatureFlag('add-schedule--total-hours-last-schedule-date-tracker-export')) {
            $dataTableFields[Carbon::now()->subMonth(3)->format('F').'ScheduleHours'] = Carbon::now()->subMonth(3)->format('F') . ' Approved Hours Worked';
            $dataTableFields[Carbon::now()->subMonth(3)->format('F').'ScheduleDate'] = Carbon::now()->subMonth(3)->format('F') . ' Approved Last Shift Date';
            $dataTableFields[Carbon::now()->subMonth(2)->format('F').'ScheduleHours'] = Carbon::now()->subMonth(2)->format('F') . ' Approved Hours Worked';
            $dataTableFields[Carbon::now()->subMonth(2)->format('F').'ScheduleDate'] = Carbon::now()->subMonth(2)->format('F') . ' Approved Last Shift Date';
            $dataTableFields[Carbon::now()->subMonth()->format('F').'ScheduleHours'] = Carbon::now()->subMonth()->format('F') . ' Approved Hours Worked';
            $dataTableFields[Carbon::now()->subMonth()->format('F').'ScheduleDate'] = Carbon::now()->subMonth()->format('F') . ' Approved Last Shift Date';
        }

        if(LD::checkFeatureFlag('add-import-option-to-recruiter-tracker')){
            $dataTableFields['id'] = 'TrackerID';
        }
        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=trackerData.csv",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $columns = [];

        foreach ($dataTableFields as $k => $v) {
            $columns[] = $v;
        }

        $callback = function () use ($data, $columns, $dataTableFields,$allRecruitersAdmin) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach (array_chunk($data, 1000) as $dataChunk) {
                $trackerIds = array_column($dataChunk, 'id');
                $schedules = Schedule::getScheduleByTrackerIds($trackerIds);

                foreach ($dataChunk as $tableData) {
                    $cntr = 0;
                    $csvData = [];
                    foreach ($dataTableFields as $fields => $val) {
                        if ($fields == 'aoc_provider_id') {
                            $csvData[] = $tableData->{$fields} != 0 ? 'Current' : 'New';
                        } elseif ($fields == 'GP') {
                            $csvData[] = $tableData->{$fields} * 100;
                        } elseif ($fields == 'RecruiterAdmin') {
                            if (isset($allRecruitersAdmin[$tableData->user_id])) {
                                $csvData[] = $allRecruitersAdmin[$tableData->user_id];
                            } else {
                                $csvData[] = '';
                            }
                        } elseif ($fields == 'ipea_day_diff') {
                            if ($tableData->IPEA_Date != '-' && $tableData->IPEA_Date != null && $tableData->IPEA_Date != '0000-00-00 00:00:00' && $tableData->Candidate_Start_Date != null && $tableData->Candidate_Start_Date != '0000-00-00 00:00:00' && $tableData->Candidate_Start_Date != '-') {
                                $ipeaDate = strtotime($tableData->IPEA_Date);
                                $candidateStartDate = strtotime($tableData->Candidate_Start_Date);
                                $csvData[] = round(($candidateStartDate - $ipeaDate) / (60 * 60 * 24));
                            } else {
                                $csvData[] = '';
                            }
                        } elseif ($fields == 'soc_date') {
                            $soc_date = '';
                            if ($tableData->aoc_patient_id != '') {
                                if ($tableData->is_intake == 1) {
                                    $soc_date = $tableData->CF_GoalSOCDate;
                                    if ($soc_date == '0000-00-00 00:00:00') {
                                        $soc_date = '';
                                    }
                                } else {
                                    $soc_date = $tableData->SOCDate;
                                    if ($soc_date == '0000-00-00 00:00:00') {
                                        $soc_date = '';
                                    }
                                }
                            }
                            $soc_date = str_replace('12:00:00 AM','',$soc_date);
                            $csvData[] = $soc_date;
                        } elseif ($fields == 'soc_after_sap') {
                            $tmp = '';
                            if ($tableData->aoc_patient_id != '') {
                                if ($tableData->is_intake == 1){
                                    $soc_date = $tableData->CF_GoalSOCDate;
                                    if ($soc_date == '0000-00-00 00:00:00') {
                                        $soc_date = '';
                                    }
                                } else {
                                    $soc_date = $tableData->SOCDate;
                                    if ($soc_date == '0000-00-00 00:00:00') {
                                        $soc_date = '';
                                    }
                                }
                                if ($tableData->payer_group != '') {
                                    if ($tableData->payer_group == 'Superior') {
                                        if ($soc_date > '2022-05-01'){
                                            $tmp = 'Yes';
                                        } else {
                                            $tmp = 'No';
                                        }
                                    } else {
                                        if ($soc_date > '2022-08-01') {
                                            $tmp = 'Yes';
                                        } else {
                                            $tmp = 'No';
                                        }
                                    }
                                }
                            }
                            $csvData[] = $tmp;
                        } elseif ($fields == 'payer_group') {
                            $tmp = '';
                            if ($tableData->aoc_patient_id != '') {
                                $tmp = $tableData->payer_group;
                            }
                            $csvData[] = $tmp;
                        } elseif ($fields == 'candidate_start_outcome') {
                            $csvData[] = Schedule::getValueOfScheduleByTrackerId($schedules,$tableData->id,6,'outcome');
                        } elseif ($fields == 'training_shift_scheduled_date') {
                            $csvData[] = Schedule::getValueOfScheduleByTrackerId($schedules,$tableData->id,5,'scheduled_date');
                        } elseif ($fields == 'training_shift_outcome') {
                            $csvData[] = Schedule::getValueOfScheduleByTrackerId($schedules,$tableData->id,5,'outcome');
                        } elseif (in_array($fields, ['IPEA_Scheduled','IPEA_Date','PEPI_Scheduled','PEPI_Date','WEPI_Scheduled','WEPI_Date','Training_Shift','Client_Match_Date','Candidate_Start_Date','Bonus_Paid_Date']) && ($tableData->{$fields} == '0000-00-00 00:00:00' || $tableData->{$fields} == null || $tableData->{$fields} == '-')) {
                            $csvData[] = '';
                        } elseif ($fields == Carbon::now()->subMonth(3)->format('F').'ScheduleHours') {
                            $hoursDateData = Schedule::getApprovedSchedulesDetail($tableData->aoc_patient_id, $tableData->aoc_provider_id, 3, 'hours');
                            $csvData[] = $hoursDateData[0]->Hours??'';
                        } elseif ($fields == Carbon::now()->subMonth(3)->format('F').'ScheduleDate') {
                            $hoursDateData = Schedule::getApprovedSchedulesDetail($tableData->aoc_patient_id, $tableData->aoc_provider_id, 3,'date');
                            $csvData[] = $hoursDateData[0]->ScheduleDate??'';
                        } elseif ($fields == Carbon::now()->subMonth(2)->format('F').'ScheduleHours') {
                            $hoursDateData = Schedule::getApprovedSchedulesDetail($tableData->aoc_patient_id, $tableData->aoc_provider_id, 2, 'hours');
                            $csvData[] = $hoursDateData[0]->Hours??'';
                        } elseif ($fields == Carbon::now()->subMonth(2)->format('F').'ScheduleDate') {
                            $hoursDateData = Schedule::getApprovedSchedulesDetail($tableData->aoc_patient_id, $tableData->aoc_provider_id, 2,'date');
                            $csvData[] = $hoursDateData[0]->ScheduleDate??'';
                        } elseif ($fields == Carbon::now()->subMonth(1)->format('F').'ScheduleHours') {
                            $hoursDateData = Schedule::getApprovedSchedulesDetail($tableData->aoc_patient_id, $tableData->aoc_provider_id, 1, 'hours');
                            $csvData[] = $hoursDateData[0]->Hours??'';
                        } elseif ($fields == Carbon::now()->subMonth(1)->format('F').'ScheduleDate') {
                            $hoursDateData = Schedule::getApprovedSchedulesDetail($tableData->aoc_patient_id, $tableData->aoc_provider_id, 1,'date');
                            $csvData[] = $hoursDateData[0]->ScheduleDate??'';
                        } else {
                            $csvData[] = $tableData->{$fields};
                        }
                        $cntr++;
                    }

                    fputcsv($file, $csvData);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getTrackerAuditData(Request $request, $columns, $csvdata = false) {
        $filters = H::getFilterInputArr($request->all());

        $search = $request->input('search.value');
        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')]['name'];
        $dir = $request->input('order.0.dir');

        $q = DB::table('recruiting_schedule')
            ->leftJoin('mssql_recruitertracker', 'recruiting_schedule.tracker_id', '=', 'mssql_recruitertracker.id')
            ->leftJoin('schedule_types', 'recruiting_schedule.type_id', '=', 'schedule_types.id')
            ->select(
                'recruiting_schedule.*',
                'mssql_recruitertracker.Recruiter as Recruiter',
                'mssql_recruitertracker.Candidate as Candidate',
                'mssql_recruitertracker.Candidate_Current_Status as Candidate_Current_Status',
                'schedule_types.type as type',
                'mssql_recruitertracker.id as rid',
            )->where('recruiting_schedule.is_deleted', '0');

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if (!empty($search)) {
            $q->whereNested(function($q) use ($search) {
                $q->where('mssql_recruitertracker.Recruiter', 'LIKE', "%{$search}%")
                ->orWhere('mssql_recruitertracker.Candidate', 'LIKE', "%{$search}%")
                ->orWhere('mssql_recruitertracker.Candidate_Current_Status', 'LIKE', "%{$search}%")
                ->orWhere('schedule_types.type', 'LIKE', "%{$search}%")
                ->orWhere(DB::raw('DATE_FORMAT(recruiting_schedule.scheduled_date, "' . config('constant')['mysqlsearchdateformat'] . '")'), 'LIKE', "%{$search}%")
                ->orWhere(DB::raw('DATE_FORMAT(recruiting_schedule.appointment_date, "' . config('constant')['mysqlsearchdateformat'] . '")'), 'LIKE', "%{$search}%")
                ->orWhere(DB::raw('DATE_FORMAT(recruiting_schedule.created_at, "' . config('constant')['mysqlsearchdateformat'] . '")'), 'LIKE', "%{$search}%");
            });
            $totalFiltered = $q->count();
        }
        if (!empty($filters)) {
            if (isset($filters['branch'])) {
                $q->where('mssql_recruitertracker.aoc_facility_id', $filters['branch']);
            }
            if (isset($filters['recruiter'])) {
                $q->where('mssql_recruitertracker.Recruiter', $filters['recruiter']);
            }
            if (isset($filters['created_date'])) {
                $q->where(DB::raw('DATE_FORMAT(recruiting_schedule.created_at, "' . config('constant')['mysqlsearchdateformat'] . '")'), '=' , $filters['created_date']);
            }
            $totalFiltered = $q->count();
        }
        if ($csvdata == false) {
            $data = $q->offset($start)->limit($limit)->orderBy($order, $dir)->get();
        } else {
            $data = $q->orderBy($order, $dir)->get();
        }

        return [$data, $totalData, $totalFiltered];
    }

    public function trackersAudit(Request $request) {
        $columns = [
            ['name' => 'id','label' => 'id', 'visible' => false, 'width' => '0'],
            ['name' => 'Recruiter', 'label' => 'Recruiter', 'width' => '15%'],
            ['name' => 'Candidate', 'label' => 'Candidate', 'width' => '10%'],
            ['name' => 'Candidate_Current_Status', 'label' => 'Candidate Current Status', 'width' => '15%'],
            ['name' => 'type', 'label' => 'Type', 'width' => '10%'],
            ['name' => 'scheduled_date', 'label' => 'Scheduled Date', 'width' => '10%'],
            ['name' => 'appointment_date','label' => 'Appointment Date', 'width' => '10%'],
            ['name' => 'created_at','label' => 'Created At', 'width' => '10%']
        ];
        if ($request->ajax()) {
            list($data, $totalData, $totalFiltered) = $this->getTrackerAuditData($request, $columns);
            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => intval($totalData),
                'recordsFiltered' => intval($totalFiltered),
                'data'            => $this->dataProcess($data),
            ]);
        }
        return view('recruiterTracker.auditgrid', ['columns' => $columns]);
    }

    private function dataProcess($data) {
        foreach ($data as $key => $value) {
            $value->scheduled_date = H::getFormatedDate($value->scheduled_date);
            $value->appointment_date = H::getFormatedDate($value->appointment_date);
            $value->created_at = H::getFormatedDate($value->created_at);
        }
        return $data;
    }

    public function exportTrackersAudit(Request $request) {
        $columns = [
            ['name' => 'Recruiter', 'label' => 'Recruiter'],
            ['name' => 'Candidate', 'label' => 'Candidate'],
            ['name' => 'Candidate_Current_Status', 'label' => 'Candidate Current Status'],
            ['name' => 'type', 'label' => 'Type'],
            ['name' => 'scheduled_date', 'label' => 'Scheduled Date'],
            ['name' => 'appointment_date','label' => 'Appointment Date'],
            ['name' => 'created_at','label' => 'Created At']
        ];

        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=trackersAudit.csv",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        list($data, $totalData, $totalFiltered) = $this->getTrackerAuditData($request, $columns, true);

        $callback = function () use ($data, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_map(function ($value) {return  $value['label'];}, $columns));

            foreach ($data as $tableData) {
                $cntr = 0;
                $csvData = [];
                foreach ($columns as $fields => $val) {
                    $csvData[] = $tableData->{$val['name']};
                    $cntr++;
                }

                fputcsv($file, $csvData);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);

    }

    public function removeCandidate(Request $request) {

        try {
            if ($request->input('recruterTrackerId') != '') {
                $recruitingSchedule = [
                'IPEA_onboarding_status'=>'Inactive',
                'outcome'=>'Removed',
                'removed_reason'=>$request->input('reason'),
                'removed_notes'=>$request->input('reasonNotes'),
                'updated_at'=>date('Y-m-d H:i:s'),
                'updated_by'=>Auth::user()->id];

                $recruiterTracker = ['live_ipea_status'=>'Removed'];


                $noteData = [];
                $noteData['entity'] = 'tracker-activity';
                $noteData['entity_id'] = $request->input('recruterTrackerId');
                $note = 'IPEA template was removed due to '.$request->input('reason').' by '.Auth::user()->name.' ';

                if(!empty($request->input('reasonNotes'))) {
                    $note.="with the reason ".$request->input('reasonNotes');
                }
                $noteData['notes'] =$note;

                H::getHelper("ActivityNotes")->setActivityNotes($noteData);

                RecruitingSchedule::where('tracker_id',$request->input('recruterTrackerId'))->where('type_id',1)->update($recruitingSchedule);
                MssqlRecruitertracker::where('id', $request->input('recruterTrackerId'))->update($recruiterTracker);

              //  MssqlRecruitertracker::where('id', $request->input('recruterTrackerId'))->update(['Candidate_Current_Status' => 'Inactive', 'candidate_inactive_reason' => $request->input('reason'), 'candidate_inactive_notes' => $request->input('reasonNotes')]);
                return response()->json(['status' => true]);
            } else {
                return response()->json(['status' => false]);
            }
        }catch(\Exception $e){
            return response()->json(['status' => false]);
        }
    }

    public function getTrackerNthSchedule(Request $request) {
        $trackerData = MssqlRecruitertracker::find($request->input('tracker_id'));
        $nthSchedules = [];
        if($request->input("all")==1) {
            $nthSchedules = H::getHelper('RecruiterTrackerHelper')->getnthschedulebyclientid($trackerData->aoc_patient_id, false, true);
        } else {
            $nthSchedules = H::getHelper('RecruiterTrackerHelper')->getnthschedulebyclientid($trackerData->aoc_patient_id, (!empty($trackerData->nth_schedule_ids) ? $trackerData->nth_schedule_ids : -9999), true,false);
        }
        return Datatables::of($nthSchedules)->with('counthours', function() use($trackerData){
            $totalHr = 0;
            if(!empty($trackerData->nth_schedule_ids)) {
                $nthScheduleIdsAll = explode(",",$trackerData->nth_schedule_ids);
                $nthSchedulesData = H::getHelper('RecruiterTrackerHelper')->getnthScheduleByScheduleId($nthScheduleIdsAll);
                $totalHr =  number_format(array_sum(array_column($nthSchedulesData,'ShiftHours')), 0);
            }
            return $totalHr;
        })
        ->addColumn('Action', function ($row) use($request){
            $btn = '';
            if($request->input("all")!=1) {
                $btn .= '<a href="javascript:void(0)" onclick="removeNthSchedule('.$row->id.');"><i class="fa fa-trash mr-0 text-dark" aria-hidden="true"></i></a>';
            }
            return $btn;
        })
        ->addColumn('StartTime', function ($row){
            return H::getHelper('ConstantHelper')->getSmallTimeFormat($row->StartHour . ':' . $row->StartMinute);
        })
        ->addColumn('EndTime', function ($row){
            return H::getHelper('ConstantHelper')->getSmallTimeFormat($row->EndHour . ':' . $row->EndMinute);
        })
        ->addIndexColumn()->escapeColumns('Action')->make(true);
    }

    public function saveTrackerNthSchedule(Request $request) {
        try {
            if(LD::checkFeatureFlag('add-schedule-matching-the-nth-schedules')) {
                $nthSchedules = H::getHelper('RecruiterTrackerHelper')->getnthScheduleByScheduleId($request->input('nth_schedule_ids'));
                $totalHr =  number_format(array_sum(array_column($nthSchedules,'ShiftHours')), 0);
                MssqlRecruitertracker::where('id', $request->input('tracker_id'))->update(['nth_schedule_ids' => ($request->input('nth_schedule_ids')) ? implode(',', $request->input('nth_schedule_ids')) : '','Total_Hours' => $totalHr]);
            }else {
                MssqlRecruitertracker::where('id', $request->input('tracker_id'))->update(['nth_schedule_ids' => ($request->input('nth_schedule_ids')) ? implode(',', $request->input('nth_schedule_ids')) : '']);
            }
            return response()->json(['status' => true]);
        }catch(\Exception $e){
            return response()->json(['status' => false]);
        }
    }

    public function removeTrackerNthSchedule(Request $request) {
        try {
            $trackerData = MssqlRecruitertracker::find($request->input('tracker_id'));
            $nthScheduleIdsData = explode(",", $trackerData->nth_schedule_ids);
            $nthData = array_diff($nthScheduleIdsData, [$request->input('nth_schedule_ids')]);
            if(LD::checkFeatureFlag('add-schedule-matching-the-nth-schedules')) {
                $nthSchedules = H::getHelper('RecruiterTrackerHelper')->getnthScheduleByScheduleId($nthData);
                $totalHr =  number_format(array_sum(array_column($nthSchedules,'ShiftHours')), 0);
                MssqlRecruitertracker::where('id', $request->input('tracker_id'))->update(['nth_schedule_ids' => implode(',', $nthData),'Total_Hours' => $totalHr]);
            }else {
                MssqlRecruitertracker::where('id', $request->input('tracker_id'))->update(['nth_schedule_ids' => implode(',', $nthData)]);
            }
            return response()->json(['status' => true]);
        }catch(\Exception $e){
            return response()->json(['status' => false]);
        }
    }

    public function massUpdateRecruiter(Request $request) {
        try {
            $user = DB::table('users')->find($request->input('recruiter_id'));
            MssqlRecruitertracker::whereIn('id', explode(",", $request->input('ids')))->update(['user_id' => $request->input('recruiter_id'),'Recruiter' => $user->name]);
            foreach(explode(",", $request->input('ids')) as $key => $tracker_id) {
                $noteData = [];
                $noteData['entity'] = 'tracker';
                $noteData['entity_id'] = $tracker_id;
                $noteData['notes'] = 'This record was reassigned to ' . $user->name;

                H::getHelper("ActivityNotes")->setActivityNotes($noteData);
            }
            return response()->json(['success' => 1]);
        }catch(\Exception $e){
            return response()->json(['success' => 0]);
        }
    }

    public function saveClientMatchOutcome(Request $request){
        $note = '';
        $id = $request->trackerId;
        $patinetId = $request->patientId;
        $clinicianName = $request->clinician_name;
        if($request->clientmatch_outcome == 'Client match completed'){
            $recruiterTracker['clientmatch_outcome']=$request->clientmatch_outcome;
            $recruiterTracker['clientmatch_comment']=$request->clientmatch_comment;
            $recruiterTracker['checked_off_at']=date("Y-m-d H:i:s");
            $recruiterTracker['checked_off_by']=Auth::user()->id;
            MssqlRecruitertracker::where('id', $id)->update($recruiterTracker);
            $note = 'Client match completed: ' .auth()->user()->name .' marked Client match completed with '.$clinicianName.'. Comments:'.$request->clientmatch_comment. ' '.date("Y-m-d H:i:s");
        }elseif($request->clientmatch_outcome == 'Client match failed'){
            $recruiterTracker['clientmatch_outcome']=$request->clientmatch_outcome;
            $recruiterTracker['clientmatch_comment']=$request->clientmatch_comment;
            MssqlRecruitertracker::where('id', $id)->update($recruiterTracker);
            $note = 'Client match failed: ' .auth()->user()->name .' marked Client match failed with '.$clinicianName.'. Comments:'.$request->clientmatch_comment. ' '.date("Y-m-d H:i:s");
        }
        $noteData = [];
        $noteData['entity'] = 'client';
        $noteData['entity_id'] = $patinetId;
        $noteData['notes'] = $note;
        H::getHelper("ActivityNotes")->setActivityNotes($noteData);
        return response()->json(['status' => true]);
    }

    public function getTrackerImport(){
        return view('recruiterTracker.import');
    }

    public function processTrackerImport(Request $request)
    {
        $request->validate([
            "trackercsv" => 'required',
        ]);

        $filePath = $request->file('trackercsv')->getRealPath();
        $trackerData = H::getHelper('Jobvite')->csvToArray($filePath);

        if (empty($trackerData)) {
            return redirect()->back()->withErrors("CSV is empty or invalid format.");
        }

        $trackerDataArray = [];

        foreach ($trackerData as $data) {
            $trackerDataArray[] = [
                'tracker_id' => $data['TrackerID'],
                'Hours_Worked' => $data['Hours Worked'],
            ];
        }

        // Process in chunks of 1000 records
        $chunks = array_chunk($trackerDataArray, 1000);

        foreach ($chunks as $chunk) {
            $trackerIds = array_column($chunk, 'tracker_id');

            // Fetch existing data in one go
            $existingTrackers = MssqlRecruitertracker::whereIn('id', $trackerIds)
                ->pluck('Hours_Worked', 'id')
                ->toArray();

            foreach ($chunk as $value) {
                $trackerId = $value['tracker_id'];
                $newTotalHr = $value['Hours_Worked'];
                $oldTotalHr = $existingTrackers[$trackerId] ?? null;

                if ($newTotalHr != $oldTotalHr) {
                    MssqlRecruitertracker::where('id', $trackerId)
                        ->update(['Hours_Worked' => $newTotalHr]);

                    // Activity note
                    $note = "Hours Worked was updated for this tracker via tracker import.";
                    H::getHelper("ActivityNotes")->setActivityNotes([
                        'entity' => 'tracker',
                        'entity_id' => $trackerId,
                        'notes' => $note,
                    ]);
                }
            }
        }

        return redirect()->back()->withSuccess("Tracker data processed successfully.");
    }

}
