<?php

namespace App\Http\Controllers\Api;

use App\Enums\NTH\ScheduleTypeEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use DB;
use App\Helpers\Helper;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Validator;
use App\Models\ExternalUsers;
use App\Models\MssqlPatients;

class NeedToHireController extends Controller
{
    //
    public $buckets = [
        "hub_city_requisition" => ["start" => "HUBcityStarted", "work" => "HUBcityWorked", "heading" => "Hub City Requisition", "subheading" => "Confirm HUB city ad is open"],
        'text_blast' => ["number" => "TextBlastINT", "start" => "TextBlastStarted", "work" => "TextBlastWorked", "heading" => "Text Blast", "subheading" => "Texted and emailed all nurses on text blast map and potential nurse list map"],
        'pet' => ["number" => "PETINT", "start" => "PETStarted", "work" => "PETWorked", "heading" => 'P.E.T', "subheading" => 'P.E.T. all candidates within open and closed requisitions'],
        'social_media' => ["number" => "SocialMediaINT", "start" => "SocialMediaStarted", "work" => "SocialMediaWorked", "heading" => 'Social Media', "subheading" => 'Join and post in FB groups in the clients city, surrounding cities and county & graduating nurses from prior years closed groups'],
        'private_message' => ["number" => "PrivateMessageINT", "start" => "PrivateMessageStarted", "work" => "PrivateMessageWorked", "heading" => 'Private Message', "subheading" => 'Private message nurses that I am connected with, that are in closed FB groups or have commented on competition pages'],
        'linkedin' => ["number" => 'LinkedININT', "start" => "LinkedINStarted", "work" => "LinkedINWorked", "heading" => 'Linkedin', "subheading" => 'Find and connect with and message nurses on LinkedIn'],
        'indeed' => ["number" => "IndeedINT", "start" => "IndeedStarted", "work" => "IndeedWorked", "heading" => "Indeed", "subheading" => 'Use Indeed resume search to headhunt nurses'],
        'cold_call' => ["number" => "ColdCallINT", "start" => "ColdCallStarted", "work" => "ColdCallWorked", "heading" => "Cold Call", "subheading" => 'Cold call nurses that are in texting and cold calling maps'],
        'mail_matters' => ["number" => "SnailMailINT", "start" => "SnailMailStarted", "work" => "SnailMailWorked", "heading" => 'Mail Matters', "subheading" => 'Mail out handwritten letters or postcards using the BON list'],
        'bon_nurse_search' => ["number" => "BONNursePMINT", "start" => "BONNursePMStarted", "work" => "BONNursePMWorked", "heading" => 'Bon Nurse Search', "subheading" => 'Search for BON nurses on Facebook and LinkedIn and private message them'],
        'social_media_sponsored' => ["number" => "SocialMediaSponsored", "start" => "SocialMediaSponsoredStarted", "work" => "SocialMediaSponsoredWorked", "heading" => 'Social Media Sponsored', "subheading" => 'Social Media Ad Requested'],
        'escaleted' => ["number" => "Escalated", "start" => "EscalatedStarted", "work" => "EscalatedWorked", "heading" => 'Escalated', "subheading" => 'Escalated to Team Lead and Manager']
    ];

