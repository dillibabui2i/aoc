<?php

namespace App\Http\Controllers;

use App\RecruiterScorecard;
use App\Schedule;
use App\WhiteBoard;
use App\Candidates;
use App\MssqlRecruitertracker as MssqlTracker;
use App\Helpers\H;
use Illuminate\Http\Request;
use DB;

class RecruiterScorecardController extends Controller
{
    protected $zeroDateFormat = '0000-00-00 00:00:00';
    protected $logFile = 'recruiterScorecard.log';

    public function index(Request $request)
    {
        $recruiter = H::getHelper('ConstantHelper')->getRecruiters('id');
        $recruiterPossiblePer = config('constant')['recruiter_scorecard_per'];
        $viewMode = $request->input('mode');
        return view('recruiterScorecard.form',
            ['recruiter' => $recruiter,
                'recruiterPossiblePer' => $recruiterPossiblePer,
                'viewMode' => $viewMode,]);
    }

    private function getWeekDays($date) {
        $m = date("m", strtotime($date));
        $y = date("Y", strtotime($date));
        $lastday = date("t",mktime(0,0,0,$m,1,$y));
        $weekdays=0;
        for($d=29;$d<=$lastday;$d++) {
            $wd = date("w",mktime(0,0,0,$m,$d,$y));
            if($wd > 0 && $wd < 6) {
                $weekdays++;
            }
        }
        return $weekdays+20;
    }
    
    private function getWorkingdays($firstDate, $lastDate) {
        H::ExcelLog('in getworkingdays: ' . $firstDate . ' - ' . $lastDate,'recruiterScorecard.log');
      
        // Array of all holidays
        $holidays = array_map('trim', explode(",", H::getCoreSettings('holiday_schedule')));
      
        $start = strtotime($firstDate);
        $end   = strtotime($lastDate);
        $workingDays = 0;
        for ($i = $start; $i <= $end; $i = strtotime("+1 day", $i)) {
          $day = date("w", $i);  // 0=sun, 1=mon, ..., 6=sat
          $mmgg = date('Y-m-d', $i);
          if ($day != 0 && $day != 6 && !in_array($mmgg, $holidays)) {
              $workingDays++;
          }
        }
      
        return intval($workingDays);
      }

