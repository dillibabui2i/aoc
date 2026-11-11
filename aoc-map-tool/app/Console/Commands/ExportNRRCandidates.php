<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use App\Helpers\H;
class ExportNRRCandidates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:nrrcandidates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $startDate = H::getReportStartingDate('week');
        $endingDate = date('Y-m-d');

        $qry = "SELECT rt.whiteboard_row, rt.Recruiter, w.territory, rs.appointment_date as CandidateStartDate,
		p1.PatientName, p2.ProviderName, left(rt.whiteboard_row,2) as state,
        reg.region_name, date_add(rs.appointment_date, interval  -WEEKDAY(rs.appointment_date) day)  as Monday, 
        rt.Total_Hours, (select sm.CGTaskID from mssql_schedulemaster sm where sm.ProviderID = p2.ProviderID and sm.PatientID = p1.PatientID and sm.ScheduleStatusID in (1,2,3) and sm.ScheduleDate = rs.appointment_date limit 1) as CGTaskID
        FROM `recruiting_schedule` rs
        left join mssql_recruitertracker rt on tracker_id = rt.id
        left join users u on rt.user_id = u.id
        left join whiteboard w on rt.whiteboard_row = w.row_id and rt.user_id = w.user_id
        left join regions reg on w.region = reg.id
        left join mssql_patients p1 on rs.aoc_patient_id = p1.id
        left join mssql_providers p2 on rt.aoc_provider_id = p2.id
        where rs.type_id = 6 and p2.is_deleted = 0 and rs.appointment_date >= '" . $startDate . "' and rs.appointment_date <= '" . $endingDate . "' and isnull((select sm.ScheduleDate from mssql_schedulemaster sm where sm.ProviderID = p2.ProviderID and sm.PatientID = p1.PatientID and sm.ScheduleStatusID in (1,2,3) and sm.ScheduleDate = rs.appointment_date limit 1)) = false AND rs.is_deleted <> 1 order by rt.whiteboard_row, region, rt.Recruiter, w.territory, rs.appointment_date";
        $tmp = DB::select($qry);
        $tmp = json_decode(json_encode($tmp), true);
        $data = [];
        //process each record in case we need to alter data programmatically
        for ($i = 0; $i < count($tmp); $i++) {
            $data[$i]=[
                'whiteboard_row' => $tmp[$i]['whiteboard_row'],
                'Recruiter' => $tmp[$i]['Recruiter'],
                'territory' => $tmp[$i]['territory'],
                'CandidateStartDate' => $tmp[$i]['CandidateStartDate'],
                'PatientName' => $tmp[$i]['PatientName'],
                'ProviderName' => $tmp[$i]['ProviderName'],
                'state' => $tmp[$i]['state'],
                'region_name' => $tmp[$i]['region_name'],
                'Monday' => $tmp[$i]['Monday'],
                'Total_Hours' => $tmp[$i]['Total_Hours'],
                'CGTaskID' => $tmp[$i]['CGTaskID'],
                'created_at' => date('Y-m-d'),
            ];
        }
        $insert=array_chunk($data,100);
        foreach($insert as $ins){
            DB::table('nrr_candidates_data')->insert($ins);
        }
        return 0;
    }
}