    public function getNThcheklist(Request $request)
    {
        $validation = $this->validatePatientDetails($request->all());

        if ($validation->fails()) {
            return Helper::response(config('constants.REQUIRED'), [], $validation->errors()->all());
        }
        $patient_id = $request->patient_id;
        try {
            $accountDetail = DB::table('NeedToHire_Accounts')
            ->where('IsActive', 1)
            ->where('aoc_patient_id', $patient_id)->first();
            if (!empty($accountDetail)) {
                $needToHireChecklistDetails = DB::table('NeedToHire_Checklist')->where('aoc_nthaccounts_id', $accountDetail->id)->first();
                $nthactivity = [];
                $hubCityCheckbox = 0;
                if($needToHireChecklistDetails->HUBcity == 1) {
                    $hubCityCheckbox = 1;
                }
                $step1Array = ['nth_checklist_step' => __('messages.recruiting_ad_opened'),"contacted" => "","is_checkbox" => $hubCityCheckbox];
                if($hubCityCheckbox == 1) {
                    $nthactivity[] = $step1Array;
                }

                $numberOfEmployees = $needToHireChecklistDetails->TextBlastINT + $needToHireChecklistDetails->ColdCallINT;
                $numberOfEmployees = number_format($numberOfEmployees);
                $step2Array = ['nth_checklist_step' => __('messages.number_of_aoc_employees'),
                "contacted" => __('messages.contacted') . ": ".$numberOfEmployees,"is_checkbox" => 0];
                if($numberOfEmployees > 0) {
                    $nthactivity[] = $step2Array;
                }

                $numberOfCandidates = $needToHireChecklistDetails->PETINT??0;
                $numberOfCandidates = number_format($numberOfCandidates);
                $step3Array = ['nth_checklist_step' => __('messages.number_of_aoc_candidates'),
                "contacted" => __('messages.contacted') . ": ".$numberOfCandidates,"is_checkbox" => 0];
                if($numberOfCandidates > 0) {
                    $nthactivity[] = $step3Array;
                }
                $numberOfContactedSocialMedia = $needToHireChecklistDetails->SocialMediaINT + $needToHireChecklistDetails->PrivateMessageINT
                + $needToHireChecklistDetails->LinkedININT + $needToHireChecklistDetails->IndeedINT  + $needToHireChecklistDetails->BONNursePMINT;
                $numberOfContactedSocialMedia = number_format($numberOfContactedSocialMedia);
                $step4Array = ['nth_checklist_step' => __('messages.number_of_nurses_social_media'),
                "contacted" => __('messages.contacted') . ": ".$numberOfContactedSocialMedia,"is_checkbox" => 0];
                if($numberOfContactedSocialMedia > 0) {
                    $nthactivity[] = $step4Array;
                }
                $numberOfPostCard = $needToHireChecklistDetails->SnailMailINT??0;
                $numberOfPostCard = number_format($numberOfPostCard);
                $step5Array = ['nth_checklist_step' => __('messages.number_of_letters_postcards'),
                "contacted" => __('messages.contacted') . ": ".$numberOfPostCard,"is_checkbox" => 0];
                if($numberOfPostCard > 0) {
                    $nthactivity[] = $step5Array;
                }
                $socialMediaSponsoredCheckbox = 0;
                if($needToHireChecklistDetails->SocialMediaSponsored == 1) {
                    $socialMediaSponsoredCheckbox = 1;
                }
                $step6Array = ['nth_checklist_step' => __('messages.social_media_ad_facebook'), "contacted" => "",
                "is_checkbox" => $socialMediaSponsoredCheckbox];
                if($socialMediaSponsoredCheckbox == 1) {
                    $nthactivity[] = $step6Array;
                }
                return Helper::response(config('constants.response_code.OK'), ['nthactivitys' => $nthactivity]);
            } else {
                return Helper::response(config('constants.response_code.OK'), [], __('messages.no_recruiting_updates'));
            }
        } catch (\Exception $e) {
            Helper::ExcelLog('getNThcheklist Error: ' . $e->getMessage(), 'getNThcheklist.log');
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }
    public function getBuckets()
    {
        return $this->buckets;
    }
    public function getLast7DaysNumber($type, $checklistId, $field = 'misc2')
    {
        $data = DB::table("notes")
            ->select(DB::raw('sum(' . $field . ') as last7DaysNumber'))
            ->where('entity', 'NeedToHire')
            ->where('type', 'NTH')
            ->where('entity_id', $checklistId)
            ->where('misc1', $type)
            ->whereDate('created_at', '>', date('Y-m-d H:i:s', strtotime('-1 week')))->get();
        return isset($data[0]->last7DaysNumber) && $data[0]->last7DaysNumber != 0 ? $data[0]->last7DaysNumber : '';
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getUpcomingAppointments(Request $request): JsonResponse
    {
        $validation = $this->validatePatientDetails($request->all());

        if ($validation->fails()) {
            return Helper::response(config('constants.REQUIRED'), [], $validation->errors()->all());
        }

        $patient_ids = $request->patients_ids;
        $currentDate = Carbon::now();
        $futureDate = $currentDate->addMonths(6)->format('Y-m-d');

        try {
            $upcomingAppointments = DB::table('recruiting_schedule')
                ->leftJoin('schedule_types', 'recruiting_schedule.type_id', '=', 'schedule_types.id')
                ->leftJoin('mssql_recruitertracker', 'mssql_recruitertracker.id', '=', 'recruiting_schedule.tracker_id')
                ->select(
                'schedule_types.type as type',
                'recruiting_schedule.*',
                'mssql_recruitertracker.Candidate',
                'mssql_recruitertracker.Skillset',
                'mssql_recruitertracker.Schedule'
                )
                ->whereIn('recruiting_schedule.aoc_patient_id', $patient_ids)
                ->whereDate('appointment_date', '>=', date('Y-m-d'))
                ->whereNotIn('recruiting_schedule.type_id', [
                    ScheduleTypeEnum::CCR->value,
                    ScheduleTypeEnum::PEPI->value,
                ])
                ->orderBy('appointment_date', 'asc')->get();

            $careGiverAppointments = DB::table('recruiting_schedule')
                ->leftJoin('schedule_types', 'recruiting_schedule.type_id', '=',
                'schedule_types.id')
                ->leftJoin('mssql_recruitertracker', 'mssql_recruitertracker.id', '=',
                'recruiting_schedule.tracker_id')
                ->select('schedule_types.type as type', 'recruiting_schedule.*',
                'mssql_recruitertracker.Candidate', 'mssql_recruitertracker.Skillset',
                'mssql_recruitertracker.Schedule')
                ->whereIn('recruiting_schedule.aoc_patient_id', $patient_ids)
                ->whereDate('appointment_date', '>=', date('Y-m-d'))
                ->whereIn('recruiting_schedule.type_id', [ScheduleTypeEnum::PEPI->value])
                ->orderBy('appointment_date', 'asc')->get();

            $schedule = DB::table('mssql_schedulemaster')
                ->select('mssql_schedulemaster.*',

                    DB::raw("CONCAT(mssql_providers.FirstName,' ',
            IF(mssql_providers.MiddleName != '',CONCAT(mssql_providers.MiddleName, ' '),''),
            mssql_providers.LastName) as providerFullName"))
                ->leftJoin('mssql_providers', 'mssql_providers.ProviderID', '=',
                    'mssql_schedulemaster.ProviderID')
                ->join('mssql_patients', 'mssql_patients.PatientID', '=',
                    'mssql_schedulemaster.PatientID')
                ->whereIn('mssql_patients.id', $patient_ids)
                ->where('mssql_schedulemaster.PlannedStart', '<', $futureDate)
                ->where('mssql_schedulemaster.PlannedStart', '>=', date('Y-m-d'))
                ->get();

            $upcoimrArray = $careGiverData = $scheduledata = [];

            if (count($upcomingAppointments) == 0 && count($careGiverAppointments) == 0 && count($schedule) == 0) {
                Helper::ExcelLog('There are no upcoming appointments.', 'getUpcomingAppointments.log');
                return Helper::response(config('constants.response_code.OK'), [], __('messages.no_upcoming_appointments'));
            }

            if (!empty($upcomingAppointments)) {
                foreach ($upcomingAppointments as $key => $data) {
                    $upcoimrArray[$key]['type'] = $this->getTypeDetail($data->type);
                    $upcoimrArray[$key]['date'] = Helper::getFormatedDate($data->appointment_date);
                    $upcoimrArray[$key]['time'] = Helper::getFormatedTime($data->appointment_time);
                    $upcoimrArray[$key]['clinician'] = $this->maskName($data->Candidate);
                    $upcoimrArray[$key]['skillset'] = $data->Skillset;
                    $upcoimrArray[$key]['Schedule'] = $data->Schedule;
                }
            }

            if (!empty($careGiverAppointments)) {
                foreach ($careGiverAppointments as $key => $data) {
                    $careGiverData[$key]['type'] = $this->getTypeDetail($data->type);
                    $careGiverData[$key]['date'] = Helper::getFormatedDate($data->appointment_date);
                    $careGiverData[$key]['time'] = Helper::getFormatedTime($data->appointment_time);
                    $careGiverData[$key]['clinician'] = $this->maskName($data->Candidate);
                    $careGiverData[$key]['skillset'] = $data->Skillset;
                    $careGiverData[$key]['Schedule'] = $data->Schedule;
                }
            }

            if (!empty($schedule)) {
                foreach ($schedule as $key => $data) {
                    $scheduledata[$key]['PatientID'] = $data->PatientID;
                    $scheduledata[$key]['fullName'] = $data->providerFullName; // No redaction for Reassessments
                    $scheduledata[$key]['PlannedStart'] = Helper::getDateAndReadableTime($data->PlannedStart);
                    $scheduledata[$key]['PlannedEnd'] = Helper::getDateAndReadableTime($data->PlannedEnd);
                    $scheduledata[$key]['PlannedHours'] = number_format($data->PlannedHours, 2);
                }
            }

            if (empty($upcoimrArray) && empty($careGiverData) && empty($scheduledata)) {
                Helper::ExcelLog('All appointment arrays empty after processing.', 'getUpcomingAppointments.log');
                return Helper::response(config('constants.response_code.OK'), [], __('messages.no_upcoming_appointments'));
            }

            return Helper::response(config('constants.response_code.OK'), ['upcomingappointments' => $upcoimrArray,'caregiver' => $careGiverData,'schedule' => $scheduledata]);
        } catch (\Exception $e) {
            Log::error('getUpcomingAppointments Exception: ' . $e->getMessage());
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }

    private function validatePatientDetails($request)
    {
        return Validator::make(
            $request,
            [
                'patient_id' => 'required'
            ],
            [
                'patient_id.required' => __('messages.patient_id_required')
            ]
        );
    }

    /**
     * @param $name
     * @return string
     */
    private function maskName($name): string
    {
        if (is_null($name)) {
            return '';
        }

        $name = trim($name);

        if ($name === '') {
            return '';
        }

        $parts = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY);

        $masked = array_map(function($part) {
            $len = strlen($part);

            if ($len <= 1) {
                return $part;
            }

            return substr($part, 0, 1) . str_repeat('*', $len - 1);
        }, $parts);

        return implode(' ', $masked);
    }

    private function getTypeDetail($type)
    {
        $typeDetail = __('messages.office_interview');
        if($type == 'WEPI') {
            $typeDetail = __('messages.working_interview');
        } elseif($type == 'Candidate Start') {
            $typeDetail = __('messages.clinicians_first_shift');
        } elseif($type == 'PEPI') {
            $typeDetail = __('messages.parent_interview');
        }
        return $typeDetail;
    }
    public function getNTHSchedules(Request $request)
    {
        $validation = $this->validatePatientDetails($request->all());

        if ($validation->fails()) {
            return Helper::response(config('constants.REQUIRED'), [], $validation->errors()->all());
        }
        $patient_id = $request->patient_id;
        try {
            $nthDetails = DB::table('mssql_patients')->leftJoin('NeedToHire_Accounts', 'mssql_patients.id', '=', 'NeedToHire_Accounts.aoc_patient_id')
            ->select('NeedToHire_Accounts.id as nthaccounts_id')->where('NeedToHire_Accounts.aoc_patient_id', $patient_id)
            ->where('NeedToHire_Accounts.IsActive', 1)->orderBy("NeedToHire_Accounts.id", "DESC")->first();
            if(!empty($nthDetails)) {
                $nthSchedules = DB::table("NeedToHire_Schedules")
                ->select("NeedToHire_Schedules.id",
                "NeedToHire_Schedules.StartDayOfWeek",
                "NeedToHire_Schedules.StartHour",
                "NeedToHire_Schedules.StartMinute",
                "NeedToHire_Schedules.EndHour",
                "NeedToHire_Schedules.EndMinute",
                "NeedToHire_Schedules.ShiftHours",
                "NeedToHire_Schedules.ShiftTypeDescription")
                ->where("IsActive", 1)
                ->where("aoc_nthaccounts_id", $nthDetails->nthaccounts_id)
                ->orderByRaw("NeedToHire_Schedules.StartDayOfWeekInt=1, NeedToHire_Schedules.StartDayOfWeekInt")
                ->orderBy("NeedToHire_Schedules.StartHour", "ASC")
                ->orderBy("NeedToHire_Schedules.StartMinute", "ASC")->get();
                $nthSchedulesList = [];
                if (count($nthSchedules) > 0) {
                    foreach ($nthSchedules as $key => $data) {
                        $nthSchedulesList[$key]['day'] = $data->StartDayOfWeek;
                        $nthSchedulesList[$key]['start_time'] = Helper::getReadableTime($data->StartHour.':'.$data->StartMinute);
                        $nthSchedulesList[$key]['end_time'] = Helper::getReadableTime($data->EndHour.':'.$data->EndMinute);
                        $nthSchedulesList[$key]['hours'] = $data->ShiftHours;
                        $nthSchedulesList[$key]['aoc_schedules_id'] = $data->id;
                        $shift_type = 'Day';
                        if($data->ShiftTypeDescription=='Weekend Nights (PM-AM)'
                        || $data->ShiftTypeDescription=='Weekday Nights (PM-AM)') {
                            $shift_type = 'Night';
                        }
                        $nthSchedulesList[$key]['shift_type'] = $shift_type;
                    }
                    return Helper::response(config('constants.response_code.OK'), ['aoc_nthaccounts_id' => $nthDetails->nthaccounts_id,'nthschedules' => $nthSchedulesList]);
                } else {
                    return Helper::response(config('constants.response_code.OK'), [], __('messages.no_nth_schedules'));
                }
            } else {
                return Helper::response(config('constants.response_code.OK'), [], __('messages.no_nth_schedules'));
            }
        } catch (\Exception $e) {
            Helper::ExcelLog('getNTHSchedules Exception: ' . $e->getMessage(), 'getNTHSchedules.log');
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }

    public function updateNTHSchedules(Request $request)
    {
        try{
            $reqData = $request->all();
            $patient_id = $request->patient_id;
            if(isset($reqData['aoc_schedules_id']) && count($reqData['aoc_schedules_id']) > 0) {
                $reqData['aoc_schedules_id'] = array_unique($reqData['aoc_schedules_id']);
            }
            if(isset($reqData['selectdays']) && count($reqData['selectdays']) > 0) {
                $reqData['selectdays'] = array_unique($reqData['selectdays']);
            }
            if($reqData['mode'] != 'delete') {
                if($this->validateSchedule($reqData)) {
                    return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.schedule_already_taken'));
                }
            }
            $userDetail = ExternalUsers::find($reqData['id']);
            $patienDetails = MssqlPatients::getPatientDetails($patient_id,['mssql_patients.ccc_id']);
            //Added activity history
            $reason = '';
            if(!empty($reqData['comment'])) {
                $reason = $reqData['comment'];
            }
            $existNoteDetailStr = '';
            if(isset($reqData['aoc_schedules_id']) && count($reqData['aoc_schedules_id']) > 0) {
                foreach($reqData['aoc_schedules_id'] as $item) { 
                    $currentData = DB::table('NeedToHire_Schedules')->where("id",$item)->first();
                    if($existNoteDetailStr != '') {
                        $existNoteDetailStr = $existNoteDetailStr."<br><br>";
                    }
                    $existNoteDetailStr = $existNoteDetailStr.'Day:'.$currentData->StartDayOfWeek."<br>";
                    $existNoteDetailStr = $existNoteDetailStr.' Start Time:'.Helper::getReadableTime($currentData->StartHour.':'.$currentData->StartMinute)."<br>";
                    $existNoteDetailStr = $existNoteDetailStr.' End Time:'.Helper::getReadableTime($currentData->EndHour.':'.$currentData->EndMinute)."<br>";
                }
            }
            $newNoteDetailStr = '';
            if(isset($reqData['selectdays']) && count($reqData['selectdays']) > 0) {
                foreach($reqData['selectdays'] as $selectdays) {
                    if($newNoteDetailStr != '') {
                        $newNoteDetailStr = $newNoteDetailStr."<br><br>";
                    }
                    $newNoteDetailStr = $newNoteDetailStr.'Day:'.$selectdays."<br>";
                    $newNoteDetailStr = $newNoteDetailStr.' Start Time:'.Helper::getReadableTime($reqData['start_time'])."<br>";
                    $newNoteDetailStr = $newNoteDetailStr.' End Time:'.Helper::getReadableTime($reqData['end_time'])."<br>";
                }
            }
            if($reqData['mode'] == 'update') {
                $notes = $userDetail->name.' wants to modified schedules on the NTH';
                if($reason != '') {
                    $notes = $notes .' for reason: '.$reason;
                }
                $notes = $notes.' for following schedule';
                $notes = $notes."<br>From <br>".$existNoteDetailStr;
                $notes = $notes."<br><br>To <br>".$newNoteDetailStr;
                $responseMessage = __('messages.permanent_schedule_changes_sent');
            }elseif($reqData['mode'] == 'add') {
                $notes = $userDetail->name.' wants to add schedules on the NTH';
                if($reason != '') {
                    $notes = $notes .' for reason: '.$reason;
                }
                $notes = $notes.' for following schedule';
                $notes = $notes."<br> ".$newNoteDetailStr;
                $responseMessage = __('messages.permanent_schedule_changes_sent');
            }elseif($reqData['mode'] == 'delete') {
                $notes = $userDetail->name.' wants to delete schedules on the NTH';
                if($reason != '') {
                    $notes = $notes .' for reason: '.$reason;
                }
                $notes = $notes.' for following schedule';
                $notes = $notes."<br> ".$existNoteDetailStr;
                $responseMessage = __('messages.permanent_schedule_changes_sent');
            }

            $notesData = ['entity' => 'nthAccount', 'entity_id' => $reqData['aoc_nthaccounts_id'], 'notes' =>$notes,'completed' => 0,'created_by' => $patient_id,'assigned_to' => $patienDetails->ccc_id, 'misc1' => 'mobileapp'];
            Helper::setActivityNotes($notesData);
            return Helper::response(config('constants.response_code.OK'), [], $responseMessage);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function validateSchedule($reqData) {
        $isConflict = 0;
        foreach($reqData['selectdays'] as $key => $day) {
            $tmp = 0;
            if($day=='Sunday') $tmp = 1;
            if($day=='Monday') $tmp = 2;
            if($day=='Tuesday') $tmp = 3;
            if($day=='Wednesday') $tmp = 4;
            if($day=='Thursday') $tmp = 5;
            if($day=='Friday') $tmp = 6;
            if($day=='Saturday') $tmp = 7;
            $startAt = $tmp . $reqData['start_time'];
            if($reqData['end_time'] < $reqData['start_time']){
                $tmp = $tmp + 1;
                if($tmp > 7) $tmp = 1;
            }
            $endAt = $tmp . $reqData['end_time'];
            $itemExclude = "";
            if(isset($reqData['aoc_schedules_id'])) {
                $aoc_schedules_id_str = "'".implode("','",$reqData['aoc_schedules_id'])."'";
                $itemExclude=' and id not in('.$aoc_schedules_id_str.')';
            }
            $query = "with cte as
                        (SELECT StartDayOfWeek, StartDayOfWeekInt, StartHour, StartMinute, EndHour, EndMinute,
                        concat(right(concat('0',StartHour),2),':',right(concat('0',StartMinute),2)) as StartTime,
                        concat(StartDayOfWeekInt,concat(right(concat('0',StartHour),2),':',right(concat('0',StartMinute),2))) as StartAt,
                        concat(right(concat('0',EndHour),2),':',right(concat('0',EndMinute),2)) as ending,
                        case when StartAndEndOnSameDay then
                            concat(StartDayOfWeekInt,concat(right(concat('0',EndHour),2),':',right(concat('0',EndMinute),2)))
                            else
                                case when StartDayOfWeekInt = 7 then
                                    concat('1',concat(right(concat('0',EndHour),2),':',right(concat('0',EndMinute),2)))
                                else
                                    concat(StartDayOfWeekInt+1,concat(right(concat('0',EndHour),2),':',right(concat('0',EndMinute),2)))
                                end
                        end as endAt

                        FROM `NeedToHire_Schedules`
                        WHERE aoc_nthaccounts_id = " . $reqData['aoc_nthaccounts_id'] . " ".$itemExclude." and IsActive = 1)
                        select * from cte where StartAt < '" . $endAt . "' and endAt > '" . $startAt . "';";
            $dbConflict = DB::select($query);
            if(count($dbConflict) > 0) {
                $isConflict = 1;
            }
        }
        return $isConflict;
    }
}