    public function getRecruiterscorecardDataByRecruiter(Request $request)
    {
        H::ExcelLog('here!!!!!',$this->logFile);

        $data = [];
        $mode = '';
        $influenceTotalPer = 0;
        $performanceTotalPer = 0;
        $recruiterPossiblePer = 0;
        $utilityTotalPer = 0;
        $phoneScreenAvg = 0;
        $id = $request->input('id');

        $mode = $request->input('mode');
        if ($id) {
            $recruiterPossiblePer = config('constant')['recruiter_scorecard_per'];
            $users = H::getLoggedInUser();
            $firstDateOfPreviousMonth = strtotime('first day of previous month');
            $startDate = date('Y-m-d', $firstDateOfPreviousMonth);
            $lastDateOfPreviousMonth = strtotime('last day of previous month');
            $lastDate = date('Y-m-d', $lastDateOfPreviousMonth);
            H::ExcelLog('user: ' . $id,$this->logFile);
            $isExistsRecord = $this->getOrCreateRecord($mode??'add', $id, $startDate);

            if ($isExistsRecord->count() == 0) {
                $data['recruiter_id'] = $id;
                $data['manager_id'] = $users['id'];
                $data['scorecard_date'] = $startDate;
                $data['skills_recruiter_dashboard'] = 0;

                //skills_nth_checklists_worked
                $data['skills_nth_checklists_worked'] = $this->getNthChecklistScore($startDate, $lastDate, $id, $recruiterPossiblePer);
                        
                //candidates
                $jobviteScore = $this->getJobviteScore($startDate, $lastDate, $id, $recruiterPossiblePer);
                $data['skills_jobvite'] = $jobviteScore;

                //headhunting
                $tmp = $this->getHeadhunting($startDate, $lastDate, $id, $recruiterPossiblePer);
                $data['perform_headhunting'] = $tmp['perform_headhunting'];
                $data['perform_headhunting_goal'] = $tmp['perform_headhunting_goal'];
                $data['perform_headhunting_actual'] = $tmp['perform_headhunting_actual'];

                //ipea counts
                $tmp = $this->getIpeaCounts($startDate, $lastDate, $id, $recruiterPossiblePer);
                $data['perform_ipea'] = $tmp['perform_ipea'];
                $data['perform_ipea_goal'] = $tmp['perform_ipea_goal'];
                $data['perform_ipea_actual'] = $tmp['perform_ipea_actual'];

                //ipea conversion
                $tmp = $this->getIpeaConversion($startDate, $lastDate, $id, $recruiterPossiblePer);
                $data['perform_conversion'] = $tmp['perform_conversion'];
                $data['perform_conversion_goal'] = $tmp['perform_conversion_goal'];
                $data['perform_conversion_actual'] = $tmp['perform_conversion_actual'];
                
                //pepi/wepi counts
                $tmp = $this->getPepiWepiCounts($startDate, $lastDate, $id, $recruiterPossiblePer);
                $data['perform_pepiwepi'] = $tmp['perform_pepiwepi'];
                $data['perform_pepiwepi_goal'] = $tmp['perform_pepiwepi_goal'];
                $data['perform_pepiwepi_actual'] = $tmp['perform_pepiwepi_actual'];
                
                 //hrsremoved counts
                 $tmp = $this->getHoursRemovedCounts($startDate, $lastDate, $id, $recruiterPossiblePer);
                 $data['perform_hrsremoved'] = $tmp['perform_hrsremoved'];
                 $data['perform_hrsremoved_goal'] = $tmp['perform_hrsremoved_goal'];
                 $data['perform_hrsremoved_actual'] = $tmp['perform_hrsremoved_actual'];

                 //first shift worked counts
                 $tmp = $this->getFirstShiftWorkedCounts($startDate, $lastDate, $id, $recruiterPossiblePer);
                 $data['perform_first_shift_worked'] = $tmp['perform_first_shift_worked'];
                 $data['perform_first_shift_worked_goal'] = $tmp['perform_first_shift_worked_goal'];
                 $data['perform_first_shift_worked_actual'] = $tmp['perform_first_shift_worked_actual'];


                $data['phone_screen_tone_confidence'] = 0;
                $data['phone_screen_aoc_values'] = 0;
                $data['phone_screen_case_specifics'] = 0;
                $data['phone_screen_hh_nurse_role'] = 0;
                $data['phone_screen_pay_and_benefits'] = 0;
                $data['phone_screen_clear_expectations_next_steps'] = 0;

                $data['phone_call_screening_score'] = 0;
                $data['attendance_and_dependability'] = 0;
                $data['professionalism'] = 0;

                $data['created_by'] = $users['id'];
                $data['updated_by'] = $users['id'];
                $data['created_at'] = date('Y-m-d');
                $data['updated_at'] = date('Y-m-d');

                $id = RecruiterScorecard::insertGetId($data);
                $mode = 'add';
            } else {
                $data = $isExistsRecord->first()->toArray();
                $id = $data['id'];
                if (!empty($data['submitted_at']) && !empty($data['submitted_by'])) {
                    $mode = 'view';
                } else {
                    $mode = 'edit';
                }
            }

            $utilityTotalFields = [
                $data['skills_recruiter_dashboard'],
                $data['skills_nth_checklists_worked'],
                $data['skills_jobvite']
            ];
            $utilityTotalPer = array_sum($utilityTotalFields);

            $performanceTotalFields = [
                $data['perform_headhunting'],
                $data['perform_ipea'],
                $data['perform_conversion'],
                $data['perform_pepiwepi'],
                $data['perform_hrsremoved'],
                $data['perform_first_shift_worked'],
            ];
            $performanceTotalPer = array_sum($performanceTotalFields);

            $phoneScreenFields = [
                $data['phone_screen_tone_confidence'],
                $data['phone_screen_aoc_values'],
                $data['phone_screen_case_specifics'],
                $data['phone_screen_hh_nurse_role'],
                $data['phone_screen_pay_and_benefits'],
                $data['phone_screen_clear_expectations_next_steps']
            ];
            $phoneScreenAvg = round(array_sum($phoneScreenFields) / count($phoneScreenFields));

            RecruiterScorecard::where('id', $id)
                ->update(['phone_call_screening_score' => round((array_sum($phoneScreenFields) * 0.33))]);
            $data['phone_call_screening_score'] = round((array_sum($phoneScreenFields) * 0.33));

            $influenceTotalFields = [
                $data['phone_call_screening_score'],
                $data['attendance_and_dependability'],
                $data['professionalism']
            ];
            $influenceTotalPer = array_sum($influenceTotalFields);

            $data['scorecard_date'] = H::getFormatedDate($data['scorecard_date']);
            $data['recruiter_name'] = H::getHelper('ConstantHelper')->getUserById($data['recruiter_id']);
            $data['manager_name'] = H::getHelper('ConstantHelper')->getUserById($data['manager_id']);
        }
        return response()->json([
            'data' => $data,
            'id' => $id,
            'mode' => $mode,
            'recruiterPossiblePer' => $recruiterPossiblePer,
            'utilityTotalPer' => $utilityTotalPer,
            'performanceTotalPer' => $performanceTotalPer,
            'influenceTotalPer' => $influenceTotalPer,
            'phoneScreenAvg' => $phoneScreenAvg
        ]);
    }

