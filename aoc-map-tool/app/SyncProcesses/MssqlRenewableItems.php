<?php

namespace App\SyncProcesses;

use App\Helpers\H;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class MssqlRenewableItems
{
    /**
     * Sync renewables table
     */
    public function syncRenewableItems()
    {
        echo "Sync Renewable Item List \n";
        $syncType = 'renewables';
        $mssqlTableName = '[Excelss].[RenewableItems]';
        $tableName = 'renewables';
        $fieldName = 'renewable_item_id';
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
			WHERE [UniqueKey]  > '$last_updated_id'
			ORDER BY [UniqueKey] ASC";

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
            $last_updated_id = $row['UniqueKey'];
            echo $last_updated_date . ' - ' . $last_updated_id . "\n";
            H::ExcelLog(print_r($row,true),'renewablesSync.log');

            //get providerID
                $dbRecord = DB::select("select * from $tableName where UniqueKey = '" . $row['UniqueKey'] . "'");
                $newRow['UniqueKey'] = $row['UniqueKey'];
                $newRow['ProviderID'] = $row['ProviderID'];
                $newRow['renewables_type'] = $row['ExpiringItemID'];
                $newRow['expiration_date'] = $row['ExpiryDate'];
                $newRow['issued_date'] = $row['IssuedDate'];
                $newRow['license_no'] = $row['RenewableLicenseNo'];
                $newRow['license_state'] = $row['LicenseState'];
                $newRow['mandatory'] = $row['isMandatory'];
                $newRow['description'] = $row['Description'];
                $newRow['renewable_item_id'] = $row['RenewableItemID'];
                if (!empty($dbRecord)) {
                    echo 'Update Record' . "\n";
                    $newRow['is_updated'] = '1';
                    $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                    DB::table($tableName)->whereRaw("id = ?", [$dbRecord[0]->id])->update($newRow);
                    H::ExcelLog('updating id ' . $dbRecord[0]->id,'renewablesSync.log');
                } else {
                    echo 'Insert Record' . "\n";
                    $newRow['is_updated'] = '1';
                    $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                    $newRow['created_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                    DB::table($tableName)->insert($newRow);
                    H::ExcelLog('inserting','renewablesSync.log');
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

    public function syncRenewableTypes()
    {
        echo "Sync Renewable type List \n";
        $syncType = 'renewabletypes';
        $mssqlTableName = '[Excelss].[RenewableItems]';
        $tableName = 'renewables_types';
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

        $conn = H::getHelper('Sync')->OpenConnection();
        $tsql = "SELECT distinct ExpiringItemID, RenewableItem
                         FROM [Excelss].[RenewableItems] order by ExpiringItemID";

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
            $last_updated_id = $row['ExpiringItemID'];
            echo $last_updated_date . ' - ' . $last_updated_id . "\n";

            $dbRecord = DB::select("select * from renewables_types where renewable_type_id = " . $row['ExpiringItemID']);
            $newRow['name'] = $row['RenewableItem'];
            $newRow['renewable_type_id'] = $row['ExpiringItemID'];
            if (!empty($dbRecord)) {
                echo 'Update Record' . "\n";
                $newRow['is_updated'] = '1';
                $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                DB::table($tableName)->whereRaw("id = ?", [$dbRecord[0]->id])->update($newRow);
            } else {
                echo 'Insert Record' . "\n";
                $newRow['is_updated'] = '1';
                $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                $newRow['created_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                DB::table($tableName)->insert($newRow);
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
