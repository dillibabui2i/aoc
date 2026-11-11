<?php

namespace App\Helpers;

use App\Candidates as Candidates;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\NeedToHireAccounts;
use App\MssqlPatients;
use Illuminate\Support\Facades\Auth;
use App\Helpers\LD;
use App\Helpers\Notifications;
use App\Services\NeedToHire\SocialMediaAdAutoCloseService;

class NTHChecklist
{
    public $buckets = [
    "hub_city_requisition"=>["start"=>"HUBcityStarted","work"=>"HUBcityWorked","heading"=>"Hub City Requisition","subheading"=>"Confirm HUB city ad is open"],
    'text_blast'=>["number"=>"TextBlastINT","start"=>"TextBlastStarted","work"=>"TextBlastWorked","heading"=>"Text Blast","subheading"=>"Texted and emailed all nurses on text blast map and potential nurse list map"],
    'pet'=>["number"=>"PETINT","start"=>"PETStarted","work"=>"PETWorked","heading"=>'P.E.T',"subheading"=>'P.E.T. all candidates within open and closed requisitions'],
    'social_media'=>["number"=>"SocialMediaINT","start"=>"SocialMediaStarted","work"=>"SocialMediaWorked","heading"=>'Social Media',"subheading"=>'Join and post in FB groups in the clients city, surrounding cities and county & graduating nurses from prior years closed groups'],
    'private_message'=>["number"=>"PrivateMessageINT","start"=>"PrivateMessageStarted","work"=>"PrivateMessageWorked","heading"=>'Private Message',"subheading"=>'Private message nurses that I am connected with, that are in closed FB groups or have commented on competition pages'],
    'linkedin'=>["number"=>'LinkedININT',"start"=>"LinkedINStarted","work"=>"LinkedINWorked","heading"=>'Linkedin',"subheading"=>'Find and connect with and message nurses on LinkedIn'],
    'indeed'=>["number"=>"IndeedINT","start"=>"IndeedStarted","work"=>"IndeedWorked","heading"=>"Indeed","subheading"=>'Use Indeed resume search to headhunt nurses'],
    'cold_call'=>["number"=>"ColdCallINT","start"=>"ColdCallStarted","work"=>"ColdCallWorked","heading"=>"Cold Call","subheading"=>'Cold call nurses that are in texting and cold calling maps'],
    'mail_matters'=>["number"=>"SnailMailINT","start"=>"SnailMailStarted","work"=>"SnailMailWorked","heading"=>'Mail Matters',"subheading"=>'Mail out handwritten letters or postcards using the BON list'],
    'bon_nurse_search'=>["number"=>"BONNursePMINT","start"=>"BONNursePMStarted","work"=>"BONNursePMWorked","heading"=>'Bon Nurse Search',"subheading"=>'Search for BON nurses on Facebook and LinkedIn and private message them'],
    'social_media_sponsored'=>["number"=>"SocialMediaSponsored","start"=>"SocialMediaSponsoredStarted","work"=>"SocialMediaSponsoredWorked","heading"=>'Social Media Sponsored',"subheading"=>'Social Media Ad Requested'],
    'escaleted'=>["number"=>"Escalated","start"=>"EscalatedStarted","work"=>"EscalatedWorked","heading"=>'Escalated',"subheading"=>'Escalated to Team Lead and Manager']];


    public function getBuckets(){
        return $this->buckets;
    }
    public function getFieldNameBuckets(){
        $fieldNameBuckets = [
            'text_blast' => "TextBlast", 'pet' => 'PET', 'social_media' => 'SocialMedia',
            'private_message' => 'PrivateMessage', 'linkedin' => 'LinkedIN', 'indeed' => "Indeed", 'cold_call' => "ColdCall", 'mail_matters' => 'SnailMail',
            'bon_nurse_search' => 'BONNursePM','social_media_sponsored' => 'SocialMediaSponsored', 'escaleted' => 'Escalated'
        ];
        return $fieldNameBuckets;
    }
    /**
     * The function return bucket title
     *
     * @param string    $type
     * @author Pradip
     * @return string
     */
    public function getBucketTitle($type) {
        return $this->buckets[$type]['heading'];
    }

    public function getDataTableHtml($type,$label)
    {
        $dataTable = [];
        if(LD::checkFeatureFlag('add-date-field-into-social-media-sponsored-bucket')){
            switch($label) {
                case "Social Media Sponsored":
                    $dataTable = ['select'=>'select','ClientNameCustom'=>'Client Name','Recruiter'=>'Recruiter','Patient_City'=>'Location','SocialMediaStarted' => 'Date Submitted','start_date' => 'Date Posted','DaysBucket'=>'Days in Bucket','DaysNTH'=>'Days on NTH'];
                    break;
                default:
                    $dataTable = ['select'=>'select','ClientNameCustom'=>'Client Name','Recruiter'=>'Recruiter','Patient_City'=>'Location','DaysBucket'=>'Days in Bucket','DaysNTH'=>'Days on NTH'];
                    break;
            }
        }else{
            $dataTable = ['select'=>'select','ClientNameCustom'=>'Client Name','Recruiter'=>'Recruiter','Patient_City'=>'Location','DaysBucket'=>'Days in Bucket','DaysNTH'=>'Days on NTH'];
        }
        return view('nthchecklist.datatable',["type"=>$type,'label'=>$label,'dataTable'=>$dataTable]);

    }

