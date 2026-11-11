<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\H;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Schedule extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'recruiting_schedule';
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

	public static function booted() {

        parent::booted();

        static::addGlobalScope('exclude_deleted', function (Builder $builder) {
			$builder->where('recruiting_schedule.is_deleted', '0');
		});
    }

    public static function getScheduleType()
    {
        return DB::table('schedule_types')->get();
    }

    public static function getScheduleByTrackerIds($trackerIds){
        $inQuery = DB::table('recruiting_schedule')->whereIn('tracker_id',$trackerIds)->orderBy('updated_at','desc');
        $data = DB::table(DB::raw('(' . $inQuery->toSql() . ') as tb'))
        ->mergeBindings($inQuery)
        ->groupBy('tb.type_id')->groupBy('tb.tracker_id')
        ->get()->toArray();
        return $data;
    }
    public static function getValueOfScheduleByTrackerId($schedulesArr,$trackerId,$typeId,$fieldName){
        $filtered = array_filter($schedulesArr, function ($var) use ($trackerId,$typeId) {
            return $var->tracker_id == $trackerId && $var->type_id == $typeId;
        });
        if(isset($filtered[0]) && isset($filtered[0]->{$fieldName})){
            return $filtered[0]->{$fieldName};
        }else{
            return '';
        }
    }
    public static function updateAppointmentDate($id,$date)
    {
        if($id != '' && $date != ''){
            $schedule = Schedule::find($id);
            $scheduleType = ScheduleType::find($schedule->type_id);
            $userId = Auth::user()->id;
            Schedule::where('id', $id)->limit(1)->update(['appointment_date' => date('Y-m-d',strtotime($date)), 'updated_at'=> date('Y-m-d H:i:s'), 'updated_by'=> $userId]);

            $notes = $scheduleType->type. ' was rescheduled from ' . $schedule->appointment_date . ' at ' . $schedule->appointment_time . ' to ' . date('Y-m-d') . ' at ' . $schedule->appointment_time . ' by ' . Auth::user()->name;
            H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'tracker_activity', 'entity_id' => $schedule->tracker_id, 'tracker_id' => $schedule->tracker_id, 'notes' => $notes]);
        }
    }
    public static function updateAppointmentTime($id,$time)
    {
        if($id != '' && $time != ''){
            $schedule = Schedule::find($id);
            $scheduleType = ScheduleType::find($schedule->type_id);
            $userId = Auth::user()->id;
            Schedule::where('id', $id)->limit(1)->update(['appointment_time' => date('H:i:s',strtotime(date('Y-m-d').' '.$time)), 'updated_at'=> date('Y-m-d H:i:s'), 'updated_by'=> $userId]);

            $notes = $scheduleType->type. ' was rescheduled from ' . $schedule->appointment_date . ' at ' . $schedule->appointment_time . ' to ' . $schedule->appointment_date . ' at ' . date('H:i:s') . ' by ' . Auth::user()->name;
            H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'tracker_activity', 'entity_id' => $schedule->tracker_id, 'tracker_id' => $schedule->tracker_id, 'notes' => $notes]);
        }
    }

    public static function getApprovedSchedulesDetail($patientId, $providerId, $number, $field)
    {
        $totalRecords = DB::select("select ".
                        ($field == 'hours' ? 'sum(mssql_schedulemaster.Hours) as Hours' :
						'max(mssql_schedulemaster.ScheduleDate) as ScheduleDate').
                        " from mssql_schedulemaster
                        left join mssql_patients on mssql_schedulemaster.PatientID = mssql_patients.PatientID
                        left join mssql_providers on mssql_schedulemaster.ProviderID = mssql_providers.ProviderID
                        where
                        mssql_patients.id = ? and
                        mssql_providers.id = ? and
                        mssql_schedulemaster.ScheduleStatusID = 1 and
                        month(mssql_schedulemaster.ScheduleDate) = ? and
                        year(mssql_schedulemaster.ScheduleDate) = ?", [
                            $patientId,
                            $providerId,
                            Carbon::now()->subMonth($number)->format('m'),
                            Carbon::now()->subMonth($number)->format('Y')
                        ]);
        return $totalRecords;
    }
}