    private function getOrCreateRecord($mode, $id, $startDate)
    {
        if ($mode != 'add') {
            $isExistsRecord = RecruiterScorecard::where('id', $id);
        } else {
            $isExistsRecord = RecruiterScorecard::where('recruiter_id', $id)->where('scorecard_date', $startDate);
        }
        return $isExistsRecord;
    }

    private function getFirstShiftWorkedCounts($startDate, $lastDate, $id, $recruiterPossiblePer)
    {
        $toalHrsFirstShiftWorked = Schedule::leftJoin('mssql_recruitertracker',
            'recruiting_schedule.tracker_id', '=', 'mssql_recruitertracker.id')
            ->where('mssql_recruitertracker.user_id', $id)
            ->where('recruiting_schedule.type_id', 6)
            ->whereBetween('recruiting_schedule.appointment_date', [$startDate, $lastDate])
            ->sum('mssql_recruitertracker.Total_Hours');
        $clientStartGoal = Whiteboard::select('candidate_start_hour_goals')
            ->where('user_id', $id)
            ->orderBy('primary', 'desc')
            ->orderBy('candidate_start_hour_goals', 'desc')
            ->first()->candidate_start_hour_goals??0;
        if ($clientStartGoal) {
            $tmp['perform_first_shift_worked'] = round(((($toalHrsFirstShiftWorked / ($clientStartGoal * 4))
                * 100) * $recruiterPossiblePer['perform_first_shift_worked']) / 100);
        } else {
            $tmp['perform_first_shift_worked'] = 0;
        }
        if($tmp['perform_first_shift_worked']>$recruiterPossiblePer['perform_first_shift_worked']){
            $tmp['perform_first_shift_worked'] = $recruiterPossiblePer['perform_first_shift_worked'];
        }
        $tmp['perform_first_shift_worked_goal'] = $clientStartGoal * 4;
        $tmp['perform_first_shift_worked_actual'] = $toalHrsFirstShiftWorked;
        return $tmp;
    }

    private function getHoursRemovedCounts($startDate, $lastDate, $id, $recruiterPossiblePer)
    {
        $tmp = [];
        $toalHrsRemoved = Schedule::leftJoin('mssql_recruitertracker',
            'recruiting_schedule.tracker_id', '=', 'mssql_recruitertracker.id')
            ->where('mssql_recruitertracker.user_id', $id)
            ->where('recruiting_schedule.type_id', 6)
            ->whereBetween('recruiting_schedule.scheduled_date', [$startDate, $lastDate])
            ->sum('mssql_recruitertracker.Total_Hours');
        $clientMatchHoursGoal = Whiteboard::select('client_match_hours_goal')
            ->where('user_id', $id)
            ->orderBy('primary', 'desc')
            ->orderBy('client_match_hours_goal', 'desc')
            ->first()->client_match_hours_goal??0;
        if ($clientMatchHoursGoal) {
            $tmp['perform_hrsremoved'] = round(((($toalHrsRemoved / ($clientMatchHoursGoal * 4))
                * 100) * $recruiterPossiblePer['perform_hrsremoved']) / 100);
        } else {
            $tmp['perform_hrsremoved'] = 0;
        }
        if($tmp['perform_hrsremoved']>$recruiterPossiblePer['perform_hrsremoved']){
            $tmp['perform_hrsremoved'] = $recruiterPossiblePer['perform_hrsremoved'];
        }
        $tmp['perform_hrsremoved_goal'] = $clientMatchHoursGoal * 4;
        $tmp['perform_hrsremoved_actual'] = $toalHrsRemoved;
        return $tmp;
    }


