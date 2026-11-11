<?php

namespace App\Http\Controllers;

use App\BranchLocations;
use App\DTO\PrcCalendarFilterDTO;
use App\Enums\PrcShiftType;
use App\Helpers\Calendar;
use App\Helpers\H;
use App\MssqlFacilities;
use App\MssqlPatients;
use App\MssqlProviders;
use App\MssqlRecruitertracker;
use App\Repositories\CCRAvailability\CCRAvailabilityRepository;
use App\Repositories\CCRCalendar\CCRCalendarReadRepository;
use App\ScheduleType;
use App\Candidates;
use App\RecruitingSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Helpers\Notifications;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CCRCalendarController extends Controller
{
    public function __construct(
        private CCRCalendarReadRepository $ccrCalendarReadRepository,
        private CCRAvailabilityRepository $availabilityRepository,
        private Calendar $calendarHelper
    ) {
    }

    public function getWeekEvents(Request $request)
    {
        $reqData = $request->all();
        $scheduleData = [];
        $blockData = [];
        $week = '';
        $stateName = '';
        if (isset($reqData['date']) && $reqData['date'] != '') {
            $searchArr = [];
            if (isset($reqData['search']) && $reqData['search'] != '') {
                $searchArr = json_decode($reqData['search'], true);
                $reqData['searchData'] = $reqData['search'];
                $reqData['search'] = $searchArr['label'];
            }
            Session::put(['lastCalendarSearch'=>$reqData]);
            $week = Calendar::getWeekStartEndByDates($reqData['date']);

            $state = isset($reqData['state']) ? $reqData['state'] : '';
            $branch = isset($reqData['branch']) && is_array($reqData['branch']) ? array_filter($reqData['branch']) : [];
            $shiftTypes = isset($reqData['shiftTypes']) && is_array($reqData['shiftTypes'])
                ? PrcShiftType::fromValues($reqData['shiftTypes'])
                : [];

            if ($state !== '') {
                $dashboardState = config('constant.dashboard_state');
                $stateName = isset($dashboardState[$state]) ? $dashboardState[$state] : $state;
            }

            $hasAnyFilter = $state !== '' || !empty($branch);

            if ($hasAnyFilter) {
                $schedules = $this->ccrCalendarReadRepository->getWeekEvents(
                    $week['start'],
                    $week['end'],
                    new PrcCalendarFilterDTO($shiftTypes, $state, $branch)
                );
                foreach ($schedules as $key => $value) {
                    if ($value->appointment_time != '00:00:00') {
                        $day = date('l', strtotime($value->appointment_date));
                        $bucket = $this->calendarHelper->clampToHourBucket($value->appointment_time);
                        $scheduleData[$bucket][$day][] = $value;
                    }
                }

                if (!empty($branch)) {
                    $blocks = $this->availabilityRepository->getBlocksForFacilityInRange(
                        array_map('intval', $branch),
                        $week['start'],
                        $week['end']
                    );
                    $blockData = $this->availabilityRepository->bucketBlocksByHourAndDay($blocks);
                } elseif ($state !== '') {
                    $blocks = $this->availabilityRepository->getBlocksForStateInRange(
                        $state,
                        $week['start'],
                        $week['end']
                    );
                    $blockData = $this->availabilityRepository->bucketBlocksByHourAndDay($blocks);
                }
            }
        }

        return view('ccr.weekEvents', ['scheduleData' => $scheduleData, 'blockData' => $blockData, 'week' => $week, 'stateName' => $stateName]);
    }
    public function index(Request $request) : View {

        $prcStates = H::getHelper('CommonDatatableFilters')->getFilterDataSource('state');
        $prcAvailabilityFacilities = H::getHelper('ConstantHelper')->getFacilities();

        return view(
            'ccr.calendar',
            [
                'prcStates' => $prcStates,
                'prcShiftTypes' => PrcShiftType::cases(),
                'prcAvailabilityFacilities' => $prcAvailabilityFacilities,
            ]
        );
    }
    public function addEvent(Request $request){
        $branches = H::getHelper('ConstantHelper')->getFacilitiesByState('TX');
        return view('ccr.addEvent',['branches'=>$branches]);
    }
    public function saveEvent(Request $request){
        try{
            $data = $request->all();
            unset($data['_token']);
            if (isset($data['id'])) {
                $data['appointment_time'] = date("H:i:s",strtotime($data['appointment_time']));
                $data['appointment_end_time'] = date("H:i:s",strtotime($data['appointment_end_time']));
                $data['updated_at'] = date('Y-m-d H:i:s');
                $data['updated_by'] = Auth::user()->id;

                RecruitingSchedule::where('id', $data['id'])->update($data);
            } else {
                $data['appointment_time'] = date("H:i:s",strtotime($data['appointment_time']));
                $data['appointment_end_time'] = date("H:i:s",strtotime($data['appointment_end_time']));
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');
                $data['created_by'] = Auth::user()->id;
                $data['updated_by'] = Auth::user()->id;

                RecruitingSchedule::insert($data);
            }
            return response()->json(['success' => 1, 'message' => 'Event saved successfuly!']);
        } catch (\Exception $ex) {
            return response()->json(['success' => 0, 'message' => 'Something went wrong, Please try again.']);
        }
    }
    public function getEventDetails(Request $request){
        $schedule = [];
        if(isset($request->id) && $request->id != ''){
            $schedule = DB::table('recruiting_schedule')
                    ->leftJoin('mssql_recruitertracker', 'recruiting_schedule.tracker_id', '=', 'mssql_recruitertracker.id')
                    ->leftJoin('candidates', 'recruiting_schedule.aoc_candidate_id', '=', 'candidates.id')
                    ->leftJoin('mssql_providers', 'recruiting_schedule.aoc_provider_id', '=', 'mssql_providers.id')
                    ->leftJoin('mssql_patients', 'recruiting_schedule.aoc_patient_id', '=', 'mssql_patients.id')
                    ->leftJoin('mssql_facilities', 'mssql_facilities.id', '=', 'recruiting_schedule.aoc_facility_id')
                    ->leftJoin('coverage_tracker', 'coverage_tracker.id', '=', 'recruiting_schedule.coverage_id')
                ->select(
                    'recruiting_schedule.*',
                    $this->ccrCalendarReadRepository->candidateSelect(),
                    'mssql_patients.PatientName as client',
                    'mssql_recruitertracker.Recruiter as recruiter',
                    DB::raw(
                        'CASE WHEN mssql_providers.Mobile is not null THEN mssql_providers.Mobile ELSE candidates.mobile END as phone'
                    ),
                    'mssql_recruitertracker.proposed_next_step',
                    'mssql_recruitertracker.proposed_ns_date',
                    'mssql_recruitertracker.proposed_ns_time',
                    'coverage_tracker.shift_date as coverage_shift_date',
                    'coverage_tracker.Schedule_Time as coverage_schedule_time'
                )->where('recruiting_schedule.id', $request->id)->whereRaw(
                    'IFNULL(mssql_providers.is_deleted, 0) = 0'
                )->first();
        }

        return view('ccr.eventDetails',['schedule'=>$schedule]);
    }
    public function fetchScheduleDetailsOfCCR(Request $request){
        try {
            $reqData = $request->all();
            $schedules = [];
            if(isset($reqData['appointment_date']) && $reqData['appointment_date'] != ''){
                $state = isset($reqData['state']) ? $reqData['state'] : '';
                $branch = isset($reqData['branch']) && is_array($reqData['branch']) ? array_filter($reqData['branch']) : [];
                $hasAnyFilter = $state !== '' || !empty($branch);

                if (!$hasAnyFilter) {
                    return response()->json(['status'=>true,'data'=>[]]);
                }

                $appointmentDate = date('Y-m-d', strtotime($reqData['appointment_date']));
                $shiftTypes = isset($reqData['shiftTypes']) && is_array($reqData['shiftTypes'])
                    ? PrcShiftType::fromValues($reqData['shiftTypes'])
                    : [];
                $schedules = $this->ccrCalendarReadRepository->getScheduleDetailsForDate(
                    $appointmentDate,
                    new PrcCalendarFilterDTO($shiftTypes, $state, $branch)
                );
            }else{
                return response()->json(['status'=>false,'msg'=>'Date must be selected']);
            }
            return response()->json(['status'=>true,'data'=>$schedules]);
        } catch (\Exception $e) {
            H::ExcelLog('fetchScheduleDetailsOfCCR:'.$e->getMessage(),'ccr_calendar.log');
            return response()->json(['status'=>false,'msg'=>$e->getMessage()]);
        }
    }
}
