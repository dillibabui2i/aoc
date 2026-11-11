<?php

namespace App\SyncProcesses;

use App\Events\Map\MapUpdatedEvent;
use App\Helpers\H;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MssqlCoverageClinicianMap
{
    private bool $mapTouched = false;

    /**
     * Sync coverage clinician map.
     */
    public function syncCoverageClinicianMap()
    {
        echo "Sync Coverage Clinician List \n";
        $syncType = 'coverageClinicianMap';
        $mssqlTableName = '[Excelss].[MAP_CoverageClinicianList]';
        $tableName = 'maps_covclin';
        $fieldName = 'ProviderID';
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
			ORDER BY [$fieldName] ASC";
        echo $tsql . "\n";
        $getRecords = sqlsrv_query($conn, $tsql);

        if ($getRecords == false) {
            echo 'error';
            die();
        }
        $recordCount = 0;
        while ($row = sqlsrv_fetch_array($getRecords, SQLSRV_FETCH_ASSOC)) {
            $recordCount++;
            var_dump($row);
            echo "\n";
            $newRow = [];
            $last_updated_date = '';
            $last_updated_id = $row[$fieldName];
            echo $last_updated_date . ' - ' . $last_updated_id . "\n";
            $uniqueID = $row[$fieldName];
            $clinicianID = $row['ClinicianID'];
            //first look for this record in the exclude_cc table
            $dbRecord = DB::select("select * from exclude_cc where `ClinicianID` = '" . $clinicianID . "'");
            if (!empty($dbRecord)) {
                H::ExcelLog('skipping: ' . $clinicianID, 'skipcovclin.log');
            } else {
                $is_ignored = 0;
                $formatPhoneNumber = H::getHelper('MassMessageWidget')->getFormatPhoneForMessage($row['Mobile']);
                $twilioErrorData = DB::table('twilio_error_logs')->where('phone_number',$formatPhoneNumber)->first();
                if(!empty($twilioErrorData)) {
                    $is_ignored = 1;
                    H::ExcelLog("Number - ".$formatPhoneNumber.' found in Twilio error log Clinician_ID = ' . $uniqueID, 'coverageClinicianMap.log');
                }
				
				// update days available and shift types for Active Coverage
                $dbRecord2 = DB::select("select ifnull(mssql_patients.PatientID,'') as PatientID, ifnull(mssql_patients.Patient_FirstName,'') as Patient_FirstName, ifnull(mssql_patients.Patient_LastName,'') as Patient_LastName, ifnull(mssql_recruitertracker.shift_type,'') as Shift_Type, ifnull(mssql_recruitertracker.days_available,'') as Days_Available, ifnull(mssql_recruitertracker.aoc_patient_id,0) as aoc_patient_id, mssql_recruitertracker.aoc_provider_id,mssql_recruitertracker.id as tracker_id from mssql_recruitertracker left join mssql_providers on mssql_providers.id = mssql_recruitertracker.aoc_provider_id left join mssql_patients on mssql_patients.id = mssql_recruitertracker.aoc_patient_id WHERE mssql_providers.`".$fieldName."` = '".$uniqueID."' AND mssql_providers.EmploymentStatusID = 24 AND mssql_recruitertracker.live_ipea_status = 'Ready to work' AND mssql_recruitertracker.nurse_status = 2 order by mssql_recruitertracker.id DESC limit 1");
				if (!empty($dbRecord2)) {
					$newRow = array_merge($newRow, (array)$dbRecord2[0]);
					$newRow['custom_sort_prn'] = 1;
				}
				
                $dbRecord = DB::select("select * from $tableName where `$fieldName` = '" . $uniqueID . "'");
                if (!empty($dbRecord)) {
                    echo 'Update Record' . "\n";
                    foreach ($row as $key => &$value) {
                        if (is_null($value)) {
                            $value = '';
                        }
                        $newKey = str_replace(' ', '_', $key);
                        if ($newKey == 'AOC_Email') {
                            $newKey = 'Email';
                        }
                        if (Schema::hasColumn($tableName, $newKey)) {
                            $newRow[$newKey] = $value;
                        }
                    }
                    $newRow['is_updated'] = '1';
                    $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                    $newRow['needs_geocoding'] = 1;
                    $newRow['is_ignored'] = $is_ignored;
                    DB::table($tableName)->whereRaw("`$fieldName`= ?", [$uniqueID])->update($newRow);
                    $this->mapTouched = true;
                } else {
                    echo 'Insert Record' . "\n";
                    foreach ($row as $key => &$value) {
                        if (is_null($value)) {
                            $value = '';
                        }
                        $newKey = str_replace(' ', '_', $key);
                        if (Schema::hasColumn($tableName, $newKey)) {
                            $newRow[$newKey] = $value;
                        }
                    }
                    $newRow['is_updated'] = '1';
                    $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                    $newRow['needs_geocoding'] = 1;
                    $newRow['is_ignored'] = $is_ignored;
                    DB::table($tableName)->insert($newRow);
                    $this->mapTouched = true;
                }
            }
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        }
        sqlsrv_free_stmt($getRecords);
        sqlsrv_close($conn);
        //if no records were found,   delete all rows with 'is_updated = 0' and stop from processing for rest of day
        if ($recordCount == 0) {
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, 'donefortheday');
            $deleted = DB::table($tableName)->where('is_updated', 0)->delete();
            $this->mapTouched = true;
        }
        //******************************************************************************************* */

        if ($this->mapTouched) {
            event(new MapUpdatedEvent($tableName));
        }

        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }
}
