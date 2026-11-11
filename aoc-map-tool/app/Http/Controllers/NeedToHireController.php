<?php

namespace App\Http\Controllers;

use App\DTO\NeedToHire\NeedToHireScheduleByClientIdDTO;
use App\Http\Requests\NeedToHire\NeedToHireScheduleByClientIdRequest;
use App\Http\Resources\NeedToHire\ScheduleBasicResource;
use App\Http\Resources\NeedToHire\ScheduleWithHoursResource;
use App\Repositories\CustomFields\CustomFieldsReadRepository;
use App\Services\LogSq;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Helpers\H;
use App\Helpers\LD;
use DB;
use App\MssqlPatients;
use App\MssqlFacilities;
use App\NeedToHireAccounts;
use App\NeedToHireAccountsAudit;
use App\NeedToHireChecklist;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Helpers\Geocoding;
use App\Helpers\Notifications;

class NeedToHireController extends Controller
{
    public function __construct(private CustomFieldsReadRepository $customFieldsReadRepository)
    {}

    public function validateClientId(Request $request){
        $clientId = $request->input('clientId');
        $patientDetails = [];
        if(isset($clientId) && $clientId != ''){
            $patientDetails = DB::table('mssql_patients')->select('id',DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) as clientName"),'PatientAccount','Patient_CurrentStatusID')->where('PatientAccount',$clientId)->first();
            $patientDetails = json_decode(json_encode($patientDetails),true);
            if(isset($patientDetails['Patient_CurrentStatusID']) && $patientDetails['Patient_CurrentStatusID'] != ''){
                $statusDetails = DB::table('mssql_patientstatus')->select('PatientStatus')->where('PatientStatusID',$patientDetails['Patient_CurrentStatusID'])->first();
                $patientDetails['status'] = $statusDetails->PatientStatus;
            }
            return response()->json(['status' => true, 'patientDetails' =>$patientDetails ]);
        }else{
            return response()->json(['status' => false]);
        }
    }
    public function validateClientName(Request $request){
        $clientName = $request->input('clientName');
        $patientDetails = [];
        if(isset($clientName) && $clientName != ''){
            $patientDetails = DB::table('mssql_patients')->select('id',
            DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) as clientName"),
            'PatientAccount',
            'Patient_CurrentStatusID')
            ->whereRaw("LOWER(CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName))
            like ?",['%' . strtolower($clientName) . '%'])
            ->first();
            $patientDetails = json_decode(json_encode($patientDetails),true);
            if(isset($patientDetails['Patient_CurrentStatusID']) && $patientDetails['Patient_CurrentStatusID'] != ''){
                $statusDetails = DB::table('mssql_patientstatus')->select('PatientStatus')->where('PatientStatusID',$patientDetails['Patient_CurrentStatusID'])->first();
                $patientDetails['status'] = $statusDetails->PatientStatus;
            }
            return response()->json(['status' => true, 'patientDetails' =>$patientDetails ]);
        }else{
            return response()->json(['status' => false]);
        }
    }

    public function getNthStepForm(Request $request) {
        $type = $request->input("type");
        $title = H::getHelper("NTHChecklist")->getBucketTitle($request->input("type"));
        $ids = $request->input("ids");
        $fieldLabelArray = config('constant')['nthCheckListMainStepsLabel'];
        $fieldLabel = $fieldLabelArray[$type];
        $escalatedRateText = '';
        if(LD::checkFeatureFlag('escalated-content-fields-for-text-blast')){
            if ($type == 'text_blast') {
                if(strpos($ids,",") === false) {
                    $checklistDetail = DB::table("NeedToHire_Checklist")->where('id',$ids)->first();
                    if($checklistDetail->TextBlastEscalatedRate != NULL) {
                        $escalatedRateText = 'Previous Rate: '.$checklistDetail->TextBlastEscalatedRate;
                    }
                }
            }
        }
        return view('nthchecklist.stepsform', compact('title', 'type', 'ids', 'fieldLabel','escalatedRateText'));
    }

    /**
     * The function to save NTH steps counts
     *
     * @param Request $request
     * @author Nayan
     * @throws \Exception
     * @return JsonResponse
     */
    public function saveNthSteps(Request $request) {
        try {
            $params = $request->all();
            $bucket = DB::table("NeedToHire_Checklist");
            $createdByName = H::getLoggedInUser()['name'];
            $type = $params['type'];
            $buckets = H::getHelper('NTHChecklist')->getFieldNameBuckets();
            $updateIntField = $buckets[$type] . 'INT';
            if($type == 'social_media_sponsored') {
                $updateIntField = $buckets[$type];
            }
            $updateWorkedField = $buckets[$type] . 'Worked';
            $updateAllTimeField = $buckets[$type] . 'AllTime';
            $updateStartedField = $buckets[$type] . 'Started';
            $checklistArray = explode(",",$params['ids_'.$type]);
            foreach($checklistArray as $checklistId){
                $bucket = DB::table("NeedToHire_Checklist");
                $checklistDetail  = $bucket->where('id',$checklistId)->first();
                $checklistDetail  = H::objToArr($checklistDetail);
                $updateAry[$updateWorkedField] = date('Y-m-d H:i:s');
                if($checklistDetail[$updateStartedField] == NULL){
                    $updateAry[$updateStartedField] = date('Y-m-d H:i:s');
                }
                if(isset($params['no_of_nurses_contacted']) && $params['no_of_nurses_contacted'] > 0) {
                    $updateAry[$updateIntField] = DB::raw($checklistDetail[$updateIntField] . "+" . $params['no_of_nurses_contacted']);
                    $updateAry[$updateAllTimeField] = DB::raw($updateAllTimeField . "+" . $params['no_of_nurses_contacted']);
                }
                $misc3 = '';
                if ($type == 'social_media') {
                    if(isset($params['no_of_post_made']) && $params['no_of_post_made'] > 0) {
                        $updateAry['SocialMediaPostsINT'] = DB::raw("SocialMediaPostsINT +" . $params['no_of_post_made']);
                        $updateAry['SocialMediaPostsAllatime'] = DB::raw("SocialMediaPostsAllatime +" . $params['no_of_post_made']);
                        $misc3 = $params['no_of_post_made'];
                    }
                }
                if (LD::checkFeatureFlag('escalated-content-fields-for-text-blast')) {
                    if (isset($params['TextBlastEscalatedRate']) && $params['TextBlastEscalatedRate'] > 0) {
                        $updateAry['TextBlastEscalatedRate'] = $params['TextBlastEscalatedRate'];
                    }
                    if (isset($params['TextBlastContent']) && $params['TextBlastContent'] != '') {
                        $updateAry['TextBlastContent'] = $params['TextBlastContent'];
                    }
                }
                if (LD::checkFeatureFlag('add-escalated-margin-field-to-nth-text-blast-widget')) {
                    if (isset($params['TextBlastEscalatedMargin']) && $params['TextBlastEscalatedMargin'] > 0) {
                        $updateAry['TextBlastEscalatedMargin'] = $params['TextBlastEscalatedMargin'];

                        $escalatedRateNote = $createdByName.' escalated the margin to '.$params['TextBlastEscalatedMargin'] . '%';
                        $escalatedRateNotesData = ['entity' => 'NeedToHire', 'notes' => $escalatedRateNote, 'type' => 'NTH','entity_id' => $checklistId,'misc1' => 'TextBlastEscalatedMargin','misc2'=> $params['TextBlastEscalatedMargin']];
                        H::getHelper('ActivityNotes')->setActivityNotes($escalatedRateNotesData);
                    }
                }
                $bucket->where('id',$checklistId)->update($updateAry);
                //Saving activity notes
                if ($type != 'social_media') {
                    $note = $this->getNoteMessage($type);
                    $str = ['[user_name]', '[selected_count]'];
                    $rplc = [$createdByName, $params['no_of_nurses_contacted']];
                    $finalnote = str_replace($str, $rplc, $note);
                } else {
                    $finalnote = $createdByName;
                    if(isset($params['no_of_nurses_contacted']) && $params['no_of_nurses_contacted'] > 0) {
                        $finalnote = $finalnote. ' joined '.$params['no_of_nurses_contacted'].' groups.';
                    }
                    if(isset($params['no_of_nurses_contacted']) && $params['no_of_nurses_contacted'] > 0 && $misc3 != '') {
                        $finalnote = substr($finalnote,0,-1);
                        $finalnote = $finalnote. ' and ';
                    }
                    if($misc3 != '') {
                        $finalnote = $finalnote. ' made '.$misc3.' posts.';
                    }
                }

                $notesData = ['entity' => 'NeedToHire', 'notes' => $finalnote, 'type' => 'NTH','misc1'=>$updateIntField,'misc2'=>$params['no_of_nurses_contacted'],'entity_id' => $checklistId,'misc3' => $misc3];
                H::getHelper('ActivityNotes')->setActivityNotes($notesData);

                if (LD::checkFeatureFlag('escalated-content-fields-for-text-blast')) {
                    if ($type == 'text_blast') {
                        if (isset($params['TextBlastEscalatedRate']) && $params['TextBlastEscalatedRate'] > 0) {
                            $escalatedRateNote = $createdByName.' escalated the rate to $'.$params['TextBlastEscalatedRate'];
                            $escalatedRateNotesData = ['entity' => 'NeedToHire', 'notes' => $escalatedRateNote, 'type' => 'NTH','entity_id' => $checklistId,'misc1' => 'TextBlastEscalatedRate','misc2'=> $params['TextBlastEscalatedRate']];
                            H::getHelper('ActivityNotes')->setActivityNotes($escalatedRateNotesData);
                        }
                        if (isset($params['TextBlastContent']) && $params['TextBlastContent'] != '') {
                            $contentNotes = $createdByName.' updated the content of the Text Blast:';
                            $contentNotesData = ['entity' => 'NeedToHire', 'notes' => $contentNotes, 'type' => 'NTH','entity_id' => $checklistId,'misc1' => 'TextBlastContent','misc2'=> $params['TextBlastContent']];
                            H::getHelper('ActivityNotes')->setActivityNotes($contentNotesData);
                        }
                    }
                }
            }
            return response()->json(['success' => 1, 'message' => 'Details has been updated successfully!']);
        } catch (\Exception $ex) {
            return response()->json(['success' => 0, 'message' => 'Something went wrong, Please try again.']);
        }
    }