    /**
     * @param $type
     * @param $request
     * @param $showCount
     * @return string|Collection
     * @throws \Exception
     * todo fix me
     */
    public function getNTHChecklistDataByType($type, $request = [], $showCount = 0): string|Collection
    {
        $bucket = DB::table('NeedToHire_Checklist');
        $bucket->leftJoin('NeedToHire_Accounts', function ($query) {
            $query->on('NeedToHire_Accounts.id', '=', 'NeedToHire_Checklist.aoc_nthaccounts_id');
        });
        $bucket->leftJoin('mssql_patients', function ($query) {
            $query->on('mssql_patients.id', '=', 'NeedToHire_Accounts.aoc_patient_id');
        });
        $bucket->leftJoin('mssql_facilities', function ($query) {
            $query->on('mssql_facilities.FacilityID', '=', 'mssql_patients.FacilityID');
        });
        if (LD::checkFeatureFlag('add-date-field-into-social-media-sponsored-bucket')) {
            $bucket->leftJoin('social_media_ads', function ($query) {
                $query->on('social_media_ads.aoc_nthaccounts_id', '=', 'NeedToHire_Checklist.aoc_nthaccounts_id');
            });
        }
        $bucket->where('NeedToHire_Accounts.IsActive', '=', 1);
        $users = H::getLoggedInUser();
        $currentRole = $users['role'];

        if (LD::checkFeatureFlag('multiple-roles-to-user')) {
            if (H::getHelper('UserPermissionResolver')->isRoleHasPermissionNew('is_recruiting_admin_form')) {
                if (isset($request['filters'][0]['value']) && !empty($request['filters'][0]['value']) && $request['filters'][0]['name'] == 'nth-memode') {
                    $allSubOrdinates = H::getHelper('UserPermissionResolver')->getRAsRecruiters($users['id']);
                    $bucket->whereIn('Recruiter', $allSubOrdinates);
                    H::ExcelLog(print_r($allSubOrdinates, true), 'NTH-RAF.log');
                }
            } elseif (H::getHelper('UserPermissionResolver')->isRoleHasPermissionNew('is_recruiting_team_leader') || H::getHelper('UserPermissionResolver')->isRoleHasPermissionNew('is_recruiting_manager')) {
                if (
                    isset($request['filters'][0]['value']) &&
                    !empty($request['filters'][0]['value']) &&
                    $request['filters'][0]['name'] == 'nth-memode'
                ) {
                    $allSubOrdinates = H::getHelper('UserPermissionResolver')->getAllSubordinatesFromCurrentUser($users['id']);
                    $bucket->whereIn('Recruiter', $allSubOrdinates);
                }
            } else {
                if (
                    isset($request['filters'][0]['value']) &&
                    !empty($request['filters'][0]['value']) &&
                    $request['filters'][0]['name'] == 'nth-memode'
                ) {
                    $bucket->where('Recruiter', $users['name']);
                }
            }
        } else {
            if (H::getHelper('UserPermissionResolver')->isRoleHasPermission($currentRole, 'is_recruiting_admin_form')) {
                if (isset($request['filters'][0]['value']) && !empty($request['filters'][0]['value']) && $request['filters'][0]['name'] == 'nth-memode') {
                    $allSubOrdinates = H::getHelper('UserPermissionResolver')->getRAsRecruiters($users['id']);
                    $bucket->whereIn('Recruiter', $allSubOrdinates);
                    H::ExcelLog(print_r($allSubOrdinates, true), 'NTH-RAF.log');
                }
            } elseif (H::getHelper('UserPermissionResolver')->isRoleHasPermission($currentRole, 'is_recruiting_admin')) {
                //don't add restrictions based on role
            } elseif (H::getHelper('UserPermissionResolver')->isRoleHasPermission($currentRole, 'is_recruiting_team_leader') || H::getHelper('UserPermissionResolver')->isRoleHasPermission($currentRole, 'is_recruiting_manager')) {
                if (isset($request['filters'][0]['value']) && !empty($request['filters'][0]['value']) && $request['filters'][0]['name'] == 'nth-memode') {
                    $allSubOrdinates = H::getHelper('UserPermissionResolver')->getAllSubordinatesFromCurrentUser($users['id']);
                    $bucket->whereIn('Recruiter', $allSubOrdinates);
                    H::ExcelLog(print_r($allSubOrdinates, true), 'NTH-RTL-RM.log');
                }
            } else {
                if (isset($request['filters'][0]['value']) && !empty($request['filters'][0]['value']) && $request['filters'][0]['name'] == 'nth-memode') {
                    $bucket->where('Recruiter', $users['name']);
                }
            }
        }
        if (LD::checkFeatureFlag('add-date-field-into-social-media-sponsored-bucket')) {
            $bucket
                ->select('*',
                    'NeedToHire_Checklist.id as nthcheckid',
                    'NeedToHire_Accounts.id as nthaccounts_id',
                    'mssql_patients.id as clientId',
                    DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS ClientNameCustom"),
                    DB::raw('DATEDIFF(now(),NeedToHire_Accounts.created_at) as DaysNTH'),
                    DB::raw("DATE_FORMAT(social_media_ads.date_posted, '%m/%d/%Y') as start_date"),
                    DB::raw("DATE_FORMAT(social_media_ads.created_at, '%m/%d/%Y') as SocialMediaStarted"))
                ->where('mssql_patients.CF_MeetingNurses', 'not like', '%No%');
        } else {
            $bucket
                ->select('*',
                    'NeedToHire_Checklist.id as nthcheckid',
                    'NeedToHire_Accounts.id as nthaccounts_id',
                    'mssql_patients.id as clientId',
                    DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS ClientNameCustom"),
                    DB::raw("DATEDIFF(now(),NeedToHire_Accounts.created_at) as DaysNTH"))
                ->where('mssql_patients.CF_MeetingNurses', 'not like', '%No%');
        }
        switch ($type) {
            case 'hub_city_requisition':
                $bucket->where('HUBcity', 0);
                $bucket->addSelect(DB::raw('DATEDIFF(now(),NeedToHire_Checklist.CreatedOn) as DaysBucket'));
                break;
            case 'text_blast':
                $bucket->where('NeedToHire_Checklist.CurrentStep', 1);
                $bucket->addSelect(DB::raw('DATEDIFF(now(),NeedToHire_Checklist.TextBlastStarted) as DaysBucket'));
                break;
            case 'pet':
                $bucket->where('NeedToHire_Checklist.CurrentStep', 2);
                $bucket->addSelect(DB::raw('DATEDIFF(now(),NeedToHire_Checklist.PETStarted) as DaysBucket'));
                break;
            case 'social_media':
                $bucket->where('NeedToHire_Checklist.CurrentStep', 3);
                $bucket->addSelect(DB::raw('DATEDIFF(now(),NeedToHire_Checklist.SocialMediaStarted) as DaysBucket'));
                break;
            case 'private_message':
                $bucket->where('NeedToHire_Checklist.CurrentStep', 4);
                $bucket->addSelect(DB::raw('DATEDIFF(now(),NeedToHire_Checklist.PrivateMessageStarted) as DaysBucket'));
                break;
            case 'linkedin':
                $bucket->where('NeedToHire_Checklist.CurrentStep', 5);
                $bucket->addSelect(DB::raw('DATEDIFF(now(),NeedToHire_Checklist.LinkedINStarted) as DaysBucket'));
                break;
            case 'indeed':
                $bucket->where('NeedToHire_Checklist.CurrentStep', 6);
                $bucket->addSelect(DB::raw('DATEDIFF(now(),NeedToHire_Checklist.IndeedStarted) as DaysBucket'));
                break;
            case 'cold_call':
                $bucket->where('NeedToHire_Checklist.CurrentStep', 7);
                $bucket->addSelect(DB::raw('DATEDIFF(now(),NeedToHire_Checklist.ColdCallStarted) as DaysBucket'));
                break;
            case 'mail_matters':
                $bucket->where('NeedToHire_Checklist.CurrentStep', 8);
                $bucket->addSelect(DB::raw('DATEDIFF(now(),NeedToHire_Checklist.SnailMailStarted) as DaysBucket'));
                break;
            case 'bon_nurse_search':
                $bucket->where('NeedToHire_Checklist.CurrentStep', 9);
                $bucket->addSelect(DB::raw('DATEDIFF(now(),NeedToHire_Checklist.BONNursePMStarted) as DaysBucket'));
                break;
            case 'social_media_sponsored':
                $bucket->where('NeedToHire_Checklist.CurrentStep', 10);
                $bucket->addSelect(DB::raw('DATEDIFF(now(),NeedToHire_Checklist.SocialMediaSponsoredStarted) as DaysBucket'));
                break;
            case 'escaleted':
                $bucket->where('NeedToHire_Checklist.CurrentStep', 11);
                $bucket->addSelect(DB::raw('DATEDIFF(now(),NeedToHire_Checklist.EscalatedStarted) as DaysBucket'));
                break;
            default:
                break;
        }

        if (isset($request['filters'])) {
            $searchText = '';
            $state = [];
            $branch = [];
            $recruiter = [];
            $payer = [];
            $payerGroup = [];
            $region = [];
            $servicesNeeded = [];

            foreach ($request['filters'] as $key => $value) {
                if ($value['name'] == 'nth-state') {
                    $state[] = $value['value'];
                }
                if ($value['name'] == 'nth-aoc_facility_id') {
                    $branch[] = $value['value'];
                }
                if ($value['name'] == 'nth-region') {
                    $region[] = $value['value'];
                }
                if ($value['name'] == 'nth-Recruiter') {
                    $recruiter[] = $value['value'];
                }
                if ($value['name'] == 'nth-payer') {
                    $payer[] = $value['value'];
                }
                if ($value['name'] == 'nth-payer-group') {
                    $payerGroup[] = $value['value'];
                }
                if ($value['name'] == 'search-by-text') {
                    $searchText = $value['value'];
                }
                if ($value['name'] == 'serviceneeded') {
                    $servicesNeeded[] = $value['value'];
                }
            }

            if (!empty($branch)) {
                $bucket->whereIn('mssql_patients.aoc_facility_id', $branch);
            }

            if (LD::checkFeatureFlag('region-filter-for-all-dashboard')) {
                if (!empty($region)) {
                    $bucket->where(function ($query) use ($region) {
                        foreach ($region as $val) {
                            $query->orWhere('mssql_facilities.region', '=', $val);
                        }
                    });
                }
            }

            if (!empty($state)) {
                $bucket->whereIn('mssql_patients.Patient_State', $state);
            }

            if (!empty($recruiter)) {
                $bucket->whereIn('NeedToHire_Accounts.Recruiter', $recruiter);
            }
            if (!empty($payer)) {
                $bucket->whereIn('mssql_patients.Payer', $payer);
            }
            if (!empty($payerGroup)) {
                $bucket->whereIn('mssql_patients.payer_group', $payerGroup);
            }
            if (!empty($searchText)) {
                $bucket->where(function ($q) use ($searchText) {
                    $q->orWhere(DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName)"), 'like', '%' . $searchText . '%');
                    $q->orWhere('Patient_City', 'like', '%' . $searchText . '%');
                    $q->orWhere('PatientID', 'like', '%' . $searchText . '%');
                });
            }
            if (LD::checkFeatureFlag('add-service-needed-filter-on-nthcheklist')) {
                if (count($servicesNeeded) > 0) {
                    $bucket->whereIn('NeedToHire_Accounts.ServicesNeeded', $servicesNeeded);
                }
            }
        }

        $bucket->groupBy([
            'NeedToHire_Checklist.id',
            'NeedToHire_Accounts.id',
            'mssql_patients.id'
        ]);

        if ($showCount) {
            return '(' . $bucket->count() . ')';
        }

        return $bucket->get();
    }

