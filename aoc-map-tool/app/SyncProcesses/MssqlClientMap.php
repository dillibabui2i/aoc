<?php

namespace App\SyncProcesses;

use App\Events\Map\MapUpdatedEvent;
use App\Helpers\H;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MssqlClientMap
{
    private bool $mapTouched = false;
    /**
     * Sync client list map.
     */
    public function syncClientMap()
    {
        echo "Sync Client List \n";
        $syncType = 'clientListMap';
        $mssqlTableName = '[Excelss].[MAP_ClientList]';
        $tableName = 'maps_clients';
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
            $dbRecord = DB::select("select * from $tableName where `$fieldName` = '" . $uniqueID . "'");
            if (!empty($dbRecord)) {
                echo 'Update Record' . "\n";
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
                DB::table($tableName)->insert($newRow);
                $this->mapTouched = true;
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
