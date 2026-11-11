<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
class ScheduleMasterTrack extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sm:track';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'record scheudle master data count track';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
		$query = 'SELECT
				CURRENT_TIMESTAMP AS current_date,
				CAST(ETL_UPDATED_TIMESTAMP AS VARCHAR(23)) AS etl_date,
				COUNT(SCHEDULE_ID) AS count
			FROM SCHEDULEMASTER_SVW
			WHERE ETL_UPDATED_TIMESTAMP >= DATEADD(MONTH, -2, GETDATE())
			AND ISVALIDRECORD = 1
			GROUP BY CAST(ETL_UPDATED_TIMESTAMP AS VARCHAR(23))';

		DB::purge('daas');
        $smRecords = DB::connection('daas')->select(DB::raw($query));
		$smRecords = json_decode(json_encode($smRecords), true);
		foreach($smRecords as $data) {
			DB::table('sm_snowflake_track')->insert($data);
		}
        return Command::SUCCESS;
    }
}
