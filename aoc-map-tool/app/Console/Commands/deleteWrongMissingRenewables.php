<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\MssqlProviders;
use App\Helpers\H;
use Illuminate\Support\Facades\DB;

class deleteWrongMissingRenewables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:worng_missing_renewables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'delete wrong missing renewables from database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        /*$rules = DB::select('select r.id, rr.has_client_over21, r.ProviderID,
        (select sm.PatientID from mssql_schedulemaster sm
        WHERE sm.ProviderID = r.ProviderID and sm.ScheduleDate >= NOW()
        ORDER BY `PatientID` ASC limit 1) AS PatientID from renewables r,
         renewables_types rt, renewable_rules rr
        where r.renewables_type = rt.renewable_type_id
        AND rt.id = rr.renewable_type_id
        AND rr.id in ("26","27")');
        foreach($rules as $rule){
            $renewableId = $rule->id;
            if(!empty($rule->PatientID)){
                $patientDob = DB::table('mssql_patients')->select('id','Patient_DOB')
                ->where('PatientID', $rule->PatientID)
                ->where('mssql_patients.Patient_DOB', '>', DB::raw('DATE_SUB(NOW(), INTERVAL 21 YEAR)'))
                ->first();
                if(!empty($patientDob->id)){
                    //echo $rule->PatientID."---".$patientDob->Patient_DOB."\n";
                    H::ExcelLog('Deleted recordID:'.$renewableId.'', 'deleteMissingRenewable.log');
                    DB::table('renewables')->where('id', $renewableId)->delete();
                }
            }
        }*/
		/*$rules = DB::select("SELECT mp.id Provider_Id, mpa.id patient_id, mpa.PatientAccount, sfm.id masterid, date(sfm.form_date) FormDate FROM supervisory_form_master sfm
LEFT join mssql_providers mp on mp.ProviderID = sfm.supervisee_cgid
left join mssql_patients mpa on mpa.PatientID = sfm.client_id
where sfm.created_at >= '2024-03-01' and date(sfm.form_date) > '2024-03-01' and date(sfm.form_date) < '2024-06-15' order by sfm.form_date, sfm.client_id");

		$RecCnt = 1;
		$myfile = fopen("supvisit.txt", "w") or die("Unable to open file!");
		$txt = "";
		foreach($rules as $rule){
			if(!empty($rule->patient_id) && !empty($rule->Provider_Id)){
				$providerid = $rule->Provider_Id;
				$patient_id = $rule->patient_id;
				$PatientAccount = $rule->PatientAccount;
				$masterid = $rule->masterid;
				$FormDate = $rule->FormDate;

				$svisit = DB::select("SELECT count(id) Cnt FROM supervisory_visits
				where aoc_patient_id = ".$patient_id."
				and aoc_provider_id = ".$providerid." and form_sup_visit_date = '".$FormDate."'");

				if($svisit[0]->Cnt == 0) {
					//print_r($svisit);
					echo "RecCnt : ".$RecCnt."\n";
					echo "providerid : ".$providerid."\n";
					echo "patient_id : ".$patient_id."\n";
					echo "PatientAccount : ".$PatientAccount."\n";
					echo "masterid : ".$masterid."\n";
					echo "FormDate : ".$FormDate."\n";
					echo $RecCnt.",".$providerid.",".$patient_id.",".$PatientAccount.",".$masterid.",".$FormDate."\n";
					$txt = $RecCnt.",".$providerid.",".$patient_id.",".$PatientAccount.",".$masterid.",".$FormDate."\n";
					fwrite($myfile, $txt);

					$RecCnt++;
				}
			}

        }
		fclose($myfile);*/
		$mssql_employmentstatus = array(
  1 => 'Active',
  2 => 'Standby',
  6 => 'Cover Nurse',
  21 => 'On Leave',
  22 => 'PRN Agreement',
  23 => 'Pending Renewable',
  24 => 'Active Coverage',
  25 => 'Active Advocacy'
);
		$chunkSize = 100;
		$myfile = fopen("FieldEvals.txt", "w") or die("Unable to open file!");

		$txt = "";
		MssqlProviders::select(DB::raw('DATE(
                                IF(
                                    (RehireDate = "0000-00-00" || RehireDate = "1900-01-01" || RehireDate = "" || RehireDate is null),
                                    HiredDate,
                                    coalesce(RehireDate, HiredDate)
                                )
                            ) as HireDate'),'fullName','EmploymentStatusID','id','ClinicianID')
		->whereIn('EmploymentStatusID', [1, 24, 25, 6, 21, 22, 2, 23])
		->whereNotIn('DisciplineName', ['PT', 'OT', 'ST', 'PTA', 'OTA', 'STA'])
		->where('State', '<>', 'CO')
		->where('IsOfficeStaff', 0)
		->where('IsClinician', 1)
		->where('is_deleted', 0)
		->whereRaw('
				DATE(
					IF(
						(RehireDate = "0000-00-00" || RehireDate = "1900-01-01" || RehireDate = "" || RehireDate is null),
						HiredDate,
						coalesce(RehireDate, HiredDate)
					)
				) < \'' . date('Y') . '-09-01\'')
		->orderBy('id')
		->chunk($chunkSize, function ($providers) use($mssql_employmentstatus,$myfile){
			foreach ($providers as $provider) {
					$providerName = $provider->fullName;
					$HireDate = date('m/d/Y', strtotime($provider->HireDate));
					$empStatusName  = $mssql_employmentstatus[$provider->EmploymentStatusID];
					$scheduleStartDate = date("Y-09-01",strtotime("-1 Year"));
					$scheduleEndDate = date("Y-08-31");

					$supDataExist = $this->getSuperVisoryVisitAvgForOtherState(
													$provider->id,
													'item1_score',
													$scheduleStartDate,
													$scheduleEndDate
												);
				echo $provider->id;echo "\n";
				echo $provider->ClinicianID;echo "\n";
				echo $providerName;echo "\n";
				echo $HireDate;echo "\n";
				echo $empStatusName;echo "\n";
				echo $supDataExist;echo "\n";
				$txt = $provider->ClinicianID.",".$providerName.",".$HireDate.",".$empStatusName.",".$supDataExist."\n";
					fwrite($myfile, $txt);
			}
		});
    }

    private function getSuperVisoryVisitAvgForOtherState($aoc_provider_id, $field, $scheduleStartDate, $scheduleEndDate)
    {
        $avg = DB::table('supervisory_visits')
            ->where('aoc_provider_id', $aoc_provider_id)
            ->whereBetween('visit_date_due', [$scheduleStartDate, $scheduleEndDate])
            ->whereNotNull('completed_by')
            ->active()
            ->get()->toArray();

        return empty($avg) ? 'No' : 'Yes';
    }
}
