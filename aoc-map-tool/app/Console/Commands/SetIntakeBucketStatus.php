<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Intake;
use Carbon\Carbon;
use DB;
use App\Helpers\H;

class SetIntakeBucketStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'intake:setbucket';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set bucket status for intake records';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
		H::ExcelLog('start', 'SetIntakeBucket.log');
		H::ExcelLog('set incomplete intakes bucket to empty', 'SetIntakeBucket.log');
		Intake::whereRaw("intake_bucket <> ''
							and intake_completed <> 1")->update(['intake_bucket' => '']);
		
		// set intake prospective type as bucket
		H::ExcelLog('set intake prospective type as bucket', 'SetIntakeBucket.log');
		$patients = Intake::where(function ($query) {
			// non-theray
			$query->where(function ($query) {
				$query->whereNotIn('intake.LOBID', ['1003', '1004', '1006']);
				$query->where('mssql_patients.Patient_CurrentStatusID', 4);
				$query->whereRaw('ifnull(intake.admission_visit_scheduled,0) <> 1');
				$query->where(function ($query) {
					$query->whereNull('mssql_patients.ActualIntakeDate')
					->orWhereRaw('mssql_patients.ActualIntakeDate = \'0000-00-00\'');
				});
			});

			// therapy
			H::ExcelLog('set intake prospective type for therapy', 'SetIntakeBucket.log');
			$query->orWhere(function ($query) {
				$query->whereIn('intake.LOBID', ['1003', '1004', '1006']);
				//add clause so that Status = 4 OR (Status = 7 AND there is NO intake_admission_visit
				$query->where(function ($query) {
					$query->where('mssql_patients.Patient_CurrentStatusID', 4);
					$query->orWhere(function ($query) {
						$query->where('mssql_patients.Patient_CurrentStatusID', 7);
						$query->whereRaw('ifnull(intake.intake_admission_visit,0) = 0');
					});
				});
				$query->whereIn('mssql_patients.IntakeStatus', [
					'T-Intake Pending',
					'T-Staffing Pending',
					'T-Waitlist for Eval',
					'T - Intake Pending',
					'T - Staffing Pending',
					'T - Waitlist for Eval'   
				]);
			});
			
			//New referral
			H::ExcelLog('new referrals', 'SetIntakeBucket.log');
			$query->orWhere(function ($query) {
				$query->orWhere(function ($query) {
					$query->where('intake.IntakeStatus', 'Pending');
					$query->whereRaw('ifnull(intake.admission_visit_scheduled,0) <> 1');
					$query->whereRaw('ifnull(intake.intake_admission_visit,0) = 0');
					//$query->whereRaw('mssql_patients.Patient_CurrentStatusID not in (1,2,5) AND mssql_patients.LOBID <> IFNULL(intake.LOBID, 0)');
					$query->whereRaw('mssql_patients.Patient_CurrentStatusID not in (2,5) AND mssql_patients.LOBID <> IFNULL(intake.LOBID, 0)');
				});
			});
			
			$query->orWhere(function ($query) {
				$query->whereNull("intake.LOBID");
			});
		});
		$patients->leftJoin("mssql_patients", function ($query) {
			$query->on("mssql_patients.id", "=", "intake.aoc_patient_id");
		});
		$patients->whereRaw('ifnull(mssql_patients.IsNonAdmit,0) = 0');
		//$patients->whereRaw('(mssql_patients.IsLatestAdmit = 1 OR IntakeID IS NULL)');
		$patients->whereRaw('CONCAT(mssql_patients.Patient_FirstName,\' \',
		mssql_patients.Patient_LastName) Not Like ?',['%test%']);
		$patients->where('intake.intake_completed', '<>', 1);
		$patients->whereRaw("ifnull(intake.intake_outcome,'') <> 'Non Admit'");
        $patients->whereRaw("ifnull(intake.intake_outcome,'') <> 'Approve Non Admit'");
		$patients->update(['intake.intake_bucket' => 'intake_prospective_clients']);

		//set intake schedule type as bucket
		H::ExcelLog('intake scheduled', 'SetIntakeBucket.log');
		$patients = Intake::where(function ($query) {
			// non-theray
			$query->where(function ($query) {
				$query->whereNotIn('intake.LOBID', ['1003', '1004', '1006']);
				$query->where('mssql_patients.IntakeDate', '>', '2024-01-01');
				$query->whereRaw(
					"((
						ifnull(mssql_patients.ActualIntakeDate,'0000-00-00') <> '0000-00-00' and
						mssql_patients.Patient_CurrentStatusID = 4
					) OR (
						intake.admission_visit_scheduled = 1 
						and mssql_patients.Patient_CurrentStatusID <> 1
						and IFNULL((select ScheduleStatusID from mssql_schedulemaster
									where mssql_schedulemaster.CGTaskID = intake.intake_admission_visit 
									order by ScheduleDate desc limit 1),-1) not in (1,2,3)
					) OR (
						mssql_patients.Patient_CurrentStatusID = 7 
							and IFNULL((select ScheduleStatusID from mssql_schedulemaster
									where mssql_schedulemaster.CGTaskID = intake.intake_admission_visit 
									order by ScheduleDate desc limit 1),-1) in (4,5)
					))
					"
				);
			});

			// theray
			H::ExcelLog('intake scheduled for therapy', 'SetIntakeBucket.log');
			$query->orWhere(function ($query) {
				$query->whereIn('intake.LOBID', ['1003', '1004', '1006']);
				$query->where('mssql_patients.IntakeDate', '>', '2024-01-01');
				$query->whereRaw(
					"((
						mssql_patients.IntakeStatus = 'Scheduled' and
						mssql_patients.Patient_CurrentStatusID = 4
					) OR (
						intake.admission_visit_scheduled = 1 and mssql_patients.Patient_CurrentStatusID <> 1
						and IFNULL((select ScheduleStatusID from mssql_schedulemaster
									where mssql_schedulemaster.CGTaskID = intake.intake_admission_visit 
									order by ScheduleDate desc limit 1),-1) not in (1,2,3)
					) OR (
						mssql_patients.Patient_CurrentStatusID = 7 
							and IFNULL((select ScheduleStatusID from mssql_schedulemaster
									where mssql_schedulemaster.CGTaskID = intake.intake_admission_visit 
									order by ScheduleDate desc limit 1),-1) in (4,5)
					))"
				);
			});
			
			$query->orWhere(function ($query) {
				$query->whereRaw("intake.IntakeStatus = 'Scheduled' and intake.admission_visit_scheduled = 1");
				$query->whereRaw("IFNULL((select ScheduleStatusID from mssql_schedulemaster
									where mssql_schedulemaster.CGTaskID = intake.intake_admission_visit 
									order by ScheduleDate desc limit 1),5) in (4,5)");
				$query->whereRaw('mssql_patients.Patient_CurrentStatusID not in (2,5)');
				$query->whereRaw('mssql_patients.LOBID <> IFNULL(intake.LOBID, 0)');
			});
		});
		$patients->leftJoin("mssql_patients", function ($query) {
			$query->on("mssql_patients.id", "=", "intake.aoc_patient_id");
		});
		//$patients->whereRaw('(mssql_patients.IsLatestAdmit = 1 OR IntakeID IS NULL)');
		$patients->whereRaw('CONCAT(mssql_patients.Patient_FirstName,\' \',
			mssql_patients.Patient_LastName) Not Like ?',['%test%']);
		$patients->where('intake.intake_completed', '<>', 1);
		$patients->whereRaw("ifnull(intake.intake_outcome,'') <> 'Non Admit'");
        $patients->whereRaw("ifnull(intake.intake_outcome,'') <> 'Approve Non Admit'");
		$patients->update(['intake.intake_bucket' => 'intake_schedule']);
		
		//set intake pending SOC type as bucket
		H::ExcelLog('intake pending soc', 'SetIntakeBucket.log');
		$patients = Intake::where(function ($query) {
			// non-theray
			$query->where(function ($query) {
				$query->whereNotIn('intake.LOBID', ['1003', '1004', '1006']);
				$query->whereIn('mssql_patients.Patient_CurrentStatusID', [1,6,8]);
				$query->where(function ($query) {
					$query->where('mssql_patients.LOBID', '<>', 1023);
					$query->orWhere(DB::raw('IFNULL(intake.LOBID, 1023)'), '<>', 1023);
				});
				$query->where(function ($query) {
					$query->whereDate('intake.CF_GoalSOCDate', '>', Carbon::now());
					$query->orWhere(DB::raw("IFNULL(intake.intake_readytostart,0)"), "0");
				});
			});
		});
		$patients->leftJoin("mssql_patients", function ($query) {
			$query->on("mssql_patients.id", "=", "intake.aoc_patient_id");
		});
		$patients->leftJoin("mssql_schedulemaster", function ($query) {
			$query->on("mssql_schedulemaster.CGTaskID", "=", "intake.intake_first_billable_visit");
		});
		$patients->whereRaw('IFNULL(mssql_schedulemaster.ScheduleStatusID,-1) NOT IN (1,2,3) and IFNULL(mssql_schedulemaster.is_deleted, 0) = 0');
		$patients->whereRaw("IFNULL((select ScheduleStatusID from mssql_schedulemaster
                                            where mssql_schedulemaster.CGTaskID = intake.intake_admission_visit 
                                            order by ScheduleDate desc limit 1),0) in (1,2,3)");
		//$patients->whereRaw('(mssql_patients.IsLatestAdmit = 1 OR IntakeID IS NULL)');
		$patients->whereRaw('CONCAT(mssql_patients.Patient_FirstName,\' \',
			mssql_patients.Patient_LastName) Not Like ?',['%test%']);
		$patients->where('intake.intake_completed', '<>', 1);
		$patients->whereRaw("ifnull(intake.intake_outcome,'') <> 'Non Admit'");
        $patients->whereRaw("ifnull(intake.intake_outcome,'') <> 'Approve Non Admit'");
		$patients->update(['intake.intake_bucket' => 'intake_pending_soc']);

		//set intake client start type as bucket
		H::ExcelLog('intake client start', 'SetIntakeBucket.log');
		$patients = Intake::where(function ($query) {
			// non-theray
			$query->where(function ($query) {
				$query->whereNotIn('intake.LOBID', ['1003', '1004', '1006']);
				//$query->where('mssql_patients.Patient_CurrentStatusID', 1);
				$query->whereIn('mssql_patients.Patient_CurrentStatusID', [1,6,8]);
				$query->whereRaw("ifnull(intake.CF_GoalSOCDate,'0000-00-00 00:00:00') <= NOW()");
				$query->where('mssql_patients.IntakeDate', '>', '2024-01-01');
				/*$query->whereRaw('IFNULL((select ScheduleStatusID from mssql_schedulemaster
									where mssql_schedulemaster.CGTaskID = mssql_patients.intake_first_billable_visit 
									order by ScheduleDate desc limit 1),-1) NOT IN (1,2,3)');*/
				$query->whereRaw('IFNULL(mssql_schedulemaster.ScheduleStatusID,-1) NOT IN (1,2,3) and IFNULL(mssql_schedulemaster.is_deleted, 0) = 0');
				//$query->whereNull('mssql_patients.intake_first_billable_visit');
			});

			$query->where(DB::raw("IFNULL(intake.intake_readytostart,0)"),"1");
		});
		$patients->leftJoin("mssql_patients", function ($query) {
			$query->on("mssql_patients.id", "=", "intake.aoc_patient_id");
		});
		$patients->leftJoin("mssql_schedulemaster", function ($query) {
			$query->on("mssql_schedulemaster.CGTaskID", "=", "intake.intake_first_billable_visit");
		});

		//$patients->whereRaw('(mssql_patients.IsLatestAdmit = 1 OR IntakeID IS NULL)');
		$patients->whereRaw("IFNULL((select ScheduleStatusID from mssql_schedulemaster
                                        where mssql_schedulemaster.CGTaskID = intake.intake_admission_visit 
                                        order by ScheduleDate desc limit 1),0) in (1,2,3)");
		$patients->whereRaw('CONCAT(mssql_patients.Patient_FirstName,\' \',
			mssql_patients.Patient_LastName) Not Like ?',['%test%']);
		$patients->where('intake.intake_completed', '<>', 1);
		$patients->whereRaw("ifnull(intake.intake_outcome,'') <> 'Non Admit'");
        $patients->whereRaw("ifnull(intake.intake_outcome,'') <> 'Approve Non Admit'");
		$patients->update(['intake.intake_bucket' => 'intake_client_starts']);

		//set intake pending non admit type as bucket
		$patients = Intake::whereIn('intake.intake_outcome', ['Non Admit', 'Approve Non Admit']);
		$patients->leftJoin("mssql_patients", function ($query) {
			$query->on("mssql_patients.id", "=", "intake.aoc_patient_id");
		});
		$patients->whereNotIn("mssql_patients.Patient_CurrentStatusID", [2,3,5]);
        $patients->whereRaw('IFNULL(intake.intake_nonadmit_reason,\'\') <> \'\'');
		//$patients->whereRaw('(mssql_patients.IsLatestAdmit = 1 OR IntakeID IS NULL)');
		$patients->whereRaw('CONCAT(mssql_patients.Patient_FirstName,\' \',
		mssql_patients.Patient_LastName) Not Like ?',['%test%']);
		$patients->update(['intake.intake_bucket' => 'intake_pending_non_admit']);

		//set intake admitted 
		H::ExcelLog('intake admitted', 'SetIntakeBucket.log');
		$patients = Intake::where(function ($query) {
			$query->where(function ($query) {
				$query->whereRaw("IFNULL(mssql_schedulemaster.ScheduleStatusID, 0) in (1,2,3)");
			});
		});
		$patients->leftJoin("mssql_schedulemaster", function ($query) {
			$query->on("mssql_schedulemaster.CGTaskID", "=", "intake.intake_first_billable_visit");
		});
		$patients->update(['intake.intake_bucket' => 'intake_admitted']);
		H::ExcelLog('finished', 'SetIntakeBucket.log');
        return Command::SUCCESS;
    }
}
