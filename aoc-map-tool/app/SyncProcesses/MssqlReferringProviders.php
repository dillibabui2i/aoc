<?php

namespace App\SyncProcesses;

use App\Helpers\H;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class MssqlReferringProviders
{
    /**
     * Sync referring physicians table
     */
    public function syncReferringProviders()
    {
        echo "Sync Referring Providers List \n";
        $syncType = 'referring_providers';
        $mssqlTableName = '[Excelss].[ReferringProviders]';
        $tableName = 'ReferringProviders';
        $fieldName = 'ReferringProviderID';
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

        $conn = H::getHelper('Sync')->OpenConnection();
        $tsql = 'SELECT top ' . $limit . " * FROM $mssqlTableName 
			WHERE [ReferringProviderID]  > '$last_updated_id'
			ORDER BY [ReferringProviderID] ASC";

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
            $last_updated_id = $row['ReferringProviderID'];
            echo $last_updated_date . ' - ' . $last_updated_id . "\n";
            H::ExcelLog(print_r($row,true),'ReferringProvidersSync.log');

            $dbRecord = DB::select("select * from $tableName where ReferringProviderID = '" . $row['ReferringProviderID'] . "'");
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
                DB::table($tableName)->whereRaw("ReferringProviderID = ?", [$dbRecord[0]->ReferringProviderID])->update($newRow);
                H::ExcelLog('updating ReferringProviderID ' . $dbRecord[0]->ReferringProviderID,'ReferringProvidersSync.log');
            } else {
                echo 'Insert Record' . "\n";
                $newRow['is_updated'] = '1';
                $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                $newRow['created_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                DB::table($tableName)->insert($newRow);
                H::ExcelLog('inserting','ReferringProvidersSync.log');
            }

            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        }
        sqlsrv_free_stmt($getRecords);
        sqlsrv_close($conn);
        //if no records were found,   delete all rows with 'is_updated = 0' and stop from processing for rest of day
        if ($recordCount == 0) {
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, 'donefortheday');
            $updateDeleted = [];
            $updateDeleted['is_deleted'] = 1;
            $deleted = DB::table($tableName)->where('is_updated', 0)->update($updateDeleted);
        }
        //******************************************************************************************* */
        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }

}