    public function getLast7DaysNumber($type,$checklistId,$field = 'misc2'){
        $data = DB::table("notes")
        ->select(DB::raw('sum('.$field.') as last7DaysNumber'))
        ->where('entity','NeedToHire')
        ->where('type','NTH')
        ->where('entity_id',$checklistId)
        ->where('misc1',$type)
        ->whereDate('created_at','>',date('Y-m-d H:i:s',strtotime('-1 week')))->get();
        return isset($data[0]->last7DaysNumber) && $data[0]->last7DaysNumber != 0 ? $data[0]->last7DaysNumber : '';
    }

    public function getNthShiftDescription($acctId)
    {
        $days = ['', 'S', 'M', 'T', 'W', 'Th', 'F', 'Sa'];
        $qry = "SELECT StartDayOfWeek,StartDayOfWeekInt, StartHour,
						StartMinute, EndHour, EndMinute, StartAndEndOnSameDay
				FROM `NeedToHire_Schedules`
				where aoc_nthaccounts_id = ? and IsActive = 1
				order by StartHour, StartDayOfWeekInt";
        $dbShifts = DB::select($qry,[$acctId]);
        $shifts = [];
        $scheduleDetails = '';
        foreach ($dbShifts as $shift) {
            $startAmPm = 'a';
            $startHour = $shift->StartHour;
            if ($startHour >= 12) {
                $startAmPm = 'p';
            }
            if ($startHour > 12) {
                $startHour = $startHour - 12;
            }
            $endHour = $shift->EndHour;
            $endAmPm = 'a';
            if ($endHour >= 12) {
                $endAmPm = 'p';
            }
            if ($endHour > 12) {
                $endHour = $endHour - 12;
            }
            $mytime = $startHour;
            if ($shift->StartMinute != 0) {
                $tmp = '0' . $shift->StartMinute;
                $tmp = substr($tmp, -2);
                $mytime .= $tmp;
            }
            $mytime .= $startAmPm . '-';
            $mytime .= $endHour;
            if ($shift->EndMinute != 0) {
                $mytime .= substr('0' . $shift->EndMinute, -2);
            }
            $mytime .= $endAmPm;
            $tmp = '';
            if (isset($shifts[$mytime])) {
                $tmp = $shifts[$mytime] . '&';
            }
            $tmp .= $days[$shift->StartDayOfWeekInt];
            $shifts[$mytime] = $tmp;
        }
        $shiftDesc = '';
        foreach ($shifts as $key => $value) {
            if ($shiftDesc != '') {
                $shiftDesc .= ', ';
            }
            $tmp = $value;
            if (substr($tmp, 0, 2) == 'S&') {
                $tmp = substr($tmp, 2) . '&S';
            }
            if ($tmp == 'M&T&W&Th&F&Sa&S') {
                $tmp = 'M-S';
            }
            if ($tmp == 'M&T&W&Th&F&Sa') {
                $tmp = 'M-Sa';
            }
            if ($tmp == 'M&T&W&Th&F') {
                $tmp = 'M-F';
            }
            if ($tmp == 'M&T&W&Th') {
                $tmp = 'M-Th';
            }
            if ($tmp == 'M&T&W') {
                $tmp = 'M-W';
            }
            if ($tmp == 'M&T') {
                $tmp = 'M-T';
            }
            if ($tmp == 'T&W&Th&F&Sa&S') {
                $tmp = 'T-S';
            }
            if ($tmp == 'T&W&Th&F&Sa') {
                $tmp = 'T-Sa';
            }
            if ($tmp == 'T&W&Th&F') {
                $tmp = 'T-F';
            }
            if ($tmp == 'T&W&Th') {
                $tmp = 'T-Th';
            }
            if ($tmp == 'T&W') {
                $tmp = 'T-W';
            }
            if ($tmp == 'W&Th&F&Sa&S') {
                $tmp = 'W-S';
            }
            if ($tmp == 'W&Th&F&Sa') {
                $tmp = 'W-Sa';
            }
            if ($tmp == 'W&Th&F') {
                $tmp = 'W-F';
            }
            if ($tmp == 'W&Th') {
                $tmp = 'W-Th';
            }
            if ($tmp == 'Th&F&Sa&S') {
                $tmp = 'Th-S';
            }
            if ($tmp == 'Th&F&Sa') {
                $tmp = 'Th-Sa';
            }
            if ($tmp == 'Th&F') {
                $tmp = 'Th-F';
            }
            if ($tmp == 'F&Sa&S') {
                $tmp = 'F-S';
            }
            if ($tmp == 'F&Sa') {
                $tmp = 'F-Sa';
            }
            if ($tmp == 'Sa&S') {
                $tmp = 'Sa-S';
            }
            $shiftDesc .= $tmp . ' ' . $key;
        }
        return $shiftDesc;
    }