    private function getPepiWepiCounts($startDate, $lastDate, $id, $recruiterPossiblePer)
    {
        $tmp = [];
        $pepiwepiScheduleCount = Schedule::leftJoin('mssql_recruitertracker', 
            'recruiting_schedule.tracker_id', '=', 'mssql_recruitertracker.id')
            ->where('mssql_recruitertracker.user_id', $id)
            ->whereIn('recruiting_schedule.type_id', [3,4])
            ->whereBetween('recruiting_schedule.scheduled_date', [$startDate, $lastDate])
            ->count();
        $pepiwepiGoal = Whiteboard::select('pepi_wepi_goal')
            ->where('user_id', $id)
            ->orderBy('primary', 'desc')
            ->orderBy('pepi_wepi_goal', 'desc')
            ->first()->pepi_wepi_goal??0;
        if ($pepiwepiGoal) {
            $tmp['perform_pepiwepi'] = round(((($pepiwepiScheduleCount / ($pepiwepiGoal * 4)) * 100)
                * $recruiterPossiblePer['perform_pepiwepi']) / 100);
        } else {
            $tmp['perform_pepiwepi'] = 0;
        }
        if($tmp['perform_pepiwepi']>$recruiterPossiblePer['perform_pepiwepi']){
            $tmp['perform_pepiwepi'] = $recruiterPossiblePer['perform_pepiwepi'];
        }
        $tmp['perform_pepiwepi_goal'] = $pepiwepiGoal * 4;
        $tmp['perform_pepiwepi_actual'] = $pepiwepiScheduleCount;
        return $tmp;
    }

    private function getIpeaConversion($startDate, $lastDate, $id, $recruiterPossiblePer)
    {
        $tmp = [];
        $ipeaConversionGoal = Whiteboard::select('ipea_coversion_per')
            ->where('user_id', $id)
            ->orderBy('primary', 'desc')
            ->orderBy('ipea_coversion_per', 'desc')
            ->first()->ipea_coversion_per??0;
        $conversionCount = DB::select("SELECT 
            (select count(*) from recruiting_schedule left join mssql_recruitertracker rt on tracker_id = rt.id
                where recruiting_schedule.type_id = 1 and appointment_date >= ? and appointment_date <= ?
                and rt.user_id = ? AND recruiting_schedule.is_deleted <> 1 ) as allCount,
            (select count(*) from recruiting_schedule left join mssql_recruitertracker rt on tracker_id = rt.id
                where recruiting_schedule.type_id = 1 and appointment_date >= ? and appointment_date <= ?
                and rt.user_id = ? AND recruiting_schedule.is_deleted <> 1 and
                ifnull(IPEA_onboarding_status,'') = 'Ready To Work') as convertedCount",
                [$startDate, $lastDate, $id, $startDate, $lastDate, $id]);
        H::ExcelLog(print_r($conversionCount,true),$this->logFile);
        $converstionPct = 0;
        if ($conversionCount[0]->allCount > 0) {
            $converstionPct = ($conversionCount[0]->convertedCount / $conversionCount[0]->allCount) * 100;
        }
        H::ExcelLog('conversion pct: ' . $converstionPct,$this->logFile);

        if ($ipeaConversionGoal) {
            $tmp['perform_conversion'] = ($ipeaConversionGoal > 0) ?
                round(($converstionPct / $ipeaConversionGoal) * $recruiterPossiblePer['perform_conversion'])
                : 0;
        } else {
            $tmp['perform_conversion'] = 0;
        }
        if($tmp['perform_conversion']>$recruiterPossiblePer['perform_conversion']){
            $tmp['perform_conversion'] = $recruiterPossiblePer['perform_conversion'];
        }
        $tmp['perform_conversion_goal'] = $ipeaConversionGoal;
        $tmp['perform_conversion_actual'] = $converstionPct;
        return $tmp;
    }


    private function getIpeaCounts($startDate, $lastDate, $id, $recruiterPossiblePer)
    {
        $tmp = [];
        $totalIPEACount = Schedule::leftJoin('mssql_recruitertracker',
            'recruiting_schedule.tracker_id', '=', 'mssql_recruitertracker.id')
            ->where('mssql_recruitertracker.user_id', $id)
            ->where('recruiting_schedule.type_id', 1)
            ->whereBetween('recruiting_schedule.scheduled_date', [$startDate, $lastDate])
            ->count();
    
        $ipeaGoal = Whiteboard::select('ipea_goal')
            ->where('user_id', $id)
            ->orderBy('primary', 'desc')
            ->orderBy('ipea_goal', 'desc')
            ->first()->ipea_goal??0;

        if ($ipeaGoal) {
            $tmp['perform_ipea'] = round(((($totalIPEACount / ($ipeaGoal * 4)) * 100)
                * $recruiterPossiblePer['perform_ipea']) / 100);
        } else {
            $tmp['perform_ipea'] = 0;
        }
        if($tmp['perform_ipea']>$recruiterPossiblePer['perform_ipea']){
            $tmp['perform_ipea'] = $recruiterPossiblePer['perform_ipea'];
        }
        $tmp['perform_ipea_goal'] = $ipeaGoal * 4;
        $tmp['perform_ipea_actual'] = $totalIPEACount;
        return $tmp;
    }

