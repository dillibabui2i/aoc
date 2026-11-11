<?php

namespace App\SyncProcesses;

use App\Events\Map\MapUpdatedEvent;
use App\Helpers\H;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MssqlTextBlastMap
{
    private bool $mapTouched = false;
    /**
     * Sync text blasting map list - this is reset every morning
     * when checkETL returns a new successful job,  we reset process_settings for coldCall and textBlast and morningNTH and set
     * records in maps_nth, maps_coldcall, and maps_textblast is_updated = 0.
     */
    public function syncTextBlast()
    {
        echo "Sync Text Blast \n";
        $syncType = 'textBlast';
        $tableName = 'maps_textblast';
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
        $tsql = 'SELECT top ' . $limit . " * FROM Excelss.MAP_TextBlasting 
			WHERE [Clinician ID]  > '$last_updated_id'
			ORDER BY [Clinician ID] ASC";

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
            $last_updated_id = $row['Clinician ID'];
            echo $last_updated_date . ' - ' . $last_updated_id . "\n";
            $uniqueID = $row['Clinician ID'];
            H::ExcelLog('checking ' . $uniqueID,'textBlastMap.log');
            //find Clinician_ID in mssql_recruitertracker
            H::ExcelLog("select rt.id, Candidate, rt.updated_at from rt 
                left join mssql_providers p on rt.aoc_provider_id = p.id 
                where rt.is_active = 1 and rt.Candidate_Current_Status not in ('Inactive', 'First Shift Confirmed') and p.ClinicianID = '" . $uniqueID . "'",'textBlastMap.log');
            $dbTracker = DB::select("select rt.id, Candidate, rt.updated_at from mssql_recruitertracker rt 
                            left join mssql_providers p on rt.aoc_provider_id = p.id 
                            where rt.is_active = 1 and rt.Candidate_Current_Status not in ('Inactive', 'First Shift Confirmed') and p.ClinicianID = '" . $uniqueID . "'");
            if (!empty($dbTracker)){
                echo 'skipping because ' . $dbTracker[0]->Candidate . ' has an active tracker (' . $dbTracker[0]->id . ')';
                H::ExcelLog('skipping because ' . $dbTracker[0]->Candidate . ' has an active tracker (' . $dbTracker[0]->id . ')','textBlastMap.log');
            }else{
                $dbIgnore = DB::select("select id, phone from mssql_textblast_ignore where phone = '" . $row['Phone'] . "' or phone = '" . $row['Mobile'] . "'");
                if (!empty($dbIgnore)){
                    echo 'skipping because ' . $row['Phone'] . ' or ' . $row['Mobile'] . ' for ' . $row['Provider Name'] . ' is in the ignore table';
                    H::ExcelLog('skipping because ' . $row['Phone'] . ' or ' . $row['Mobile'] . ' for ' . $row['Provider Name'] . ' is in the ignore table','textBlastMap.log');
                }else{
                    $is_ignored = 0;
                    $formatPhoneNumber = H::getHelper('MassMessageWidget')->getFormatPhoneForMessage($row['Mobile']);
                    $twilioErrorData = DB::table('twilio_error_logs')->where('phone_number',$formatPhoneNumber)->first();
                    if(!empty($twilioErrorData)) {
                        $is_ignored = 1;
                        H::ExcelLog("Number - ".$formatPhoneNumber.' found in Twilio error log Clinician_ID = ' . $uniqueID, 'textBlastMap.log');
                    }
                    $dbClient = DB::select("select * from maps_textblast where `Clinician_ID` = '" . $uniqueID . "'");
                    if (!empty($dbClient)) {
                        echo 'Update Clinician' . "\n";
                        H::ExcelLog('updating ' . $uniqueID,'textBlastMap.log');
                        foreach ($row as $key => &$value) {
                            if (is_null($value)) {
                                $value = '';
                            }
                            $newKey = str_replace(' ', '_', $key);
                            if (Schema::hasColumn($tableName, $newKey)) {
                                $newRow[$newKey] = $value;
                            }
                        }
						if($newRow['Provider_Status']=='Active Coverage') {
							if(!$is_ignored) {
								$is_ignored = $this->checkActiveCoverageClinician($uniqueID);
							}
                        }
                        $newRow['is_updated'] = '1';
                        $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                        $newRow['needs_geocoding'] = 1;
                        $newRow['is_ignored'] = $is_ignored;
                        DB::table('maps_textblast')->whereRaw('`Clinician_ID`= ?', [$uniqueID])->update($newRow);
                        $this->mapTouched = true;
                    } else {
                        echo 'Insert Clinician' . "\n";
                        H::ExcelLog('inserting ' . $uniqueID,'textBlastMap.log');
                        foreach ($row as $key => &$value) {
                            if (is_null($value)) {
                                $value = '';
                            }
                            if($key=='#_of_Weeks_in_Period') {
                                $key = 'Weeks_in_Period';
                            }
                            $newKey = str_replace(' ', '_', $key);
                            if (Schema::hasColumn($tableName, $newKey)) {
                                $newRow[$newKey] = $value;
                            }
                        }
						 if($newRow['Provider_Status']=='Active Coverage') {
                            if(!$is_ignored) {
								$is_ignored = $this->checkActiveCoverageClinician($uniqueID);
							}
                        }
                        $newRow['is_updated'] = '1';
                        $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                        $newRow['needs_geocoding'] = 1;
                        $newRow['is_ignored'] = $is_ignored;
                        DB::table('maps_textblast')->insert($newRow);
                        $this->mapTouched = true;
                    }
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
	
	private function getDateRange(){
        $lastWeek  = date('Y-m-d', strtotime("sunday -3 week"));
        $nextWeek  = date('Y-m-d', strtotime("sunday 1 week"));

        return [$lastWeek,$nextWeek];
    }

    private function checkActiveCoverageClinician($clinicianId) {
        $isMoreThanHour = 0;
        list($lastWeek,$nextWeek) = $this->getDateRange();
       
        $scheduledHour = DB::table("mssql_schedulemaster")
        ->select(DB::raw("IFNULL(sum(Hours)/4,0) as average_hour"))
        ->leftJoin("service_codes_daas","mssql_schedulemaster.ServiceCodeID","=","service_codes_daas.service_id")
        ->leftJoin("mssql_providers","mssql_providers.ProviderID","=","mssql_schedulemaster.ProviderID")
        ->where("mssql_schedulemaster.IsBillable",1)
        ->whereIn("mssql_schedulemaster.ScheduleStatusID",[1,2,3,5])
        ->whereDate("mssql_schedulemaster.ScheduleDate",">",$lastWeek)
        ->whereDate("mssql_schedulemaster.ScheduleDate","<=",$nextWeek)
        ->where("service_codes_daas.name","not like",'%coverage%')
        ->where("mssql_providers.ClinicianID",$clinicianId)->first();
        
        if($scheduledHour->average_hour>=12) {
            H::ExcelLog('Hour: ' . $scheduledHour->average_hour,'textblast_scheduledaverage.log');
            H::ExcelLog('Clinician: ' . $clinicianId,'textblast_scheduledaverage.log');
            return 1;
        } else {
            return 0;
        }
    }
}
