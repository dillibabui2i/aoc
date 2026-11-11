<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessToSetNurseLostClient extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:nurse_lost_client';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backend process to set nurse_lost_client';

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
        $startOfLastWeek = \Carbon\Carbon::now()->subDays(7)->startOfWeek()->format('Y-m-d');
        $endOfLastWeek = \Carbon\Carbon::now()->subDays(7)->endOfWeek()->format('Y-m-d');
        $today = \Carbon\Carbon::now()->format('Y-m-d');
        
        $records = DB::select(DB::raw("with cte as
        (SELECT ProviderID, PatientID, sc.name
        FROM `mssql_schedulemaster`
        left join service_codes_daas sc on mssql_schedulemaster.ServiceCodeID = sc.service_id
        where ScheduleDate >= '" . $startOfLastWeek . "' and ScheduleDate <= '" . $endOfLastWeek . "' and ScheduleStatusID in (1,2,3) and sc.name NOT LIKE '%Coverage%'
        group by ProviderID, PatientID),
        cte2 as
        (SELECT ProviderID, PatientID
        FROM `mssql_schedulemaster`
        where ScheduleDate >= '" . $today . "'
        group by ProviderID, PatientID)
        select cte.ProviderID, cte.PatientID from cte
        left join mssql_patients p on cte.PatientID = p.PatientID
        left join mssql_providers pr on cte.ProviderID = pr.ProviderID
        left join cte2 on cte.ProviderID = cte2.ProviderID and cte.PatientID = cte2.PatientID
        where cte2.ProviderID is NULL and p.Patient_CurrentStatusID NOT IN (6,8) AND (IFNULL(pr.nurse_lost_client,0) <> 1 OR DATEDIFF (now(), IFNULL(pr.nurse_lost_client_resolved, now())) > 7)"));
        foreach ($records as $row) {
            DB::table("mssql_providers")->where('mssql_providers.ProviderID', $row->ProviderID)
                ->update(
                    [
                        "nurse_lost_client" => "1",
                        "nurse_lost_patientid" => $row->PatientID,
                        "nurse_lost_client_resolved" => '',
                        "nurse_lost_client_resolved_by" => ''
                    ]
                );
        }
        return 0;
    }
}