    private function getHeadhunting($startDate, $lastDate, $id, $recruiterPossiblePer)
    {
        $tmp = [];
        $candidateCount = Candidates::where('created_by', $id)
            ->whereBetween('created_at', [$startDate, $lastDate])
            ->count();
        $actualWorkingDays = $this->getWeekDays($startDate);
        if ($actualWorkingDays) {
            $tmp['perform_headhunting'] = round((($candidateCount / ($actualWorkingDays * 2))
                * $recruiterPossiblePer['perform_headhunting']) / 100);
        } else {
            $tmp['perform_headhunting'] = 0;
        }
        $tmp['perform_headhunting_goal'] = 0;
        $tmp['perform_headhunting_actual'] = $candidateCount;
        return $tmp;
    }



    private function getJobviteScore($startDate, $lastDate, $id, $recruiterPossiblePer)
    {
        $candidates = Candidates::where('user_id', $id)
            ->whereBetween('appSentDate', [$startDate, $lastDate])->get()->toArray();
        $candidateCount = 0;
        $candidateSuccess = 0;
        $percentSuccess = 0;
        foreach ($candidates as $candidate) {
            $candidateCount++;
            H::ExcelLog($candidate['fullName'],$this->logFile);
            H::ExcelLog('app Created: ' . $candidate['appSentDate'],$this->logFile);
            $candidateDates = $this->getCandidateDates($candidate);
            H::ExcelLog(print_r($candidateDates,true),$this->logFile);
            $thisCandidateSuccess = $this->calculateCandidateJobviteScore($candidate, $candidateDates);
            if($thisCandidateSuccess==1){
                $candidateSuccess++;
            }
        }
        if($candidateCount!=0 && $candidateSuccess!=0){
            $percentSuccess = $candidateSuccess / $candidateCount;
        }
        $jobviteScore = round($percentSuccess * $recruiterPossiblePer['skills_jobvite']);
        H::ExcelLog('Jobvite Score: ' . $percentSuccess . ' of ' .
            $recruiterPossiblePer['skills_jobvite'] . ' for ' . $jobviteScore,$this->logFile);
        return $jobviteScore;
    }

    private function calculateCandidateJobviteScore($candidate, $candidateDates)
    {
        $thisCandidateSuccess = 1;
        if (!empty($candidateDates)) {
            $candidateDatesArr1 = $candidateDates;
            sort($candidateDatesArr1);
            //reset($candidateDatesArr1)
            $workingDays = $this->getWorkingdays($candidate['appSentDate'], reset($candidateDatesArr1));
            H::ExcelLog('working days: ' . $workingDays,$this->logFile);
            if ($workingDays  > 2) {
                H::ExcelLog('true',$this->logFile);
                $thisCandidateSuccess = 0;
            }
        }
        if($thisCandidateSuccess==1 && $this->processPET($candidateDates, 'PET1Date')){
            $thisCandidateSuccess = 0;
        }
        if($thisCandidateSuccess==1 && $this->processPET($candidateDates, 'PET2Date')){
            $thisCandidateSuccess = 0;
        }
        if($thisCandidateSuccess==1 && $this->processPET($candidateDates, 'PET3Date')){
            $thisCandidateSuccess = 0;
        }
        
        if ($thisCandidateSuccess==1 && date("Y-m-d", strtotime($candidate['recruiterPhoneScreenDate']))
            != date("Y-m-d", strtotime($candidate['FollowUpDateTime']))) {
                $thisCandidateSuccess = 0;
        }
        return $thisCandidateSuccess;
    }

    private function processPET($candidateDates, $status)
    {
        $candidateDatesArr2 = $candidateDates;
        $tmpRecordExists = false;
        H::ExcelLog('This Date for ' . $status . ': ' . print_r($candidateDatesArr2,true),$this->logFile);
        if (!empty($candidateDatesArr2[$status]) && $candidateDatesArr2[$status] != $this->zeroDateFormat) {
            unset($candidateDatesArr2[$status]);
            sort($candidateDatesArr2);
            H::ExcelLog(print_r($candidateDatesArr2,true),$this->logFile);
            foreach ($candidateDatesArr2 as $candidateDatesArr22) {
                H::ExcelLog(print_r($candidateDatesArr22,true),$this->logFile);
                if (isset($candidateDates[$status]) && $candidateDates[$status] <= $candidateDatesArr22) {
                    H::ExcelLog('here',$this->logFile);
                    $workingDays = $this->getWorkingdays($candidateDates[$status], $candidateDatesArr22);
                    H::ExcelLog('working days: ' . $workingDays,$this->logFile);
                    if ($workingDays  > 2) {
                        H::ExcelLog('TRUE',$this->logFile);
                        $tmpRecordExists = true;
                        break;
                    }else{
                        H::ExcelLog('FALSE',$this->logFile);
                    }
                    break;
                }
            }
            return $tmpRecordExists;
        }
    }

