<?php
namespace App\Helpers;

use App\Enums\MssqlRecruiterTrackerActiveCoverageOutcome;
use App\Enums\MssqlRecruiterTrackerLiveIpeaStatus;
use App\User;
use App\WhiteBoard;
use App\MssqlFacilities;
use App\NeedToHireAccounts;
use App\MssqlRecruitertracker;
use Carbon\Carbon;
use App\Enums\NotificationTypeEnum;
use App\Enums\MssqlRecreturTrackerStatus;
use App\Enums\MssqlRecruiterTrackerBonusStatus;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecruiterTrackerHelper
{
    private const CANDIDATE_MIN_START_DATE = '2022-12-31';
    private const COMPLETED_HOURS_AMOUNT = '40';

    public function getWhiteboardRow($userId, $facilityId, $skillSet){
        $whiteBoard = '';
        if($facilityId!=0 && $facilityId!=''){
            $facility = MssqlFacilities::find($facilityId);
            H::ExcelLog($facilityId . ' - ' . $facility->FacilityName,'testWB.log');
            if (!empty($facility)) {
                $whiteBoard = substr($facility->FacilityName, 0, 2);
                if ($skillSet == 'LPN' || $skillSet == 'LVN' || $skillSet == 'RN') {
                    $whiteBoard .= ' - PDN';
                } else {
                    $whiteBoard .= ' - ' . $skillSet;
                }
            }
        }
        //now insert row into whiteboard table if needed
        if($whiteBoard!=''){
            $wbRow = WhiteBoard::select("id")->where('user_id', $userId)->where('row_id', $whiteBoard)->first();
            if(empty($wbRow)) {
                H::ExcelLog('user: '  . $userId,'testWB.log');
                $recruiter = User::find($userId);
                H::ExcelLog(print_r($recruiter,true),'testWP.log');
                $wbRow = ['user_id' => $userId, 'user_name' => $recruiter->name, 'row_number' => $userId, 'row_id' => $whiteBoard, 'territory' => 'Out of Territory',
                                'candidate_start_hour_goals' => 0, 'ipea_goal' => 0, 'pepi_wepi_goal' => 0, 'client_match_goal' => 0, 'client_match_hours_goal' => 0,
                                'candidate_start_goal' => 0, ];
                WhiteBoard::create($wbRow);
            }
        }
        return $whiteBoard;
    }

    public function getnthschedulebyclientid($aocPatient, $selectedIds = false, $returnObj = false,$isActive=true) {


        $NTHSchedulesQuery = NeedToHireAccounts::select("NeedToHire_Schedules.id","NeedToHire_Schedules.StartDayOfWeek","NeedToHire_Schedules.StartHour","NeedToHire_Schedules.StartMinute","NeedToHire_Schedules.EndHour","NeedToHire_Schedules.EndMinute","NeedToHire_Schedules.ShiftHours")->where("NeedToHire_Accounts.IsActive",1)->where("NeedToHire_Accounts.aoc_patient_id",$aocPatient);
        $NTHSchedulesQuery->leftJoin("NeedToHire_Schedules", function($q){
            $q->on("NeedToHire_Accounts.id","=","NeedToHire_Schedules.aoc_nthaccounts_id");
        });
        if($isActive) {
            $NTHSchedulesQuery->where("NeedToHire_Schedules.IsActive",1);
        }
        if ($selectedIds) {
            $NTHSchedulesQuery->whereIn('NeedToHire_Schedules.id', explode(",",$selectedIds));
        }
        $NTHSchedulesQuery->orderBy("NeedToHire_Schedules.StartDayOfWeekInt","ASC");
        $NTHSchedulesQuery->orderBy("NeedToHire_Schedules.StartHour","ASC");
        $NTHSchedulesQuery->orderBy("NeedToHire_Schedules.StartMinute","ASC");

        if ($returnObj) {
            return $NTHSchedulesQuery;
        } else {
            return $NTHSchedulesQuery->get()->toArray();
        }
    }

    public function validateNthSchedules($selectedClientSchedulesIds, $recruiter_nth_schedule_ids) {
        $result = [];
        $recruiter_nth_schedule_ids = array_unique(explode(",", $recruiter_nth_schedule_ids));

        foreach($selectedClientSchedulesIds as $selectedClientSchedulesId) {
            $currentData = DB::table("NeedToHire_Schedules")->where("id", $selectedClientSchedulesId)->first();
            $day = $currentData->StartDayOfWeek;
            $startHour = substr('0' . $currentData->StartHour, -2);
            $endHour = substr('0' . $currentData->EndHour, -2);
            $startMinute = substr('0' . $currentData->StartMinute, -2);
            $endMinute = substr('0' . $currentData->EndMinute, -2);
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
            $startAt = $tmp . $startHour . ':' . $startMinute;
            if ($endHour . ':' . $endMinute < $startHour . ':' . $startMinute) {
                $tmp = $tmp + 1;
                if($tmp > 7) $tmp = 1;
            }
            $endAt = $tmp . $endHour . ':' . $endMinute;

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
            WHERE id IN (" . H::generateQuestionMarksArray($recruiter_nth_schedule_ids) . ") and IsActive = 1)
            select * from cte where StartAt < ? and endAt > ? ;";
            H::ExcelLog($query,'tsValidateSchedules.log');
            $conflictData = DB::select($query,array_merge($recruiter_nth_schedule_ids,[$endAt,$startAt]));
            foreach ($conflictData as $schedule) {
                $result[] = $schedule;
            }
        }

        return $result;
    }

    public function getnthScheduleByScheduleId($selectedIds = false) {
        $NTHSchedulesQuery = NeedToHireAccounts::select("NeedToHire_Schedules.id","NeedToHire_Schedules.StartDayOfWeek","NeedToHire_Schedules.StartHour","NeedToHire_Schedules.StartMinute","NeedToHire_Schedules.EndHour","NeedToHire_Schedules.EndMinute","NeedToHire_Schedules.ShiftHours")->where("NeedToHire_Accounts.IsActive",1)->whereIn('NeedToHire_Schedules.id', $selectedIds);
        $NTHSchedulesQuery->leftJoin("NeedToHire_Schedules", function($q){
            $q->on("NeedToHire_Accounts.id","=","NeedToHire_Schedules.aoc_nthaccounts_id");
        });
        $NTHSchedulesQuery->orderBy("NeedToHire_Schedules.StartDayOfWeekInt","ASC");
        $NTHSchedulesQuery->orderBy("NeedToHire_Schedules.StartHour","ASC");
        $NTHSchedulesQuery->orderBy("NeedToHire_Schedules.StartMinute","ASC");
        return $NTHSchedulesQuery->get()->toArray();
    }

    public function markCrossRecruiterDuplicatesIneligible(): void
    {
        //Bonus eligibility: only the first match (by Candidate_Start_Date) for a given
        //clinician+client pair within a rolling 30-day window may qualify for a bonus,
        //regardless of which recruiter created the match. Later matches within that
        //window are permanently marked Bonus Ineligible for audit/reporting purposes.
        //DATE_SUB stays in SQL (not Carbon) because it must be evaluated per-row against
        //this row's own Candidate_Start_Date, not against a single fixed PHP-side date.
        $duplicateIds = MssqlRecruitertracker::query()
            ->where('Bonus_Status', '=', MssqlRecruiterTrackerBonusStatus::PENDING_HOURS->value)
            ->where('Candidate_Current_Status', '=', MssqlRecreturTrackerStatus::FIRST_SHIFT_CONFIRMED->value)
            ->where('Candidate_Start_Date', '>', '2022-12-31')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('mssql_recruitertracker as rt2')
                    ->whereColumn('rt2.aoc_provider_id', 'mssql_recruitertracker.aoc_provider_id')
                    ->whereColumn('rt2.aoc_patient_id', 'mssql_recruitertracker.aoc_patient_id')
                    ->whereColumn('rt2.id', '<>', 'mssql_recruitertracker.id')
                    ->where('rt2.is_deleted', '=', 0)
                    ->whereNotNull('rt2.Candidate_Start_Date')
                    ->whereColumn('rt2.Candidate_Start_Date', '<', 'mssql_recruitertracker.Candidate_Start_Date')
                    ->whereRaw('rt2.Candidate_Start_Date >= DATE_SUB(mssql_recruitertracker.Candidate_Start_Date, INTERVAL 30 DAY)');
            })
            ->pluck('id');

        foreach ($duplicateIds as $id) {
            DB::transaction(function () use ($id) {
                MssqlRecruitertracker::where('id', $id)->update(['Bonus_Status' => MssqlRecruiterTrackerBonusStatus::BONUS_INELIGIBLE->value]);
                H::getHelper('ActivityNotes')->setActivityNotesFromConsole([
                    'entity' => NotificationTypeEnum::TRACKER_ACTIVITY_TYPE->value,
                    'entity_id' => $id,
                    'notes' => 'Bonus ineligible',
                ]);
            });
        }
    }

    private const HOURS_WORKED_UPDATE_CHUNK_SIZE = 300;

    public function updateHoursWorkedToTrackers(): void
    {
        //first update hours worked to each tracker
        //Nik here
        //$startDate = '2023-05-01';
        //$endDate = '2023-06-04';
        //$weeks = 5;
		$startDate = date("Y-m-d", strtotime("first Monday of Previous Month"));
		$endDate = date("Y-m-d", strtotime('next sunday', strtotime(date("Y-m-d", strtotime("last Monday of Previous Month")))));

		$startDateObj = new \DateTime($startDate);
		$endDateObj = new \DateTime($endDate);

		$diff = $endDateObj->diff($startDateObj);
		$weeks = ceil($diff->days / 7);

        $pendingHoursTrackerIds = fn () => DB::table('mssql_recruitertracker')
            ->where('Bonus_Status','=','Pending Hours')
            ->where('Candidate_Current_Status','=','First Shift Confirmed')
            ->where('Candidate_Start_Date','>','2022-12-31');

        $this->runChunkedUpdate('mssql_recruitertracker', 'Hours_Worked', $pendingHoursTrackerIds(), function (array $ids) use ($endDate) {
            return DB::table('mssql_recruitertracker')
                ->leftJoin('mssql_patients','mssql_recruitertracker.aoc_patient_id','=','mssql_patients.id')
                ->leftJoin('mssql_providers','mssql_recruitertracker.aoc_provider_id','=','mssql_providers.id')
                ->whereIn('mssql_recruitertracker.id', $ids)
                ->update(['Hours_Worked' => DB::raw("Case when mssql_patients.IsPRN = 1 then
                    Case
                    when (select sum(ActualHours) from mssql_schedulemaster where ScheduleDate >= mssql_recruitertracker.Candidate_Start_Date and IsBillable = 1 and HasChild = 0 and ProviderID = mssql_providers.ProviderID and ScheduleDate <= '" . $endDate . "' and ScheduleStatusID in (1,2)) > 40 then 40
                    else (select sum(ActualHours) from mssql_schedulemaster where ScheduleDate >= mssql_recruitertracker.Candidate_Start_Date and IsBillable = 1 and HasChild = 0 and ProviderID = mssql_providers.ProviderID and ScheduleDate <= '" . $endDate . "' and ScheduleStatusID in (1,2)) end
                else
                    Case
                    when (select sum(ActualHours) from mssql_schedulemaster where ScheduleDate >= mssql_recruitertracker.Candidate_Start_Date and IsBillable = 1 and HasChild = 0 and PatientID = mssql_patients.PatientID and ProviderID = mssql_providers.ProviderID and ScheduleDate <= '" . $endDate . "' and ScheduleStatusID in (1,2)) > 40 then 40
                    else (select sum(ActualHours) from mssql_schedulemaster where ScheduleDate >= mssql_recruitertracker.Candidate_Start_Date and IsBillable = 1 and HasChild = 0 and PatientID = mssql_patients.PatientID and ProviderID = mssql_providers.ProviderID and ScheduleDate <= '" . $endDate . "' and ScheduleStatusID in (1,2)) end
                end")]);
        });
        //update actual gross profic to each tracker
        $this->runChunkedUpdate('mssql_recruitertracker', 'actual_gp', $pendingHoursTrackerIds()->where('Hours_Worked','>=',40), function (array $ids) use ($endDate) {
            return DB::table('mssql_recruitertracker')
                ->leftJoin("mssql_patients","mssql_recruitertracker.aoc_patient_id","=","mssql_patients.id")
                ->leftJoin("mssql_providers","mssql_recruitertracker.aoc_provider_id","=","mssql_providers.id")
                ->whereIn('mssql_recruitertracker.id', $ids)
                ->update(['actual_gp' => DB::raw("(select round(avg(Cost) / avg(Revenue),2) from mssql_schedulemaster where PatientID = mssql_patients.PatientID and
                        ProviderID = mssql_providers.ProviderID and ScheduleDate <= '" . $endDate . "' and ScheduleStatusID in (1,2) and Cost > 0)")]);
        });
        //update tmp field to whiteboard for total candidate start hours during the current period
        $this->runChunkedUpdate('whiteboard', 'tmpTotalCandidateStartHours', DB::table('whiteboard')->where('candidate_start_hour_goals','>',0), function (array $ids) use ($startDate, $endDate) {
            return DB::table('whiteboard')
                ->whereIn('id', $ids)
                ->update(['tmpTotalCandidateStartHours' => DB::raw("ifnull((select sum(rt.Total_Hours) from mssql_recruitertracker rt where rt.user_id = whiteboard.user_id
                        and rt.Candidate_Start_Date >= '" . $startDate . "' and rt.Candidate_Start_Date <= '" . $endDate . " 23:59:59'),0)")]);
        });
        //update tmpTotalCandidateStartHours to every tracker record for this bonus period
        $this->runChunkedUpdate('mssql_recruitertracker', 'bonus_calc_total_hours', $pendingHoursTrackerIds(), function (array $ids) {
            return DB::table('mssql_recruitertracker')
                ->whereIn('id', $ids)
                ->update([
                        'bonus_calc_total_hours' => DB::raw("ifnull((select wb.tmpTotalCandidateStartHours from whiteboard wb where wb.user_id = mssql_recruitertracker.user_id and wb.primary = 1 limit 1),0)"),
                        'bonus_calc_goals' => DB::raw("ifnull((select sum(wb.candidate_start_hour_goals) from whiteboard wb where wb.user_id = mssql_recruitertracker.user_id),0)"),
                        'bonus_calc_pct_to_goal' => DB::raw("case
                            when ifnull((select sum(wb.candidate_start_hour_goals) from whiteboard wb where wb.user_id = mssql_recruitertracker.user_id),0) <> 0 then
                                ifnull((select wb.tmpTotalCandidateStartHours from whiteboard wb where wb.user_id = mssql_recruitertracker.user_id and wb.primary = 1 limit 1),0) / ifnull((select sum(wb.candidate_start_hour_goals) from whiteboard wb where wb.user_id = mssql_recruitertracker.user_id),0)
                            else 0 end")
                        ]);
        });
        //update bonus amount to each tracker record
        $this->runChunkedUpdate('mssql_recruitertracker', 'bonus_amount', $pendingHoursTrackerIds(), function (array $ids) use ($weeks) {
            return DB::table('mssql_recruitertracker')
                ->whereIn('id', $ids)
                ->update(['bonus_amount' => DB::raw("case when bonus_override > 0 then bonus_override
                        when ifnull(Hours_Worked,0) < 40 then 0
                        when Skillset = 'CNA' then 25
                        when (select wb.tmpTotalCandidateStartHours from whiteboard wb where wb.user_id = mssql_recruitertracker.user_id and wb.primary and wb.candidate_start_hour_goals > 0 order by wb.id limit 1)
                            / ((select sum(wb2.candidate_start_hour_goals) from whiteboard wb2 where wb2.user_id = mssql_recruitertracker.user_id) * " . $weeks . ") >= 1.25 then 45
                        else 40 end"),
                        'bonus_tier' => DB::raw("case when bonus_override > 0 then 0
                        when ifnull(Hours_Worked,0) < 40 then 0
                        when Skillset = 'CNA' then 0
                        when (select wb.tmpTotalCandidateStartHours from whiteboard wb where wb.user_id = mssql_recruitertracker.user_id and wb.primary and wb.candidate_start_hour_goals > 0 order by wb.id limit 1)
                            / ((select sum(wb2.candidate_start_hour_goals) from whiteboard wb2 where wb2.user_id = mssql_recruitertracker.user_id) * " . $weeks . ") >= 1.25 then 2
                        else 1 end")

                    ]);
        });
    }

    //Deadlocks/lock-wait-timeouts on these large multi-table updates used to abort the whole
    //command (process:hoursworked runs once/day, so a failed step stayed uncorrected for a day).
    //Splitting each step into small id-based chunks keeps every transaction's lock footprint
    //(rows locked across the joined tables) small and short-lived, which is what actually drives
    //deadlock/lock-wait-timeout probability once the read paths are already indexed.
    //DB::transaction() with $attempts>1 makes Laravel auto-retry a chunk on deadlock/lock-timeout
    //(see DetectsConcurrencyErrors); if a chunk still fails after retries, it's logged with its id
    //range and the loop continues so one bad chunk doesn't block the rest of the table.
    private function runChunkedUpdate(string $table, string $step, Builder $idQuery, Closure $updateForIds): void
    {
        $startedAt = microtime(true);
        $chunkNumber = 0;
        $rowsUpdated = 0;
        $chunksFailed = 0;
        $updatedIds = [];

        $idQuery->select("{$table}.id")
            ->chunkById(self::HOURS_WORKED_UPDATE_CHUNK_SIZE, function ($rows) use ($step, $updateForIds, &$chunkNumber, &$rowsUpdated, &$chunksFailed, &$updatedIds) {
                $chunkNumber++;
                $ids = $rows->pluck('id')->all();
                $chunkStartedAt = microtime(true);

                try {
                    $affected = DB::transaction(fn () => $updateForIds($ids), 3);
                    $rowsUpdated += $affected;
                    $updatedIds = array_merge($updatedIds, $ids);
                    Log::info("updateHoursWorkedToTrackers: {$step} chunk #{$chunkNumber} updated", [
                        'id_from' => min($ids),
                        'id_to' => max($ids),
                        'rows_updated' => $affected,
                        'ids' => $ids,
                        'duration_ms' => round((microtime(true) - $chunkStartedAt) * 1000),
                    ]);
                } catch (Throwable $e) {
                    $chunksFailed++;
                    Log::error("updateHoursWorkedToTrackers: {$step} chunk #{$chunkNumber} failed after retries", [
                        'id_from' => min($ids),
                        'id_to' => max($ids),
                        'ids' => $ids,
                        'duration_ms' => round((microtime(true) - $chunkStartedAt) * 1000),
                        'exception' => $e,
                    ]);
                }
            }, "{$table}.id", 'id');

        Log::info("updateHoursWorkedToTrackers: {$step} finished", [
            'chunks' => $chunkNumber,
            'chunks_failed' => $chunksFailed,
            'rows_updated' => $rowsUpdated,
            'updated_ids' => $updatedIds,
            'duration_ms' => round((microtime(true) - $startedAt) * 1000),
        ]);
    }

    public function sendNotificationForReadyToWork($trackerId,$appointmentDate='',$callFromCron=false){
        //adding notification to recruiter, Recruiting Admin and Recruiting Team Lead
        $recruiterDetails = MssqlRecruitertracker::find($trackerId);
        if(isset($recruiterDetails->user_id) && $recruiterDetails->user_id != ''){
            $data = [];
            $data['user_id'] = $recruiterDetails->user_id;
            if($callFromCron){
                $data['title'] = $recruiterDetails->Candidate.' is scheduled for a Candidate Start on '
                .H::getFormatedDate($appointmentDate).' but is not marked as Ready To Work.';
            }else{
                $data['title'] = 'The branch has marked '.$recruiterDetails->Candidate.' as ready to work ';
            }
            $data['type'] = 'trackerDetails';
            $data['related_item'] = $trackerId;
            $data['notes'] = '';
            Notifications::addNotification($data);
            H::ExcelLog('recruiter: ' . $recruiterDetails->user_id,'ipeaSummaryChange.log');
            $ra = H::getRecruiterAdmin($recruiterDetails->user_id,true);
            if($ra != ''){
                $data['user_id'] = $ra;
                Notifications::addNotification($data);
                H::ExcelLog('ra notification added:'.$ra,'ipeaSummaryChange.log');
            }
            $rt = H::getHelper('ConstantHelper')->
            getUserNameById($recruiterDetails->user_id,"recruiting_teamleader");
            if($rt != ''){
                $data['user_id'] = $rt;
                Notifications::addNotification($data);
                H::ExcelLog('recruiter team leader notification added:
                    '.$rt,'ipeaSummaryChange.log');
            }
        }
    }

    //complete trackers where candidate reached 40 hours
    public function completeTrackersReachedMinHours()
    {
        $completedTrackersQuery = MssqlRecruitertracker::query()
            ->leftJoin('mssql_patients as pat', 'pat.id', '=', 'aoc_patient_id')
            ->where('pat.isPRN', 1)
            ->where('Candidate_Start_Date', '>', self::CANDIDATE_MIN_START_DATE)
            ->where('Hours_Worked', '>=', self::COMPLETED_HOURS_AMOUNT)
            ->where('is_active', 1)
            ->where('Candidate_Current_Status', '!=', MssqlRecreturTrackerStatus::INACTIVE->value)
            ->where('live_ipea_status', MssqlRecruiterTrackerLiveIpeaStatus::READY_TO_WORK->value)
            ->where('is_prn_confirmed', 0);

        $completedTrackers = $completedTrackersQuery->get()->toArray();

        foreach ($completedTrackers as $completedTracker) {
            $currentDate = Carbon::now()->format("Y-m-d H:i:s");
            try {
                DB::transaction(function () use ($completedTracker, $currentDate) {
                    MssqlRecruitertracker::query()->where('id', $completedTracker['id'])
                        ->update([
                            'is_prn_confirmed' => 1,
                            'prn_confirmed_date' => $currentDate,
                            'prn_confirmed_by' => User::SYSTEM_ID,
                            'activecoverage_outcome' => MssqlRecruiterTrackerActiveCoverageOutcome::CONFIRM_COMPLETED->value,
                        ]);

                    H::getHelper('ActivityNotes')->setActivityNotesFromConsole([
                        'entity' => 'tracker-activity',
                        'entity_id' => $completedTracker['id'],
                        'notes' => sprintf('Tracker made inactive by App Bot due to 40 hours worked and bonus paid on [%s]. %s',
                            $completedTracker['Bonus_Paid_Date'],
                            $currentDate,
                        ),
                    ]);
                });
            } catch (Throwable $e) {
                Log::error($e);
            }
        }
    }
}