    public function getNoteMessage($type) {
        $note = '';
        switch ($type) {
            case 'text_blast':
                $note = '[user_name] text-blasted [selected_count] potential candidates.';
                break;
            case 'pet':
                $note = '[user_name] PET [selected_count] potential candidates.';
                break;
            case 'social_media':
                $note = '[user_name] joined [selected_count] groups.';
                break;
            case 'private_message':
                $note = '[user_name] private messaged [selected_count] potential candidates.';
                break;
            case 'linkedin':
                $note = '[user_name] connected with [selected_count] potential candidates on LinkedIn.';
                break;
            case 'indeed':
                $note = '[user_name] connected [selected_count] potential candidates through Indeed.';
                break;
            case 'cold_call':
                $note = '[user_name] cold-called [selected_count] potential candidates from maps.';
                break;
            case 'mail_matters':
                $note = '[user_name] mailed [selected_count] handwritten letters/postcards to potential candidates on BON lists.';
                break;
            case 'bon_nurse_search':
                $note = '[user_name] private messaged [selected_count] potential candidates on FB/LinkedIn from BON list.';
                break;
            case 'social_media_sponsored':
                $note = '[user_name] requested [selected_count] social media Ad.';
                break;
            case 'escaleted':
                $note = '[user_name] text-blasted [selected_count] potential candidates.';
                break;
            default:
                break;
        }
        return $note;
    }
    public function nthList(Request $request)
    {
        DB::enableQueryLog();

        $columns = [
            ['name' => 'nthaccounts_id', 'label' => 'Account ID', 'visible' => false],
            ['name' => 'clientId', 'label' => 'Client ID', 'visible' => false],
            ['name' => 'patientFullName', 'label' => 'Client'],
            ['name' => 'PatientAccountID', 'label' => 'Client ID'],
            ['name' => 'FacilityName', 'label' => 'Branch'],
            ['name' => 'ServicesNeeded', 'label' => 'Service Needed'],
            ['name' => 'TerminalPayer', 'label' => 'Payer'],
            ['name' => 'hoursOnNTH', 'label' => 'Hours On NTH'],
            ['name' => 'daysInNTH', 'label' => 'Days On NTH'],
            ['name' => 'CurrentStep', 'label' => 'NTH Checklist Step'],
            ['name' => 'IntakeCoordinatorName', 'label' => 'Intake Coordinator'],
            ['name' => 'ClientCareCoordinatorName', 'label' => 'Client Care Coordinator'],
            ['name' => 'Recruiter', 'label' => 'Recruiter'],
        ];

        if (LD::checkFeatureFlag('NTH-add-acquisition-column-filter')) {
            array_push($columns, ['name' => 'acquisition', 'label' => 'Acquisition']);
        }

        if ($request->ajax()) {
            $filters = H::getFilterInputArr($request->all());

            $search = $request->input('search.value');
            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columns[$request->input('order.0.column')]['name'];
            $dir = $request->input('order.0.dir');

            $q = DB::table('mssql_patients')
                ->leftJoin('NeedToHire_Accounts', 'mssql_patients.id', '=', 'NeedToHire_Accounts.aoc_patient_id')
                ->leftJoin('NeedToHire_Checklist', 'NeedToHire_Accounts.id', '=', 'NeedToHire_Checklist.aoc_nthaccounts_id')
                ->leftJoin('mssql_facilities', 'mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id')
                ->select(
                    'NeedToHire_Accounts.id as nthaccounts_id',
                    DB::raw("CASE WHEN NeedToHire_Checklist.CurrentStep = 0 THEN 'Hub City Requisition' WHEN NeedToHire_Checklist.CurrentStep = 1 THEN 'Text Blast' WHEN NeedToHire_Checklist.CurrentStep = 2 THEN 'P.E.T' WHEN NeedToHire_Checklist.CurrentStep = 3 THEN 'Social Media' WHEN NeedToHire_Checklist.CurrentStep = 4 THEN 'Private Message' WHEN NeedToHire_Checklist.CurrentStep = 5 THEN 'Linkedin' WHEN NeedToHire_Checklist.CurrentStep = 6 THEN 'Indeed' WHEN NeedToHire_Checklist.CurrentStep = 7 THEN 'Cold Call' WHEN NeedToHire_Checklist.CurrentStep = 8 THEN 'Mail Matters' WHEN NeedToHire_Checklist.CurrentStep = 9 THEN 'Bon Nurse Search' WHEN NeedToHire_Checklist.CurrentStep = 10 THEN 'Social Media Sponsored' WHEN NeedToHire_Checklist.CurrentStep = 11 THEN 'Escalated' END as CurrentStep"),
                    DB::raw("CASE WHEN NeedToHire_Checklist.TextBlast = 0 THEN '' WHEN NeedToHire_Checklist.PET = 0 THEN 'Text Blast' WHEN NeedToHire_Checklist.SocialMedia = 0 THEN 'P.E.T' WHEN NeedToHire_Checklist.PrivateMessage = 0 THEN 'Social Media' WHEN NeedToHire_Checklist.LinkedIN = 0 THEN 'Private Message' WHEN NeedToHire_Checklist.Indeed = 0 THEN 'Linkedin' WHEN NeedToHire_Checklist.ColdCall = 0 THEN 'Indeed' WHEN NeedToHire_Checklist.SnailMail = 0 THEN 'Cold Call' WHEN NeedToHire_Checklist.BONNursePM = 0 THEN 'Mail Matters' WHEN NeedToHire_Checklist.SocialMediaSponsored = 0 THEN 'Bon Nurse Search' WHEN NeedToHire_Checklist.Escalated = 0 THEN 'Social Media Sponsored' ELSE 'Escalated' END as LastStep"),
                    'mssql_patients.Patient_State',
                    'mssql_patients.id as clientId',
                    'NeedToHire_Accounts.ClientID as PatientAccountID',
                    'mssql_patients.PatientName',
                    'mssql_patients.Patient_FirstName',
                    'mssql_patients.Patient_LastName',
                    'NeedToHire_Accounts.Recruiter',
                    DB::raw("IFNULL(mssql_patients.Payer, mssql_patients.RespitePayer) as TerminalPayer"),
                    DB::raw("NeedToHire_Accounts.ServicesNeeded as ServicesNeeded"),
                    'NeedToHire_Accounts.IntakeCoordinator',
                    'NeedToHire_Accounts.ClientCareCoordinator',
                    DB::raw("(select users.name from users WHERE mssql_patients.ccc_id = users.id) as ClientCareCoordinatorName"),
                    DB::raw("(select users.name from users WHERE mssql_patients.ic_id = users.id) as IntakeCoordinatorName"),
                    DB::raw("(select SUM(ShiftHours) from NeedToHire_Schedules WHERE NeedToHire_Schedules.IsActive = 1 and NeedToHire_Accounts.id= NeedToHire_Schedules.aoc_nthaccounts_id) as hoursOnNTH"),
                    'mssql_facilities.FacilityName',
                    DB::raw("DATEDIFF(now(),NeedToHire_Accounts.created_at) as daysInNTH"),
                    DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) as patientFullName"),
                    'NeedToHire_Accounts.IsActive'
                )->whereNotNull('NeedToHire_Accounts.id')
                ->whereNotNull('NeedToHire_Checklist.id')
                ->havingRaw('hoursOnNTH IS NOT NULL');

            if (LD::checkFeatureFlag('NTH-add-acquisition-column-filter')) {
                $q->addSelect(DB::raw("IF(mssql_patients.aoc_cf_acquisition IN ('1', 'Yes'), 'Yes', 'No') as acquisition"));
            }
            $isShowAll = false;

            if(isset($request['filters'])){
                $isShowAll = array_search('showAll', array_column($request['filters'], 'name'));
            }

            if($isShowAll !== false){
                $q->where(function ($query){
                    $query->orWhere('NeedToHire_Accounts.IsActive',1)->orWhere('NeedToHire_Accounts.IsActive',0);
                });
            }else{
                $q->where('NeedToHire_Accounts.IsActive',1);
            }

            $totalData = $q->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $q->where(function ($query) use ($search) {
                    $query->where('mssql_patients.Search_Name', 'LIKE', "%{$search}%");
                    $query->orWhere('mssql_patients.PatientAccount', 'LIKE', "%{$search}%");
                    $query->orWhere('NeedToHire_Accounts.Recruiter', 'LIKE', "%{$search}%");
                    $query->orWhere('mssql_facilities.FacilityName', 'LIKE', "%{$search}%");
                });
                $totalFiltered = $q->count();
            }

            $filters = [];

            if (isset($request['filters'])) {
                foreach ($request['filters'] as $fl) {
                    if (isset($fl['value']) && $fl['value'] !== '') {
                        $filters[$fl['name']][] = $fl['value'];
                    }
                }

                foreach ($filters as $fkey => $fval) {
                    if (LD::checkFeatureFlag('nth-meeting-nurses-filter')) {
                        if ($fkey == 'state') {
                            $q->where(function ($query) use ($fval) {
                                foreach ($fval as $val) {
                                    $query->orWhere("mssql_patients.Patient_State", '=', $val);
                                }
                            });
                        } elseif ($fkey == 'aoc_facility_id') {
                            $q->where(function ($query) use ($fval) {
                                foreach ($fval as $val) {
                                    $query->orWhere("mssql_patients.aoc_facility_id", '=', $val);
                                }
                            });
                        } elseif ($fkey == 'IntakeCoordinator') {
                            $q->where(function ($query) use ($fval) {
                                foreach ($fval as $val) {
                                    $query->orWhere("mssql_patients.ic_id", '=', $val);
                                }
                            });
                        } elseif ($fkey == 'ClientCareCoordinator') {
                            $q->where(function ($query) use ($fval) {
                                foreach ($fval as $val) {
                                    $query->orWhere("mssql_patients.ccc_id", '=', $val);
                                }
                            });
                        } elseif ($fkey == 'recruiter') {
                            $q->where(function ($query) use ($fval) {
                                foreach ($fval as $val) {
                                    $query->orWhere("NeedToHire_Accounts.Recruiter", '=', $val);
                                }
                            });
                        } elseif ($fkey == 'IsPriority') {
                            $q->where('NeedToHire_Accounts.IsPriority', 1);
                        } elseif ($fkey == 'is_intake') {
                            $q->where('mssql_patients.is_intake', $fval[0] === 'true' ? 1 : 0);
                        } elseif ($fkey == 'meetingNurses') {
                            $q->where(function ($query) use ($fval) {
                                foreach ($fval as $val) {
                                    $query->orWhere("mssql_patients.CF_MeetingNurses", '=', $val);
                                }
                            });
                        }
                    } else {
                        if ($fkey == 'state') {
                            $q->where(function ($query) use ($fval) {
                                foreach ($fval as $val) {
                                    $query->orWhere("mssql_patients.Patient_State", '=', $val);
                                }
                            });
                        } elseif ($fkey == 'aoc_facility_id') {
                            $q->where(function ($query) use ($fval) {
                                foreach ($fval as $val) {
                                    $query->orWhere("mssql_patients.aoc_facility_id", '=', $val);
                                }
                            });
                        } elseif ($fkey == 'IntakeCoordinator') {
                            $q->where(function ($query) use ($fval) {
                                foreach ($fval as $val) {
                                    $query->orWhere("mssql_patients.ic_id", '=', $val);
                                }
                            });
                        } elseif ($fkey == 'ClientCareCoordinator') {
                            $q->where(function ($query) use ($fval) {
                                foreach ($fval as $val) {
                                    $query->orWhere("mssql_patients.ccc_id", '=', $val);
                                }
                            });
                        } elseif ($fkey == 'recruiter') {
                            $q->where(function ($query) use ($fval) {
                                foreach ($fval as $val) {
                                    $query->orWhere("NeedToHire_Accounts.Recruiter", '=', $val);
                                }
                            });
                        } elseif ($fkey == 'IsPriority') {
                            $q->where('NeedToHire_Accounts.IsPriority', 1);
                        } elseif ($fkey == 'is_intake') {
                            $q->where('mssql_patients.is_intake', 1);
                        }
                    }

                    if (LD::checkFeatureFlag('payer-filters-on-nth-listing')) {
                        if ($fkey == 'nth-payer') {
                            $q->where(function ($query) use ($fval) {
                                foreach ($fval as $val) {
                                    $query->orWhere("mssql_patients.Payer", '=', $val);
                                }
                            });
                        } elseif ($fkey == 'payer_group') {
                            $q->where(function ($query) use ($fval) {
                                foreach ($fval as $val) {
                                    $query->orWhere("mssql_patients.payer_group", '=', $val);
                                }
                            });
                        }
                    }

                    if (LD::checkFeatureFlag('NTH-add-acquisition-column-filter')) {
                        if ($fkey === 'acquisiton') {
                            if ((int)$fval[0] === 1) {
                                $q->whereIn('mssql_patients.aoc_cf_acquisition', ['1', 'Yes']);
                            }
                            else {
                                $q->where(function($query) {
                                    $query->whereNotIn('mssql_patients.aoc_cf_acquisition', ['1', 'Yes'])
                                        ->orWhereNull('mssql_patients.aoc_cf_acquisition');
                                });
                            }
                        }
                    }

                    if (LD::checkFeatureFlag('add-me-mode-client-nth-list-page')) {
                        if ($fkey == 'default') {
                            $q->where(function ($query) {
                                $query->orWhere("mssql_patients.ccc_id", '=', Auth::user()->id);
                                $query->orWhere("mssql_patients.ccm_id", '=', Auth::user()->id);
                                $query->orWhere("NeedToHire_Accounts.user_id", '=', Auth::user()->id);
                            });
                        }
                    }
                }
                $totalFiltered = $q->count();
            }