    private function getCandidateDates($candidate)
    {
        $statusList = ['recruiterPhoneScreenDate',
                'PET1Date','PET2Date','PET3Date','IPEAScheduledDate',
                'nursingSchoolGradDate','addedToPotentialNurseDate',
                'offerRejectedDate','rejectedByAgencyDate'];
        $candidateDates = [];
        foreach($statusList as $thisStatus){
            if($this->getValidCandidateDate($candidate, $thisStatus, $candidateDates)!=''){
                $candidateDates[$thisStatus] = $this->getValidCandidateDate($candidate, $thisStatus, $candidateDates);
            }
        }
        return $candidateDates;
    }

    private function getValidCandidateDate($candidate, $status)
    {
        H::ExcelLog($status . ' - ' . $candidate[$status],$this->logFile);
        if (!empty($candidate[$status]) && $candidate[$status] != '0000-00-00 00:00:00'
            && $candidate[$status] > $candidate['appSentDate']) {
            return $candidate[$status];
        }else{
            return '';
        }
    }

    private function getNthChecklistScore($startDate, $lastDate, $id, $recruiterPossiblePer)
    {
        $checklistCount = 0;
        $checklistworked = 0;
        $nthChecklist = DB::table("NeedToHire_Checklist")
            ->leftJoin('NeedToHire_Accounts', 'NeedToHire_Accounts.id', '=',
                'NeedToHire_Checklist.aoc_nthaccounts_id')
            ->leftJoin('mssql_patients', 'NeedToHire_Accounts.aoc_patient_id', '=',
                'mssql_patients.id')
            ->select('mssql_patients.PatientAccount', 'NeedToHire_Checklist.created_at',
                    'NeedToHire_Accounts.IsActive', 'NeedToHire_Accounts.updated_at')
            ->selectRaw("(select min(created_at) from notes where entity = 'NeedToHire' and 
                            entity_id = NeedToHire_Checklist.id and misc1 = 'TextBlastINT') as TextBlastStarted,
                        (select min(created_at) from notes where entity = 'NeedToHire' and
                            entity_id = NeedToHire_Checklist.id and misc1 = 'PETINT') as PETStarted,
                        (select min(created_at) from notes where entity = 'NeedToHire' and
                            entity_id = NeedToHire_Checklist.id and misc1 = 'SocialMediaINT') as SocialMediaStarted")
            ->whereBetween('NeedToHire_Checklist.created_at', [$startDate, $lastDate])
            ->where('NeedToHire_Accounts.user_id',$id)
            ->get()->toArray();

        $nthChecklist = json_decode(json_encode($nthChecklist), true);
        foreach($nthChecklist as $item){
            if($item['IsActive']==1 || $this->getWorkingdays($item['created_at'], $item['updated_at']) > 1){
                $checklistCount++;
                // make sure each step was worked in time
                if($this->processNthStep($item,'TextBlastStarted')
                    && $this->processNthStep($item,'PETStarted')
                    && $this->processNthStep($item,'SocialMediaStarted')){
                        $checklistworked++;
                }
            }
        }
        H::ExcelLog('NTH Records worked: ' . $checklistworked . ' out of ' . $checklistCount,$this->logFile);
        if($checklistCount==0){
            $tmpScore = 0;
        }else{
            $tmpScore =  round($checklistworked / $checklistCount *
                    $recruiterPossiblePer['skills_nth_checklists_worked']);
        }
        return $tmpScore;
    }

    private function processNthStep($item, $step)
    {
        $stepWorked = true;
        H::ExcelLog('nth (' . $step . ') - ' . print_r($item,true),$this->logFile);
        if (!empty($item[$step]) && $item[$step] != $this->zeroDateFormat) {
            $compareDate = $item[$step];
        }else{
            $compareDate = date('Y-m-d');
        }
        $workingDays = $this->getWorkingdays($item['created_at'], $compareDate);
        H::ExcelLog('working days: ' . $workingDays,$this->logFile);
        if ($workingDays  > 2 && $item['IsActive']==1) {
            $stepWorked = false;
        }
        return $stepWorked;
    }

