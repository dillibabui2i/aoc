<?php

namespace App\Console\Commands;

use App\Helpers\H;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncPatientCustomFieldsOnly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patients:customfields';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update patients custom fields only';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
		$syncType = 'patients_customfields';
        $mssqlTableName = '[Excelss].[GENERAL_Patients]';
        $tableName = 'mssql_patients_test_daas1';
		$fieldName = 'PatientAccount';

        //start batch
        $batch_status = H::getHelper('Sync')->startBatch($syncType);
        echo $batch_status . "\n";
        if ($batch_status == -1) {
            echo 'stop process';
            exit;
        }
        $batch_info = H::getHelper('Sync')->get_batch_information($syncType);
        $batch_result = 0;
        var_dump($batch_info);
        echo "\n";

        $last_updated_date = $batch_info['last_updated_date'];
        $last_updated_id = $batch_info['last_updated_id'];
        $limit = $batch_info['batch_size'];
        if ($last_updated_id == 'donefortheday') {
            echo 'Skipping because we are done for the day';
            $batch_status = H::getHelper('Sync')->stopBatch($syncType);
            exit;
        }
        $combinedString = str_replace("'", "''", $last_updated_date . $last_updated_id);
        $conn = H::getHelper('Sync')->OpenConnection();
        $tsql = 'SELECT top ' . $limit . " * FROM $mssqlTableName 
			WHERE [$fieldName]  > '$last_updated_id'
				and IsLatestAdmit = 1 
			ORDER BY [$fieldName] ASC, EpisodeStartDate ASC";
            H::ExcelLog($tsql,'patients_customfields_syncs.log');
        echo $tsql . "\n";
        $getRecords = sqlsrv_query($conn, $tsql);
        
        if ($getRecords == false) {
            echo 'error';
            die();
        }
		
		$recordCount = 0;
		while ($row = sqlsrv_fetch_array($getRecords, SQLSRV_FETCH_ASSOC)) {
			$customFields = [];
			//Go Live with snowflake, remove the foreach below and uncomment the line # 81, 82 & 83
			foreach($row as $key => $value) {
				if (strstr($key, "CF_") !== false) {
					$customFields[$key] = $value;
				}
			}
			//$customFields['CF_GoalSOCDate'] = $row['CF_GoalSOCDate'];
			//$customFields['CF_ReferralType'] = $row['CF_ReferralType'];
			//$customFields['CF_RespWeeklyHrs'] = $row['CF_RespWeeklyHrs'];
			$recordCount++;
			$PatientID = $row['PatientID'];
			H::ExcelLog('PatientID : ' . $PatientID,'patients_customfields_syncs.log');
			$IntakeID = $row['IntakeID'];
			H::ExcelLog('IntakeID : ' . $IntakeID,'patients_customfields_syncs.log');
			$patientAccount = $row['PatientAccount'];
			H::ExcelLog('patientAccount : ' . $patientAccount,'patients_customfields_syncs.log');
			
			if ($PatientID == 0) {
                //IntakeID Only
                H::ExcelLog("select * from " . $tableName .
                    " where IntakeID = " . $IntakeID, 'patients_customfields_syncs.log');
                $dbRecord = DB::select("select * from " . $tableName .
                " where IntakeID = ?",[$IntakeID]);
            } else {
                //IntakeID and PatientID
                H::ExcelLog("select * from " . $tableName .
                " where PatientID = " . $PatientID . " and IntakeID = " . $IntakeID,'patients_customfields_syncs.log');
                $dbRecord = DB::select("select * from " . $tableName .
                " where PatientID = ? and IntakeID = ?",[$PatientID, $IntakeID]);
            }

            if (empty($dbRecord)) {
                //if IntakeID + PatientID didn't find a match,  it could be because the patientID was just assigned,  
                //so we need to find just IntakeID 
                $dbRecord = DB::select("select * from " . $tableName .
                " where IntakeID = ?",[$IntakeID]);   
                H::ExcelLog("select * from " . $tableName .
                " where IntakeID = " . $IntakeID,'patients_customfields_syncs.log');            
            }

            if (empty($dbRecord)) {
                //if IntakeID isn't found,  it could be because it's a new intake that was added in the app AND KT
                //so we will need to link them instead of inserting as new
                $dbRecord = DB::select("select * from " . $tableName .
                " where PatientAccount = ?",[$patientAccount]);   
                H::ExcelLog("look to match new intake:  select * from " . $tableName .
                " where PatientAccount = " . $patientAccount,'patients_customfields_syncs.log');            
            }
			
			if (!empty($dbRecord)) {
                $aoc_patient_id = $dbRecord[0]->id;
                echo 'Existing Record Found: ' . $aoc_patient_id . "\n";
                H::ExcelLog('Existing Record Found: ' . $aoc_patient_id, 'patients_customfields_syncs.log');
				H::ExcelLog('Custom Fields : ' . print_r($customFields, true) . $aoc_patient_id, 'patients_customfields_syncs.log');
				DB::table($tableName)->where('id', $aoc_patient_id)->update($customFields);
			}
			
			$last_updated_date = '';
            $last_updated_id = $row[$fieldName];
            echo $last_updated_date . ' - ' . $last_updated_id . "\n";
			
			H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
		}
		
		H::ExcelLog('finished with loop','patients_customfields_syncs.log');
        sqlsrv_free_stmt($getRecords);
        sqlsrv_close($conn);
        //if no records were found,   Set all records with Patient_CurrentStatusID = 4 and updated_at < today to non-admit
        if ($recordCount == 0) {
            H::ExcelLog('Setting to done for the day','patients_customfields_syncs.log');
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, 'donefortheday');
        }
        //******************************************************************************************* */
        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
        return Command::SUCCESS;
    }
}