            $data = $q->offset($start)->limit($limit)->orderBy($order, $dir)->get();

            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => intval($totalData),
                'recordsFiltered' => intval($totalFiltered),
                'data'            => $data,
            ]);
        }

        $states = DB::table('mssql_patients')->select(DB::raw('DISTINCT Patient_State as state'))->where('Patient_State','!=','')->get()->toArray();

        LogSq::processSqlLogs('NeedToHireController-nthList');

        return view('nthchecklist.grid', [
            'columns' => $columns,
            'states' => array_column($states, 'state')
        ]);
    }

    public function nthChecklistDetails(Request $request)
    {
        try {
            if ($request->input('id') != '') {
                $needToHireChecklistDetails = [];
                $patientsDetails = [];
                $needToHireAccounts = NeedToHireAccounts::select('NeedToHire_Accounts.*', DB::raw("DATEDIFF(now(),NeedToHire_Accounts.created_at) as daysInNTH"))->where('id',$request->input('id'))->first();
                if (isset($needToHireAccounts->id) && $needToHireAccounts->id != '') {
                    $needToHireChecklistDetails = NeedToHireChecklist::where('aoc_nthaccounts_id', $needToHireAccounts->id)->first();
                    $nthScheduleTable = DB::table("NeedToHire_Schedules")->select("*")
                        ->where('IsActive', 1)
                        ->where("aoc_nthaccounts_id",$needToHireAccounts->id)->get();
                    $nthScheduleTableArray = json_decode(json_encode($nthScheduleTable), true);
                    $totalShiftHours = array_sum(array_column($nthScheduleTableArray, 'ShiftHours'));
                }
                if(isset($needToHireAccounts->aoc_patient_id) && $needToHireAccounts->aoc_patient_id != ''){
                    $patientsDetails = MssqlPatients::where('id',$needToHireAccounts->aoc_patient_id)->first();
                    if(!empty($patientsDetails)) {
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
                        if ($patientsDetails->PreferenceList != '' && strpos(strtolower($patientsDetails->PreferenceList),"accepts coverage") !== false) {
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

                        if (isset($patientsDetails->aoc_cf_male_nurses) && isset($patientsDetails->aoc_cf_female_nurses)) {
                            if ($patientsDetails->aoc_cf_female_nurses === 'No') {
                                $genderPref = 'Male Only';
                            } elseif ($patientsDetails->aoc_cf_male_nurses === 'No') {
                                $genderPref = 'Female Only';
                            }
                        } else {
                            $preferenceList = strtolower($patientsDetails->PreferenceList);

                            if (str_contains($preferenceList, 'does not accept female nurses')) {
                                $genderPref = 'Male Only';
                            } elseif (!str_contains($preferenceList, 'male nurse accepted')) {
                                $genderPref = 'Female Only';
                            }
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
                        if ($needToHireAccounts->is_deleted) {
                            $webVisibility = 'removed';
                        } elseif (!$needToHireAccounts->IsActive) {
                            $webVisibility = 'not_published';
                        } else {
                            $webVisibility = 'published';
                        }

                        if (!isset($webVisibility)) {
                            $webVisibility = 'error';
                        }
                        $needToHireChecklistDetails->webVisibility = $webVisibility;
                    }
                }
                return view(
                    'nthchecklist.nthChecklistDetails',
                    [
                        'needToHireAccounts' => $needToHireAccounts,
                        'patientsDetails' => $patientsDetails,
                        'needToHireChecklistDetails' => $needToHireChecklistDetails,
                        'hideactivity' => $request->input('hideactivity'),
                        'totalShiftHours' => $totalShiftHours ?? 0,
                    ]
                );
            }
        } catch (\Exception $e) {
            H::ExcelLog($e->getMessage(),'nthChecklistDetails.log');
        }
    }

    /**
     * The function to save NTH Social Media
     *
     * @param Request $request
     * @author Nayan
     */
    public function getNthSocialMediaForm(Request $request) {
        $id = $request->input("id");
        $bucket = DB::table("NeedToHire_Checklist");
        $bucket->leftJoin("NeedToHire_Accounts", function ($query) {
            $query->on("NeedToHire_Accounts.id", "=", "NeedToHire_Checklist.aoc_nthaccounts_id");
        });
        $bucket->leftJoin("mssql_patients", function ($query) {
            $query->on("mssql_patients.id", "=", "NeedToHire_Accounts.aoc_patient_id");
        });
        $bucket->where("NeedToHire_Accounts.IsActive", "=", 1);
        $bucket->select('NeedToHire_Accounts.id as nthaccounts_id', 'NeedToHire_Checklist.id as  nthchecklist_id', 'mssql_patients.Patient_City', 'mssql_patients.CF_Skills', 'mssql_patients.Patient_Zip', 'mssql_patients.Patient_State', DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS ClientNameCustom"), 'mssql_patients.CF_TheSale', 'mssql_patients.CF_ClinicianDiscipline', 'mssql_patients.PatientAccount', 'mssql_patients.Payer', 'NeedToHire_Accounts.Status', 'NeedToHire_Accounts.created_at', 'NeedToHire_Accounts.ServicesNeeded', 'mssql_patients.NTHNotes');
        $bucket->where("NeedToHire_Accounts.id", "=", $id);
        $clientDetail = $bucket->first();

        if(LD::checkFeatureFlag('allow-ads-if-closed')){
            $isClientRequestAvail = DB::table("social_media_ads")->select('id')->where('aoc_nthaccounts_id',$id)->where('completed',0)->count();
        }else{
            $isClientRequestAvail = DB::table("social_media_ads")->select('id')->where('aoc_nthaccounts_id',$id)->count();
        }
        $scheduleDescription = H::getHelper('NTHChecklist')->getNthShiftDescription($id);

        return view('nthchecklist.socialmediaform', compact('id','clientDetail','scheduleDescription','isClientRequestAvail'));
    }

    /**
     * The function to save NTH steps counts
     *
     * @param Request $request
     * @author Nayan
     * @throws \Exception
     * @return JsonResponse
     */
    public function saveNthSocialMedia(Request $request) {
        try {
            $params = $request->all();
            $data['aoc_nthaccounts_id'] = $params['aoc_nthaccounts_id'];
            $data['discipline'] = $params['discipline'];
            $data['skills_diagnosis'] = $params['skills_diagnosis'];
            $data['shifts_needed'] = $params['shifts_needed'];
            $data['the_sale'] = $params['the_sale'];
            $data['state'] = $params['state'];
            $data['city'] = $params['city'];
            $data['created_by'] = Auth::user()->id;
            $data['updated_by'] = Auth::user()->id;
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            $bucket = DB::table("social_media_ads");
            $bucket->insert($data);
            //Update Checklist record
            $bucket = DB::table("NeedToHire_Checklist");
            $checklistDetail  = $bucket->where('id', $params['nthchecklist_id'])->first();
            $checklistDetail  = H::objToArr($checklistDetail);
            $updateArr = ['SocialMediaSponsored' => 1, 'SocialMediaSponsoredWorked' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id];
            if ($checklistDetail['SocialMediaSponsoredStarted'] == NULL) {
                $updateArr['SocialMediaSponsoredStarted'] = date('Y-m-d H:i:s');
            }
            $bucket->where('id', $params['nthchecklist_id'])->update($updateArr);
            //Email
            $email = 'bskaggs@angelsofcare.com';
            $subject = 'New Social Media Sponsor Request';
            $emailBody = self::getNthSocialMediaEmailBody($params);
            $notification = $emailBody;
            H::getHelper('EmailNotification')->sendEmailNotification($email, $subject, $notification,'html');
            return response()->json(['success' => 1, 'message' => 'Details has been updated successfully!']);
        } catch (\Exception $ex) {
            return response()->json(['success' => 0, 'message' => 'Something went wrong, Please try again.']);
        }
    }

    /**
     * The function return the HTML for email body
     *
     * @param Request $params
     * @author Nayan
     * @throws \Exception
     * @return String
     */
    function getNthSocialMediaEmailBody($params) {
        $str = '';
        $str = '<table width="100%">';
        $str = $str.'<tr><td>A Social Media Sponsor Request was added by '. Auth::user()->name.'</td></tr>';
        $str = $str.'</table>';
        $str = $str.'<table border="1" width="100%">';
        $str = $str.'<tr><td>Client</td><td>'.$params['patient_name'].'</td></tr>';
        $str = $str.'<tr><td>Location</td><td>'.$params['city'].', '.$params['state'].'</td></tr>';
        $str = $str.'<tr><td>Client Zip Code</td><td>'.$params['zip'].'</td></tr>';
        $str = $str.'<tr><td>Discipline</td><td>'.$params['discipline'].'</td></tr>';
        $str = $str.'<tr><td>Skills/Diagnosis</td><td>'.$params['skills_diagnosis'].'</td></tr>';
        $str = $str.'<tr><td>Shifts Needed</td><td>'.$params['shifts_needed'].'</td></tr>';
        $str = $str.'<tr><td>The Sale</td><td>'.$params['the_sale'].'</td></tr>';
        $str = $str.'</table>';
        return $str;
    }
    /**
     * The function to map Nth details from text blast map
     */
    public function getMapDetailsForNthTextBlast($reqData)
    {
        $mapData = [];
        $allSessions = Session::all();
        if(isset($allSessions['maps_textblast']['lastmapsearch']['lat']) && isset($allSessions['maps_textblast']['lastmapsearch']['lng'])
            && $allSessions['maps_textblast']['lastmapsearch']['lat']!='' && $allSessions['maps_textblast']['lastmapsearch']['lng']!=''){
            $lat = $allSessions['maps_textblast']['lastmapsearch']['lat'];
            $lng = $allSessions['maps_textblast']['lastmapsearch']['lng'];
        }else{
            H::ExcelLog('no map coordinates','getMapDetailsForNthTextBlast.log');
            return response()->json(['success' => '0', 'message' => 'Map coordinates are missing.']);
        }
        H::ExcelLog('lat/lng: ' . $lat . '/' . $lng,'getMapDetailsForNthTextBlast.log');
        if(isset($reqData['radius']) && $reqData['radius']!=''){
            $radius = $reqData['radius'];
        }else{
            H::ExcelLog('no radius','getMapDetailsForNthTextBlast.log');
            return response()->json(['success' => '0', 'message' => 'No Radius']);
        }

        $distanceField = "(3959 * acos(cos(radians('" . $lat . "')) * cos(radians(lat)) * cos( radians(lng) - radians('" . $lng . "')) + sin(radians('" . $lat . "')) * sin(radians(lat))))";
        $mapBucket = DB::table("maps_nth");
        $mapBucket->select('maps_nth.id', DB::raw('NeedToHire_Accounts.id as aoc_nthaccounts_id'), DB::raw($distanceField . '  AS distance'));
        $mapBucket->join("NeedToHire_Accounts", function ($query) {
            $query->on("NeedToHire_Accounts.NeedToHireAccountsID", "=", "maps_nth.NeedToHireAccountsID");
        });
        $mapBucket->where("NeedToHire_Accounts.IsActive", "=", 1)->where("lat", "!=", "")->where("lng", "!=", "");
        $mapBucket->havingRaw('distance <= '.($radius + 0.5));
        H::ExcelLog($mapBucket->toSql(),'getMapDetailsForNthTextBlast.log');
        $mapData = $mapBucket->get();
        return $mapData;
    }
    /**
     * The function to get Nth details from text blast map
     *
     * @param Request $request
     * @author Nayan
     * @throws \Exception
     * @return JsonResponse
     */
    public function getDetailsForNthTextBlast(Request $request)
    {
        try {
            $reqData = $request->all();
            H::ExcelLog(print_r($reqData,true),'getDetailsForNthTextBlast.log');
            H::ExcelLog('total Text Blast ='.$reqData['totalTextBlast'],'getDetailsForNthTextBlast.log');
            if($reqData['totalTextBlast'] == 0) {
                H::ExcelLog('total Text Blast ='.$reqData['totalTextBlast'],'getDetailsForNthTextBlast.log');
                return response()->json(['success' => '0', 'message' => 'No data available.']);
            }
            $mapData = $this->getMapDetailsForNthTextBlast($reqData);
            $totalMapdata = count($mapData);
            H::ExcelLog('count: ' . $totalMapdata,'getDetailsForNthTextBlast.log');
            $successMessage = 'This will update '.$totalMapdata.' NTH Accounts that you have text blasted '.$reqData['totalTextBlast'].' potential nurses.';
            return response()->json(['success' => 1, 'message' => $successMessage]);
        } catch (\Exception $ex) {
            H::ExcelLog('updateToNth Exception: ' . $ex->getMessage(),'getDetailsForNthTextBlast.log');
            return response()->json(['success' => 0, 'message' => 'Something went wrong, Please try again.']);
        }
    }
    /**
     * The function to update Nth from text blast map
     *
     * @param Request $request
     * @author Nayan
     * @throws \Exception
     * @return JsonResponse
     */
    public function updateToNth(Request $request)
    {
        try {
            $reqData = $request->all();
            H::ExcelLog(print_r($reqData,true),'tsNTH.log');
            H::ExcelLog('total Text Blast ='.$reqData['totalTextBlast'],'tsNTH.log');
            if($reqData['totalTextBlast'] == 0) {
                H::ExcelLog('total Text Blast ='.$reqData['totalTextBlast'],'tsNTH.log');
                return response()->json(['success' => '0', 'message' => 'No data available.']);
            }
            $map1Data = $this->getMapDetailsForNthTextBlast($reqData);
            $totalMapdata = count($map1Data);
            H::ExcelLog('count: ' . $totalMapdata,'tsNTH.log');
            $createdByName = H::getLoggedInUser()['name'];

            foreach ($map1Data as $map1DataDetail) {
                $checklistDetail = DB::table("NeedToHire_Checklist");
                $checklistDetail->select('NeedToHire_Accounts.id as nthaccounts_id', 'NeedToHire_Checklist.id as  nthchecklist_id', 'NeedToHire_Checklist.TextBlastStarted');
                $checklistDetail->leftJoin("NeedToHire_Accounts", function ($query) {
                    $query->on("NeedToHire_Accounts.id", "=", "NeedToHire_Checklist.aoc_nthaccounts_id");
                });
                $checklistDetail->leftJoin("mssql_patients", function ($query) {
                    $query->on("mssql_patients.id", "=", "NeedToHire_Accounts.aoc_patient_id");
                });
                $checklistDetail->where("NeedToHire_Accounts.IsActive", "=", 1);
                $checklistDetail->where("NeedToHire_Accounts.id", "=", $map1DataDetail->aoc_nthaccounts_id);
                $checklistDetailData = $checklistDetail->first();
                if (!empty($checklistDetailData)) {
                    $updateAry = ['TextBlastINT' => DB::raw("TextBlastINT +" . $reqData['totalTextBlast']), 'TextBlastAllTime' => DB::raw(" TextBlastAllTime +" . $reqData['totalTextBlast']), 'TextBlastWorked' => date('Y-m-d H:i:s')];
                    if ($checklistDetailData->TextBlastStarted == NULL) {
                        $updateAry['TextBlastStarted'] = date('Y-m-d H:i:s');
                    }
                    H::ExcelLog('id: ' . $checklistDetailData->nthchecklist_id,'tsNTH.log');
                    H::ExcelLog(print_r($updateAry,true),'tsNTH.log');
                    $bucket = DB::table("NeedToHire_Checklist");
                    $bucket->where('id', $checklistDetailData->nthchecklist_id)->update($updateAry);
                    //Saving activity notes
                    $finalnote = $createdByName.' text-blasted '.$reqData['totalTextBlast'].' potential candidates from the Text Blast map.';
                    $notesData = ['entity' => 'NeedToHire', 'notes' => $finalnote, 'type' => 'NTH','misc1'=>'TextBlastINT','misc2'=>$reqData['totalTextBlast'], 'entity_id' => $checklistDetailData->nthchecklist_id];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                }
            }
            $successMessage = $totalMapdata.' NTH clients were updated with '.$reqData['totalTextBlast'].' text blasts.';
            return response()->json(['success' => 1, 'message' => $successMessage]);
        } catch (\Exception $ex) {
            H::ExcelLog('updateToNth Exception: ' . $ex->getMessage(),'tsNTH.log');
            return response()->json(['success' => 0, 'message' => 'Something went wrong, Please try again.']);
        }
    }

    /**
     * The function to move NTH bucket one step ahead
     *
     * @param Request $request
     * @author Nayan
     * @throws \Exception
     * @return JsonResponse
     */
    public function moveNthSteps(Request $request) {
        try {
            $params = $request->all();
            $bucket = DB::table("NeedToHire_Checklist");
            $createdByName = H::getLoggedInUser()['name'];
            $type = $params['type'];
            $buckets = [
                'text_blast' => "Text Blast", 'pet' => 'P.E.T', 'social_media' => 'Social Media',
                'private_message' => 'Private Message', 'linkedin' => 'Linkedin', 'indeed' => "Indeed", 'cold_call' => "Cold Call", 'mail_matters' => 'Mail Matters',
                'bon_nurse_search' => 'Bon Nurse Search','social_media_sponsored' => 'Social Media Sponsored','escaleted' => 'Escalted'
            ];
            $fieldNameBuckets = H::getHelper('NTHChecklist')->getFieldNameBuckets();
            $nextStepName = '';
            $nextStepKey = 0;
            $currentStep = array_search($type, array_keys($buckets));
            $nextStep = $currentStep+2;
            $values = array_values($buckets);
            $keys = array_keys($buckets);
            if ($currentStep + 1 < count($values)) {
                $nextStepName = $values[$currentStep + 1];
                $nextStepKey = $keys[$currentStep + 1];
            }
            $checklistArray = explode(",",$params['ids']);
            $updateStartedField = $fieldNameBuckets[$nextStepKey] . 'Started';
            $updateAry = [
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => Auth::user()->id,
                'CurrentStep' => $nextStep,
                $updateStartedField => date('Y-m-d H:i:s')
            ];
            foreach($checklistArray as $checklistId){
                DB::table("NeedToHire_Checklist")->where('id',$checklistId)->update($updateAry);
                //Saving activity notes
                $note = $createdByName.' changed the status to '.$nextStepName;
                $notesData = ['entity' => 'NeedToHire', 'notes' => $note, 'type' => 'NTH','entity_id' => $checklistId];
                H::getHelper('ActivityNotes')->setActivityNotes($notesData);
            }
            $nextStepCounter = H::getHelper('NTHChecklist')->getNTHChecklistDataByType($nextStepKey,'',1);
            return response()->json(['success' => 1,'next_step_counter' => $nextStepCounter, 'message' => 'Records moved to '.$nextStepName.' successfully!']);
        } catch (\Exception $ex) {
            H::ExcelLog('moveNthSteps Exception: ' . $ex->getMessage(),'moveNthSteps.log');
            return response()->json(['success' => 0, 'message' => 'Something went wrong, Please try again.']);
        }
    }

    /**
     * To Open the Add NTH form
     *
     * @param Request $request
     * @throws \Exception
     */
    public function addNTHForm(Request $request)
    {
        try {
            if ($request->input('id') != '') {
                $patientsDetails = [];
                $branchDetails = [];
                $names = [];
                $needToHireAccounts = NeedToHireAccounts::where('id',$request->input('id'))->first();
                if(isset($needToHireAccounts->aoc_patient_id) && $needToHireAccounts->aoc_patient_id != ''){
                    $patientsDetails = MssqlPatients::where('id',$needToHireAccounts->aoc_patient_id)->first();

                    $names['clientName'] = $patientsDetails->Patient_FirstName . ' ' . $patientsDetails->Patient_LastName;
                    // $names['ccc'] = isset($patientsDetails->ccc_id) ? H::getHelper('ConstantHelper')->getUserNameById($patientsDetails->ccc_id,'kantime_username') : '';
                    $names['ccc'] = isset($patientsDetails->ccc_id) ? H::getHelper('ConstantHelper')->getUserNameById($patientsDetails->ccc_id,'name') : '';
                    $names['ic'] = isset($patientsDetails->ic_id) ? H::getHelper('ConstantHelper')->getUserNameById($patientsDetails->ic_id,'name') : '';
                }
                if(isset($patientsDetails->aoc_facility_id) && $patientsDetails->aoc_facility_id != ''){
                    $branchDetails = MssqlFacilities::find($patientsDetails->aoc_facility_id);
                }
                $names['scheduleSpecialized'] = 0;
                if (LD::checkFeatureFlag('specialized-vent-included-in-skills-nth-form')) {
                    $scheduleDetail = H::getHelper('NTHChecklist')->getPatientScheduleSpecialized($patientsDetails->PatientID);
                    if(!empty($scheduleDetail)) {
                        $names['scheduleSpecialized'] = 1;
                    }
                }
                return view('nthchecklist.addNTH', ['needToHireAccounts'=>$needToHireAccounts,'patientsDetails'=>$patientsDetails,'branchDetails'=>$branchDetails,'names'=>$names,'mode'=>'edit']);
            }else{
                return view('nthchecklist.addNTH',['mode'=>'insert']);
            }
        } catch (\Exception $e) {
            H::ExcelLog('addNTHForm:'.$e->getMessage(),'nthChecklistDetails.log');
            return false;
        }
    }
    /**
     * To get the Client details in JSON to fill the other fields in the NTH form
     *
     * @param Request $request
     * @throws \Exception
     */
    public function getClientDetailsInJSON(Request $request)
    {
        try {
            if ($request->input('id') != '') {
                $branchDetails = [];
                $names = [];
                $patientsDetails = MssqlPatients::find($request->input('id'));
                $needToHireAccounts = NeedToHireAccounts::where('aoc_patient_id',$request->input('id'))->where('IsActive',1)->first();
                $names['clientName'] = $patientsDetails->Patient_FirstName . ' ' . $patientsDetails->Patient_LastName;
                $names['ccc'] = isset($patientsDetails->ccc_id) ? H::getHelper('ConstantHelper')->getUserNameById($patientsDetails->ccc_id,'kantime_username') : '';
                $names['ic'] = isset($patientsDetails->ic_id) ? H::getHelper('ConstantHelper')->getUserNameById($patientsDetails->ic_id,'name') : '';
                if(isset($patientsDetails->aoc_facility_id) && $patientsDetails->aoc_facility_id != ''){
                    $branchDetails = MssqlFacilities::find($patientsDetails->aoc_facility_id);
                }
                $names['scheduleSpecialized'] = 0;
                if (LD::checkFeatureFlag('specialized-vent-included-in-skills-nth-form')) {
                    $scheduleDetail = H::getHelper('NTHChecklist')->getPatientScheduleSpecialized($patientsDetails->PatientID);
                    if(!empty($scheduleDetail)) {
                        $names['scheduleSpecialized'] = 1;
                    }
                }
                return response()->json(['status'=>true,'patientsDetails'=>$patientsDetails,'branchDetails'=>$branchDetails,'names'=>$names,'nthAccountId'=>isset($needToHireAccounts->id) ? $needToHireAccounts->id : '']);
            }
        } catch (\Exception $e) {
            H::ExcelLog('getClientDetailsInJSON:'.$e->getMessage(),'nthChecklistDetails.log');
            return response()->json(['status'=>false,'msg'=>$e->getMessage()]);
        }
    }
    /**
     * To save the build schedule to NeedToHire_Account
     *
     * @param Request $request
     * @throws \Exception
     */
    public function saveBuildSchedule(Request $request)
    {
        try {
            $reqData = $request->all();

            $user = H::getLoggedInUser();
            H::ExcelLog('Logged in User(ID):'.$user['name'].'('.$user['id'].')','updateNth.log');
            H::ExcelLog('form data:','updateNth.log');
            H::ExcelLog(print_r($reqData,true),'updateNth.log');

                // Validating the client Id if its already attached to other clients [we already have validation for this at frontend but still some how we are getting duplicated client and account.]
                if(!isset($reqData['aoc_patient_id']) && isset($reqData['clientName']) && isset($reqData['clientId'])){
                    if(LD::checkFeatureFlag('add-client-form-allow-discharged-as-new-client')) {

                        if($reqData['is_discharged_client']) {
                            $patientDetails = DB::table('mssql_patients')
                            ->select('id','Patient_CurrentStatusID',DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) as clientName"),'PatientAccount','Patient_CurrentStatusID')
                            ->where('PatientAccount',$reqData['clientId'])
                            ->where("Patient_CurrentStatusID","<>",5)->first();
                                if(!empty($patientDetails)) {
                                    if($patientDetails->Patient_CurrentStatusID==4) {
                                        H::ExcelLog('This '.$reqData['clientId'].' matches a active Intake record for '.$patientDetails->clientName.'.','updateNth.log');
                                        return response()->json(['status'=>false,'msg'=>'This '.$reqData['clientId'].' matches a active Intake record for '.$patientDetails->clientName.'.']);
                                    }
                                }
                        } else {
                            $patientDetails = DB::table('mssql_patients')->select('id',DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) as clientName"),'PatientAccount','Patient_CurrentStatusID')->where('PatientAccount',$reqData['clientId'])->first();
                        }

                    } else {
                        $patientDetails = DB::table('mssql_patients')->select('id',DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) as clientName"),'PatientAccount','Patient_CurrentStatusID')->where('PatientAccount',$reqData['clientId'])->first();
                    }

                    if(!empty($patientDetails)){
                        H::ExcelLog('This '.$reqData['clientId'].' matches a Discharged record for '.$patientDetails->clientName.'.','updateNth.log');
                        return response()->json(['status'=>false,'msg'=>'This '.$reqData['clientId'].' matches a Discharged record for '.$patientDetails->clientName.'.']);
                    }
                }


                // If Client is not available then need to add on the fly. This might be the temporary to add the client
                if(!isset($reqData['aoc_patient_id']) && isset($reqData['clientName'])){
                    $nameArr = explode(' ',$reqData['clientName'], 2);
                    $clientInsertArr = [
                        'ccc_id' => $reqData['ccc_id'],
                        'Search_Name' => (isset($nameArr[0]) ? $nameArr[0] : '') . ' ' . (isset($nameArr[1]) ? $nameArr[1] : '') . ' ' . (isset($nameArr[0]) ? $nameArr[0] : ''),
                        'PatientName' => $reqData['clientName'],
                        'Patient_FirstName' => isset($nameArr[0]) ? $nameArr[0] : '',
                        'Patient_LastName' => isset($nameArr[1]) ? $nameArr[1] : '',
                        'ic_id' => isset($reqData['ic_id'])?$reqData['ic_id']:'',
                        'aoc_facility_id' => isset($reqData['aoc_facility_id'])?$reqData['aoc_facility_id']:'',
                        'PatientAccount' => isset($reqData['clientId'])?$reqData['clientId']:'',
                        'Patient_Address1' => isset($reqData['address1'])?$reqData['address1']:'',
                        'Patient_Address2' => isset($reqData['address2'])?$reqData['address2']:'',
                        'Patient_City' => isset($reqData['city'])?$reqData['city']:'',
                        'Patient_State' => isset($reqData['state'])?$reqData['state']:'',
                        'Patient_Zip' => isset($reqData['zipCode'])?$reqData['zipCode']:'',
                        'Payer' => isset($reqData['terminalPlayer'])?$reqData['terminalPlayer']:'',
                        'CF_ClinicianDiscipline' => isset($reqData['clinicianDiscipline']) ? implode(',',$reqData['clinicianDiscipline']) : '',
                        'RespitePayer' => isset($reqData['respiteTerminalPlayer']) ? $reqData['respiteTerminalPlayer'] : '',
                        'CF_LiftingRequired' => isset($reqData['liftingRequirements']) ? $reqData['liftingRequirements'] : '',
                        'CF_ServicesNeeded' => isset($reqData['serviceNeeded']) ? $reqData['serviceNeeded'] : '',
                        'CF_Skills' => isset($reqData['skills']) ? implode(',',$reqData['skills']) : '',
                        'CF_Specialized' => isset($reqData['skills']) ? (H::getHelper('NTHChecklist')->isSpecialized(implode(',',$reqData['skills'])) == true ? 'Yes' : 'No') : 'No',
                        'NTHNotes' => isset($reqData['scheduleDescription']) ? $reqData['scheduleDescription'] : '',
                    ];
                    if(LD::checkFeatureFlag('allow-meeting-nurses-edit')) {
                        $clientInsertArr['CF_MeetingNurses'] = isset($reqData['meeting_nurses']) ? $reqData['meeting_nurses'] : '';
                    }
                    if(LD::checkFeatureFlag('add-client-form-allow-discharged-as-new-client')) {

                        if(isset($reqData['is_discharged_client']) && $reqData['is_discharged_client']==1 ) {
                            $clientInsertArr['Patient_CurrentStatusID'] = 4;
                        }

                    }
                    $clientInsertArr['IsLatestAdmit'] = 1;
                    $clientInsertArr['not_in_kantime'] = 1;
                    $reqData['aoc_patient_id'] = MssqlPatients::insertGetId($clientInsertArr);

                    //Adding the notes
                    $clientLabelArr = ['ccc_id' => 'Client Care Coordinator','clientName' => 'Client Name','aoc_facility_id' => 'Branch','PatientAccount' => 'Client ID','Patient_Address1' => 'Address Line 1','Patient_Address2' => 'Address Line 2','Patient_City' => 'City','Patient_State' => 'State','Patient_Zip' => 'Zip Code','Payer' => 'Terminal Payer','CF_ClinicianDiscipline' => 'Clinician Discipline','RespitePayer' => 'Respite Payer','CF_LiftingRequired' => 'Lifting Requirements','CF_ServicesNeeded' => 'Services Needed','CF_Skills' => 'Skills','CF_Specialized' => 'Specialized','NTHNotes' => 'scheduleDescription'];
                    $notesText = '';
                    foreach($clientLabelArr as $k => $v){

                        if($k == 'clientName'){
                            $notesText .= $v .': '. $reqData[$k]  .'<br />';
                        }else if($k == 'aoc_facility_id'){
                            $notesText .= $v .': '.  MssqlFacilities::getFacilitiesAttrById($clientInsertArr[$k],'FacilityName')  .'<br />';
                        }else if($k == 'ccc_id'){
                            $notesText .= $v .': '.  H::getHelper('ConstantHelper')->getUserNameById($clientInsertArr[$k])  .'<br />';
                        }else{
                            $notesText .= $v .': '. $clientInsertArr[$k]  .'<br />';
                        }

                    }
                    H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'client', 'entity_id' => $reqData['aoc_patient_id'],'type'=>'insert', 'notes' => $notesText]);
                }

            if(isset($reqData['aoc_patient_id'])){
                $address = [];
                $recruiterId = 0;
                $recruiter = '';
                //Prepare the array to insert record.
                //if recruiter is passed from form, set the name and ID / otherwise try auto assigning
                if(isset($reqData['recruiter'])){
                    $recruiter = $reqData['recruiter'];
                    $recruiterId = H::getHelper('ConstantHelper')->getUserByName($recruiter);
                }else{
                    if (LD::checkFeatureFlag('auto-assign-recruiter-add-nth-form')) {
                        if(isset($reqData['address1']) && !empty($reqData['address1'])) {
                            $address['address1'] = $reqData['address1'];
                        }
                        if(isset($reqData['address2']) && !empty($reqData['address2'])) {
                            $address['address2'] = $reqData['address2'];
                        }
                        if(isset($reqData['city']) && !empty($reqData['city'])) {
                            $address['city'] = $reqData['city'];
                        }
                        if(isset($reqData['state']) && !empty($reqData['state'])) {
                            $address['state'] = $reqData['state'];
                        }
                        if(isset($reqData['zipcode']) && !empty($reqData['zipcode'])) {
                            $address['zipcode'] = $reqData['zipcode'];
                        }
                        if (!empty($address)) {
                            $recruiterId = H::getHelper('ConstantHelper')->getRecruiterByPolygon($address);
                            if($recruiterId) {
                                $recruiter = H::getHelper('ConstantHelper')->getUserById($recruiterId);
                            }
                        }
                    }
                }

                    $needToHireArray = [
                        'aoc_patient_id' => $reqData['aoc_patient_id'],
                        'ClientID' => $reqData['clientId'],
                        'ServicesNeeded' => isset($reqData['serviceNeeded']) ? $reqData['serviceNeeded'] : '',
                        'skills' => isset($reqData['skills']) ? implode(',',$reqData['skills']) : '',
                        'Status' => $reqData['nthStatus'],
                        'ScheduleText' => isset($reqData['scheduleDescription']) ? $reqData['scheduleDescription'] : '',
                        'IsPriority' => isset($reqData['IsPriority']) && $reqData['IsPriority'] == 'on' ? '1' : '0',
                        'RespiteTerminalPayer' => isset($reqData['respiteTerminalPlayer']) ? $reqData['respiteTerminalPlayer'] : '',
                        'liftingRequirements' => isset($reqData['liftingRequirements']) ? $reqData['liftingRequirements'] : '',
                        'clinicianDiscipline' => isset($reqData['clinicianDiscipline']) ? implode(',',$reqData['clinicianDiscipline']) : '',
                        'TerminalPayer' => isset($reqData['terminalPlayer']) ? $reqData['terminalPlayer'] : '',
                        'IntakeCoordinator' => isset($reqData['ic_id']) ? $reqData['ic_id'] : '',
                        'ClientCareCoordinator' => isset($reqData['ccc_id']) ? $reqData['ccc_id'] : '',
                        'created_by' => Auth::user()->id,
                        'updated_by' => Auth::user()->id
                    ];

                if(isset($reqData['recruiter']) || $recruiterId!=0 ){
                    H::ExcelLog('setting recruiter','updateNth.log');
                    $needToHireArray['Recruiter'] = $recruiter;
                    $needToHireArray['user_id'] = $recruiterId;
                }
                //Updating the Patient with CCC and IC
                if(LD::checkFeatureFlag('allow-meeting-nurses-edit')) {
                    if(!empty($reqData['meeting_nurses'])){
                        H::getHelper("NTHChecklist")->addNotificationWhenPreferencesChange($reqData);
                    }
                    MssqlPatients::where('id',$reqData['aoc_patient_id'])->update([
                        'ccc_id' => isset($reqData['ccc_id']) ? $reqData['ccc_id'] : '',
                        'ic_id' => isset($reqData['ic_id']) ? $reqData['ic_id'] : '',
                        'CF_ServicesNeeded' => isset($reqData['serviceNeeded']) ? $reqData['serviceNeeded'] : '',
                        'CF_MeetingNurses' => isset($reqData['meeting_nurses']) ? $reqData['meeting_nurses'] : '',
                        'CF_LiftingRequired' => isset($reqData['liftingRequirements']) ? $reqData['liftingRequirements'] : '',
                        'CF_Skills' => isset($reqData['skills']) ? implode(',',$reqData['skills']) : '',
                        'CF_ClinicianDiscipline' => isset($reqData['clinicianDiscipline']) ? implode(',',$reqData['clinicianDiscipline']) : ''
                    ]);
                    if(LD::checkFeatureFlag('add-medicaid-number-on-add-nth-form')) {
                        MssqlPatients::where('id',$reqData['aoc_patient_id'])->update([
                            'ccc_id' => isset($reqData['ccc_id']) ? $reqData['ccc_id'] : '',
                            'ic_id' => isset($reqData['ic_id']) ? $reqData['ic_id'] : '',
                            'CF_ServicesNeeded' => isset($reqData['serviceNeeded']) ? $reqData['serviceNeeded'] : '',
                            'CF_MeetingNurses' => isset($reqData['meeting_nurses']) ? $reqData['meeting_nurses'] : '',
                            'CF_LiftingRequired' => isset($reqData['liftingRequirements']) ? $reqData['liftingRequirements'] : '',
                            'CF_Skills' => isset($reqData['skills']) ? implode(',',$reqData['skills']) : '',
                            'CF_ClinicianDiscipline' => isset($reqData['clinicianDiscipline']) ? implode(',',$reqData['clinicianDiscipline']) : '',
                            'MedicaidNumber' => isset($reqData['MedicaidNumber']) ? $reqData['MedicaidNumber'] : '',
                        ]);
                    } else {
                        MssqlPatients::where('id',$reqData['aoc_patient_id'])->update([
                            'ccc_id' => isset($reqData['ccc_id']) ? $reqData['ccc_id'] : '',
                            'ic_id' => isset($reqData['ic_id']) ? $reqData['ic_id'] : '',
                            'CF_ServicesNeeded' => isset($reqData['serviceNeeded']) ? $reqData['serviceNeeded'] : '',
                            'CF_MeetingNurses' => isset($reqData['meeting_nurses']) ? $reqData['meeting_nurses'] : '',
                            'CF_LiftingRequired' => isset($reqData['liftingRequirements']) ? $reqData['liftingRequirements'] : '',
                            'CF_Skills' => isset($reqData['skills']) ? implode(',',$reqData['skills']) : '',
                            'CF_ClinicianDiscipline' => isset($reqData['clinicianDiscipline']) ? implode(',',$reqData['clinicianDiscipline']) : ''
                        ]);
                    }
                } else {
                    MssqlPatients::where('id',$reqData['aoc_patient_id'])->update([
                        'ccc_id' => isset($reqData['ccc_id']) ? $reqData['ccc_id'] : '',
                        'ic_id' => isset($reqData['ic_id']) ? $reqData['ic_id'] : ''
                    ]);
                }

                $updatePatientFieldData = [];
                if (!empty($reqData['clientId'])) {
                    $updatePatientFieldData['PatientAccount'] = $reqData['clientId'];
                }
                if (!empty($reqData['aoc_facility_id'])) {
                    $updatePatientFieldData['aoc_facility_id'] = $reqData['aoc_facility_id'];
                }
                if (!empty($reqData['terminalPlayer'])) {
                    $updatePatientFieldData['Payer'] = $reqData['terminalPlayer'];
                }
                if (!empty($reqData['respiteTerminalPlayer'])) {
                    $updatePatientFieldData['RespitePayer'] = $reqData['respiteTerminalPlayer'];
                }
                if (count($updatePatientFieldData) > 0) {
                    MssqlPatients::where('id', $reqData['aoc_patient_id'])->update($updatePatientFieldData);
                }

                if(LD::checkFeatureFlag('add-relocating-address-for-client-nth')){
                    $needToHireArray['clientIsRelocating'] = 0;
                    $needToHireArray['relocatingAddress1'] =
                    $needToHireArray['relocatingAddress2'] =
                    $needToHireArray['relocatingCity'] =
                    $needToHireArray['relocatingState'] =
                    $needToHireArray['relocatingZipCode'] = null;
                    if(isset($reqData['clientIsRelocating']) && $reqData['clientIsRelocating'] == 'on'){
                        $needToHireArray['clientIsRelocating'] = 1;
                        $needToHireArray['relocatingAddress1'] = $reqData['relocatingAddress1'];
                        $needToHireArray['relocatingAddress2'] = $reqData['relocatingAddress2'];
                        $needToHireArray['relocatingCity'] = $reqData['relocatingCity'];
                        $needToHireArray['relocatingState'] = $reqData['relocatingState'];
                        $needToHireArray['relocatingZipCode'] = $reqData['relocatingZipCode'];
                    }
                }
                if(LD::checkFeatureFlag('add-client-changing-payer-client-nth')){
                        $needToHireArray['isClientChangingPayer'] = 0;
                        $needToHireArray['newTerminalPayer'] =
                        $needToHireArray['payerEffectiveDates'] =
                        $needToHireArray['newRespiteTerminalPayer'] = null;
                    if(isset($reqData['isClientChangingPayer']) && $reqData['isClientChangingPayer'] == 'on'){
                        $needToHireArray['isClientChangingPayer'] = 1;
                        $needToHireArray['newTerminalPayer'] = $reqData['newTerminalPayer'];
                        $needToHireArray['payerEffectiveDates'] = date('Y-m-d', strtotime($reqData['payerEffectiveDates']));
                        $needToHireArray['newRespiteTerminalPayer'] = $reqData['newRespiteTerminalPayer'];
                    }
                }
                if(LD::checkFeatureFlag('add-pay-range-fields-nth-form')) {
                    if(H::canPermissions('nthFormFieldsForNthCard')){
                        $needToHireArray['pay_range_rn'] = '$'.$reqData['rnStart'].' - $'.$reqData['rnEnd'];
                        $needToHireArray['pay_range_lvn'] = '$'.$reqData['lvnStart'].' - $'.$reqData['lvnEnd'];
                    }
                }
                if(isset($reqData['needToHireAccountsId'])){
                    $needToHireArray['IsActive'] = isset($reqData['IsActive']) && $reqData['IsActive'] == 'on' ? 1 : 0;
                    H::ExcelLog('update data:','updateNth.log');
                    H::ExcelLog(print_r($needToHireArray,true),'updateNth.log');
                    NeedToHireAccounts::where('id',$reqData['needToHireAccountsId'])->update($needToHireArray);
                    $nthAccountId = $reqData['needToHireAccountsId'];
                    H::getHelper('NTHChecklist')->AddEditNTHMapRecord($nthAccountId,'edit');
                    H::getHelper('NTHChecklist')->setClientAccountStatus($nthAccountId);
                }else{
                    $needToHireArray['IsActive'] = 0;
                    $nthAccountId = NeedToHireAccounts::insertGetId($needToHireArray);
                    if($nthAccountId != ''){
                        NeedToHireChecklist::insert(['CurrentStep'=>1,'TextBlastStarted'=>date('Y-m-d H:i:s'),'aoc_nthaccounts_id'=>$nthAccountId,'created_by' => Auth::user()->id,'updated_by' => Auth::user()->id]);
                        H::getHelper('NTHChecklist')->AddEditNTHMapRecord($nthAccountId);
                    }
                }

                //Inserting the record into the audit
                if($nthAccountId != ''){
                    $needToHireArray['aoc_nthaccounts_id'] = $nthAccountId;
                    $nthAccountAuditId = NeedToHireAccountsAudit::insertGetId($needToHireArray);
                    //Added activity history
                    $notes = H::getLoggedInUser()['name'].' changed NTH record.';
                    $notesData = ['entity' => 'NeedToHire', 'entity_id' => $nthAccountId, 'notes' =>$notes, 'completed' => 1,'misc1' => $nthAccountAuditId];
                    H::getHelper('ActivityNotes')->setActivityNotes($notesData);
                }

                return response()->json(['status'=>true,'aoc_nthaccounts_id'=>$nthAccountId]);
            }else{
                return response()->json(['status'=>false,'msg'=>'patient is not defined']);
            }
        } catch (\Exception $e) {
            H::ExcelLog('saveBuildSchedule:'.$e->getMessage(),'nthChecklistDetails.log');
            return response()->json(['status'=>false,'msg'=>$e->getMessage()]);
        }
    }

    public function exportNthData(Request $request) {
        $scheduledFromDate = LD::checkFeatureFlag('update-nth-export-column-values') ? 'CURRENT_DATE' : 'now()';

        $dataTableFields = [
            "MYSQLFUNCONCAT(p.Patient_FirstName,\" \", p.Patient_LastName) as Client" => 'Client',
            'p.PatientAccount' => 'ClientID',
            'f.FacilityName' => 'Location',
            'ntha.Status' => 'NTHStatus',
            "MYSQLFUN(select u.name from users as u where u.id = p.ccc_id) as CCC" => 'Client Care Coordinator',
            'ntha.CreatedReason' => 'CreatedReason',
            "MYSQLFUNDATEDIFF(CURDATE(), ntha.created_at) as DaysSinceCreated" => 'Days Since Created',
            'ntha.created_at' => 'CreatedOn',
            "ntha.Recruiter" => 'Recruiter',
            'm.Schedule' => 'Schedule Text',
            'm.Schedule_Details' => 'ScheduleDetails',
            //'' => 'Schedule',
            'ntha.ScheduleText' => 'Schedule Free Text',
            "MYSQLFUN(select sum(ShiftHours) from NeedToHire_Schedules where NeedToHire_Schedules.IsActive = 1 and NeedToHire_Schedules.aoc_nthaccounts_id = ntha.id) as hoursonNTH" => 'hours on NTH',
            "MYSQLFUNIFNULL(p.AuthorizedWeeklyHours, 0) as TotalAuthorizedPDNHours" => 'Total Authorized PDN Hours',
            "MYSQLFUNIFNULL(p.CF_RespWeeklyHrs, 0) as TotalAuthorizedRespiteHours" => 'Total Authorized Respite Hours',
            "MYSQLFUNIFNULL(p.CNAAuthorizedWeeklyHours, 0) as TotalAuthorizedCNAHours" => 'Total Authorized CNA Hours',
            "MYSQLFUNDATE_FORMAT(FROM_DAYS(DATEDIFF(now(), p.Patient_DOB)), '%Y')+0 as Age" => 'Age',
            "MYSQLFUNIF(p.AuthorizedWeeklyHours > 0, (p.AuthorizedWeeklyHours - (select sum(ShiftHours) from NeedToHire_Schedules where NeedToHire_Schedules.IsActive = 1 and NeedToHire_Schedules.aoc_nthaccounts_id = ntha.id)) / p.AuthorizedWeeklyHours, 0) AS PermStaffing" => 'Perm Staffing %',
            "MYSQLFUNCOALESCE(p.Patient_Gender,\"UNKNOWN\") as Gender" => 'Gender',
            'p.Biography' => 'Biography',
            'p.CF_TheSale' => 'The Sale',
            "MYSQLFUNCASE WHEN p.PreferenceList LIKE \"%Nurse Attending Child%\" THEN 'Yes' ELSE 'No' END  as AttendingSchool" => 'Attending School',
            'p.Languages' => 'Language',
            'p.CF_NurseLanguage' => 'Nurse Language',
            'p.PetsinHome' => 'Pets In Home',
            'p.PetsNurseCannotOwn' => 'Pets Nurse Can not Own',
            'p.CF_Weight' => 'Weight',
            'ntha.Liftingrequirements' => 'Lifting Requirements',
            'MYSQLFUNIF(m.SpecializedclientBIT = 1, \'Yes\', \'No\') as SpecializedclientBIT' => 'Specialized Client',
            'ntha.Skills' => 'Skills',
            'ntha.ServicesNeeded' => 'Services Needed',
            'm.Clinician_Gender' => 'Clinician Gender',
            'ntha.Cliniciandiscipline' => 'Clinician Discipline',
            'p.Payer' => 'Payer',
            'p.payer_group' => 'Special Payer Group',
            'p.RespitePayer' => 'RespitePayer',
            "MYSQLFUNCONCAT(p.Patient_Address1,\" \", p.Patient_Address2) as Address" => 'Address',
            'p.Patient_City' => 'City',
            'p.Patient_State' => 'State',
            'p.Patient_Zip' => 'Zipcode',
            "MYSQLFUNCOALESCE(p.Patient_Phone,p.Patient_MobilPhone) as Phone" => 'Phone',
            'p.IntakeStatus' => 'Intake Status',
            'p.IntakeDate' => 'Intake Date',
            'p.CF_GoalSOCDate' => 'SOC Date',
            'p.ReferralDate' => 'Referral Date',
            "MYSQLFUN(select u.name from users as u where u.id = p.ic_id) as IntakeCoordinator" => 'Intake Coordinator',
            'MYSQLFUN(select u.name from users as u where u.id = p.bd_id)' => 'Business Developer',
            'p.CF_MeetingNurses' => 'Meeting Nurses',
            'p.Patient_DOB' => 'Patient DOB',
            'p.CF_WorkForceDevProj' => 'WorkForce DevProj',
            'MYSQLFUNIF(ntha.IsPriority = 1, \'Yes\', \'No\') as IsPriority' => 'Is Priority',
            'MYSQLFUNIF(p.is_intake = 1, \'Yes\', \'No\') as IsIntake' => 'Is Intake',
            'p.NTHNotes' => 'NTHNotes',
            "MYSQLFUNCASE WHEN p.PreferenceList LIKE \"%Nurses must be COVID-19 vaccin%\" THEN 'Yes' ELSE 'No' END as Covid19VaccineRequired" => 'Covid19 Vaccine Required',
            "MYSQLFUNCASE WHEN p.PreferenceList LIKE \"%Media Consent Form on File%\" THEN 'Yes' ELSE 'No' END as MediaConsent" => 'Media Consent',
            "MYSQLFUNCASE WHEN p.PreferenceList LIKE \"%Client prefers non-smoking%\" THEN 'Yes' ELSE 'No' END as NonSmokingNurses" => 'NonSmoking Nurses',
            "MYSQLFUNCASE WHEN p.PreferenceList LIKE \"%Smokers Live in Home%\" THEN 'Yes' ELSE 'No' END as SmokersLiveInHome" => 'Smokers LiveIn Home',
            "MYSQLFUN(select u.name from users as u where u.id = ntha.created_by) as CreatedBy" => 'CreatedBy',
            'm.ChecklistBit' => 'Checklist Bit',
            'p.SchoolDistrict' => 'School District',
            'p.SchoolName' => 'School Name',
            'MYSQLFUNIF(p.OrdersReceived = 1, \'Yes\', \'No\') as OrdersReceived' => 'Orders Received',
            "MYSQLFUN(select IFNULL(count(*),0) from recruiting_schedule where recruiting_schedule.aoc_patient_id = p.id AND recruiting_schedule.type_id = 1 AND recruiting_schedule.appointment_date >= {$scheduledFromDate}) as IPEACOUNT" => 'Count of IPEAs Schedules',
            "MYSQLFUN(select IFNULL(sum(mssql_recruitertracker.Total_Hours), 0) from recruiting_schedule left join mssql_recruitertracker on recruiting_schedule.tracker_id = mssql_recruitertracker.id where recruiting_schedule.aoc_patient_id = p.id and recruiting_schedule.type_id = 1 and recruiting_schedule.appointment_date >= {$scheduledFromDate}) as IPEAHOURS" => 'Hours for IPEAs Scheduled',
            "MYSQLFUN(select IFNULL(count(*),0) from recruiting_schedule where recruiting_schedule.aoc_patient_id = p.id AND recruiting_schedule.type_id = 3 AND recruiting_schedule.appointment_date >= {$scheduledFromDate}) as PEPICOUNT" => 'Count of PEPIs Schedules',
            "MYSQLFUN(select IFNULL(sum(mssql_recruitertracker.Total_Hours), 0) from recruiting_schedule left join mssql_recruitertracker on recruiting_schedule.tracker_id = mssql_recruitertracker.id where recruiting_schedule.aoc_patient_id = p.id and recruiting_schedule.type_id = 3 and recruiting_schedule.appointment_date >= {$scheduledFromDate}) as PEPIHOURS" => 'Hours for PEPIs Scheduled',
            "MYSQLFUN(select IFNULL(count(*),0) from recruiting_schedule where recruiting_schedule.aoc_patient_id = p.id AND recruiting_schedule.type_id = 4 AND recruiting_schedule.appointment_date >= {$scheduledFromDate}) as WEPICOUNT" => 'Count of WEPIs Schedules',
            "MYSQLFUN(select IFNULL(sum(mssql_recruitertracker.Total_Hours), 0) from recruiting_schedule left join mssql_recruitertracker on recruiting_schedule.tracker_id = mssql_recruitertracker.id where recruiting_schedule.aoc_patient_id = p.id and recruiting_schedule.type_id = 4 and recruiting_schedule.appointment_date >= {$scheduledFromDate}) as WEPIHOURS" => 'Hours for WEPIs Scheduled',
            "MYSQLFUN(select IFNULL(count(*),0) from recruiting_schedule where recruiting_schedule.aoc_patient_id = p.id AND recruiting_schedule.type_id = 6 AND recruiting_schedule.appointment_date >= {$scheduledFromDate}) as CMCOUNT" => 'Count of CM Scheduled',
            "MYSQLFUN(select IFNULL(sum(mssql_recruitertracker.Total_Hours), 0) from recruiting_schedule left join mssql_recruitertracker on recruiting_schedule.tracker_id = mssql_recruitertracker.id where recruiting_schedule.aoc_patient_id = p.id and recruiting_schedule.type_id = 6 and recruiting_schedule.appointment_date >= {$scheduledFromDate}) as CMHOURS" => 'Hours for CM Scheduled',
        ];

        $newKey = "MYSQLFUNCASE WHEN p.hh_chartid IS NOT NULL AND p.hh_chartid <> '' THEN 'Yes' ELSE 'No' END AS `Health Home Services`";
        $newValue = 'Health Home Services';
        $index = array_search('ntha.ServicesNeeded', array_keys($dataTableFields));
        if ($index !== false) {
            $dataTableFields = array_merge(
                array_slice($dataTableFields, 0, $index + 1, true),
                [$newKey => $newValue],
                array_slice($dataTableFields, $index + 1, null, true)
            );
        }
        $dataTableFields['nthc.TextBlastEscalatedRate'] = 'The last escalated rate';
        $dataTableFields['MYSQLFUNCONCAT(nthc.TextBlastEscalatedMargin,"%") as TextBlastEscalatedMargin'] = 'The last escalated margin';
        $dataTableFields['MYSQLFUN(select created_at from notes where notes.misc1 = "TextBlastEscalatedRate" AND notes.entity_id = nthc.id AND notes.entity = "NeedToHire" order by created_at desc limit 1) as TextBlastEscalatedDate'] = 'Last Escalation';
        $dataTableFields['nthc.TextBlastContent'] = 'TextBlastContent';
        $dataTableFields['ntha.manager_comments'] = 'Manager Comments';
        $dataTableFields['ntha.newTerminalPayer'] = 'New Terminal Payer';
        $dataTableFields['ntha.newRespiteTerminalPayer'] = 'New Respite Terminal Payer';
        $dataTableFields['ntha.payerEffectiveDates'] = 'Payer Effective Dates';
        $dataTableFields['m.CNA_Auth_Hrs'] = 'CNA Auth Hours';
        $dataTableFields['m.intake'] = 'Intake';
        $dataTableFields['m.non_vbc'] = 'Non VBC';
        $dataTableFields['m.Notes'] = 'Notes';
        $dataTableFields['m.NTH_Hours'] = 'NTH Hours';
        $dataTableFields['m.NTH_Schedule'] = 'NTH Schedule';
        $dataTableFields['m.PDN_Auth_Hrs'] = 'PDN Auth Hours';
        $dataTableFields['m.Respite_Auth_Hrs'] = 'Respite Auth Hours';
        $dataTableFields['m.Schedule'] = 'Schedule';
        $dataTableFields['m.Schedule_Details'] = 'Schedule Details';
        $dataTableFields['m.Type_of_Shift'] = 'Type of Shift';
        $dataTableFields["MYSQLFUN(SELECT email
        FROM `client_emergency_contacts`
        WHERE client_id = p.BillingInternalPatientID
        AND priority = 'Primary'
        LIMIT 1
    ) as parent_email"] = 'Parent Email';
        $dataTableFields['MYSQLFUN(IF(IFNULL(nthc.HUBcity,0) = 1, "Yes", "No")) as adOpened'] = 'Recruiting Ad Opened?';
        $dataTableFields['MYSQLFUN((COALESCE(nthc.TextBlastINT,0) + COALESCE(nthc.ColdCallINT, 0))) as
        aocEmployeesContacted'] = 'Number of AOC Employees Contacted';
        $dataTableFields['MYSQLFUN(COALESCE(nthc.PETINT,0)) as
        aocCandidatesContacted'] = 'Number of AOC Candidates Contacted';
        $dataTableFields['MYSQLFUN((COALESCE(nthc.SocialMediaINT,0) + COALESCE(nthc.PrivateMessageINT, 0) +
        COALESCE(nthc.LinkedININT, 0) + COALESCE(nthc.IndeedINT, 0) + COALESCE(nthc.BONNursePMINT, 0))) as
        cliniciansContactedSMR'] = 'Number of Clinicians Contacted via Social Media Recruiting';
        $dataTableFields['MYSQLFUN(COALESCE(nthc.SnailMailINT,0)) as
        postcardsSent'] = 'Number of Letters and/or Postcards Sent';
        $dataTableFields['MYSQLFUN(IF(IFNULL(nthc.SocialMediaSponsored,0) = 1, "Yes", "No")) as
        SocialMediaAd'] = 'Social Media Ad on the AOC Facebook Page';
        $dataTableFields['MYSQLFUN(GREATEST(
        IFNULL(DATE(nthc.HUBcityWorked), \'0000-00-00\'),
        IFNULL(DATE(nthc.TextBlastWorked), \'0000-00-00\'),
        IFNULL(DATE(nthc.PETWorked), \'0000-00-00\'),
        IFNULL(DATE(nthc.SocialMediaWorked), \'0000-00-00\'),
        IFNULL(DATE(nthc.PrivateMessageWorked), \'0000-00-00\'),
        IFNULL(DATE(nthc.LinkedINWorked), \'0000-00-00\'),
        IFNULL(DATE(nthc.IndeedWorked), \'0000-00-00\'),
        IFNULL(DATE(nthc.ColdCallWorked), \'0000-00-00\'),
        IFNULL(DATE(nthc.SnailMailWorked), \'0000-00-00\'),
        IFNULL(DATE(nthc.BONNursePMWorked), \'0000-00-00\'),
        IFNULL(DATE(nthc.SocialMediaSponsoredWorked), \'0000-00-00\'),
        IFNULL(DATE(nthc.EscalatedWorked), \'0000-00-00\'))) as
        lastActivityDate'] = 'Last Activity Date';

        $dataTableFields["MYSQLFUN(IF(((IFNULL(p.not_in_kantime, 0) = 1) OR (IFNULL(p.aoc_cf_acquisition, 0) = 1)), 'Yes', 'No')) as acquisition"] = 'Acquisition';
        $q = DB::table('mssql_patients as p')
            ->select(array_map(function($v) { return (strpos($v, "MYSQLFUN") !== false) ? DB::raw(str_replace("MYSQLFUN","",$v)) : $v; }, array_keys($dataTableFields)))
            ->leftJoin('NeedToHire_Accounts as ntha', 'p.id', '=', 'ntha.aoc_patient_id')
            ->leftJoin('NeedToHire_Checklist as nthc', 'ntha.id', '=', 'nthc.aoc_nthaccounts_id')
            ->leftJoin('mssql_facilities as f', 'f.id', '=', 'p.aoc_facility_id')
            ->leftJoin('maps_nth as m', 'ntha.id', '=', 'm.aoc_nthaccounts_id')
            ->whereNotNull('nthc.id')->whereNotNull('ntha.id')->where('ntha.IsActive', 1);

        $fieldName = [];

        if (!empty($request->cf_ids) && is_array($request->cf_ids)) {
            $customFields = $this->customFieldsReadRepository->getCustomFieldsByIds($request->cf_ids);

            foreach ($customFields as $cf) {
                $selectKey = "p.`{$cf->db_field_name}`";

                if (!array_key_exists($selectKey, $dataTableFields)) {
                    $dataTableFields[$selectKey] = $cf->field_name;
                    $fieldName[] = DB::raw($selectKey);
                }
            }
        }

        if ($fieldName) {
            $q->addSelect($fieldName);
        }

        $search = $request->search['value'];
        if (isset($search) && $search != '') {
            $q->where(function ($query) use ($search) {
                $query->where('p.Search_Name', 'LIKE', "%{$search}%");
                $query->orWhere('p.PatientAccount', 'LIKE', "%{$search}%");
                $query->orWhere('ntha.Recruiter', 'LIKE', "%{$search}%");
                $query->orWhere('f.FacilityName', 'LIKE', "%{$search}%");
            });
        }

        $filters = [];
        if(isset($request['filters'])) {
            foreach($request['filters'] as $fl) {
                if(!empty($fl['value'])) {
                    $filters[$fl['name']][] = $fl['value'];
                }
            }
            foreach($filters as $fkey => $fval) {
                if($fkey=='state') {
                    $q->where(function($query) use ($fval) {
                        foreach($fval as $val) {
                            $query->orWhere("p.Patient_State",'=',$val);
                        }
                    });
                }elseif($fkey=='aoc_facility_id') {
                    $q->where(function($query) use ($fval) {
                        foreach($fval as $val) {
                            $query->orWhere("p.aoc_facility_id",'=',$val);
                        }
                    });
                }elseif($fkey=='IntakeCoordinator') {
                    $q->where(function($query) use ($fval) {
                        foreach($fval as $val) {
                            $query->orWhere("p.ic_id",'=',$val);
                        }
                    });
                }elseif($fkey=='ClientCareCoordinator') {
                    $q->where(function($query) use ($fval) {
                        foreach($fval as $val) {
                            $query->orWhere("p.ccc_id",'=',$val);
                        }
                    });
                }elseif($fkey=='recruiter') {
                    $q->where(function($query) use ($fval) {
                        foreach($fval as $val) {
                            $query->orWhere("ntha.Recruiter",'=',$val);
                        }
                    });
                }elseif($fkey=='IsPriority') {
                    $q->where('ntha.IsPriority', 1);
                }elseif($fkey=='is_intake') {
                    $q->where('p.is_intake', 1);
                }elseif($fkey=='meetingNurses') {
                    $q->where(function($query) use ($fval) {
                        foreach($fval as $val) {
                            $query->orWhere("p.CF_MeetingNurses",'=',$val);
                        }
                    });
                }elseif($fkey=='nth-payer'){
                    $q->where(function($query) use ($fval) {
                        foreach($fval as $val) {
                            $query->orWhere("p.Payer",'=',$val);
                        }
                    });
                }elseif($fkey=='payer_group'){
                    $q->where(function($query) use ($fval) {
                        foreach($fval as $val) {
                            $query->orWhere("p.payer_group",'=',$val);
                        }
                    });
                }

                if($fkey=='acquisiton') {
                    $q->whereRaw('((IFNULL(p.not_in_kantime, 0) = 1) OR (IFNULL(p.aoc_cf_acquisition, 0) = 1)) = ' . $fval[0]);
                }

                if (LD::checkFeatureFlag('add-me-mode-client-nth-list-page')) {
                    if ($fkey == 'default') {
                        $q->where(function($query) {
                            $query->orWhere("p.ccc_id", '=', Auth::user()->id);
                            $query->orWhere("p.ccm_id", '=', Auth::user()->id);
                            $query->orWhere("ntha.user_id", '=', Auth::user()->id);
                        });
                    }
                }
            }
        }

        $data = json_decode(json_encode($q->get()->toArray()), true);

        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=NthData.csv",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];


        $callback = function () use ($data, $dataTableFields) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_values($dataTableFields));

            foreach ($data as $tableData) {
                fputcsv($file, $tableData);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }
    /**
     * getTerminalPayer
     * function to autocomplete terminal payer when adding a NTH account
     *
     */
    public function getTerminalPayer(Request $request)
    {
        $term = str_replace('  ', ' ', str_replace(',', '', $request->term));
        $payerList = DB::table('payment_source')
        ->select('name')
        ->where('name','like','%'.$term.'%')
        ->where('name','not like','%EVV%')
        ->where('name','not like','%Therapy%')
        ->whereRaw('IFNULL(is_deleted, 0) <> 1')
        ->limit(15)
        ->orderBy('name')
        ->get()->toArray();
        $payerListData = [];
        foreach ($payerList as $payerSubData) {
            $payerListData[] = ['payer'=>$payerSubData->name, 'label' => $payerSubData->name];
        }
        return response()->json($payerListData);
    }
    /**
     * getRespiteTerminalPayer
     * function to autocomplete terminal payer when adding a NTH account
     *
     */
    public function getRespiteTerminalPayer(Request $request)
    {
        $term = str_replace('  ', ' ', str_replace(',', '', $request->term));
        $payerList = MssqlPatients::distinct()->select('RespitePayer')
        ->where('RespitePayer','like','%'.$term.'%')
        ->where('RespitePayer','not like','%EVV%')
        ->where('RespitePayer','not like','%Therapy%')
        ->limit(15)
        ->orderBy('RespitePayer')
        ->get()->toArray();
        $payerListData = [];
        foreach ($payerList as $payerSubData) {
            $payerListData[] = ['payer'=>$payerSubData['RespitePayer'],'label' => $payerSubData['RespitePayer']];
        }
        return response()->json($payerListData);
    }

    public function blankBuildScheduleNotification(Request $request)
    {
        try {
            if ($request->input('id') != '') {
                $aoc_nthaccounts_id = $request->input('id');
                $nthScheduleTable = DB::table("NeedToHire_Schedules")->select("*")->where("IsActive",1)->where("aoc_nthaccounts_id",$aoc_nthaccounts_id)->get();
                $nthScheduleTableArray = json_decode(json_encode($nthScheduleTable), true );
                $totalShiftHours = array_sum(array_column($nthScheduleTableArray, 'ShiftHours'));

                if($totalShiftHours == 0) {
                    $nthTrackerSchedule =  DB::table('NeedToHire_Accounts')->select(DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS ClientNameCustom"));
                    $nthTrackerSchedule->where('NeedToHire_Accounts.id', '=', $aoc_nthaccounts_id);
                    $nthTrackerSchedule->leftJoin("mssql_patients",function($query){
                        $query->on("mssql_patients.id","=","NeedToHire_Accounts.aoc_patient_id");
                    });
                    $nthTrackerScheduleData = $nthTrackerSchedule->get()->toArray();

                    $data2['user_id'] = 15;
                    $data2['title'] = 'A NTH ticket was created for '.$nthTrackerScheduleData[0]->ClientNameCustom.' but no schedules were added.  The ticket was set to inactive.';
                    $data2['type'] = 'NeedToHire';
                    $data2['related_item'] = $aoc_nthaccounts_id;
                    $data2['notes'] = '';
                    Notifications::addNotification($data2);

                    $data2['user_id'] = Auth::user()->id;
                    $data2['title'] = 'A NTH ticket was created for '.$nthTrackerScheduleData[0]->ClientNameCustom.' but no schedules were added.  The ticket was set to inactive.';
                    $data2['type'] = 'NeedToHire';
                    $data2['related_item'] = $aoc_nthaccounts_id;
                    $data2['notes'] = '';
                    Notifications::addNotification($data2);
                }
                return response()->json(['status'=>true]);
            }
        } catch (\Exception $e) {
            return response()->json(['status'=>false,'msg'=>$e->getMessage()]);
        }
    }

    /**
     * @param NeedToHireScheduleByClientIdRequest $request
     * @return array
     * @throws \Exception
     */
    public function getNthScheduleByClientId(NeedToHireScheduleByClientIdRequest $request): array
    {
        $aocPatient = $request->toDTO()->getClientId();
        if ($aocPatient != 0 && $aocPatient != '') {
            $schedules = H::getHelper('RecruiterTrackerHelper')->getnthschedulebyclientid($aocPatient);
            $schedules = collect($schedules)->map(function ($item) {
                return (object) $item;
            });

            if (LD::checkFeatureFlag("clinician-form-auto-populate-hours-from-schedule")) {
                return ScheduleWithHoursResource::collection($schedules)->toArray($request);
            }
        }

        return ScheduleBasicResource::collection($schedules)->toArray($request);
    }
}