    public function recruiterScoreCard() {
        return view('recruiterScorecard.grid');
    }

    public function getRecruiterScoreCardList(Request $request) {

        $columns = [
            0 => 'scorecard_date',
            1 => 'manager.name',
            2 => 'recruiter.name',
            3 => 'submitted_at',
        ];

        $recruiterScoreCardLists = RecruiterScorecard::select('recruiter_scorecards.*',
            'recruiter.name as recruiter_name','manager.name as manager_name')
            ->join('users as recruiter', 'recruiter.id', '=', 'recruiter_id')
            ->join('users as manager', 'manager.id', '=', 'recruiter_scorecards.manager_id');

        if (isset($request->search['value']) && $request->search['value'] != '') {
            $searchValue = '%' . $request->search['value'] . '%';
            $recruiterScoreCardLists = $recruiterScoreCardLists
            ->where(function ($query) use ($searchValue) {
                $query->where('recruiter.name', 'like', $searchValue)
                    ->orWhere('manager.name', 'like', $searchValue);
            });
        }


        //filters
        if (isset(($request['filters']))) {
            $recruiterScoreCardLists = $this->setFilters($request, $recruiterScoreCardLists);
        }

        if (isset($request['order'][0]) && $request['order'][0] != '') {
            $recruiterScoreCardLists = $recruiterScoreCardLists
                ->orderby($columns[intval($request['order'][0]['column'])], $request['order'][0]['dir']);
        }
        $total_records = $recruiterScoreCardLists->get();
        if (isset($request['start']) && $request['length'] != '-1') {
            $recruiterScoreCardLists = $recruiterScoreCardLists->offset($request['start'])->limit($request['length']);
        }

        $recruiterScoreCardLists = $recruiterScoreCardLists->get()->toArray();

        $cnt = 0;
        foreach ($recruiterScoreCardLists as $RecruiterScoreCardList) {
            $recruiterScoreCardLists[$cnt]['month'] = H::getFormatedDate($RecruiterScoreCardList['scorecard_date']);
            $recruiterScoreCardLists[$cnt]['manager'] = $RecruiterScoreCardList['manager_name'];
            $recruiterScoreCardLists[$cnt]['recruiter'] = $RecruiterScoreCardList['recruiter_name'];
            $recruiterScoreCardLists[$cnt]['submitted_on'] = $RecruiterScoreCardList['submitted_at'] ?
                H::getFormatedDate($RecruiterScoreCardList['submitted_at']) : '';
            $btn = '';
            if (empty($RecruiterScoreCardList['submitted_at'])) {
                $btn = '<a href="javascript:recruiterScorecard(\'edit\', \''.
                    $RecruiterScoreCardList['id'].'\')" class="edit"><i class="fa fa-edit" title="Edit"></i></a>';
            } else {
                $btn = '<a href="javascript:recruiterScorecard(\'view\', \''.
                    $RecruiterScoreCardList['id'].'\')" class="disable-it"  data-href=""
                        class="disable btn btn-info btn-sm"><i class="fa fa-eye" title="View"></i></a>';
            }

            $btn .= '<a href="javascript:void(0)" class="delete-it"
                    data-href="'. route('recruiterScoreCardDelete') . '?id=' . $RecruiterScoreCardList['id'] . '"
                    class="delete btn btn-info btn-sm"><i class="fa fa-trash-o" title="Delete"></i></a>';

            $recruiterScoreCardLists[$cnt]['action'] = $btn;
            $cnt++;
        }
        $data = [
            'draw'            => intval($request['draw']),
            'recordsTotal'    => intval(count($total_records)),
            'recordsFiltered' => intval(count($total_records)),
            'data'            => $recruiterScoreCardLists,
        ];

        return $data;
    }


    private function setFilters($request, $recruiterScoreCardLists)
    {
            $recruiter = [];
            $recruiterManager = [];
            $fromDate = $toDate = null;
            $filters = $request['filters'];
            foreach ($filters as $filter) {
                if($filter['name'] == 'Recruiter') {
                    $recruiter[] = $filter['value'];
                }elseif($filter['name'] == 'Recruiting_Manager') {
                    $recruiterManager[] = $filter['value'];
                }elseif($filter['name'] == 'from_date' && $filter['value'] != null){
                    $fromDate = date('Y-m-d',strtotime($filter['value']));
                }elseif($filter['name'] == 'to_date' && $filter['value'] != null){
                    $toDate = date('Y-m-d',strtotime($filter['value']));
                }
            }
          
            if(!empty($recruiter)){
                $recruiterScoreCardLists = $recruiterScoreCardLists->whereIn('recruiter.email', $recruiter);
            }
            if(!empty($recruiterManager)){
                $recruiterScoreCardLists = $recruiterScoreCardLists->whereIn('manager.email', $recruiterManager);
            }
            if($fromDate != null && $toDate != null){
                $recruiterScoreCardLists = $recruiterScoreCardLists
                    ->whereBetween('recruiter_scorecards.scorecard_date', [$fromDate, $toDate]);
            }
        return $recruiterScoreCardLists;
    }



