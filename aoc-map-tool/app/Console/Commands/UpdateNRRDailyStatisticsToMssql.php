<?php

namespace App\Console\Commands;

use App\Models\JobCategories;
use App\Services\JobCategory\JobCategoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Helpers\H;
use App\Enums\NthNewHoursReasons;

class UpdateNRRDailyStatisticsToMssql extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:mssqlnrrdata';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'process to update MSSQL with NRR daily statistics';

	protected $date = null;

	public function __construct(private JobCategoryService $jobCategoryService)
    {
        $this->date = Carbon::now()->subDays('1');
		//$this->date = '2024-09-24';
        parent::__construct();
    }
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
		H::ExcelLog("Start to Send data to Mssql", 'nrrdailystatistics.log');
		$today = Carbon::now()->format('l');
		if ($today == 'Wednesday') {
			H::ExcelLog("It's Wednesday so send data for last 12 weeks", 'nrrdailystatistics.log');
			$startDate = Carbon::now()->subDays(84)->startOfWeek();
			$endDate = $this->date;
		} else {
			$startDate = Carbon::now()->subDays(7)->startOfWeek();
			$endDate = $this->date;
		}
		for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
			$this->date = $date->format('Y-m-d');
			H::ExcelLog("Start to Send data for date : " . $this->date, 'nrrdailystatistics.log');
			$ipeaData = $this->getIPEAData();
			$this->sendDataToMssql('IPEA', $ipeaData);
			$candidateData = $this->getCandidateData();
			$this->sendDataToMssql('CANDIDATES', $candidateData);
			$candidateMatchData = $this->getCandidateMatchData();
			$this->sendDataToMssql('CM', $candidateMatchData);
			$candidateStartData = $this->getCandidateStartData();
			$this->sendDataToMssql('CANDIDATE STARTS', $candidateStartData);
			$clinicianHiredData = $this->getNurseChurnClinicianHiredData();
			$this->sendDataToMssql('NURSE CHURN CLINICIANS HIRED', $clinicianHiredData);
			$newHoursData = $this->getNeedToHireData('New Hours');
			$this->sendDataToMssql('NTH_NEW_HOURS', $newHoursData);
			$prevStaffedData = $this->getNeedToHireData('Previously Staffed Hours');
			$this->sendDataToMssql('NTH_PREV_STAFFED_HOURS', $prevStaffedData);
			$dischargedData = $this->getNeedToHireData('Discharged');
			$this->sendDataToMssql('NTH_DISCHARGED', $dischargedData);
			$nonAdmitData = $this->getNeedToHireData('Non Admit');
			$this->sendDataToMssql('NTH_NONADMIT', $nonAdmitData);
			H::ExcelLog("End to Send data for date : " . $this->date, 'nrrdailystatistics.log');
		}
		H::ExcelLog("End to Send data to Mssql", 'nrrdailystatistics.log');
		echo "Process finished";
        return Command::SUCCESS;
    }

    private function sendDataToMssql($type, $rawData)
    {
		H::ExcelLog("Start to Send data for type : " . $type, 'nrrdailystatistics.log');
        //H::ExcelLog("Type: " . $type, 'nrrdailystatistics.log');
        H::ExcelLog("Data : " . print_r($rawData, true), 'nrrdailystatistics.log');
        foreach ($rawData as $state => $row) {
			if (isset($row->myCount)) {
				$row->myCount = $row->myCount ?? 0;
				if ($row->myCount == 0) {
					continue;
				}
			}
            $conn = H::getHelper('Sync')->OpenConnection();
			$data = [];
            $data['CalendarDate'] = $this->date . ' 00:00:00';
            $data['Classification'] = $type;
			$data['State'] = (isset($row->myCount)) ? $row->state : $state;
            if (
				$type == 'IPEA' ||
				$type == 'CANDIDATES' ||
				$type == 'CM' ||
				$type == 'CANDIDATE STARTS'
			) {
                $data['PDN'] = isset($row['PDN']) ? number_format($row['PDN'], 0, '.', ',') : null;
                $data['CNA'] = isset($row['CNA']) ? number_format($row['CNA'], 0, '.', ',') : null;
				$data['ATT'] = isset($row['ATT']) ? number_format($row['ATT'], 0, '.', ',') : null;
				$data['PNA'] = isset($row['PNA']) ? number_format($row['PNA'], 0, '.', ',') : null;
				$data['PPEC'] = isset($row['PPEC']) ? number_format($row['PPEC'], 0, '.', ',') : null;
				$data['LHA'] = isset($row['LHA']) ? number_format($row['LHA'], 0, '.', ',') : null;
				$data['IHSS'] = isset($row['IHSS']) ? number_format($row['IHSS'], 0, '.', ',') : null;
				$data['ST'] = isset($row['ST']) ? number_format($row['ST'], 0, '.', ',') : null;
				$data['PT'] = isset($row['PT']) ? number_format($row['PT'], 0, '.', ',') : null;
				$data['OT'] = isset($row['OT']) ? number_format($row['OT'], 0, '.', ',') : null;
				$data['Skilled_Visits'] = isset($row['Skilled Visits']) ? number_format($row['Skilled Visits'], 0, '.', ',') : null;
				$data['Respite'] = isset($row['Respite']) ? number_format($row['Respite'], 0, '.', ',') : null;
                $data['NTH'] = null;
            } else {
                $data['PDN'] = null;
                $data['CNA'] = null;
				$data['ATT'] = null;
				$data['PNA'] = null;
				$data['PPEC'] = null;
				$data['LHA'] = null;
				$data['IHSS'] = null;
				$data['ST'] = null;
				$data['PT'] = null;
				$data['OT'] = null;
				$data['Skilled_Visits'] = null;
				$data['Respite'] = null;
                $data['NTH'] = number_format($row->myCount, 0, '.', ',');
            }
            $procedure_params = [
                    [&$data['CalendarDate'], SQLSRV_PARAM_IN],
                    [&$data['Classification'], SQLSRV_PARAM_IN],
                    [&$data['PDN'], SQLSRV_PARAM_IN],
                    [&$data['CNA'], SQLSRV_PARAM_IN],
					[&$data['ATT'], SQLSRV_PARAM_IN],
					[&$data['PNA'], SQLSRV_PARAM_IN],
					[&$data['PPEC'], SQLSRV_PARAM_IN],
					[&$data['LHA'], SQLSRV_PARAM_IN],
					[&$data['IHSS'], SQLSRV_PARAM_IN],
					[&$data['ST'], SQLSRV_PARAM_IN],
					[&$data['PT'], SQLSRV_PARAM_IN],
					[&$data['OT'], SQLSRV_PARAM_IN],
					[&$data['Skilled_Visits'], SQLSRV_PARAM_IN],
					[&$data['Respite'], SQLSRV_PARAM_IN],
                    [&$data['State'], SQLSRV_PARAM_IN],
                    [&$data['NTH'], SQLSRV_PARAM_IN],
                ];
            $sql = 'EXEC [Excelss].[SpAOCAPP_DataLoad] @CalendarDate = ?, @Classification = ?, @PDN = ?,
                            @CNA = ?, @ATT = ?, @PNA = ?, @PPEC = ?, @LHA = ?, @IHSS = ?, @ST = ?,
							@PT = ?, @OT = ?, @Skilled_Visits = ?, @Respite = ?, @State = ?, @NTH = ?';

            $stmt = sqlsrv_prepare($conn, $sql, $procedure_params);
            if (!$stmt) {
                H::ExcelLog('Error In MSSQL Update', 'nrrdailystatistics.log');
                H::ExcelLog(print_r(sqlsrv_errors(), true), 'nrrdailystatistics.log');
            } else {
                if (sqlsrv_execute($stmt)) {
					H::ExcelLog("TYPE: " . $type . " - STATE: " . $state . " Submitted ", 'nrrdailystatistics.log');
                } else {
                    H::ExcelLog('Error In MSSQL Update', 'nrrdailystatistics.log');
                    H::ExcelLog(print_r(sqlsrv_errors(), true), 'nrrdailystatistics.log');
                }
            }
            sqlsrv_close($conn);

			//iserting records into temp_nrr_statistics
			DB::statement("insert into temp_nrr_statistics (CalendarDate, Classification, State, PDN, CNA, ATT, PNA, PPEC, NTH, LHA, IHSS, ST, PT, OT, Skilled_Visits, Respite, created_at)
			VALUES ('".$this->date."','".$data['Classification']."','".$data['State']."','".$data['PDN']."','".$data['CNA']."','".$data['ATT']."','".$data['PNA']."','".$data['PPEC']."','".$data['NTH']."','".$data['LHA']."','".$data['IHSS']."','".$data['ST']."','".$data['PT']."','".$data['OT']."','".$data['Skilled_Visits']."','".$data['Respite']."','".date('Y-m-d h:i:s')."')");
        }
		H::ExcelLog("End to Send data for type : " . $type, 'nrrdailystatistics.log');
    }

    private function getIPEAData()
    {
        $date = $this->date;
        $qry = "SELECT left(rt.whiteboard_row,2) as state, rt.whiteboard_row, count(rs.id) as myCount
            FROM `recruiting_schedule` rs
            left join mssql_recruitertracker rt on tracker_id = rt.id
            left join mssql_patients mp on rs.aoc_patient_id = mp.id
            left join users u on rt.user_id = u.id
            left join whiteboard w on rt.whiteboard_row = w.row_id and rt.user_id = w.user_id
            left join mssql_facilities mf on mf.id = mp.aoc_facility_id
            left join regions reg on mf.region = reg.id
            where rs.type_id = 1 and rs.scheduled_date = ? AND rs.is_deleted <> 1
            group by left(rt.whiteboard_row,2), rt.whiteboard_row
            order by left(rt.whiteboard_row,2), rt.whiteboard_row";

        $result = DB::select($qry, [
                    $date
                ]);

		$finalResult = [];
		foreach ($result as $row) {
			$row->whiteboard_row = str_replace($row->state . " - ", "", $row->whiteboard_row);
			if (in_array($row->whiteboard_row,[
						'PDN',
						'CNA',
						'ATT',
						'PNA',
						'PPEC',
						'LHA',
						'IHSS',
						'ST',
						'PT',
						'OT',
						'Skilled Visits',
						'Respite'
				])) {
				if (isset($finalResult[$row->state][$row->whiteboard_row])) {
					$finalResult[$row->state][$row->whiteboard_row] += $row->myCount;
				} else {
					$finalResult[$row->state][$row->whiteboard_row] = $row->myCount;
				}
			}
		}

        return $finalResult;
    }

    private function getCandidateData()
    {
        $date = $this->date;
		$carbonDate = Carbon::parse($date);
		$monday = $carbonDate->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
		$sunday = $carbonDate->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');

        $activeCategories = $this->jobCategoryService->getCategories();

        $categoriesSql = "'" . implode(
                "','",
                array_map('addslashes', $activeCategories->pluck('category')->toArray())
            ) . "'";

		$qry = "
                with cte as
                (
					SELECT candidate_id, DATE_FORMAT(
						date_add(candidate_applications.application_date, interval  -WEEKDAY(candidate_applications.application_date) day),'%Y-%m-%d'
					) as Monday, min(id) as appId
                    FROM `candidate_applications`
                    WHERE candidate_applications.application_date >= ? and candidate_applications.application_date <= ?
                    group by candidate_id, DATE_FORMAT(date_add(candidate_applications.application_date,
					interval  -WEEKDAY(candidate_applications.application_date) day),'%Y-%m-%d')
                )

                SELECT jobs.state, jobs.category,
                            count(candidate_applications.id) as myCount
                    FROM cte
                        left join `candidate_applications` on cte.appId = candidate_applications.id
                        left join candidates on candidate_applications.candidate_id = candidates.id
                        left join jobs on candidate_applications.job_id = jobs.id
                    WHERE candidate_applications.application_date = ?
                        and jobs.category in ($categoriesSql)
                    GROUP BY jobs.state, jobs.category
                    ORDER BY jobs.state, jobs.category";

        $result = DB::select($qry, [
                    $monday,
					$sunday,
					$date
                ]);

        $xref = $activeCategories->mapWithKeys(function ($cat) {
            return [
                $cat->category => [
                    'label' => $cat->Type,
                    'state' => $cat->State,
                ],
            ];
        })->toArray();

		$finalResult = [];
		$states = config('constant')['states'];
		foreach ($result as $row) {
			if (isset($xref[$row->category])) {
				$state = array_search($row->state, $states);
				$category = $xref[$row->category]['label'];
				if (isset($finalResult[$state][$category])) {
					$finalResult[$state][$category] += $row->myCount;
				} else {
					$finalResult[$state][$category] = $row->myCount;
				}
			}
		}

        return $finalResult;
    }

    private function getCandidateMatchData()
    {
        $date = $this->date;
        $qry = "SELECT left(rt.whiteboard_row,2) as state, rt.whiteboard_row, count(rs.id) as myCount
            FROM `recruiting_schedule` rs
            left join mssql_recruitertracker rt on tracker_id = rt.id
            left join mssql_patients mp on rs.aoc_patient_id = mp.id
            left join users u on rt.user_id = u.id
            left join whiteboard w on rt.whiteboard_row = w.row_id and rt.user_id = w.user_id
            left join mssql_facilities mf on mf.id = mp.aoc_facility_id
            left join regions reg on mf.region = reg.id
            where rs.type_id = 6 and rs.scheduled_date = ? AND rs.is_deleted <> 1
            group by left(rt.whiteboard_row,2), rt.whiteboard_row
            order by left(rt.whiteboard_row,2), rt.whiteboard_row";

        $result = DB::select($qry, [
                    $date
                ]);

        $finalResult = [];
		foreach ($result as $row) {
			$row->whiteboard_row = str_replace($row->state . " - ", "", $row->whiteboard_row);
			if (in_array($row->whiteboard_row,[
						'PDN',
						'CNA',
						'ATT',
						'PNA',
						'PPEC',
						'LHA',
						'IHSS',
						'ST',
						'PT',
						'OT',
						'Skilled Visits',
						'Respite'
				])) {
				if (isset($finalResult[$row->state][$row->whiteboard_row])) {
					$finalResult[$row->state][$row->whiteboard_row] += $row->myCount;
				} else {
					$finalResult[$row->state][$row->whiteboard_row] = $row->myCount;
				}
			}
		}

        return $finalResult;
    }

    private function getCandidateStartData()
    {
        $date = $this->date;
        $qry = "SELECT rt.whiteboard_row, left(rt.whiteboard_row,2) as state, sum(rt.Total_Hours) as myCount
            FROM `recruiting_schedule` rs
            left join mssql_recruitertracker rt on tracker_id = rt.id
            left join users u on rt.user_id = u.id
            left join whiteboard w on rt.whiteboard_row = w.row_id and rt.user_id = w.user_id
            left join regions reg on w.region = reg.id
            left join mssql_patients p1 on rs.aoc_patient_id = p1.id
            left join mssql_providers p2 on rt.aoc_provider_id = p2.id
            where rs.type_id = 6 and p2.is_deleted = 0 and
            rs.appointment_date = ?
            and (
				(
					p1.isPRN <> 1 AND rs.has_billable_visit = 1 and p1.not_in_kantime = 0
				) OR p1.isPRN = 1 OR p1.not_in_kantime = 1
			) AND rs.is_deleted <> 1
            group by left(rt.whiteboard_row,2), rt.whiteboard_row
            order by left(rt.whiteboard_row,2), rt.whiteboard_row";

        $result = DB::select($qry, [
                    $date
                ]);

        $finalResult = [];
		foreach ($result as $row) {
			$row->whiteboard_row = str_replace($row->state . " - ", "", $row->whiteboard_row);
			if (in_array($row->whiteboard_row,[
						'PDN',
						'CNA',
						'ATT',
						'PNA',
						'PPEC',
						'LHA',
						'IHSS',
						'ST',
						'PT',
						'OT',
						'Skilled Visits',
						'Respite'
				])) {
				if (isset($finalResult[$row->state][$row->whiteboard_row])) {
					$finalResult[$row->state][$row->whiteboard_row] += $row->myCount;
				} else {
					$finalResult[$row->state][$row->whiteboard_row] = $row->myCount;
				}
			}
		}

        return $finalResult;
    }

    private function getNurseChurnClinicianHiredData()
    {
        $date = $this->date;
        $qry = "SELECT mssql_facilities.state, count(mssql_providers.id) as myCount
                FROM `mssql_providers`
                LEFT JOIN mssql_facilities on mssql_providers.FacilityID = mssql_facilities.FacilityID
                WHERE mssql_providers.DisciplineName in ('RN','LVN','LPN') and IsClinician = 1
                and date_format(mssql_providers.HiredDate, '%Y-%m-%d') = ?
                group by mssql_facilities.state
                order by mssql_facilities.state";

        $result = DB::select($qry, [
                    $date
                ]);

		return $result;
        /*$finalResult = [];
		foreach ($result as $row) {
			if (isset($finalResult[$row->state])) {
				$finalResult[$row->state] += $row->myCount;
			} else {
				$finalResult[$row->state] = $row->myCount;
			}
		}

        return $finalResult;*/
    }

    private function getNeedToHireData($classification)
    {
        $date = $this->date;
        $nonAdmit = "audit.Reason LIKE '%Non-admit'";
        $nonAdmit2 = "(audit.Reason LIKE '%Non-admit' OR audit.Reason in
                        ('Non-admit','Discharged','Staffed','AOC - Staffed'))";
        $qry = "with audit as
                (Select NeedToHire_Audit.* from NeedToHire_Audit where isNet <> 1 and DATE(created_at) = ?),
                cte as
                    (select mssql_facilities.State, mssql_facilities.FacilityName, audit.Type,
                        CASE WHEN " . $nonAdmit . " THEN 'Non Admit'
                            WHEN audit.Reason = 'Discharged' THEN 'Discharged'
                            WHEN audit.Reason like '%Staffed' THEN 'Staffed'
                            WHEN audit.Reason in (" . NthNewHoursReasons::getSqlString() . ") THEN 'New Hours'
                            ELSE 'Previously Staffed Hours' END AS Classification,
                        CASE WHEN " . $nonAdmit2 . " THEN NetChangeinHours
                            WHEN audit.Reason in (" . NthNewHoursReasons::getSqlString() . ") THEN HoursAdded
                            ELSE NetChangeinHours END AS Hours
                    FROM audit
                    left join NeedToHire_Schedules on audit.schedule_id = NeedToHire_Schedules.id
                    left join NeedToHire_Accounts on NeedToHire_Schedules.aoc_nthaccounts_id = NeedToHire_Accounts.id
                    left join mssql_patients on NeedToHire_Accounts.aoc_patient_id = mssql_patients.id
                    left join mssql_facilities on mssql_patients.aoc_facility_id = mssql_facilities.id), ";
            $qry .= " cte2 as
                    (select cte.State as state, sum(cte.Hours) as myCount
                        from cte where Classification = ? group by cte.State
                    )
                select cte2.* from cte2";

        $result = DB::select($qry, [
                    $date,
                    $classification
                ]);

        return $result;
    }
}
