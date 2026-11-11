<?php

namespace App\SyncProcesses;

use App\Helpers\H;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class MssqlScheduleMaster
{
    /**
     * Sync schedule master table
     */
    public function syncScheduleMaster($mode)
    {
        echo "Sync Schedule Master List \n";
        $syncType = 'sm_all';
        if($mode=='yesterday') {
            $syncType = 'sm_yesterday';
        }
        if($mode=='lastweek') {
            $syncType = 'sm_lastweek';
        }
        $mssqlTableName = '[Excelss].[ScheduleMaster]';
        $tableName = 'mssql_schedulemaster';
        $fieldName = 'CGTaskID';

        if($syncType=='sm_lastweek'){
            //first make sure yesterday sync is done syncing for the day
            $batch_info = H::getHelper('Sync')->get_batch_information('sm_yesterday');
            if ($batch_info['last_updated_id'] != 'donefortheday') {
                echo 'Skipping because yesterday sync is not done for the day';
                exit;
            }
        }
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
        if($last_updated_id=='') $last_updated_id = 0;
        echo 'last_updated_id: ' . $last_updated_id . "\n";
        $limit = $batch_info['batch_size'];
        if ($last_updated_id == 'donefortheday') {
            echo 'Skipping because we are done for the day';
            $batch_status = H::getHelper('Sync')->stopBatch($syncType);
            exit;
        }
        $combinedString = str_replace("'", "''", $last_updated_date . $last_updated_id);
        $conn = H::getHelper('Sync')->OpenConnection();
        $startDate = Carbon::now()->subMonths(1)->toDateString();
        $startDate = '2022-01-01';
        $startDate = '2021-10-01';
        if($mode=='yesterday') {
            $startDate = Carbon::now()->subDays(1)->toDateString();
        }
        if($mode=='lastweek') {
            $startDate = Carbon::now()->subDays(8)->toDateString();
        }
        echo $startDate . "\n";
        $endDate = Carbon::now()->addDays(30)->toDateString();
        if($mode=='yesterday') {
            $endDate = Carbon::now()->addDays(1)->toDateString();
        }
        if($mode=='lastweek') {
            $endDate = Carbon::now()->addDays(30)->toDateString();
        }
        echo $endDate . "\n";
        $combinedString = str_replace("'", "''", $last_updated_date . $last_updated_id);
        $tsql = 'SELECT top ' . $limit . " cast(CONVERT(char(19), CreatedOn, 120) as varchar(19)) as lastupdated, cast(CONVERT(char(19), ScheduleDate, 120) as varchar(19)) + cast(CGTaskID as varchar(10)) as lastId, * 
        FROM $mssqlTableName 
			WHERE ScheduleDate >= '" . $startDate . "' and ScheduleDate < '" . $endDate . "' and 
            cast(CONVERT(char(19), CreatedOn, 120) as varchar(19)) + cast(CONVERT(char(19), ScheduleDate, 120) as varchar(19)) + cast(CGTaskID as varchar(10)) > '" . $combinedString . "'
            ORDER by cast(CONVERT(char(19), CreatedOn, 120) as varchar(19)) + cast(CONVERT(char(19), ScheduleDate, 120) as varchar(19)) + cast(CGTaskID as varchar(10)) ASC";
        echo $tsql . "\n";
        $getRecords = sqlsrv_query($conn, $tsql);

        if ($getRecords == false) {
            echo 'error';
            die();
        }
        $recordCount = 0;
        while ($row = sqlsrv_fetch_array($getRecords, SQLSRV_FETCH_ASSOC)) {
            $recordCount++;
            echo $row['lastupdated'] . "\n";
            echo $row['CreatedOn'] . "\n";
            $newRow = [];
            $last_updated_date = $row['lastupdated'];
            $last_updated_id = $row['lastId'];
            echo $row['ScheduleDate'] . ' - last updated: ' . $last_updated_date . ' - ' . $last_updated_id . "\n";
            $uniqueID = $row[$fieldName];
            $dbRecord = DB::select("select * from $tableName where `$fieldName` = '" . $uniqueID . "'");
            foreach ($row as $key => &$value) {
                if (is_null($value)) {
                    $value = '';
                }
                $newKey = str_replace(' ', '_', $key);
                if($key=='ID') {
                    $newKey = 'MSSQL_ID';
                }
                if (Schema::hasColumn($tableName, $newKey)) {
                    $newRow[$newKey] = $value;
                }
            }
            if (!empty($dbRecord)) {
                echo 'Update Record' . "\n";
                $newRow['is_updated'] = '1';
                $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                DB::table($tableName)->whereRaw("`$fieldName`= ?", [$uniqueID])->update($newRow);
            } else {
                echo 'Insert Record' . "\n";
                $newRow['is_updated'] = '1';
                $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                $newRow['checked_off_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                DB::table($tableName)->insert($newRow);
            }
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        }
        sqlsrv_free_stmt($getRecords);
        sqlsrv_close($conn);
        //if no records were found,   Set all records with Patient_CurrentStatusID = 4 and updated_at < today to non-admit
        if ($recordCount == 0) {
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, 'donefortheday');
        }
        //******************************************************************************************* */
        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }

     /**
     * Sync schedule master table
     */
    public function syncServiceCodes()
    {
        echo "Sync service codes \n";
        $syncType = 'servicecodes';
        $mssqlTableName = '[Excelss].[ServiceCodes]';
        $tableName = 'service_codes';
        $fieldName = 'ServiceCodeID';
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
        if($last_updated_id=='') {
            $last_updated_id = 0;
        }
        echo 'last_updated_id: ' . $last_updated_id . "\n";
        $limit = $batch_info['batch_size'];
        if ($last_updated_id == 'donefortheday') {
            echo 'Skipping because we are done for the day';
            $batch_status = H::getHelper('Sync')->stopBatch($syncType);
            exit;
        }
        $conn = H::getHelper('Sync')->OpenConnection();
        $tsql = 'SELECT top ' . $limit . " * FROM $mssqlTableName 
			WHERE ServiceCodeID >= " . $last_updated_id . " ORDER by ServiceCodeID ASC";
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
            $uniqueID = $row[$fieldName];
            $dbRecord = DB::select("select * from $tableName where `$fieldName` = '" . $uniqueID . "'");
            foreach ($row as $key => &$value) {
                if (is_null($value)) {
                    $value = '';
                }
                $newKey = str_replace(' ', '_', $key);
                if($key=='ID') {
                    $newKey = 'MSSQL_ID';
                }
                if (Schema::hasColumn($tableName, $newKey)) {
                    $newRow[$newKey] = $value;
                }
            }
            if (!empty($dbRecord)) {
                echo 'Update Record' . "\n";
                $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                DB::table($tableName)->whereRaw("`$fieldName`= ?", [$uniqueID])->update($newRow);
            } else {
                echo 'Insert Record' . "\n";
                $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                $newRow['created_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                DB::table($tableName)->insert($newRow);
            }
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        }
        sqlsrv_free_stmt($getRecords);
        sqlsrv_close($conn);
        //if no records were found,   Set all records with Patient_CurrentStatusID = 4 and updated_at < today to non-admit
        if ($recordCount == 0) {
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, 'donefortheday');
        }
        //******************************************************************************************* */
        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }
}