    public function submitRecruiterScorecard(Request $request) {
        H::ExcelLog('Submitting Scorecard Start',$this->logFile);
        $reqData = $request->all();
        H::ExcelLog('Request Data' . print_r($reqData, true) ,$this->logFile);
        try {
            $grade = '';
            $action = '';
            $totalSum = 0;
            $users = H::getLoggedInUser();
            $recruiterPossiblePer = config('constant')['recruiter_scorecard_per'];
            $recruiterScoreCard = RecruiterScorecard::find($reqData['id']);
            H::ExcelLog('Fields values',$this->logFile);
            foreach(array_keys($recruiterPossiblePer) as $key) {
                $totalSum += $recruiterScoreCard->{$key};
                H::ExcelLog($key . ' - ' . $recruiterScoreCard->{$key}, $this->logFile);
            }
            if ($totalSum >=90 && $totalSum <= 100) {
                $grade = 'A';
                $action = 'Where we want all recruiters';
            } elseif ($totalSum >=80 && $totalSum <= 89) {
                $grade = 'B';
                $action = 'Verbal Coaching';
            } elseif ($totalSum >=70 && $totalSum <= 79) {
                $grade = 'C';
                $action = 'Written Warning and 14 day PIP';
            } elseif ($totalSum >=60 && $totalSum <= 69) {
                $grade = 'D';
                $action = 'PIP, with 14 day improvement plan, or termination';
            } elseif ($totalSum >=0 && $totalSum <= 59) {
                $grade = 'F';
                $action = 'Termination';
            }
            H::ExcelLog('Total : ' . $totalSum, $this->logFile);
            H::ExcelLog('Grade : ' . $grade, $this->logFile);
            H::ExcelLog('Action : ' . $action, $this->logFile);
            RecruiterScorecard::where('id', $reqData['id'])
                ->update(['score' => $totalSum,
                        'grade' => $grade,
                        'action' => $action,
                        'submitted_at' => date('Y-m-d'),
                        'submitted_by' => $users['id']]);
            H::ExcelLog('Submitting Scorecard End',$this->logFile);
            return response()->json(['status'=>true]);
        } catch (\Exception $e) {
            return response()->json(['status'=>false,'msg'=>$e->getMessage()]);
        }
    }

    protected function getLast12Months() {
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = date("Y-m", strtotime( date( 'Y-m-01' )." -$i months"));
        }
        return $months;
    }

    public function recruiterScorecardReport(Request $request) {
        $request = $request->all();
        $result = [];
        if (!empty($request['to_date']) && !empty($request['from_date'])) {
            $period = \Carbon\CarbonPeriod::create(date("Y-m-d",
                strtotime($request['from_date'])), '1 month', date("Y-m-d", strtotime($request['to_date'])));
            $months = [];
            foreach ($period as $dt) {
                $months[] = $dt->format("Y-m");
            }
            $months = array_reverse($months);
        } else {
            $months = $this->getLast12Months();
        }
        $startDate = $months[count($months) - 1]."-01";
        $endDate = date('Y-m-t', strtotime($months[0].'-01'));
        $recruiterData = RecruiterScorecard::whereBetween('scorecard_date', [$startDate, $endDate])
            ->whereNotNull('submitted_by')
            ->get()
            ->toArray();
        foreach ($recruiterData as $key => $data) {
            $recruiter = DB::table('users')
                ->select('name')
                ->where('id', $data['recruiter_id'])
                ->first();
            $result[$data['id'].'#'.$data['recruiter_id'].'#'.
                $recruiter->name][date('Y-m',strtotime($data['scorecard_date']))] = $data['score'];
        }
        return view('reports.recruiterscorecard', compact('result', 'months', 'startDate', 'endDate'));
    }

    public function delete(Request $request)
    {
        try {
            if ($request->input('id') != '') {
                RecruiterScorecard::where('id', $request->input('id'))->delete();
                return response()->json(["status" => true]);
            } else {
                return response()->json(['status' => false]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false]);
        }
    }
}
