<?php

namespace App\SyncProcesses;

use App\Events\Map\MapUpdatedEvent;
use App\Helpers\H;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MssqlColdCallMap
{
    private bool $mapTouched = false;
    /**
     * Sync cold calling map list - this is reset every morning
     * when checkETL returns a new successful job,  we reset process_settings for coldCall and textBlast and morningNTH and set
     * records in maps_nth, maps_coldcall, and maps_textblast is_updated = 0.
     */
    public function syncColdCall()
    {
        echo "Sync Cold Call \n";
        $syncType = 'coldCall';
        $tableName = 'maps_coldcall';
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
        $tsql = 'SELECT top ' . $limit . " * FROM Excelss.MAP_ColdCallingExport 
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
            //find Clinician_ID in mssql_recruitertracker
            $dbTracker = DB::select("select id, Candidate, updated_at from mssql_recruitertracker where is_active = 1 and Clinician_ID = '" . $uniqueID . "'");
            if (!empty($dbTracker)){
                echo 'skipping because ' . $dbTracker[0]->Candidate . ' has an active tracker (' . $dbTracker[0]->id . ')';
                H::ExcelLog('skipping because ' . $dbTracker[0]->Candidate . ' has an active tracker (' . $dbTracker[0]->id . ')','textBlastMap.log');
            }else{
                $dbIgnore = DB::select("select id, phone from mssql_textblast_ignore where phone = '" . $row['Phone'] . "' or phone = '" . $row['Mobile'] . "'");
                if (!empty($dbIgnore)){
                    echo 'skipping because ' . $row['Phone'] . ' or ' . $row['Mobile'] . ' for ' . $row['Provider Name'] . ' is in the ignore table';
                    H::ExcelLog('skipping because ' . $row['Phone'] . ' or ' . $row['Mobile'] . ' for ' . $row['Provider Name'] . ' is in the ignore table','textBlastMap.log');
                }else{ 
                    $dbClient = DB::select("select * from maps_coldcall where `Clinician_ID` = '" . $uniqueID . "'");
                    if (!empty($dbClient)) {
                        echo 'Update Clinician' . "\n";
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
                        DB::table('maps_coldcall')->whereRaw('`Clinician_ID`= ?', [$uniqueID])->update($newRow);
                        $this->mapTouched = true;
                    } else {
                        echo 'Insert Clinician' . "\n";
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
                        DB::table('maps_coldcall')->insert($newRow);
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
}
