<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;

class GenerateIpeasHoursDays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:ipeahoursdays';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate IPEA scheduled hours and IPEA days';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
		$qry = "SELECT rs.id, rs.aoc_candidate_id, rs.outcome, rs.created_at as IPEAScheduledDate,
					CONCAT(DATE(rs.appointment_date), ' ', CAST(rs.appointment_time AS TIME)) AS IPEA_Date,
					(select ca.sentDate from candidate_applications ca
						where ca.candidate_id = rs.aoc_candidate_id
						and date(ca.sentDate) <= date(rs.created_at)
						order by ca.sentDate desc limit 1
					) as appSentDate,
					rs.ipeadays,
					rs.ipeaschedulehours
				FROM recruiting_schedule rs
				WHERE rs.type_id = 1
                ANd rs.outcome <> 'Cancelled'
                AND ifnull(rs.aoc_candidate_id,0) <> 0
				AND (select ca.sentDate from candidate_applications ca
					where ca.candidate_id = rs.aoc_candidate_id
					and date(ca.sentDate) <= date(rs.created_at)
					order by ca.sentDate desc limit 1
				) is not null";
		$result = DB::select($qry);
		foreach($result as $row) {
			
			//If they are rescheduled,  we need to recalculate application_date to IPEA Date
			$ipea_date = new \DateTime($row->IPEA_Date);
			$current_date = new \DateTime();
			if (empty($row->ipeadays) || $ipea_date > $current_date) {
				DB::table("recruiting_schedule")->where("id", $row->id)->update(
				[
					'ipeadays' => H::getWorkingdays($row->appSentDate, $row->IPEA_Date)
				]);
			}

			//Never recalculate application date to IPEA Scheduled Date
			if (empty($row->ipeaschedulehours)) {
				DB::table("recruiting_schedule")->where("id", $row->id)->update(
				[
					'ipeaschedulehours' => H::calculateWorkingHours($row->appSentDate, $row->IPEAScheduledDate),
				]);
			}
		}
        return Command::SUCCESS;
    }
}