    function isSpecialized($skills, $scheduleSpecialized = 0){
        if (LD::checkFeatureFlag('specialized-vent-included-in-skills-nth-form')) {
            $skillsLower = strtolower($skills);
            $skillSpecialized = false;
            $keywords = ['trach', 'trach & vent', 'vent'];
            foreach ($keywords as $keyword) {
                if ($keyword === 'vent') {
                    if (strpos($skillsLower, 'vent') !== false && $scheduleSpecialized) {
                        $skillSpecialized = true;
                    }
                } elseif (strpos($skillsLower, $keyword) !== false) {
                    $skillSpecialized = true;
                }
            }
            return $skillSpecialized;
        } else {
            return isset($skills) && (strpos(strtolower($skills),'trach') !== false) ? true : false;
        }
    }

    function getPatientScheduleSpecialized($patientID){
        $date = date('Y-m-d');
        $currentMonday = H::getMondayFromDate($date, 0);
        $currentSunday = date('Y-m-d', strtotime('next Sunday', strtotime($date)));
        return DB::table('mssql_schedulemaster')->select('mssql_schedulemaster.id')
        ->leftJoin("service_codes_daas","service_codes_daas.service_id","=","mssql_schedulemaster.ServiceCodeID")
        ->where('mssql_schedulemaster.PatientID',$patientID)
        ->where("mssql_schedulemaster.ScheduleDate", ">=",$currentMonday)
        ->where("mssql_schedulemaster.ScheduleDate", "<=",$currentSunday)
        ->where('service_codes_daas.name','LIKE','%specialized%')
        ->first();
    }

    public function setNTHScheduleToMap($nthaccountid, $candidate_id = 0){
		H::ExcelLog('nthaccountid -'.$nthaccountid,'setNTHScheduleToMap.log');
        H::ExcelLog('candidate_id -'.$candidate_id,'setNTHScheduleToMap.log');
       $schedules =  DB::table('NeedToHire_Schedules')->select("ShiftHours","ShiftTypeDescription")->where("isActive",1)->where("aoc_nthaccounts_id",$nthaccountid)->get();
       H::ExcelLog(print_r($schedules,true),'setNTHScheduleToMap.log');
		$totalHour = 0;
       $shiftDescription = [];
       foreach($schedules as $schedule) {
            $totalHour+=$schedule->ShiftHours;
            $shiftDescription[] = $schedule->ShiftTypeDescription;
       }

       $Type_of_Shift = implode(" | ",array_unique($shiftDescription));
       $Schedule = $this->getNthShiftDescription($nthaccountid);
       $NTH_Hours = $totalHour;

       if(H::getHelper("ConstantHelper")->isNTHMap("maps_nth")){
            H::ExcelLog('totalHour -'.$totalHour,'setNTHScheduleToMap.log');
            if($totalHour>0){
                $data = ["Type_of_Shift"=>$Type_of_Shift,"NTH_Hours"=>$NTH_Hours,"Schedule"=>$Schedule];
            }else{
                $data = ["IsActive"=> 0,"NTH_Hours"=>$NTH_Hours];
            }
            H::ExcelLog('Map update data','setNTHScheduleToMap.log');
            H::ExcelLog(print_r($data,true),'setNTHScheduleToMap.log');
            DB::table("maps_nth")->where("aoc_nthaccounts_id",$nthaccountid)->update($data);
       }

        if(LD::checkFeatureFlag("notify-recruiter-for-no-logner-schedule")) {
            $nthSchedule =  DB::table('NeedToHire_Schedules')->select("NeedToHire_Schedules.id");
            $nthSchedule->leftJoin("NeedToHire_Accounts", function($query) {
                $query->on("NeedToHire_Schedules.aoc_nthaccounts_id","=","NeedToHire_Accounts.id");
            });
            $nthSchedule->where('NeedToHire_Schedules.aoc_nthaccounts_id', $nthaccountid);
            $nthSchedule->where(function ($query){
                $query->where("NeedToHire_Schedules.isActive", 0);
                $query->orWhere("NeedToHire_Accounts.isActive", 0);
            });
            $nthScheduleIds = $nthSchedule->get()->toArray();
            $nthScheduleIds = array_column(json_decode(json_encode($nthScheduleIds), true), 'id');

            if (count($nthScheduleIds) > 0) {
                $nthTrackerSchedule =  DB::table('mssql_recruitertracker')->select("mssql_recruitertracker.*", DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS ClientNameCustom"));
                $nthTrackerSchedule->whereNotNull('nth_schedule_ids');
                if ($candidate_id != 0) {
                    $nthTrackerSchedule->where('aoc_candidate_id', '<>', $candidate_id);
                }
                $nthTrackerSchedule->whereRaw('CONCAT(\',\', nth_schedule_ids, \',\') REGEXP \',('.implode("|",$nthScheduleIds).'),\'');
                $nthTrackerSchedule->leftJoin("mssql_patients",function($query){
                    $query->on("mssql_patients.id","=","mssql_recruitertracker.aoc_patient_id");
                });
                $nthTrackerScheduleData = $nthTrackerSchedule->get()->toArray();
                $nthTrackerScheduleData = json_decode(json_encode($nthTrackerScheduleData), true);
                foreach ($nthTrackerScheduleData as $key => $recuriterData) {
                    $data2['user_id'] = $recuriterData['user_id'];
                    $data2['title'] = $recuriterData['Candidate'] . ' is currently scheduled for ' . $recuriterData['ClientNameCustom'] . ' for hours that are no longer on NTH.';
                    $data2['type'] = 'trackerDetails';
                    $data2['related_item'] = $recuriterData['id'];
                    $data2['notes'] = '';
                    Notifications::addNotification($data2);
                }
            }
        }
    }

    //Adding the records after the NTH record created
    public function AddEditNTHMapRecord($nthAccountId,$mode='insert'){
        //figure out if adding or editing

        $currentMap = DB::table('maps_nth')->select('id')->where('aoc_nthaccounts_id',$nthAccountId)->first();
        if(isset($currentMap->id)){
            $mode='edit';
        }else{
            $mode='insert';
        }
        $nthDetails = NeedToHireAccounts::find($nthAccountId);
        $patientDetails = [];
        if(isset($nthDetails->aoc_patient_id)){
            $patientDetails = MssqlPatients::find($nthDetails->aoc_patient_id);
        }else{
            //return an error!
        }

        $clinicianGender = ''; //get from preferences
        $intake = 0;
        if($patientDetails->is_intake==1) {
            $intake=1;
        }
        $genderPref = 'Either Gender';
        if (isset($patientDetails->aoc_cf_male_nurses) && isset($patientDetails->aoc_cf_female_nurses)) {
            if ($patientDetails->aoc_cf_female_nurses === 'No') {
                $genderPref = 'Male Only';
            } elseif ($patientDetails->aoc_cf_male_nurses === 'No') {
                $genderPref = 'Female Only';
            }
        } else {
            $preferenceList = strtolower($patientDetails->PreferenceList);

            if (str_contains($preferenceList, 'does not accept female nurses')) {
                $genderPref = 'Male Only';
            } elseif (!str_contains($preferenceList, 'male nurse accepted')) {
                $genderPref = 'Female Only';
            }
        }
        //figure out payer_group_sort
        $payer_group_sort = 0;
        if(!empty($patientDetails->payer_group)){
            $pg = $patientDetails->payer_group;
            if($pg=='Wellpoint' || $pg == 'Wellpoint Starplus'){
                $payer_group_sort = 7;
            }elseif($pg=='Aetna'){
                $payer_group_sort = 6;
            }elseif($pg=='SCA'){
                $payer_group_sort = 5;
            }elseif($pg=='Community First Health Plan'){
                $payer_group_sort = 4;
            }elseif($pg=='Texas Childrens'){
                $payer_group_sort = 3;
            }elseif($pg=='BCBS'){
                $payer_group_sort = 2;
            }elseif($pg=='Superior' || $pg =='SUPERIOR STARPLUS'){
                $payer_group_sort = 1;
            }
        }
        //mapping fields to insert the map_nth
        if(LD::checkFeatureFlag('add-client-changing-payer-client-nth')){
            $newPayer = $patientDetails->Payer;
            $newRespitePayer = $patientDetails->RespitePayer;
            if($nthDetails->isClientChangingPayer==1){
                if(isset($nthDetails->newTerminalPayer) && $nthDetails->newTerminalPayer!=''){
                    $newPayer = $nthDetails->newTerminalPayer;
                }
                if(isset($nthDetails->newRespiteTerminalPayer) && $nthDetails->newRespiteTerminalPayer!=''){
                    $newRespitePayer = $nthDetails->newRespiteTerminalPayer;
                }
            }
            $insertArr = [
                'IsActive'=>$nthDetails->IsActive,
                'Priority'=>$nthDetails->IsPriority,
                'FacilityID'=>$patientDetails->FacilityID,
                'aoc_nthaccounts_id'=>$nthAccountId,
                'ClientID'=>$patientDetails->PatientAccount,
                'Client'=>$patientDetails->PatientName,
                'NTH_Status'=>$nthDetails->Status,
                'Schedule_Details'=>$nthDetails->ScheduleText,
                'PDN_Auth_Hrs'=>$patientDetails->AuthorizedWeeklyHours,
                'Respite_Auth_Hrs'=>$patientDetails->CF_RespWeeklyHrs,
                'CNA_Auth_Hrs'=>$patientDetails->CNAAuthorizedWeeklyHours,
                'Gender'=>$patientDetails->Patient_Gender,
                'Weight'=>$patientDetails->CF_Weight,
                'Biography'=>$patientDetails->Biography,
                'The_Sale'=>$patientDetails->CF_TheSale,
                'Language'=>$patientDetails->Languages,
                'Nurse_Language'=>$patientDetails->CF_NurseLanguage,
                'Pets_in_Home'=>$patientDetails->PetsinHome,
                'Pets_Nurse_Cannot_Own'=>$patientDetails->PetsNurseCannotOwn,
                'Liftingrequirements'=>$patientDetails->CF_LiftingRequired,
                'Attending_School'=>strpos(strtolower($patientDetails->PreferenceList),"nurse attending child") !== false  ? 'Yes' : 'No',
                'Smokers_Live_in_Home'=>strpos(strtolower($patientDetails->PreferenceList),"smokers live in home") !== false  ? 'Yes' : 'No',
                'NonSmoking_Nurses'=>strpos(strtolower($patientDetails->PreferenceList),"client prefers non-smoking") !== false  ? 'Yes' : 'No',
                'MediaConsent'=>strpos(strtolower($patientDetails->PreferenceList),"media consent form on file") !== false ? 'Yes' : 'No',
                'Covid_Vaccine_Required'=>strpos(strtolower($patientDetails->PreferenceList),"nurses must be covid-19 vaccin") !== false  ? 'Yes' : 'No',
                'Recruiter'=>$nthDetails->Recruiter,
                'Client_Care_Coordinator'=>H::getHelper('ConstantHelper')->getUserNameById($patientDetails->ccc_id),
                'location'=>H::getHelper('ConstantHelper')->getFacilityById($patientDetails->aoc_facility_id),
                'Days_on_NTH'=>Carbon::now()->diffInYears(Carbon::parse($nthDetails->created_at)),
                'Referral_Date'=>$patientDetails->ReferralDate,
                'Intake_Coordinator'=>H::getHelper('ConstantHelper')->getUserNameById($patientDetails->ic_id),
                'Specialized'=>$this->isSpecialized($patientDetails->CF_Skills)==true ? 'Yes' : 'No',
                'SpecializedclientBIT'=>$this->isSpecialized($patientDetails->CF_Skills)==true ? 1 : 0,
                'Skills'=>$patientDetails->CF_Skills,
                'Service_needed'=>$patientDetails->CF_ServicesNeeded,
                'Clinician_Gender'=>$genderPref,
                'Clinician_Discipline'=>$patientDetails->CF_ClinicianDiscipline,
                'Payer'=>$newPayer,
                'Respite_Payer'=>$newRespitePayer,
                'Intake'=>$intake,
                'Intake_Status'=>$patientDetails->IntakeStatus,
                'Intake_Date'=>$patientDetails->IntakeDate,
                'WFD_Client'=>$patientDetails->CF_WorkForceDevProj,
                'SOC_Date'=>$patientDetails->SOCDate,
                'Address'=>$patientDetails->Patient_Address1,
                'City'=>$patientDetails->Patient_City,
                'State'=>$patientDetails->Patient_State,
                'Zipcode'=>$patientDetails->Patient_Zip,
                'Phone'=>$patientDetails->Patient_Phone,
                'Notes' =>$patientDetails->NTHNotes,
                'CreatedOn'=>$nthDetails->created_at,
                'CreatedReason'=>$nthDetails->CreatedReason,
                'Meeting_Nurses'=>$patientDetails->CF_MeetingNurses,
                'Client_DOB'=>$patientDetails->Patient_DOB,
                'Age'=>Carbon::now()->diffInYears(Carbon::parse($patientDetails->Patient_DOB)),
                'has_payer_group'=>$payer_group_sort,
                'non_vbc'=>$patientDetails->non_vbc,
                'ChecklistBit'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
                'needs_geocoding'=>1
            ];
        }else{
            $insertArr = [
                'IsActive'=>$nthDetails->IsActive,
                'Priority'=>$nthDetails->IsPriority,
                'FacilityID'=>$patientDetails->FacilityID,
                'aoc_nthaccounts_id'=>$nthAccountId,
                'ClientID'=>$patientDetails->PatientAccount,
                'Client'=>$patientDetails->PatientName,
                'NTH_Status'=>$nthDetails->Status,
                'Schedule_Details'=>$nthDetails->ScheduleText,
                'PDN_Auth_Hrs'=>$patientDetails->AuthorizedWeeklyHours,
                'Respite_Auth_Hrs'=>$patientDetails->CF_RespWeeklyHrs,
                'CNA_Auth_Hrs'=>$patientDetails->CNAAuthorizedWeeklyHours,
                'Gender'=>$patientDetails->Patient_Gender,
                'Weight'=>$patientDetails->CF_Weight,
                'Biography'=>$patientDetails->Biography,
                'The_Sale'=>$patientDetails->CF_TheSale,
                'Language'=>$patientDetails->Languages,
                'Nurse_Language'=>$patientDetails->CF_NurseLanguage,
                'Pets_in_Home'=>$patientDetails->PetsinHome,
                'Pets_Nurse_Cannot_Own'=>$patientDetails->PetsNurseCannotOwn,
                'Liftingrequirements'=>$patientDetails->CF_LiftingRequired,
                'Attending_School'=>strpos(strtolower($patientDetails->PreferenceList),"nurse attending child") !== false  ? 'Yes' : 'No',
                'Smokers_Live_in_Home'=>strpos(strtolower($patientDetails->PreferenceList),"smokers live in home") !== false  ? 'Yes' : 'No',
                'NonSmoking_Nurses'=>strpos(strtolower($patientDetails->PreferenceList),"client prefers non-smoking") !== false  ? 'Yes' : 'No',
                'MediaConsent'=>strpos(strtolower($patientDetails->PreferenceList),"media consent form on file") !== false ? 'Yes' : 'No',
                'Covid_Vaccine_Required'=>strpos(strtolower($patientDetails->PreferenceList),"nurses must be covid-19 vaccin") !== false  ? 'Yes' : 'No',
                'Recruiter'=>$nthDetails->Recruiter,
                'Client_Care_Coordinator'=>H::getHelper('ConstantHelper')->getUserNameById($patientDetails->ccc_id),
                'location'=>H::getHelper('ConstantHelper')->getFacilityById($patientDetails->aoc_facility_id),
                'Days_on_NTH'=>Carbon::now()->diffInYears(Carbon::parse($nthDetails->created_at)),
                'Referral_Date'=>$patientDetails->ReferralDate,
                'Intake_Coordinator'=>H::getHelper('ConstantHelper')->getUserNameById($patientDetails->ic_id),
                'Specialized'=>$this->isSpecialized($patientDetails->CF_Skills)==true ? 'Yes' : 'No',
                'SpecializedclientBIT'=>$this->isSpecialized($patientDetails->CF_Skills)==true ? 1 : 0,
                'Skills'=>$patientDetails->CF_Skills,
                'Service_needed'=>$patientDetails->CF_ServicesNeeded,
                'Clinician_Gender'=>$genderPref,
                'Clinician_Discipline'=>$patientDetails->CF_ClinicianDiscipline,
                'Payer'=>$patientDetails->Payer,
                'Respite_Payer'=>$patientDetails->RespitePayer,
                'Intake'=>$intake,
                'Intake_Status'=>$patientDetails->IntakeStatus,
                'Intake_Date'=>$patientDetails->IntakeDate,
                'WFD_Client'=>$patientDetails->CF_WorkForceDevProj,
                'SOC_Date'=>$patientDetails->SOCDate,
                'Address'=>$patientDetails->Patient_Address1,
                'City'=>$patientDetails->Patient_City,
                'State'=>$patientDetails->Patient_State,
                'Zipcode'=>$patientDetails->Patient_Zip,
                'Phone'=>$patientDetails->Patient_Phone,
                'Notes' =>$patientDetails->NTHNotes,
                'CreatedOn'=>$nthDetails->created_at,
                'CreatedReason'=>$nthDetails->CreatedReason,
                'Meeting_Nurses'=>$patientDetails->CF_MeetingNurses,
                'Client_DOB'=>$patientDetails->Patient_DOB,
                'Age'=>Carbon::now()->diffInYears(Carbon::parse($patientDetails->Patient_DOB)),
                'has_payer_group'=>$payer_group_sort,
                'non_vbc'=>$patientDetails->non_vbc,
                'ChecklistBit'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
                'needs_geocoding'=>1
            ];
        }
        //disable if $patientDetails->CF_MeetingNurses starts with No
        if (strtolower(substr($patientDetails->CF_MeetingNurses . '   ',0,2)) == 'no') {
            $insertArr['IsActive'] = 0;
        }
        //Age
        //Attending_School
        //Intake_Status
        //Intake_Date
        //Location
        //WFD_Client
        //Notes
        //covid_VAccine_Required
        //MediaConsent
        //NonSmoking_Nurses
        //Smokers_Live_in_Home
        //

        // Adding the relocating address of the client to the map
        if(LD::checkFeatureFlag('add-relocating-address-for-client-nth')){
            if(isset($nthDetails->clientIsRelocating) && $nthDetails->clientIsRelocating == 1){
                $insertArr['Address'] = $nthDetails->relocatingAddress1.' '.$nthDetails->relocatingAddress2;
                $insertArr['City'] = $nthDetails->relocatingCity;
                $insertArr['State'] = $nthDetails->relocatingState;
                $insertArr['Zipcode'] = $nthDetails->relocatingZipCode;
            }
        }

        $insertArr['not_in_kantime'] = $patientDetails->not_in_kantime ?? 0;
        H::ExcelLog($mode . ' - Updating NTH Map for: ' . $nthAccountId,'updateNthMap.log');
        H::ExcelLog(print_r($insertArr,true),'updateNthMap.log');
        if($mode == 'edit'){
            DB::table('maps_nth')->where('aoc_nthaccounts_id',$nthAccountId)->update($insertArr);
        }else{
            DB::table('maps_nth')->insert($insertArr);
        }

        $patientDetail = MssqlPatients::find($nthDetails->aoc_patient_id);
        $needToHireArray = [
            'ServicesNeeded' => $patientDetail->CF_ServicesNeeded,
            'skills' => $patientDetail->CF_Skills,
            'liftingRequirements' => $patientDetail->CF_LiftingRequired,
            'clinicianDiscipline' => $patientDetail->CF_ClinicianDiscipline,
            'ClientID' => $patientDetail->PatientAccount
        ];
        NeedToHireAccounts::where('id',$nthAccountId)->update($needToHireArray);
    }

    public function ignoreNTHScheduleReason($reason,$schedule) {
        $data = [
                'AOC - Non-admit',
                'Caregiver - Non-admit',
                'Clinician - Non-admit',
                'AOC - Discharged',
                'Caregiver - Discharged',
                'Clinician - Discharged',
                'AOC - Staffed',
                'Caregiver - Staffed',
                'Clinician - Staffed',
            ];
        if(!in_array($reason,$data)) {
            $voidSchedules = DB::table("NeedToHire_Audit")->select("*")->where("schedule_id",$schedule->id)->whereIn("Type",['A','R'])->get();
             $isDateMatch = count($voidSchedules)==2;

            foreach($voidSchedules as $scheduleData) {
                $shortDate = date('Y-m-d',strtotime($scheduleData->created_at));

                if($shortDate!=date('Y-m-d')){
                    $isDateMatch = false;
                }
            }
            if($isDateMatch) {
                DB::table("NeedToHire_Audit")->where("schedule_id",$schedule->id)->whereIn("Type",['A','R'])->update(["isNet"=>1]);;
                H::ExcelLog('Void Schedule ID:'.$schedule->id,'schedulevoid.log');
            }
        }
    }

    public function setClientAccountStatus($aoc_nthaccounts_id){
		H::ExcelLog('aoc_nthaccounts_id -'.$aoc_nthaccounts_id,'setClientAccountStatus.log');
        $totalShiftHours = 0;
        $nthScheduleTable = [];
        if(!empty($aoc_nthaccounts_id)) {
            $nthScheduleTable = DB::table("NeedToHire_Schedules")->select("*")->where("IsActive",1)->where("aoc_nthaccounts_id",$aoc_nthaccounts_id)->get();
            $nthScheduleTableArray = json_decode(json_encode($nthScheduleTable), true );
            $totalShiftHours = array_sum(array_column($nthScheduleTableArray, 'ShiftHours'));
        }
		H::ExcelLog('totalShiftHours -'.$totalShiftHours,'setClientAccountStatus.log');
        if($totalShiftHours>0) {
            NeedToHireAccounts::where('id', $aoc_nthaccounts_id)->update(['updated_at'=>date('Y-m-d H:i:s'),'IsActive' => '1','updated_by'=>Auth::user()->id]);
            DB::table('maps_nth')->where('aoc_nthaccounts_id', $aoc_nthaccounts_id)->update(['IsActive' => '1']);
        } else {
            try {
                DB::transaction(function () use ($aoc_nthaccounts_id) {
                    $wasActive = (int) NeedToHireAccounts::where('id', $aoc_nthaccounts_id)->lockForUpdate()->value('IsActive');

                    NeedToHireAccounts::where('id', $aoc_nthaccounts_id)->update(['updated_at'=>date('Y-m-d H:i:s'),'IsActive' => '0','updated_by'=>Auth::user()->id]);
                    DB::table('maps_nth')->where('aoc_nthaccounts_id', $aoc_nthaccounts_id)->update(['IsActive' => '0']);

                    if ($wasActive === 1) {
                        app(SocialMediaAdAutoCloseService::class)->closeOpenAdsForNth($aoc_nthaccounts_id);
                    }
                });
            } catch (\Throwable $e) {
                H::ExcelLog('setClientAccountStatus close failed for '.$aoc_nthaccounts_id.': '.$e->getMessage(), 'setClientAccountStatus.log');
                throw $e;
            }
        }
    }


    public function getSelectNTHShiftDescription($selectedSchedule)
    {
        $questionMarkString = H::generateQuestionMarksArray($selectedSchedule);
        $days = ['', 'S', 'M', 'T', 'W', 'Th', 'F', 'Sa'];
        $qry = "SELECT StartDayOfWeek,StartDayOfWeekInt, StartHour,
						StartMinute, EndHour, EndMinute, StartAndEndOnSameDay
				FROM `NeedToHire_Schedules`
				where id in (".$questionMarkString.") and IsActive = 1
				order by StartHour, StartDayOfWeekInt";
        $dbShifts = DB::select($qry,$selectedSchedule);
        $shifts = [];
        $scheduleDetails = '';
        foreach ($dbShifts as $shift) {
            $startAmPm = 'a';
            $startHour = $shift->StartHour;
            if ($startHour >= 12) {
                $startAmPm = 'p';
            }
            if ($startHour > 12) {
                $startHour = $startHour - 12;
            }
            $endHour = $shift->EndHour;
            $endAmPm = 'a';
            if ($endHour >= 12) {
                $endAmPm = 'p';
            }
            if ($endHour > 12) {
                $endHour = $endHour - 12;
            }
            $mytime = $startHour;
            if ($shift->StartMinute != 0) {
                $tmp = '0' . $shift->StartMinute;
                $tmp = substr($tmp, -2);
                $mytime .= $tmp;
            }
            $mytime .= $startAmPm . '-';
            $mytime .= $endHour;
            if ($shift->EndMinute != 0) {
                $mytime .= substr('0' . $shift->EndMinute, -2);
            }
            $mytime .= $endAmPm;
            $tmp = '';
            if (isset($shifts[$mytime])) {
                $tmp = $shifts[$mytime] . '&';
            }
            $tmp .= $days[$shift->StartDayOfWeekInt];
            $shifts[$mytime] = $tmp;
        }
        $shiftDesc = '';
        foreach ($shifts as $key => $value) {
            if ($shiftDesc != '') {
                $shiftDesc .= ', ';
            }
            $tmp = $value;
            if (substr($tmp, 0, 2) == 'S&') {
                $tmp = substr($tmp, 2) . '&S';
            }
            if ($tmp == 'M&T&W&Th&F&Sa&S') {
                $tmp = 'M-S';
            }
            if ($tmp == 'M&T&W&Th&F&Sa') {
                $tmp = 'M-Sa';
            }
            if ($tmp == 'M&T&W&Th&F') {
                $tmp = 'M-F';
            }
            if ($tmp == 'M&T&W&Th') {
                $tmp = 'M-Th';
            }
            if ($tmp == 'M&T&W') {
                $tmp = 'M-W';
            }
            if ($tmp == 'M&T') {
                $tmp = 'M-T';
            }
            if ($tmp == 'T&W&Th&F&Sa&S') {
                $tmp = 'T-S';
            }
            if ($tmp == 'T&W&Th&F&Sa') {
                $tmp = 'T-Sa';
            }
            if ($tmp == 'T&W&Th&F') {
                $tmp = 'T-F';
            }
            if ($tmp == 'T&W&Th') {
                $tmp = 'T-Th';
            }
            if ($tmp == 'T&W') {
                $tmp = 'T-W';
            }
            if ($tmp == 'W&Th&F&Sa&S') {
                $tmp = 'W-S';
            }
            if ($tmp == 'W&Th&F&Sa') {
                $tmp = 'W-Sa';
            }
            if ($tmp == 'W&Th&F') {
                $tmp = 'W-F';
            }
            if ($tmp == 'W&Th') {
                $tmp = 'W-Th';
            }
            if ($tmp == 'Th&F&Sa&S') {
                $tmp = 'Th-S';
            }
            if ($tmp == 'Th&F&Sa') {
                $tmp = 'Th-Sa';
            }
            if ($tmp == 'Th&F') {
                $tmp = 'Th-F';
            }
            if ($tmp == 'F&Sa&S') {
                $tmp = 'F-S';
            }
            if ($tmp == 'F&Sa') {
                $tmp = 'F-Sa';
            }
            if ($tmp == 'Sa&S') {
                $tmp = 'Sa-S';
            }
            $shiftDesc .= $tmp . ' ' . $key;
        }
        return $shiftDesc;
    }

    public function addNotificationWhenPreferencesChange($data){
        if(!empty($data['needToHireAccountsId'])){
            $needToHire= DB::table('NeedToHire_Accounts')
            ->select('NeedToHire_Accounts.user_id as assigned_recruiter',
            'users.recruiting_teamleader as recruiting_team_lead',
            'mssql_patients.CF_MeetingNurses',
            DB::raw("CONCAT(mssql_patients.Patient_FirstName, ' ', IFNULL(mssql_patients.Patient_MiddleName, ''), ' ', mssql_patients.Patient_LastName) as full_name"))
            ->leftJoin('mssql_patients', 'mssql_patients.id', 'NeedToHire_Accounts.aoc_patient_id')
            ->leftJoin('users', 'users.id', 'NeedToHire_Accounts.user_id')
            ->where('NeedToHire_Accounts.id', $data['needToHireAccountsId'])->first();
            if(!empty($needToHire) &&
            $needToHire->CF_MeetingNurses != $data['meeting_nurses']){
                $title = $needToHire->full_name."'s Preferences have changed. Please review";
                foreach($needToHire as $key => $id){
                    if($key != 'full_name' && $key != 'CF_MeetingNurses'){
                        if(!empty($id)){
                            Notifications::addNotification(['user_id'=>$id,'title'=>$title,'type'=>'nthAccount','related_item'=>$data['needToHireAccountsId']]);
                        }
                    }
                }
            }
        }
    }
}
